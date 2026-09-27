<?php
require_once __DIR__.'/Language.php';
final class WbceSecurityCenterVirusScanner
{
    private $database;
    public function __construct($database){$this->database=$database;}

    public function run($limit=80,$maxBytes=16777216,$scanArchives=true)
    {
        $job=$this->claim();if(!$job)return array('message'=>$this->text('virus_none'));
        try{
            require_once __DIR__.'/RuntimeConfig.php';$runtime=WbceSecurityCenterRuntimeConfig::load();
            $limit=max(5,min(300,(int)$limit));$maxBytes=max(262144,min(67108864,(int)$maxBytes));
            $files=$this->files((string)$job['root_path'],(string)$job['cursor_path'],$limit+1,(array)($runtime['compiled_rules']['file_allow']??array()),$maxBytes);
            $more=count($files)>$limit;if($more)array_pop($files);$found=0;$last=(string)$job['cursor_path'];
            foreach($files as $file){$last=$file;$found+=$this->scanFile((string)$job['scan_id'],$file,$maxBytes,$scanArchives);}
            $status=$more?'queued':'done';$finished=$more?'NULL':"'".gmdate('Y-m-d H:i:s')."'";
            $this->database->query("UPDATE `{TP}mod_security_center_virus_queue` SET status='$status',lock_token=NULL,cursor_path='".$this->database->escapeString($last)."',files_scanned=files_scanned+".count($files).",findings_count=findings_count+$found,finished_at=$finished,last_error=NULL WHERE id=".(int)$job['id']." AND lock_token='".$this->database->escapeString((string)$job['lock_token'])."'");
            return array('message'=>$this->text('virus_result',array('{files}'=>count($files),'{findings}'=>$found)),'more'=>$more);
        }catch(Throwable $e){$this->database->query("UPDATE `{TP}mod_security_center_virus_queue` SET status='failed',lock_token=NULL,finished_at='".gmdate('Y-m-d H:i:s')."',last_error='".$this->database->escapeString($e->getMessage())."' WHERE id=".(int)$job['id']);throw $e;}
    }

    private function claim()
    {
        $stale=gmdate('Y-m-d H:i:s',time()-1800);$this->database->query("UPDATE `{TP}mod_security_center_virus_queue` SET status='queued',started_at=NULL,lock_token=NULL WHERE status='running' AND started_at<'$stale'");
        $result=$this->database->query("SELECT * FROM `{TP}mod_security_center_virus_queue` WHERE status='queued' ORDER BY id LIMIT 1");$job=$result?$result->fetchRow(MYSQLI_ASSOC):null;if(!$job)return null;
        $now=gmdate('Y-m-d H:i:s');$token=bin2hex(random_bytes(16));$this->database->query("UPDATE `{TP}mod_security_center_virus_queue` SET status='running',lock_token='$token',started_at=COALESCE(started_at,'$now') WHERE id=".(int)$job['id']." AND status='queued'");
        $claimed=$this->database->query("SELECT * FROM `{TP}mod_security_center_virus_queue` WHERE id=".(int)$job['id']." AND status='running' AND lock_token='$token' LIMIT 1");return $claimed?$claimed->fetchRow(MYSQLI_ASSOC):null;
    }

