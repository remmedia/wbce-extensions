<?php
require_once __DIR__.'/Language.php';

final class WbceSecurityCenterIntegrityMonitor
{
    public static function run($database,$limit=250)
    {
        $service=new WbceSecurityCenterService($database);$settings=$service->settings();
        if(($settings['integrity_enabled']??'1')!=='1')return array('message'=>self::text('integrity_disabled'));
        $cursor=(string)($settings['integrity_cursor']??'');
        $cycleStarted=$cursor===''?gmdate('Y-m-d H:i:s'):(string)($settings['integrity_cycle_started']??gmdate('Y-m-d H:i:s'));
        if($cursor==='')$database->query("REPLACE INTO `{TP}mod_security_center_settings` (`name`,`value`) VALUES ('integrity_cycle_started','$cycleStarted')");
        $files=self::files($cursor,(int)$limit+1,(string)($settings['integrity_paths']??''));$more=count($files)>(int)$limit;if($more)array_pop($files);$changes=0;$last=$cursor;
        foreach($files as $relative){
            $last=$relative;$base=realpath(WB_PATH);$path=realpath(WB_PATH.'/'.$relative);
            if(!$base||!$path||($path!==$base&&!str_starts_with($path,$base.DIRECTORY_SEPARATOR))||!is_file($path)||is_link($path))continue;
            $hash=@hash_file('sha256',$path);$size=@filesize($path);$mtime=@filemtime($path);
            if(!is_string($hash)||$size===false||$mtime===false)continue;
            $pathHash=hash('sha256',$relative);$critical=self::critical($relative);
            $result=$database->query("SELECT * FROM `{TP}mod_security_center_file_integrity` WHERE path_hash='$pathHash' LIMIT 1");$old=$result?$result->fetchRow(MYSQLI_ASSOC):null;
            if($old&&!hash_equals((string)$old['sha256'],$hash)){
                $service->event('file.changed',$critical?'critical':'warning',array('file'=>$relative,'old_sha256'=>$old['sha256'],'new_sha256'=>$hash));
                self::finding($database,$relative,$critical?'critical':'medium','integrity_changed',self::text('integrity_changed'),$hash);$changes++;
            }elseif(!$old&&self::unexpected($relative)){
                $service->event('file.created',$critical?'critical':'warning',array('file'=>$relative,'sha256'=>$hash));
                self::finding($database,$relative,$critical?'critical':'high','integrity_created',self::text('integrity_created'),$hash);$changes++;
            }
            $now=gmdate('Y-m-d H:i:s');
            $database->query("REPLACE INTO `{TP}mod_security_center_file_integrity` (`path_hash`,`file_path`,`sha256`,`size_bytes`,`modified_at`,`critical`,`checked_at`) VALUES ('$pathHash','".$database->escapeString($relative)."','$hash',".(int)$size.",".(int)$mtime.",".($critical?1:0).",'$now')");
        }
        if(!$more){
            $missing=$database->query("SELECT file_path,critical FROM `{TP}mod_security_center_file_integrity` WHERE checked_at<'".$database->escapeString($cycleStarted)."' LIMIT 100");
            while($missing&&($row=$missing->fetchRow(MYSQLI_ASSOC))){if(is_file(WB_PATH.'/'.$row['file_path']))continue;$service->event('file.deleted',!empty($row['critical'])?'critical':'warning',array('file'=>$row['file_path']));$database->query("DELETE FROM `{TP}mod_security_center_file_integrity` WHERE file_path='".$database->escapeString($row['file_path'])."'");$changes++;}
            $last='';
        }
        $database->query("REPLACE INTO `{TP}mod_security_center_settings` (`name`,`value`) VALUES ('integrity_cursor','".$database->escapeString($more?$last:'')."')");
        return array('message'=>self::text('integrity_result',array('{files}'=>count($files),'{changes}'=>$changes)),'more'=>$more);
    }

    private static function files($cursor,$limit,$configuredPaths)
    {
        $roots=array();foreach(preg_split('/[\r\n,]+/',str_replace('\\','/',trim($configuredPaths))) as $root){$root=trim($root," /\t");if($root!==''&&!str_contains($root,'..'))$roots[]=$root;}
        if(!$roots)$roots=array('admin','framework','include','modules','templates','index.php','config.php');
        $out=array();$iterator=new RecursiveIteratorIterator(new RecursiveDirectoryIterator(WB_PATH,FilesystemIterator::SKIP_DOTS),RecursiveIteratorIterator::LEAVES_ONLY,RecursiveIteratorIterator::CATCH_GET_CHILD);
        foreach($iterator as $entry){if(!$entry->isFile()||$entry->isLink()||$entry->getSize()>16*1024*1024)continue;$relative=str_replace('\\','/',ltrim(substr($entry->getPathname(),strlen(WB_PATH)),'/'));if(!self::selected($relative,$roots)||$relative<=$cursor||preg_match('~^(?:temp|var/cache|media/.*\.(?:jpe?g|png|gif|webp|pdf|zip|mp4|mp3))~i',$relative))continue;if(!preg_match('/(?:\.php|\.phtml|\.inc|\.module|\.js|\.htaccess|config\.php)$/i',$relative))continue;$out[]=$relative;}
        sort($out,SORT_STRING);return array_slice($out,0,$limit);
    }

    private static function selected($relative,$roots){foreach($roots as $root)if($relative===$root||str_starts_with($relative,rtrim($root,'/').'/'))return true;return false;}
    private static function critical($path){return (bool)preg_match('~^(?:admin|framework|include|install)/|(?:^|/)config\.php$|(?:^|/)\.htaccess$~i',$path);}
    private static function unexpected($path){return (bool)preg_match('~^(?:media|temp|var|uploads?)/.*\.(?:php|phtml|inc|module)$~i',$path);}
    private static function finding($database,$path,$severity,$rule,$message,$hash){$database->query("INSERT IGNORE INTO `{TP}mod_security_center_findings` (`scan_id`,`file_path`,`line_number`,`rule_id`,`severity`,`message`,`code_hash`,`created_at`) VALUES ('integrity','".$database->escapeString($path)."',0,'$rule','$severity','".$database->escapeString($message)."','$hash','".gmdate('Y-m-d H:i:s')."')");}
    private static function text($key,array $replace=array()){return WbceSecurityCenterLanguage::text('worker_runtime.'.$key,$key,$replace);}
}
