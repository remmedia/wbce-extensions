<?php
require_once __DIR__.'/Language.php';
final class WbceSecurityCenterScanner
{
    private $database;
    public function __construct($database){$this->database=$database;}
    public function run($limit=120)
    {
        $job=$this->claim();if(!$job)return array('message'=>$this->text('code_none'));
        try{
            require_once __DIR__.'/RuntimeConfig.php';$runtime=WbceSecurityCenterRuntimeConfig::load();$files=$this->files($job['root_path'],$job['cursor_path'],max(10,min(500,(int)$limit))+1,(array)($runtime['compiled_rules']['file_allow']??array()));$more=count($files)>(int)$limit;if($more)array_pop($files);$found=0;$last=$job['cursor_path'];
            foreach($files as $file){$last=$file;$found+=$this->scanFile($job['scan_id'],$file);}
            $status=$more?'queued':'done';$finished=$more?'NULL':"'".gmdate('Y-m-d H:i:s')."'";$this->database->query("UPDATE `{TP}mod_security_center_scan_queue` SET status='$status',lock_token=NULL,cursor_path='".$this->database->escapeString($last)."',files_scanned=files_scanned+".count($files).",findings_count=findings_count+$found,finished_at=$finished,last_error=NULL WHERE id=".(int)$job['id']." AND lock_token='".$this->database->escapeString((string)$job['lock_token'])."'");
            return array('message'=>$this->text('code_result',array('{files}'=>count($files),'{findings}'=>$found)),'more'=>$more);
        }catch(Throwable $e){$this->database->query("UPDATE `{TP}mod_security_center_scan_queue` SET status='failed',lock_token=NULL,finished_at='".gmdate('Y-m-d H:i:s')."',last_error='".$this->database->escapeString($e->getMessage())."' WHERE id=".(int)$job['id']." AND lock_token='".$this->database->escapeString((string)$job['lock_token'])."'");throw $e;}
    }
    private function claim()
    {
        $stale=gmdate('Y-m-d H:i:s',time()-1800);$this->database->query("UPDATE `{TP}mod_security_center_scan_queue` SET status='queued',started_at=NULL,lock_token=NULL WHERE status='running' AND started_at<'$stale'");$r=$this->database->query("SELECT * FROM `{TP}mod_security_center_scan_queue` WHERE status='queued' ORDER BY id LIMIT 1");$job=$r?$r->fetchRow(MYSQLI_ASSOC):null;if(!$job)return null;$now=gmdate('Y-m-d H:i:s');try{$token=bin2hex(random_bytes(16));}catch(Throwable $exception){throw new RuntimeException($this->text('secure_random'));}$this->database->query("UPDATE `{TP}mod_security_center_scan_queue` SET status='running',lock_token='$token',started_at=COALESCE(started_at,'$now') WHERE id=".(int)$job['id']." AND status='queued'");$claimed=$this->database->query("SELECT * FROM `{TP}mod_security_center_scan_queue` WHERE id=".(int)$job['id']." AND status='running' AND lock_token='$token' LIMIT 1");return $claimed?$claimed->fetchRow(MYSQLI_ASSOC):null;
    }
    private function files($root,$cursor,$limit,$allowedFiles=array())
    {
        $base=realpath(WB_PATH);$root=realpath($root);if(!$base||!$root||($root!==$base&&!str_starts_with($root,$base.DIRECTORY_SEPARATOR)))throw new RuntimeException($this->text('code_path_invalid'));$files=array();$iterator=new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root,FilesystemIterator::SKIP_DOTS),RecursiveIteratorIterator::LEAVES_ONLY,RecursiveIteratorIterator::CATCH_GET_CHILD);foreach($iterator as $entry){if(!$entry->isFile()||$entry->isLink())continue;$path=str_replace('\\','/',$entry->getPathname());$relative=ltrim(substr($path,strlen($root)),'/');if(self::allowed($relative,$allowedFiles)||$relative<=$cursor||!preg_match('/\.(?:php|phtml|inc|module)$/i',$relative)||preg_match('~^(?:temp|var/cache|modules/security_center/Scanner\.php)~i',$relative))continue;if($entry->getSize()>8*1024*1024)continue;$files[]=$relative;}sort($files,SORT_STRING);return array_slice($files,0,$limit);
    }
    private function text($key,array $replace=array()){return WbceSecurityCenterLanguage::text('worker_runtime.'.$key,$key,$replace);}
    private static function allowed($relative,$rules){foreach($rules as $rule){$rule=trim(str_replace('\\','/',(string)$rule),'/');if($rule!==''&&($relative===$rule||str_starts_with($relative,$rule.'/')))return true;}return false;}
    private function scanFile($scanId,$relative)
    {
        $base=realpath(WB_PATH);$path=realpath(WB_PATH.'/'.$relative);if(!$base||!$path||($path!==$base&&!str_starts_with($path,$base.DIRECTORY_SEPARATOR))||!is_file($path)||is_link($path))return 0;$code=@file_get_contents($path);if($code===false)return 0;$rules=self::rules();$count=0;
        foreach($rules as $id=>$rule){if(!preg_match_all($rule['pattern'],$code,$matches,PREG_OFFSET_CAPTURE))continue;foreach($matches[0] as $match){$line=substr_count(substr($code,0,$match[1]),"\n")+1;$snippet=substr($code,max(0,$match[1]-50),180);$hash=hash('sha256',$id."\0".$snippet);$sql="INSERT IGNORE INTO `{TP}mod_security_center_findings` (`scan_id`,`file_path`,`line_number`,`rule_id`,`severity`,`message`,`code_hash`,`created_at`) VALUES ('".$this->database->escapeString($scanId)."','".$this->database->escapeString($relative)."',$line,'$id','".$rule['severity']."','".$this->database->escapeString($rule['message'])."','$hash','".gmdate('Y-m-d H:i:s')."')";$this->database->query($sql);$count++;}}
        return $count;
    }
    public static function rules(){return array(
        'dynamic_eval'=>array('severity'=>'critical','message'=>self::findingText('dynamic_eval'),'pattern'=>'/\beval\s*\(/i'),
        'assert_code'=>array('severity'=>'high','message'=>self::findingText('assert_code'),'pattern'=>'/\bassert\s*\(\s*\$/i'),
        'encoded_payload'=>array('severity'=>'high','message'=>self::findingText('encoded_payload'),'pattern'=>'/\b(?:gzinflate|gzuncompress)\s*\(\s*(?:base64_decode|str_rot13)\s*\(/i'),
        'reversed_decoder'=>array('severity'=>'critical','message'=>self::findingText('reversed_decoder'),'pattern'=>'/edoced_46esab/i'),
        'variable_function'=>array('severity'=>'high','message'=>self::findingText('variable_function'),'pattern'=>'/\$[a-z_][a-z0-9_]*\s*\(\s*(?:base64_decode|hex2bin|str_rot13)\s*\(/i'),
        'process_execution'=>array('severity'=>'high','message'=>self::findingText('process_execution'),'pattern'=>'/(?<!->)(?<!::)\b(?:shell_exec|passthru|proc_open|popen|system)\s*\(/i'),
        'obfuscated_chr'=>array('severity'=>'medium','message'=>self::findingText('obfuscated_chr'),'pattern'=>'/(?:chr\s*\(\s*\d+\s*\)\s*\.\s*){3,}chr\s*\(/i'),
        'hex_escapes'=>array('severity'=>'low','message'=>self::findingText('hex_escapes'),'pattern'=>'/(?:\\x[0-9a-f]{2}){6,}/i'),
        'remote_include'=>array('severity'=>'critical','message'=>self::findingText('remote_include'),'pattern'=>'/\b(?:include|require)(?:_once)?\s*\(?\s*["\']https?:\/\//i'),
    );}
    private static function findingText($key){return WbceSecurityCenterLanguage::text('scanner_findings.'.$key,$key);}
}
