<?php
defined('WB_PATH') or die('No direct access');

/** Local PHP log viewer owned by Log Center. It intentionally does not use any
 * Error Logger class, function, setting or asset name, so an unmodified
 * original Error Logger can be installed independently. */
final class WbceLogCenterLogViewer
{
    private const SESSION_FILE = 'log_center_viewer_file';
    private const SESSION_VIEW = 'log_center_viewer_view';

    public static function handle($admin, &$message, &$failed)
    {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') return;
        $action = (string)($_POST['log_center_action'] ?? '');
        if (!in_array($action, array('viewer_type', 'viewer_file', 'viewer_view', 'viewer_archive'), true)) return;
        if (!$admin->checkFTAN()) { $message = log_center_text('SECURITY'); $failed = true; return; }
        $files = self::files();
        if ($action === 'viewer_type') {
            $groups = self::groups($files); $type = (string)($_POST['viewer_type'] ?? '');
            if (isset($groups[$type]) && $groups[$type]) { $_SESSION[self::SESSION_FILE] = $groups[$type][0]; $message = log_center_text('SAVED'); }
            return;
        }
        if ($action === 'viewer_file') {
            $name = basename((string)($_POST['viewer_file'] ?? ''));
            if (isset($files[$name])) { $_SESSION[self::SESSION_FILE] = $name; $message = log_center_text('SAVED'); }
            return;
        }
        if ($action === 'viewer_view') {
            $name = basename((string)($_POST['viewer_file'] ?? ''));
            if (isset($files[$name])) $_SESSION[self::SESSION_FILE] = $name;
            $view = (string)($_POST['viewer_view'] ?? 'console');
            $_SESSION[self::SESSION_VIEW] = $view === 'table' ? 'table' : 'console';
            $message = log_center_text('SAVED');
            return;
        }
        if ((string)($_POST['confirmed'] ?? '') !== '1') { $message = log_center_text('CONFIRM_REQUIRED'); $failed = true; return; }
        $file = self::selectedFile($files);
        if (!is_file($file)) { $message = log_center_text('VIEWER_EMPTY'); return; }
        $archive = dirname($file).'/'.gmdate('Ymd_His').'_'.basename($file);
        if (@rename($file, $archive)) $message = sprintf(log_center_text('ARCHIVED'), basename($archive));
        else { $message = log_center_text('ARCHIVE_FAILED'); $failed = true; }
    }

