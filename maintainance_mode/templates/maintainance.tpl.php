<div class="maintenance-settings" data-enabled="<?php echo htmlspecialchars($MOD_MAINTAINANCE['ENABLED'],ENT_QUOTES,'UTF-8');?>" data-disabled="<?php echo htmlspecialchars($MOD_MAINTAINANCE['DISABLED'],ENT_QUOTES,'UTF-8');?>" data-save-failed="<?php echo htmlspecialchars($MOD_MAINTAINANCE['SAVE_FAILED'],ENT_QUOTES,'UTF-8');?>" data-invalid-response="<?php echo htmlspecialchars($MOD_MAINTAINANCE['INVALID_RESPONSE'],ENT_QUOTES,'UTF-8');?>">
    <div class="maintenance-description"><?php echo $MOD_MAINTAINANCE['DESCRIPTION']; ?></div>
    <form id="maintenance-form" action="<?php echo htmlspecialchars($moduleUrl.'/save.php',ENT_QUOTES,'UTF-8'); ?>" method="post">
        <?php echo $admin->getFTAN(); ?>
        <div class="maintenance-row">
            <div><strong><?php echo htmlspecialchars($MOD_MAINTAINANCE['CHECKBOX'],ENT_QUOTES,'UTF-8'); ?></strong><div class="maintenance-state" aria-live="polite"></div></div>
            <label class="wbce-admin-switch maintenance-switch" for="maintMode">
                <input type="checkbox" name="maintMode" id="maintMode" value="1" role="switch" aria-checked="<?php echo $maintMode?'true':'false'; ?>"<?php echo $maintMode?' checked':''; ?>>
                <span class="wbce-admin-switch__control" aria-hidden="true"></span>
                <span class="maintenance-switch-text"></span>
            </label>
        </div>
    </form>
</div>
