<?php
defined('WB_PATH') or die('No direct access');
$installLanguage=defined('LANGUAGE')&&is_file(__DIR__.'/languages/'.strtoupper((string)LANGUAGE).'.php')?strtoupper((string)LANGUAGE):'EN';$installTexts=(array)require __DIR__.'/languages/'.$installLanguage.'.php';
if(!is_object($database)||!is_callable(array($database,'query')))throw new RuntimeException($installTexts['database_unavailable']);
$core=$database->query("SELECT directory FROM {TP}addons WHERE type='module' AND directory='two_factor' LIMIT 1");
$coreRow=null;if(is_object($core)){if(is_callable(array($core,'fetchRow')))$coreRow=$core->fetchRow(defined('MYSQLI_ASSOC')?MYSQLI_ASSOC:1);elseif(is_callable(array($core,'fetch_assoc')))$coreRow=$core->fetch_assoc();}
if(!is_array($coreRow))throw new RuntimeException($installTexts['provider_requires_core']);
require_once WB_PATH.'/modules/two_factor/Language.php';
require_once WB_PATH.'/modules/two_factor/Settings.php';

$table = TABLE_PREFIX . 'mod_two_factor_totp';
$recovery = TABLE_PREFIX . 'mod_two_factor_totp_recovery';
$attempts = TABLE_PREFIX . 'mod_two_factor_totp_attempt';
$database->query("CREATE TABLE IF NOT EXISTS `$table` (
    `user_id` INT NOT NULL,
    `secret` TEXT NOT NULL,
    `enabled` TINYINT(1) NOT NULL DEFAULT 0,
    `last_counter` BIGINT NOT NULL DEFAULT -1,
    `created_at` INT NOT NULL,
    `confirmed_at` INT NULL,
    PRIMARY KEY (`user_id`)
)");
$database->query("CREATE TABLE IF NOT EXISTS `$recovery` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `user_id` INT NOT NULL,
    `code_hash` VARCHAR(255) NOT NULL,
    `used_at` INT NULL,
    PRIMARY KEY (`id`),
    INDEX `user_id_unused` (`user_id`, `used_at`)
)");
$database->query("CREATE TABLE IF NOT EXISTS `$attempts` (
    `user_id` INT NOT NULL,
    `window_start` INT NOT NULL,
    `attempts` INT NOT NULL DEFAULT 0,
    PRIMARY KEY (`user_id`)
)");
$database->query("CREATE TABLE IF NOT EXISTS `".TABLE_PREFIX."mod_two_factor_totp_settings` (
    `name` VARCHAR(80) NOT NULL,
    `value` TEXT NOT NULL,
    PRIMARY KEY (`name`)
)");

$keyFile = __DIR__ . '/key.php';
if (!file_exists($keyFile)) {
    $key = base64_encode(random_bytes(32));
    $contents = "<?php\ndefined('WB_PATH') or die('No direct access');\nreturn '" . $key . "';\n";
    if (file_put_contents($keyFile, $contents, LOCK_EX) === false) {
        throw new RuntimeException(wbce_two_factor_t('key_create_failed',array(),'two_factor_totp'));
    }
    @chmod($keyFile, 0600);
}
if(WbceTwoFactorSettings::selected($database)==='')WbceTwoFactorSettings::set($database,'selected_provider','totp');
