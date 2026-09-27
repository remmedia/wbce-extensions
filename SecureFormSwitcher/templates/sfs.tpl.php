<div class="sfs" data-invalid-response="<?php echo htmlspecialchars($SFS['INVALID_RESPONSE'],ENT_QUOTES,'UTF-8');?>" data-save-failed="<?php echo htmlspecialchars($SFS['SAVE_FAILED'],ENT_QUOTES,'UTF-8');?>" data-show-secret="<?php echo htmlspecialchars($SFS['SHOW_SECRET'],ENT_QUOTES,'UTF-8');?>" data-hide-secret="<?php echo htmlspecialchars($SFS['HIDE_SECRET'],ENT_QUOTES,'UTF-8');?>">
<div class="sfs-intro"><?php echo $SFS['DESCRIPTION']; ?></div>
<form id="sfs_form" method="post" action="<?php echo htmlspecialchars($moduleUrl.'/save.php',ENT_QUOTES,'UTF-8'); ?>">
<?php echo $admin->getFTAN(); ?>
<?php
$rows = array(
    array('useFP','switch',$SFS['USEFP'],$SFS['USEFP_TTIP'],$useFP),
    array('ipOctets','select',$SFS['USEIP'],$SFS['USEIP_TTIP'],$ipOctets),
    array('tokenName','text',$SFS['TOKENNAME'],$SFS['TOKENNAME_TTIP'],$tokenName),
    array('timeout','number',$SFS['TIMEOUT'],$SFS['TIMEOUT_TTIP'],$timeout),
    array('secret','password',$SFS['SECRET'],$SFS['SECRET_TTIP'],$secret),
    array('secretTime','number',$SFS['SECRETTIME'],$SFS['SECRETTIME_TTIP'],$secretTime)
);
foreach ($rows as $row): list($name,$type,$label,$hint,$value)=$row; ?>
<div class="sfs-row"><div class="sfs-copy"><label for="<?php echo $name; ?>"><?php echo $label; ?></label><div class="sfs-hint"><?php echo $hint; ?></div></div><div class="sfs-control">
<?php if($type==='switch'): ?><input class="sfs-switch-input" type="checkbox" name="useFP" id="useFP" value="1"<?php echo $value?' checked':''; ?>><label class="sfs-switch wbce-admin-switch" for="useFP"><span></span></label>
<?php elseif($type==='select'): ?><select name="ipOctets" id="ipOctets"><?php for($n=0;$n<=4;$n++): ?><option value="<?php echo $n; ?>"<?php echo ((string)$n===$value)?' selected':''; ?>><?php echo $n; ?></option><?php endfor; ?></select>
<?php else: ?><input type="<?php echo $type; ?>" name="<?php echo $name; ?>" id="<?php echo $name; ?>" value="<?php echo htmlspecialchars((string)$value,ENT_QUOTES,'UTF-8'); ?>"<?php if($name==='tokenName'): ?> maxlength="20" pattern="[a-zA-Z]{5,20}"<?php elseif($name==='secret'): ?> minlength="20" maxlength="60" pattern="[a-zA-Z0-9]{20,60}" autocomplete="new-password"<?php else: ?> min="0" max="99999"<?php endif; ?>><?php if($name==='secret'): ?><button type="button" class="sfs-reveal" aria-controls="secret" aria-pressed="false"><?php echo $SFS['SHOW_SECRET']; ?></button><?php endif; ?>
<?php endif; ?></div></div>
<?php endforeach; ?>
<div class="sfs-actions"><button type="button" class="button wbce-admin-button sfs-defaults"><?php echo $SFS['RESET_SETTINGS']; ?></button><button type="submit" class="button wbce-admin-button sfs-save"><?php echo $SFS['SUBMIT']; ?></button></div>
</form></div>
<dialog class="sfs-dialog wbce-admin-dialog" id="sfs-default-dialog"><p><?php echo $SFS['RESET_CONFIRM']; ?></p><div><button type="button" class="button wbce-admin-button sfs-cancel"><?php echo $SFS['CANCEL']; ?></button><button type="button" class="button wbce-admin-button sfs-confirm"><?php echo $SFS['RESET_SETTINGS']; ?></button></div></dialog>
<link rel="stylesheet" href="<?php echo htmlspecialchars($moduleUrl.'/backend.css?v=1.3.14',ENT_QUOTES,'UTF-8'); ?>">
<script src="<?php echo htmlspecialchars($moduleUrl.'/backend.js?v=1.3.14',ENT_QUOTES,'UTF-8'); ?>" defer></script>
