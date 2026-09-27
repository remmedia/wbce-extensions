<?php
require_once __DIR__.'/../Diagnostics.php';
$errors=WbceSecurityCenterDiagnostics::errorLog();
$errorLogState=WbceSecurityCenterDiagnostics::errorLogState();
$errorEntries=array();
foreach($errors as $line){
    $inlineTrace='';
    $traceMarker=' | Trace: ';
    $tracePosition=strpos((string)$line,$traceMarker);
    if($tracePosition!==false){
        $inlineTrace=substr((string)$line,$tracePosition+strlen($traceMarker));
        $line=substr((string)$line,0,$tracePosition);
    }
    $isTrace=(bool)preg_match('/^\s*(?:#\d+\s|Stack trace:|PHP Stack trace:|thrown in\s)/i',(string)$line);
    if($isTrace&&$errorEntries){$errorEntries[count($errorEntries)-1]['trace'][]=(string)$line;continue;}
    $severity=preg_match('/Exception|Fatal|Critical|Parse error/i',(string)$line)?'critical':(preg_match('/Warning|Deprecated|Notice/i',(string)$line)?'warning':'info');
    $errorEntries[]=array('line'=>(string)$line,'severity'=>$severity,'trace'=>$inlineTrace!==''?array($inlineTrace):array());
}
?>
<section>
    <h3><?php echo sec_h(sec_t('errorlog.title','Error-Log'));?></h3>
    <div class="sec-errorlog-toolbar">
        <label><?php echo sec_h(sec_t('errorlog.severity','Schweregrad'));?><select data-errorlog-severity><option value=""><?php echo sec_h(sec_t('errorlog.all','Alle Meldungen'));?></option><option value="critical"><?php echo sec_h(sec_t('errorlog.critical','Kritisch/Exception'));?></option><option value="warning"><?php echo sec_h(sec_t('errorlog.warning','Warnung/Deprecated'));?></option><option value="info"><?php echo sec_h(sec_t('errorlog.info','Information'));?></option></select></label>
        <label><?php echo sec_h(sec_t('errorlog.search','Protokoll durchsuchen'));?><input type="search" data-errorlog-search placeholder="<?php echo sec_h(sec_t('errorlog.placeholder','Text, Datei oder Meldung'));?>"></label>
        <label class="sec-errorlog-color-toggle"><span><?php echo sec_h(sec_t('errorlog.colors','Farbig darstellen'));?></span><span class="sec-slider"><input type="checkbox" data-errorlog-color checked><i></i></span></label>
        <label class="sec-errorlog-color-toggle"><span><?php echo sec_h(sec_t('errorlog.live','Automatisch nachladen'));?></span><span class="sec-slider"><input type="checkbox" data-errorlog-live checked><i></i></span></label>
        <?php if($errorLogState['writable']){?><form method="post" data-confirm="<?php echo sec_h(sec_t('errorlog.confirm_clear',''));?>"><input type="hidden" name="sc_page" value="errors"><input type="hidden" name="action" value="error_log_clear"><?php echo $admin->getFTAN();?><button class="sec-danger"><?php echo sec_h(sec_t('errorlog.clear','Protokoll leeren'));?></button></form><?php }?>
    </div>
    <?php if(!$errorLogState['readable']){?><p><?php echo sec_h(sec_t('errorlog.unreadable',''));?></p><?php }else{?>
        <div class="sec-errorlog" data-errorlog><?php foreach($errorEntries as $entry){$trace=implode("\n",$entry['trace']);?><div data-severity="<?php echo $entry['severity'];?>"<?php if($trace!==''){?> data-errorlog-trace role="button" tabindex="0" aria-label="<?php echo sec_h(sec_t('errorlog.trace_open','Details zur Meldung anzeigen'));?>"<?php }?>><?php echo sec_h($entry['line']);?><?php if($trace!==''){?><span class="sec-errorlog-trace-hint"><?php echo sec_h(sec_t('errorlog.trace_hint','Details anzeigen'));?></span><template data-errorlog-trace-content><?php echo sec_h($trace);?></template><?php }?></div><?php }?></div>
        <p class="sec-muted" data-errorlog-empty<?php echo $errorEntries?' hidden':'';?>><?php echo sec_h(sec_t($errorEntries?'errorlog.no_match':'errorlog.empty',''));?></p><small class="sec-live-status" data-errorlog-live-status><?php echo sec_h(sec_t('errorlog.live_active',''));?></small>
        <dialog class="sec-errorlog-trace-dialog" data-errorlog-trace-dialog><form method="dialog"><header><h3><?php echo sec_h(sec_t('errorlog.trace_title','Meldungsdetails'));?></h3><button type="submit" value="close" class="sec-secondary" data-errorlog-trace-close><?php echo sec_h(sec_t('common.close','Schließen'));?></button></header><pre data-errorlog-trace-output></pre></form></dialog>
    <?php }?>
</section>