    private function files($root,$cursor,$limit,$allowedFiles,$maxBytes)
    {
        $base=realpath(WB_PATH);$root=realpath($root);if(!$base||!$root||($root!==$base&&!str_starts_with($root,$base.DIRECTORY_SEPARATOR)))throw new RuntimeException($this->text('virus_path_invalid'));$files=array();
        $iterator=new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root,FilesystemIterator::SKIP_DOTS),RecursiveIteratorIterator::LEAVES_ONLY,RecursiveIteratorIterator::CATCH_GET_CHILD);
        foreach($iterator as $entry){if(!$entry->isFile()||$entry->isLink())continue;$path=str_replace('\\','/',$entry->getPathname());$relative=ltrim(substr($path,strlen($root)),'/');if($relative<=$cursor||self::allowed($relative,$allowedFiles)||preg_match('~^(?:temp/(?:cache|sessions)|var/cache|modules/security_center/(?:VirusScanner\.php|signatures/))~i',$relative))continue;if($entry->getSize()>$maxBytes)continue;$files[]=$relative;}
        sort($files,SORT_STRING);return array_slice($files,0,$limit);
    }

    private static function allowed($relative,$rules){foreach($rules as $rule){$rule=trim(str_replace('\\','/',(string)$rule),'/');if($rule!==''&&($relative===$rule||str_starts_with($relative,$rule.'/')))return true;}return false;}

    private function scanFile($scanId,$relative,$maxBytes,$scanArchives)
    {
        $base=realpath(WB_PATH);$path=realpath(WB_PATH.'/'.$relative);if(!$base||!$path||($path!==$base&&!str_starts_with($path,$base.DIRECTORY_SEPARATOR))||!is_file($path)||is_link($path))return 0;$size=@filesize($path);if($size===false||$size>$maxBytes)return 0;$content=@file_get_contents($path);if($content===false)return 0;$found=$this->scanContent($scanId,$relative,$content);
        if($scanArchives&&class_exists('ZipArchive')&&preg_match('/\.zip$/i',$relative))$found+=$this->scanZip($scanId,$path,$relative,min($maxBytes,4194304));
        return $found;
    }

    private function scanZip($scanId,$path,$relative,$entryLimit)
    {
        $zip=new ZipArchive();if($zip->open($path)!==true)return 0;$found=0;$entries=min($zip->numFiles,150);$total=0;
        for($i=0;$i<$entries;$i++){$stat=$zip->statIndex($i);if(!is_array($stat)||substr((string)$stat['name'],-1)==='/')continue;$size=(int)($stat['size']??0);$total+=$size;if($size>$entryLimit||$total>33554432){$found+=$this->record($scanId,$relative.'::'.(string)$stat['name'],'archive_limits','medium','Archivinhalt überschreitet die sicheren Prüfgrenzen.',hash('sha256',$relative.'|'.$i.'|limits'));continue;}$data=$zip->getFromIndex($i);if(is_string($data))$found+=$this->scanContent($scanId,$relative.'::'.(string)$stat['name'],$data);}
        if($zip->numFiles>150)$found+=$this->record($scanId,$relative,'archive_entries','medium','Archiv enthält mehr Einträge als in einem sicheren Lauf geprüft werden.','entries:'.$zip->numFiles);$zip->close();return $found;
    }

    private function scanContent($scanId,$relative,$content)
    {
        $found=0;$lower=strtolower($content);$extension=strtolower(pathinfo(preg_replace('/::.*/','',$relative),PATHINFO_EXTENSION));$phpTag=strpos($lower,'<?php')!==false||strpos($lower,'<?=')!==false;
        if($phpTag&&in_array($extension,array('jpg','jpeg','png','gif','ico','svg','txt','log','cache','dat'),true))$found+=$this->record($scanId,$relative,'disguised_php','critical','Ausführbarer PHP-Code steckt in einem nicht als PHP erkennbaren Dateityp.',$content);
        if(preg_match('/\.(?:jpe?g|png|gif|ico|txt)\.(?:php\d*|phtml|phar)$/i',$relative))$found+=$this->record($scanId,$relative,'double_extension','high','Verdächtige Doppelendung kann ausführbaren Code tarnen.',$relative);
        if(strncmp($content,"MZ",2)===0||strncmp($content,"\x7fELF",4)===0)$found+=$this->record($scanId,$relative,'server_executable','high','Native ausführbare Datei innerhalb des Webverzeichnisses erkannt.',substr($content,0,4096));
        $shellSignals=0;foreach(array('/\$_(?:GET|POST|REQUEST|COOKIE)\s*\[/i','/\b(?:eval|assert|create_function)\s*\(/i','/\b(?:system|shell_exec|passthru|exec|proc_open|popen)\s*\(/i','/\b(?:base64_decode|gzinflate|gzuncompress|str_rot13|hex2bin)\s*\(/i','/\b(?:move_uploaded_file|chmod|chown)\s*\(/i') as $pattern)if(preg_match($pattern,$content))$shellSignals++;
        if($shellSignals>=3)$found+=$this->record($scanId,$relative,'webshell_combination','critical','Mehrere typische Webshell-Fähigkeiten treten gemeinsam auf.',$content);
        if(preg_match('/\b(?:filesman|b374k|c99shell|r57shell|indoxploit|alfa\s*shell)\b/i',$content))$found+=$this->record($scanId,$relative,'webshell_marker','critical','Typische Selbstbezeichnung einer Webshell erkannt.',$content);
        if(preg_match('/(?:base64_decode|gzinflate|gzuncompress|str_rot13)\s*\([^;]{0,250}(?:eval|assert)\s*\(/is',$content)||preg_match('/(?:eval|assert)\s*\([^;]{0,250}(?:base64_decode|gzinflate|gzuncompress|str_rot13)\s*\(/is',$content))$found+=$this->record($scanId,$relative,'decoder_execution_chain','critical','Dekodierung und dynamische Ausführung sind zu einer Payload-Kette verbunden.',$content);
        if(preg_match('/(?:document\.write|eval)\s*\(\s*(?:unescape|atob)\s*\(/i',$content)||preg_match('/String\.fromCharCode\s*\((?:\s*\d+\s*,){12,}/i',$content))$found+=$this->record($scanId,$relative,'javascript_payload','high','Stark verschleierte JavaScript-Nutzlast erkannt.',$content);
        if(preg_match('/<iframe\b[^>]*(?:width\s*=\s*["\']?0|height\s*=\s*["\']?0|display\s*:\s*none)[^>]*src\s*=\s*["\']?https?:\/\//i',$content))$found+=$this->record($scanId,$relative,'hidden_remote_iframe','high','Unsichtbarer externer Iframe kann auf eingeschleusten Code hindeuten.',$content);
        if($phpTag&&strlen($content)>512&&self::entropy(substr($content,0,1048576))>7.65&&preg_match('/[A-Za-z0-9+\/=]{300,}/',$content))$found+=$this->record($scanId,$relative,'high_entropy_payload','high','PHP-Datei enthält eine ungewöhnlich stark verschleierte Nutzlast.',$content);
        return $found;
    }

    private function record($scanId,$file,$rule,$severity,$message,$evidence)
    {
        $hash=hash('sha256',$rule."\0".substr((string)$evidence,0,1048576));$now=gmdate('Y-m-d H:i:s');
        $sql="INSERT IGNORE INTO `{TP}mod_security_center_virus_findings` (`scan_id`,`file_path`,`rule_id`,`severity`,`message`,`evidence_hash`,`created_at`) VALUES ('".$this->database->escapeString($scanId)."','".$this->database->escapeString(substr($file,0,700))."','".$this->database->escapeString($rule)."','$severity','".$this->database->escapeString($message)."','$hash','$now')";
        $this->database->query($sql);return 1;
    }

    private static function entropy($data)
    {
        $length=strlen($data);if($length===0)return 0.0;$counts=count_chars($data,1);$entropy=0.0;foreach($counts as $count){$p=$count/$length;$entropy-=$p*log($p,2);}return $entropy;
    }
    private function text($key,array $replace=array()){return WbceSecurityCenterLanguage::text('worker_runtime.'.$key,$key,$replace);}
}
