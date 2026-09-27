<?php
require '../../config.php';
require_once WB_PATH.'/framework/Admin.php';
$admin=new admin('Admintools','admintools',false,false);
if(!$admin->is_authenticated()||!$admin->isAdmin()||$_SERVER['REQUEST_METHOD']!=='POST'||!$admin->checkFTAN()){http_response_code(403);exit;}
header('Content-Type: application/x-ndjson; charset=utf-8');
header('Cache-Control: no-store');
header('X-Accel-Buffering: no');
while(ob_get_level()>0)@ob_end_flush();
function updater_backup_event($percent,$message,$ok=null){echo json_encode(array('percent'=>$percent,'message'=>$message,'ok'=>$ok),JSON_UNESCAPED_UNICODE)."\n";@flush();}
function updater_backup_write($handle,$content){if(@fwrite($handle,$content)===false)throw new RuntimeException('Datenbankexport konnte nicht geschrieben werden.');}
function updater_backup_identifier($value){return '`'.str_replace('`','``',(string)$value).'`';}
function updater_backup_fallback(){
    global $database;
    if(!class_exists('ZipArchive'))throw new RuntimeException('Die PHP-Erweiterung ZipArchive ist nicht verfügbar.');
    if(!isset($database)||!is_object($database)||!method_exists($database,'query'))throw new RuntimeException('Die Datenbankverbindung ist nicht verfügbar.');
    @set_time_limit(0);@ignore_user_abort(true);
    $backupDir=WB_PATH.(defined('BACKUP_DATA_DIR')?BACKUP_DATA_DIR:'/backups/');
    if(!is_dir($backupDir)&&!@mkdir($backupDir,0775,true))throw new RuntimeException('Das lokale Backup-Verzeichnis konnte nicht erstellt werden.');
    if(!is_writable($backupDir))throw new RuntimeException('Das lokale Backup-Verzeichnis ist nicht beschreibbar.');
    $stamp=date('Ymd-His');$base='wbce-updater-'.$stamp;
    $zipPath=rtrim($backupDir,'/\\').DIRECTORY_SEPARATOR.$base.'.zip';
    $tmpSql=WB_PATH.'/temp/.'.$base.'.sql';
    $tmpZip=$zipPath.'.part';
    $sqlHandle=null;$zip=null;
    try {
        updater_backup_event(7,'Eigenständiges lokales Backup wird vorbereitet …');
        $sqlHandle=@fopen($tmpSql,'wb');if($sqlHandle===false)throw new RuntimeException('Temporäre Datei für den Datenbankexport konnte nicht erstellt werden.');
        updater_backup_write($sqlHandle,"# WBCE Updater Backup\n# ".(defined('WB_URL')?WB_URL:'')."\nSET FOREIGN_KEY_CHECKS=0;\n\n");
        $prefix=defined('TABLE_PREFIX')?(string)TABLE_PREFIX:'';
        $escapedPrefix=str_replace(array('\\','_','%'),array('\\\\','\\_','\\%'),$prefix);
        $tables=$database->query("SHOW TABLES LIKE '".$database->escapeString($escapedPrefix)."%'");
        if(!$tables)throw new RuntimeException('Die Datenbanktabellen konnten nicht gelesen werden.');
        $tableNames=array();while($row=$tables->fetchRow())if(!empty($row[0]))$tableNames[]=(string)$row[0];
        if(!$tableNames)throw new RuntimeException('Keine WBCE-Datenbanktabellen gefunden.');
        $total=count($tableNames);$index=0;
        foreach($tableNames as $table){
            $identifier=updater_backup_identifier($table);
            $schema=$database->query('SHOW CREATE TABLE '.$identifier);if(!$schema)throw new RuntimeException('Tabellenstruktur konnte nicht exportiert werden: '.$table);
            $schemaRow=$schema->fetchRow(MYSQLI_ASSOC);$create=(string)($schemaRow['Create Table']??'');if($create==='')throw new RuntimeException('Tabellenstruktur ist unvollständig: '.$table);
            updater_backup_write($sqlHandle,"DROP TABLE IF EXISTS ".$identifier.";\n".$create.";\n");
            $rows=$database->query('SELECT * FROM '.$identifier);if(!$rows)throw new RuntimeException('Tabelleninhalt konnte nicht exportiert werden: '.$table);
            while($row=$rows->fetchRow(MYSQLI_ASSOC)){
                $values=array();foreach($row as $column=>$value)$values[]=updater_backup_identifier($column).'='.(($value===null)?'NULL':"'".$database->escapeString((string)$value)."'");
                updater_backup_write($sqlHandle,'INSERT INTO '.$identifier.' SET '.implode(',',$values).";\n");
            }
            $index++;updater_backup_event(8+(int)floor(($index/$total)*27),'Datenbank wird gesichert: '.$table.' …');
        }
        updater_backup_write($sqlHandle,"SET FOREIGN_KEY_CHECKS=1;\n");fclose($sqlHandle);$sqlHandle=null;
        updater_backup_event(38,'CMS-Dateien werden in das Archiv aufgenommen …');
        $zip=new ZipArchive();if($zip->open($tmpZip,ZipArchive::CREATE|ZipArchive::OVERWRITE)!==true)throw new RuntimeException('Backup-Archiv konnte nicht erstellt werden.');
        $source=realpath(WB_PATH);$backupReal=realpath($backupDir);$tempReal=realpath(WB_PATH.'/temp');$count=0;
        $files=new RecursiveIteratorIterator(new RecursiveDirectoryIterator($source,FilesystemIterator::SKIP_DOTS),RecursiveIteratorIterator::LEAVES_ONLY);
        foreach($files as $file){
            if(!$file->isFile()||$file->isLink()||!$file->isReadable())continue;
            $path=$file->getPathname();$normal=str_replace('\\','/',$path);
            if(($backupReal&&str_starts_with($normal,str_replace('\\','/',$backupReal).'/'))||($tempReal&&str_starts_with($normal,str_replace('\\','/',$tempReal).'/.wbce-updater-')))continue;
            $relative=ltrim(substr($path,strlen($source)),DIRECTORY_SEPARATOR);
            if(!$zip->addFile($path,'files/'.str_replace('\\','/',$relative)))throw new RuntimeException('Datei konnte nicht in das Archiv aufgenommen werden: '.$relative);
            $count++;if(($count%150)===0)updater_backup_event(min(88,38+(int)floor(log($count+1,1.55)*5)),$count.' Dateien wurden gesichert …');
        }
        if(!$zip->addFile($tmpSql,'database.sql'))throw new RuntimeException('Datenbankexport konnte nicht in das Archiv aufgenommen werden.');
        $zip->setArchiveComment('WBCE Updater standalone backup; files/ contains CMS files, database.sql contains the database export.');
        if(!$zip->close())throw new RuntimeException('Backup-Archiv konnte nicht abgeschlossen werden.');$zip=null;
        if(!@rename($tmpZip,$zipPath))throw new RuntimeException('Backup-Archiv konnte nicht final gespeichert werden.');
        @unlink($tmpSql);
        updater_backup_event(100,'Eigenständiges lokales Backup erfolgreich erstellt ('.$count.' Dateien und Datenbankexport).',true);
    } catch(Throwable $error) {if($zip instanceof ZipArchive)@$zip->close();if($sqlHandle)@fclose($sqlHandle);@unlink($tmpSql);@unlink($tmpZip);throw $error;}
}
$worker=WB_PATH.'/modules/backup_center/Worker.php';
try {
    updater_backup_event(5,'Lokales Backup wird vorbereitet …');
    if(!empty($_POST['enable_maintenance'])){require_once __DIR__.'/maintenance_helper.php';$errors=array();$language=__DIR__.'/languages/'.(defined('LANGUAGE')?LANGUAGE:'EN').'.php';$LANG=array();require is_file($language)?$language:__DIR__.'/languages/EN.php';updater_enable_maintenance($errors,$LANG);if($errors)throw new RuntimeException(implode(' ', $errors));}
    if(is_file($worker)){
        if(!defined('BACKUP_CENTER_UPDATER_LOCAL_ONLY'))define('BACKUP_CENTER_UPDATER_LOCAL_ONLY',true);
        $GLOBALS['BACKUP_CENTER_PROGRESS_CALLBACK']=function($percent,$message){updater_backup_event($percent,$message);};
        require_once $worker;
        $result=BackupCenterWorker::run(array('backup_type'=>'wbce','requested_backup_type'=>'wbce','storage_target'=>'local','document_root'=>WB_PATH,'http_host'=>parse_url(WB_URL,PHP_URL_HOST)),array('id'=>0));
        unset($GLOBALS['BACKUP_CENTER_PROGRESS_CALLBACK']);
        updater_backup_event(100,is_array($result)&&!empty($result['message'])?$result['message']:'Backup erfolgreich erstellt.',true);
    } else updater_backup_fallback();
}catch(Throwable $error){unset($GLOBALS['BACKUP_CENTER_PROGRESS_CALLBACK']);updater_backup_event(0,$error->getMessage(),false);}
