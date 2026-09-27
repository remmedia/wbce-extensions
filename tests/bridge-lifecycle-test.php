<?php
declare(strict_types=1);

$moduleRoot = dirname(__DIR__);
$failures = array();
$checks = 0;
function bridge_check(bool $condition, string $message): void
{
    global $checks, $failures;
    $checks++;
    if (!$condition) $failures[] = $message;
}

define('WB_PATH', $moduleRoot);
define('WB_URL', 'https://example.test/wbce');

foreach (array('php_compat_bridge', 'wbce_168_bridge', 'wbce_hook_bridge') as $directory) {
    $root = $moduleRoot.'/'.$directory;
    foreach (array('info.php', 'precheck.php', 'preinit.php', 'initialize.php', 'install.php', 'upgrade.php', 'uninstall.php') as $file) {
        bridge_check(is_file($root.'/'.$file), $directory.': '.$file.' fehlt');
    }
    $database = new class {
        public array $queries = array();
        public function query($sql) { $this->queries[] = $sql; return true; }
    };
    include $root.'/install.php';
    include $root.'/upgrade.php';
    include $root.'/uninstall.php';
    bridge_check($directory === 'wbce_hook_bridge' || $database->queries !== array(), $directory.': Upgrade registriert keinen Funktionsumfang');
}
$wbceBridgeInfo = (string)file_get_contents($moduleRoot.'/wbce_168_bridge/info.php');
bridge_check(strpos($wbceBridgeInfo, "\$module_dependencies = 'wbce_hook_bridge>=1.2.10'") !== false, 'WBCE-1.6.8-Bridge deklariert Hook-Bridge-Abhängigkeit');

require_once $moduleRoot.'/php_compat_bridge/preinit.php';
bridge_check(array_find(array(1, 3, 6), static fn($value) => $value % 2 === 0) === 6, 'array_find');
bridge_check(array_find_key(array('a' => 1, 'b' => 3), static fn($value) => $value === 3) === 'b', 'array_find_key');
bridge_check(array_any(array(1, 2), static fn($value) => $value === 2), 'array_any');
bridge_check(array_all(array(2, 4), static fn($value) => $value % 2 === 0), 'array_all');
bridge_check(json_validate('{"valid":true}') && !json_validate('{invalid}'), 'json_validate');
bridge_check(!function_exists('mb_str_pad') || mb_str_pad('ä', 3, 'ö') === 'äöö', 'mb_str_pad');
bridge_check(grapheme_str_split("a\u{0308}b") === array("a\u{0308}", 'b'), 'grapheme_str_split');

require_once $moduleRoot.'/wbce_168_bridge/preinit.php';
$legacyResult = new class {
    public function fetchRow($mode) { return $mode === 2 ? array('value') : array('name' => 'value'); }
};
$legacyDatabase = new class($legacyResult) {
    private $result;
    public function __construct($result) { $this->result = $result; }
    public function query($sql) { return $this->result; }
    public function escapeString($value) { return str_replace("'", "''", $value); }
};
bridge_check(wbce_db_value($legacyDatabase, 'SELECT 1') === 'value', 'WBCE 1.6.8 Datenbankwert');
bridge_check(wbce_db_escape($legacyDatabase, "a'b") === "a''b", 'WBCE 1.6.8 SQL-Escaping');
bridge_check(wbce_safe_redirect_url('https://evil.test/', '/fallback') === '/fallback', 'externe Weiterleitung abgewiesen');
bridge_check(wbce_safe_redirect_url('/wbce/admin/', '/fallback') === '/wbce/admin/', 'interne Weiterleitung erlaubt');

if ($failures) {
    fwrite(STDERR, implode("\n", array_map(static fn($failure) => 'FEHLER: '.$failure, $failures))."\n");
    exit(1);
}
echo $checks." Bridge-Lebenszyklusprüfungen erfolgreich.\n";
