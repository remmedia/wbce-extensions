<?php if(!defined('WB_PATH')){exit;} foreach(array('email_filter','mailto_filter','js_mailto') as $key){$data[$key]=((string)$data[$key]==='1')?'1':'0';} ?>
<div class="opf-email-settings" data-save-failed="<?php echo htmlspecialchars($OPF['SAVE_FAILED'],ENT_QUOTES,'UTF-8'); ?>">
<div class="opf-email-intro"><?php echo htmlspecialchars($OPF['HOWTO'],ENT_QUOTES,'UTF-8'); ?></div>
<form id="opf-email-form" action="<?php echo htmlspecialchars($moduleUrl.'/save.php',ENT_QUOTES,'UTF-8'); ?>" method="post"><?php echo $admin->getFTAN(); ?>
<?php foreach(array('email_filter'=>$OPF['EMAIL_FILTER'],'mailto_filter'=>$OPF['MAILTO_FILTER'],'js_mailto'=>$OPF['JS_MAILTO']) as $name=>$label): ?>
<div class="opf-email-row"><label for="<?php echo $name; ?>"><?php echo htmlspecialchars($label,ENT_QUOTES,'UTF-8'); ?></label><label class="wbce-admin-switch opf-email-switch"><input type="checkbox" role="switch" aria-checked="<?php echo $data[$name]==='1'?'true':'false'; ?>" id="<?php echo $name; ?>" name="<?php echo $name; ?>" value="1"<?php echo $data[$name]==='1'?' checked':''; ?>><span class="wbce-admin-switch__control" aria-hidden="true"></span><span class="opf-email-state" data-on="<?php echo htmlspecialchars($OPF['ENABLED'],ENT_QUOTES,'UTF-8'); ?>" data-off="<?php echo htmlspecialchars($OPF['DISABLED'],ENT_QUOTES,'UTF-8'); ?>"></span></label></div>
<?php endforeach; ?>
<div class="opf-email-section"><?php echo htmlspecialchars($OPF['REPLACEMENT_CONF'],ENT_QUOTES,'UTF-8'); ?></div>
<div class="opf-email-row"><label for="at_replacement"><?php echo htmlspecialchars($OPF['AT_REPLACEMENT'],ENT_QUOTES,'UTF-8'); ?></label><input type="text" id="at_replacement" name="at_replacement" value="<?php echo htmlspecialchars((string)$data['at_replacement'],ENT_QUOTES,'UTF-8'); ?>"></div>
<div class="opf-email-row"><label for="dot_replacement"><?php echo htmlspecialchars($OPF['DOT_REPLACEMENT'],ENT_QUOTES,'UTF-8'); ?></label><input type="text" id="dot_replacement" name="dot_replacement" value="<?php echo htmlspecialchars((string)$data['dot_replacement'],ENT_QUOTES,'UTF-8'); ?>"></div>
</form></div>
