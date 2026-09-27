<?php
defined('WB_PATH') or die('No direct access');
function captcha_captchafox_register_addon($database)
{
    if (!is_object($database) || !is_callable([$database, 'query'])) return false;
    $active = is_callable([$database, 'field_exists']) && $database->field_exists('{TP}addons', 'active') ? ", `active`=1" : '';
    $result = $database->query("UPDATE `{TP}addons` SET `function`='initialize'".$active." WHERE `type`='module' AND `directory`='captcha_captchafox'");
    return $result !== false && $result !== null;
}
