<?php
// Run each scenario in a fresh PHP process.
$source = dirname(__DIR__) . '/force_password_change';
$temporary = sys_get_temp_dir() . '/fpc-install-' . bin2hex(random_bytes(6));
mkdir($temporary);
mkdir($temporary . '/languages');
foreach (['info.php', 'Language.php', 'precheck.php', 'languages/DE.php', 'languages/EN.php'] as $file) {
    copy($source . '/' . $file, $temporary . '/' . $file);
}
register_shutdown_function(function () use ($temporary) {
    foreach (glob($temporary . '/languages/*') as $file) unlink($file);
    rmdir($temporary . '/languages');
    foreach (glob($temporary . '/*') as $file) unlink($file);
    rmdir($temporary);
});
define('WB_PATH', dirname($source));
define('WBCE_VERSION', '1.7.0');
define('TABLE_PREFIX', 'wb_');
define('LANGUAGE', 'DE');
function check($condition, $message) {
    if (!$condition) throw new RuntimeException($message);
}
$database = new class {
    public $queries = [];
    public function query($sql) { $this->queries[] = $sql; return true; }
    public function hasError() { return false; }
};
$scenario = $argv[1] ?? 'install';
if ($scenario === 'loaded-runtime') require $source . '/Service.php';
if ($scenario === 'legacy-language') {
    function fpc_t($key) { return $key; }
}
require $temporary . '/info.php';
check($module_name === 'Passwortänderung erzwingen', 'Incorrect module name');
check(strlen($module_description) > 40, 'Missing description');
check($module_version === '2.0.18', 'Incorrect version');
require $temporary . '/precheck.php';
check(isset($PRECHECK['CUSTOM_CHECKS']['Hook-Schnittstellen']), 'Precheck label is not readable');
if ($scenario === 'legacy-language') {
    echo $scenario . ": PASS\n";
    exit;
}
require $source . '/info.php';
require $source . '/Language.php';
check(fpc_t('module_name') === $module_name, 'Translation mismatch');
require $source . '/install.php';
require $source . '/upgrade.php';
check(count($database->queries) === 2, 'Unexpected metadata/version overwrite');
check(strpos($database->queries[0], 'CREATE TABLE IF NOT EXISTS') === 0, 'Schema not installed');
echo $scenario . ": PASS\n";