    public static function render($admin)
    {
        $files = self::files(); $groups = self::groups($files); $file = self::selectedFile($files); $name = basename($file); $type = self::typeKey($name);
        $view = ($_SESSION[self::SESSION_VIEW] ?? 'console') === 'table' ? 'table' : 'console';
        $lines = self::tail($file, 250); $entries = self::entries($lines, $name);
        ob_start(); ?>
<section class="log-center-viewer log-center-card" data-log-center-viewer data-log-center-file-name="<?=self::e($name)?>" data-log-center-type="<?=self::e($type)?>">
 <header class="log-center-viewer-header"><div><h3><?=self::e(log_center_text('LOCAL_LOGS'))?></h3><p><?=self::e(log_center_text('LOCAL_LOGS_DESCRIPTION'))?></p></div></header>
 <div class="log-center-viewer-toolbar">
  <div class="log-center-viewer-filters">
   <?php if(count($groups)>1): ?><form method="post" data-log-center-viewer-form><input type="hidden" name="log_center_action" value="viewer_type"><?=$admin->getFTAN()?><label><?=self::e(log_center_text('TYPE'))?><select name="viewer_type" data-log-center-type><?php foreach($groups as $key=>$items): ?><option value="<?=self::e($key)?>"<?=$key===$type?' selected':''?>><?=self::e(self::typeLabel($key))?></option><?php endforeach ?></select></label></form><?php endif ?>
   <?php if(!empty($groups[$type]) && count($groups[$type])>1): ?><form method="post" data-log-center-viewer-form><input type="hidden" name="log_center_action" value="viewer_file"><?=$admin->getFTAN()?><label><?=self::e(log_center_text('LOG_FILE'))?><select name="viewer_file" data-log-center-file><?php foreach($groups[$type] as $filename): ?><option value="<?=self::e($filename)?>"<?=$filename===$name?' selected':''?>><?=self::e(self::fileLabel($filename).' · '.self::fileTime($files[$filename]))?></option><?php endforeach ?></select></label></form><?php endif ?>
   <label><?=self::e(log_center_text('SEVERITY'))?><select data-log-center-severity><option value=""><?=self::e(log_center_text('FILTER_ALL'))?></option><option value="critical"><?=self::e(log_center_text('FILTER_CRITICAL'))?></option><option value="warning"><?=self::e(log_center_text('FILTER_WARNING'))?></option><option value="success"><?=self::e(log_center_text('FILTER_SUCCESS'))?></option><option value="info"><?=self::e(log_center_text('FILTER_INFO'))?></option></select></label>
   <label><?=self::e(log_center_text('SEARCH_LOG'))?><input type="search" data-log-center-search placeholder="<?=self::e(log_center_text('SEARCH_LOG_PLACEHOLDER'))?>"></label>
  </div>
  <div class="log-center-viewer-options"><div class="log-center-viewer-toggles">
   <label class="log-center-viewer-switch"><span><?=self::e(log_center_text('COLOR_VIEW'))?></span><input type="checkbox" data-log-center-colors checked><i></i></label>
   <form method="post" data-log-center-viewer-form><input type="hidden" name="log_center_action" value="viewer_view"><input type="hidden" name="viewer_view" value="<?=$view==='table'?'console':'table'?>"><input type="hidden" name="viewer_file" value="<?=self::e($name)?>"><?=$admin->getFTAN()?><label class="log-center-viewer-switch"><span><?=self::e(log_center_text('TABLE_VIEW'))?></span><input type="checkbox" data-log-center-view-toggle<?=$view==='table'?' checked':''?>><i></i></label></form>
   <label class="log-center-viewer-switch"><span><?=self::e(log_center_text('AUTO_RELOAD'))?></span><input type="checkbox" data-log-center-live<?=$type==='update'?' disabled':' checked'?>><i></i></label></div>
   <form method="post" data-log-center-archive-form data-confirm="<?=self::e(log_center_text('CONFIRM_ARCHIVE'))?>"><input type="hidden" name="log_center_action" value="viewer_archive"><input type="hidden" name="confirmed" value="0"><?=$admin->getFTAN()?><button type="submit" class="button log-center-danger"><i class="fa fa-trash"></i> <?=self::e(log_center_text('ARCHIVE_CLEAR'))?></button></form>
  </div>
 </div>
 <div class="log-center-log <?=$view==='table'?'is-table':''?>" data-log-center-log>
 <?php if(!$entries): ?><p class="log-center-empty"><?=self::e(log_center_text('VIEWER_EMPTY'))?></p>
 <?php elseif($view==='table'&&$type==='access'): ?><table class="log-center-log-table log-center-access-table"><thead><tr><th>Datum / Uhrzeit</th><th>Request</th><th>AJAX / API / Background</th><th>Status</th><th>Gesamtzeit</th></tr></thead><tbody><?php foreach($entries as $entry): $access=$entry['access']??array(); ?><tr data-log-center-status="<?=self::e($entry['severity'])?>"><td><?=self::e($entry['time'])?></td><td<?=($entry['trace']??'')!==''?' data-log-center-detail="'.self::detail($entry).'" role="button" tabindex="0"':''?>><?=self::e($access['request']??$entry['source'])?></td><td><?=self::e($access['trigger']??'—')?></td><td><?=self::e($access['error']??($entry['severity']==='critical'?'Error':'Success'))?></td><td><?=self::e($access['total']??'—')?></td></tr><?php endforeach ?></tbody></table>
 <?php elseif($view==='table'&&$type==='runtime'): ?><table class="log-center-log-table"><thead><tr><th>Datum / Uhrzeit</th><th>Request</th><th>Status</th><th>Gesamtzeit</th></tr></thead><tbody><?php foreach($entries as $entry): $runtime=$entry['access']??array(); ?><tr data-log-center-status="<?=self::e($entry['severity'])?>"><td><?=self::e($entry['time'])?></td><td<?=($entry['trace']??'')!==''?' data-log-center-detail="'.self::detail($entry).'" role="button" tabindex="0"':''?>><?=self::e($runtime['request']??$entry['source'])?></td><td><?=self::e($runtime['error']??($entry['severity']==='critical'?'Error':'Success'))?></td><td><?=self::e($runtime['total']??'—')?></td></tr><?php endforeach ?></tbody></table>
 <?php elseif($view==='table'): ?><table class="log-center-log-table"><thead><tr><th><?=self::e(log_center_text('DATE_TIME'))?></th><th><?=self::e(log_center_text('TYPE'))?></th><th><?=self::e(log_center_text('SOURCE'))?></th><th><?=self::e(log_center_text('MESSAGE'))?></th></tr></thead><tbody><?php foreach($entries as $entry): ?><tr data-log-center-status="<?=self::e($entry['severity'])?>"><td><?=self::e($entry['time'])?></td><td><?=self::e($entry['type']==='Information'?'':$entry['type'])?></td><td><?=self::e($entry['source'])?></td><td<?php if($entry['trace']!==''): ?> data-log-center-detail="<?=self::detail($entry)?>" role="button" tabindex="0"<?php endif ?>><?=self::e($entry['message'])?></td></tr><?php endforeach ?></tbody></table>
 <?php else: foreach($entries as $entry): ?><div data-log-center-status="<?=self::e($entry['severity'])?>"<?php if($entry['trace']!==''): ?> data-log-center-detail="<?=self::detail($entry)?>" role="button" tabindex="0"<?php endif ?>><?=self::e($entry['line'])?><?php if($entry['trace']!==''): ?><span><?=self::e(log_center_text('SHOW_DETAILS'))?></span><?php endif ?></div><?php endforeach; endif ?></div>
 <small class="log-center-live-status" data-log-center-live-status></small>
</section>
<dialog class="log-center-detail-dialog"><form method="dialog"><header><h3><?=self::e(log_center_text('TRACE_DETAILS'))?></h3><button value="close" class="button" aria-label="<?=self::e(log_center_text('CLOSE'))?>">×</button></header><div class="log-center-detail-content"><pre data-log-center-detail-line></pre><pre data-log-center-detail-trace></pre></div></form></dialog>
<?php return ob_get_clean();
    }
    private static function files()
    {
        $directory = WB_PATH.'/var/logs'; $result = array();
        foreach ((array)glob($directory.'/*') as $file) { $name=basename($file); if(is_file($file)&&is_readable($file)&&preg_match('/(?:error|log)/i',$name)&&!preg_match('/\.ndjson(?:\.\d+)?$/i',$name)) $result[$name]=$file; }
        $current=$directory.'/php_error.log.php'; if(is_file($current))$result[basename($current)]=$current; uasort($result,static fn($a,$b)=>(@filemtime($b)?:0)<=>((@filemtime($a)?:0))); return $result;
    }
    private static function groups(array $files)
    {
        $groups = array(); foreach ($files as $filename => $path) { $key=self::typeKey($filename); if(!isset($groups[$key]))$groups[$key]=array(); $groups[$key][]=$filename; }
        return $groups;
    }
    private static function typeKey($filename)
    {
        $name=strtolower((string)$filename); if(strpos($name,'php_error')!==false||strpos($name,'error')!==false)return 'error'; if(strpos($name,'runtime')!==false)return 'runtime'; if(strpos($name,'access')!==false)return 'access'; if(strpos($name,'worker')!==false||strpos($name,'cron')!==false)return 'worker'; if(strpos($name,'backup')!==false)return 'backup'; if(strpos($name,'update-')!==false)return 'update'; return 'system';
    }
    private static function typeLabel($type)
    {
        return log_center_text('FILE_'.strtoupper((string)$type).'_LOG');
    }
    private static function selectedFile(array $files)
    {
        $requested=basename((string)($_GET['viewer_file']??'')); if(isset($files[$requested]))return $files[$requested];
        $wanted=basename((string)($_SESSION[self::SESSION_FILE]??'')); if(isset($files[$wanted]))return $files[$wanted];
        $current=WB_PATH.'/var/logs/php_error.log.php'; return isset($files[basename($current)])?$files[basename($current)]:(string)(reset($files)?:$current);
    }
    private static function fileLabel($filename)
    {
        $name = strtolower((string)$filename);
        if (strpos($name, 'php_error') !== false || strpos($name, 'error') !== false) return log_center_text('FILE_ERROR_LOG');
        if (strpos($name, 'runtime') !== false) return log_center_text('FILE_RUNTIME_LOG');
        if (strpos($name, 'access') !== false) return log_center_text('FILE_ACCESS_LOG');
        if (strpos($name, 'worker') !== false || strpos($name, 'cron') !== false) return log_center_text('FILE_WORKER_LOG');
        if (strpos($name, 'backup') !== false) return log_center_text('FILE_BACKUP_LOG');
        if (preg_match('/^update-([a-z0-9._-]+)\.log(?:\.\d+)?$/i', (string)$filename, $match)) return log_center_text('FILE_UPDATE_LOG') . ' ' . $match[1];
        return log_center_text('FILE_SYSTEM_LOG');
    }
    private static function fileTime($path)
    {
        $time = @filemtime($path); return $time ? date((defined('DATE_FORMAT') ? DATE_FORMAT : 'd.m.Y').' '.(defined('TIME_FORMAT') ? TIME_FORMAT : 'H:i:s'), $time) : '';
    }
    private static function tail($file,$limit)
    {
        if(!is_file($file)||!is_readable($file))return array(); $h=@fopen($file,'rb');if(!$h)return array();$buffer='';$pos=(int)@filesize($file);
        while($pos>0&&substr_count($buffer,"\n")<=$limit){$read=min(8192,$pos);$pos-=$read;if(fseek($h,$pos)!==0)break;$chunk=fread($h,$read);if($chunk===false)break;$buffer=$chunk.$buffer;} fclose($h);
        return array_values(array_filter(preg_split('/\r\n|\r|\n/',rtrim($buffer,"\r\n"))?:array(),static fn($line)=>!preg_match('~^\s*<\?php\b.*\bdie\s*\(~i',(string)$line)));
    }
    private static function entries(array $lines, $filename = '')
    {
        if (stripos((string)$filename, 'i18n_missing') !== false) {
            $entries = self::i18nEntries(implode("\n", $lines));
            if ($entries) return $entries;
        }
        $entries=array(); foreach(self::groupArrayLines($lines) as $line){if(strpos((string)$line,"\0array\n")===0){$details=self::printArrayDetails(substr((string)$line,7));if($entries){$entries[count($entries)-1]['trace'].=($entries[count($entries)-1]['trace']!==''?"\n":'').$details;continue;}$entries[]=array('line'=>'[Information] '.log_center_text('STRUCTURED_DATA'),'time'=>'','type'=>'Information','source'=>'','message'=>log_center_text('STRUCTURED_DATA'),'trace'=>$details,'severity'=>'info');continue;}$line=trim((string)$line);if($line==='')continue;$trace='';if(strpos($line,' | Trace: ')!==false)[$line,$trace]=explode(' | Trace: ',$line,2);$line=self::normalizeTrace($line);$trace=self::normalizeTrace($trace);
            if(preg_match('/^\s*(.*?)\s+\[Runtime Access\]\s+id=([^\s]+)\s+\+(\d+)ms\s+total=(\d+)ms\s+request\.access\s*(\{.*\})?\s*$/',$line,$match)){ $context=isset($match[5])?json_decode($match[5],true):array();$context=is_array($context)?$context:array();$time=self::formatTime(trim($match[1]));$request=(string)($context['request']??'');$trigger=!empty($context['background'])?'Background':(!empty($context['api'])?'API':(!empty($context['ajax'])?'AJAX':'—'));$error=!empty($context['error'])?'Error':'Success';$total=$match[4].' ms';$details='ID: '.$match[2]."\nMethode: ".(string)($context['method']??'')."\nStatus: ".(string)($context['status']??'')."\nLangsam: ".(!empty($context['slow'])?'ja':'nein')."\nStichprobe: ".(!empty($context['sampled'])?'ja':'nein');$message=trim($request.' · '.$trigger.' · '.$error.' · '.$total);$display=trim(($time!==''?$time.' ':'').$message);$entries[]=array('line'=>$display,'time'=>$time,'type'=>'Runtime Access','source'=>$request,'message'=>$message,'trace'=>$details,'severity'=>!empty($context['error'])?'critical':'success','access'=>array('request'=>$request,'trigger'=>$trigger,'error'=>$error,'total'=>$total));continue;}
            if(preg_match('/^\s*(.*?)\s+\[Runtime Request\]\s+id=([^\s]+)\s+\+(\d+)ms\s+total=(\d+)ms\s+request\.finish\s*(\{.*\})?\s*$/',$line,$match)){ $context=isset($match[5])?json_decode($match[5],true):array();$context=is_array($context)?$context:array();$time=self::formatTime(trim($match[1]));$request=(string)($context['request']??'');$total=$match[4].' ms';$details='ID: '.$match[2]."\nSpeicher: ".(string)($context['memory_mb']??'')." MB";$message=trim($request.' · Success · '.$total);$display=trim(($time!==''?$time.' ':'').$message);$entries[]=array('line'=>$display,'time'=>$time,'type'=>'Runtime Request','source'=>$request,'message'=>$message,'trace'=>$details,'severity'=>'success','access'=>array('request'=>$request,'trigger'=>'—','error'=>'Success','total'=>$total));continue;}
            if($line!==''&&$line[0]==='{'){ $json=json_decode($line,true);if(is_array($json)){foreach(self::expandJsonEntries($json) as $record)self::appendJsonEntry($entries,$record);continue;}}
            if(preg_match('/^\s*(?:#\d+\s|Stack trace:|PHP Stack trace:|thrown in\s)/i',$line)&&$entries){$entries[count($entries)-1]['trace'].=($entries[count($entries)-1]['trace']!==''?"\n":'').$line;continue;}
            preg_match('/^\s*([^\[]+)/',$line,$date);preg_match('/\[([^\]]+)\]/',$line,$type);$kind=trim((string)($type[1]??'Information'));
            $severity=preg_match('/Exception|Fatal|Critical|Parse error|\[Error\]/i',$line)?'critical':(preg_match('/Warning|Deprecated|Notice/i',$line)?'warning':(preg_match('/\[(?:Success|Successful|Completed|Complete|OK)\]/i',$line)?'success':'info'));
            $rawTime=trim((string)($date[1]??'')); $time=self::formatTime($rawTime); if($rawTime!==''&&$time!==$rawTime)$line=preg_replace('/^\s*[^\[]+/', $time.' ', $line, 1);
            $message=trim(preg_replace('/^\s*[^\[]*(?:\[[^\]]+\])?\s*/','',$line));$display=trim(($time!==''?$time.' ':'').(strcasecmp($kind,'Information')===0?'':'['.$kind.'] ').$message);$entries[]=array('line'=>$display,'time'=>$time,'type'=>$kind,'source'=>'','message'=>$message,'trace'=>$trace,'severity'=>$severity); }
        return $entries;
    }
    private static function groupArrayLines(array $lines)
    {
        $result=array();$buffer=array();$depth=0;foreach($lines as $line){$trim=trim((string)$line);if(!$buffer&&($trim==='Array'||preg_match('/^Array\s*\($/',$trim))){$buffer=array((string)$line);$depth=substr_count($trim,'(')-substr_count($trim,')');continue;}if($buffer){$buffer[]=(string)$line;$depth+=substr_count($trim,'(')-substr_count($trim,')');if($depth===0&&count($buffer)>1){$result[]="\0array\n".implode("\n",$buffer);$buffer=array();}continue;}$result[]=(string)$line;}if($buffer)$result[]=implode("\n",$buffer);return $result;
    }
    private static function printArrayDetails($value)
    {
        $result=array();foreach(preg_split('/\r\n|\r|\n/',(string)$value) as $line){$line=trim($line);if($line===''||$line==='Array'||$line==='('||$line===')')continue;$result[]=preg_replace('/^\[([^\]]+)\]\s*=>\s*/','$1: ',$line);}return implode("\n",$result);
    }
    private static function valueSummary($value){return is_array($value)?log_center_text('STRUCTURED_DATA'):trim(html_entity_decode((string)$value,ENT_QUOTES|ENT_HTML5,'UTF-8'));}
    private static function expandJsonEntries(array $json)
    {
        if(empty($json['entries'])||!is_array($json['entries']))return array($json);$base=$json;unset($base['entries']);$records=array();foreach($json['entries'] as $entry)if(is_array($entry))$records[]=array_merge($base,$entry);return $records?:array($base);
    }
    private static function appendJsonEntry(array &$entries,array $json)
    {
        $rawTime=(string)($json['time']??$json['timestamp']??$json['datetime']??'');$kind=trim((string)($json['level']??$json['severity']??$json['type']??'Information'));$source=trim((string)($json['source']??$json['channel']??$json['logger']??''));$rawMessage=$json['message']??$json['event']??$json['text']??'';$message=self::valueSummary($rawMessage);$message=preg_replace('/^\d{4}-\d{2}-\d{2}T[^·]+\s*·\s*/u','',$message);$trace=(string)($json['trace']??$json['exception']??'');$details=self::arrayDetails($json,array('time','timestamp','datetime','level','severity','type','source','channel','logger','message','event','text','trace','exception'));if($details!=='')$trace=trim($trace.($trace!==''?"\n":'').$details);$severity=preg_match('/fatal|critical|error|exception/i',$kind)?'critical':(preg_match('/warn|deprecated|notice/i',$kind)?'warning':(preg_match('/success|complete|ok/i',$kind)?'success':'info'));$time=self::formatTime($rawTime);$display=trim(($time!==''?$time.' ':'').(strcasecmp($kind,'Information')===0?'': '['.$kind.'] ').($source!==''?$source.' · ':'').$message);$entries[]=array('line'=>$display,'time'=>$time,'type'=>$kind,'source'=>$source,'message'=>$message,'trace'=>self::normalizeTrace($trace),'severity'=>$severity);
    }
    private static function arrayDetails(array $value,array $skip=array(),$prefix='')
    {
        $lines=array();foreach($value as $key=>$item){if($prefix===''&&in_array((string)$key,$skip,true))continue;$name=$prefix===''?(string)$key:$prefix.'.'.$key;if(is_array($item)){$nested=self::arrayDetails($item,array(),$name);if($nested!=='')$lines[]=$nested;}elseif(is_scalar($item)||$item===null)$lines[]=$name.': '.($item===null?'null':(string)$item);}return implode("\n",$lines);
    }
    private static function i18nEntries($content)
    {
        $pattern = "~'((?:\\\\.|[^'])*)'\\s*=>\\s*array\\s*\\(\\s*'namespace'\\s*=>\\s*'((?:\\\\.|[^'])*)',\\s*'key'\\s*=>\\s*'((?:\\\\.|[^'])*)',\\s*'file'\\s*=>\\s*'((?:\\\\.|[^'])*)',\\s*'line'\\s*=>\\s*(\\d+),\\s*'first_seen'\\s*=>\\s*'((?:\\\\.|[^'])*)'~s";
        if (!preg_match_all($pattern, (string)$content, $matches, PREG_SET_ORDER)) return array();
        $entries = array();
        foreach ($matches as $match) {
            $namespace = stripcslashes($match[2]); $key = stripcslashes($match[3]); $file = stripcslashes($match[4]); $seen = stripcslashes($match[6]);
            $time = self::formatTime($seen); $source = $namespace !== '' ? $namespace : 'i18n';
            $message = log_center_text('I18N_MISSING') . ($key !== '' ? ': ' . $key : '');
            $trace = trim('Datei: ' . $file . "\nZeile: " . $match[5]);
            $line = trim(($time !== '' ? $time . ' ' : '') . $source . ' · ' . $message);
            $entries[] = array('line'=>$line, 'time'=>$time, 'type'=>'Information', 'source'=>$source, 'message'=>$message, 'trace'=>$trace, 'severity'=>'info');
        }
        return $entries;
    }
    private static function formatTime($value)
    {
        $value=trim((string)$value); if($value==='')return '';
        try { $time=new DateTimeImmutable($value); $zone=function_exists('wbce_timezone')?wbce_timezone():new DateTimeZone(date_default_timezone_get()); if($zone instanceof DateTimeZone)$time=$time->setTimezone($zone); return $time->format(defined('DATE_FORMAT')?DATE_FORMAT.' '.(defined('TIME_FORMAT')?TIME_FORMAT:'H:i:s'):'d.m.Y H:i:s'); } catch(Throwable $e) { return $value; }
    }
    private static function normalizeTrace($value){return (string)preg_replace('/\s*<-\s*#+/',"\n#",(string)$value);}
    private static function detail(array $entry){return self::e((string)json_encode(array('line'=>$entry['line'],'trace'=>self::normalizeTrace($entry['trace'])),JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES));}
    private static function e($value){return htmlspecialchars((string)$value,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');}
}
