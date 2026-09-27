<?php
if (count(get_included_files()) === 1) { header('Location: ../index.php', true, 301); exit; }
require_once WB_PATH.'/modules/jsadmin/jsadmin.php';
$selectedJsadminLanguage = isset($MOD_JSADMIN) && is_array($MOD_JSADMIN) ? $MOD_JSADMIN : array();
require WB_PATH.'/modules/jsadmin/languages/EN.php';
$MOD_JSADMIN = array_merge($MOD_JSADMIN, $selectedJsadminLanguage);
$persist_order = get_setting('mod_jsadmin_persist_order', true) ? ' checked' : '';
$ajax_order_pages = get_setting('mod_jsadmin_ajax_order_pages', true) ? ' checked' : '';
$ajax_order_sections = get_setting('mod_jsadmin_ajax_order_sections', true) ? ' checked' : '';
$saveUrl = WB_URL.'/modules/jsadmin/save.php';
$jsadminFtan = explode('=', $admin->getFTAN(false), 2);
$jsadminFtanName = $jsadminFtan[0] ?? 'formtoken';
$jsadminFtanValue = $jsadminFtan[1] ?? '';
?>
<div class="jsadmin-settings" data-save-failed="<?php echo htmlspecialchars($MOD_JSADMIN['TXT_SAVE_FAILED'],ENT_QUOTES,'UTF-8');?>" data-invalid-response="<?php echo htmlspecialchars($MOD_JSADMIN['TXT_INVALID_RESPONSE'],ENT_QUOTES,'UTF-8');?>">
    <div class="jsadmin-intro"><?php echo htmlspecialchars($MOD_JSADMIN['TXT_HEADING_B'],ENT_QUOTES,'UTF-8'); ?></div>
    <form id="jsadmin-form" action="<?php echo htmlspecialchars($saveUrl, ENT_QUOTES, 'UTF-8'); ?>" method="post">
        <input type="hidden" name="<?php echo htmlspecialchars($jsadminFtanName, ENT_QUOTES, 'UTF-8'); ?>" value="<?php echo htmlspecialchars($jsadminFtanValue, ENT_QUOTES, 'UTF-8'); ?>" data-jsadmin-ftan="<?php echo htmlspecialchars($jsadminFtanValue, ENT_QUOTES, 'UTF-8'); ?>">
        <?php foreach (array(
            'persist_order' => $MOD_JSADMIN['TXT_PERSIST_ORDER_B'],
            'ajax_order_pages' => $MOD_JSADMIN['TXT_AJAX_ORDER_PAGES_B'],
            'ajax_order_sections' => $MOD_JSADMIN['TXT_AJAX_ORDER_SECTIONS_B']
        ) as $name => $label): $checked = ${$name}; ?>
        <div class="jsadmin-row">
            <div><strong><?php echo htmlspecialchars($label,ENT_QUOTES,'UTF-8'); ?></strong></div>
            <label class="wbce-admin-switch" for="<?php echo $name; ?>">
                <input type="checkbox" name="<?php echo $name; ?>" id="<?php echo $name; ?>" value="1"<?php echo $checked; ?>>
                <span class="wbce-admin-switch__control" aria-hidden="true"></span>
                <span class="jsadmin-state" data-on="<?php echo htmlspecialchars($MOD_JSADMIN['TXT_ENABLED'], ENT_QUOTES, 'UTF-8'); ?>" data-off="<?php echo htmlspecialchars($MOD_JSADMIN['TXT_DISABLED'], ENT_QUOTES, 'UTF-8'); ?>"></span>
            </label>
        </div>
        <?php endforeach; ?>
    </form>
</div>
