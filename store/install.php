<?php
defined('WB_PATH') or die('No direct access');
require_once __DIR__ . '/Compatibility.php';

// During a Store self-update the old Compatibility.php may already be marked
// as included by PHP when the new files replace it. In that mixed request the
// newly introduced helper is therefore not loaded again. Keep this tiny
// lifecycle fallback local to the installer so the update can finish once;
// subsequent requests use Compatibility.php normally.
if (!function_exists('wbce_store_db_value')) {
    function wbce_store_db_value($database, $sql)
    {
        if (is_object($database) && method_exists($database, 'fetchValue')) return $database->fetchValue($sql);
        if (is_object($database) && method_exists($database, 'get_one')) return $database->get_one($sql);
        $result = is_object($database) ? $database->query($sql) : false;
        if (!$result) return null;
        $row = method_exists($result, 'fetchRow') ? $result->fetchRow(MYSQLI_NUM) : null;
        return is_array($row) && array_key_exists(0, $row) ? $row[0] : null;
    }
}

$storeActiveSql = method_exists($database, 'field_exists') && $database->field_exists('{TP}addons', 'active') ? ", `active`=1" : '';
$database->query("UPDATE `{TP}addons` SET `function`='tool,initialize'".$storeActiveSql." WHERE `type`='module' AND `directory`='store'");

// Repair stale version rows left by older Store releases after a successful
// native WBCE update. Installed info.php files are the authoritative source.
require_once __DIR__ . '/PackageInspector.php';
$installedAddons = $database->query("SELECT `directory`,`type`,`version` FROM `{TP}addons` WHERE `type` IN ('module','template')");
while ($installedAddons && ($installedAddon = $installedAddons->fetchRow(MYSQLI_ASSOC))) {
    $addonDirectory = (string)$installedAddon['directory'];
    if (!preg_match('/^[a-zA-Z0-9_-]+$/', $addonDirectory)) continue;
    $addonBase = $installedAddon['type'] === 'module' ? WB_PATH . '/modules/' : WB_PATH . '/templates/';
    $addonInfoPath = $addonBase . $addonDirectory . '/info.php';
    if (!is_file($addonInfoPath) || !is_readable($addonInfoPath)) continue;
    try {
        $addonMetadata = WbceRepositoryInfoParser::parse(file_get_contents($addonInfoPath));
        $versionKey = $installedAddon['type'] === 'module' ? 'module_version' : 'template_version';
        $fileVersion = isset($addonMetadata[$versionKey]) ? trim((string)$addonMetadata[$versionKey]) : '';
        if ($fileVersion === '' || $fileVersion === (string)$installedAddon['version']) continue;
        $database->query("UPDATE `{TP}addons` SET `version`='".$database->escapeString($fileVersion)."' WHERE `directory`='".$database->escapeString($addonDirectory)."' AND `type`='".$database->escapeString($installedAddon['type'])."'");
    } catch (Throwable $ignored) {
        // A malformed third-party info.php must not block Store installation.
    }
}

$repositories = TABLE_PREFIX . 'mod_store_sources';
$database->query("CREATE TABLE IF NOT EXISTS `$repositories` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(255) NOT NULL,
    `catalog_url` VARCHAR(1024) NOT NULL,
    `access_token` VARCHAR(255) NOT NULL DEFAULT '',
    `active` TINYINT(1) NOT NULL DEFAULT 1,
    `auto_update` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` INT NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `catalog_url` (`catalog_url`(190))
)");
$tokenColumn = $database->query("SHOW COLUMNS FROM `$repositories` LIKE 'access_token'");
if (!$tokenColumn || $tokenColumn->numRows() === 0) $database->query("ALTER TABLE `$repositories` ADD `access_token` VARCHAR(255) NOT NULL DEFAULT '' AFTER `catalog_url`");
$autoUpdateColumn = $database->query("SHOW COLUMNS FROM `$repositories` LIKE 'auto_update'");
if (!$autoUpdateColumn || $autoUpdateColumn->numRows() === 0) $database->query("ALTER TABLE `$repositories` ADD `auto_update` TINYINT(1) NOT NULL DEFAULT 1 AFTER `active`");
$database->query("UPDATE `$repositories` SET `catalog_url`=CONCAT(LEFT(`catalog_url`,CHAR_LENGTH(`catalog_url`)-7),'api/') WHERE `catalog_url` LIKE '%/api.php'");

$origins = TABLE_PREFIX . 'mod_store_origins';
$database->query("CREATE TABLE IF NOT EXISTS `$origins` (
    `package_type` VARCHAR(32) NOT NULL,
    `package_slug` VARCHAR(190) NOT NULL,
    `source_id` INT NOT NULL,
    `source_name` VARCHAR(255) NOT NULL,
    `catalog_url` VARCHAR(1024) NOT NULL,
    `package_version` VARCHAR(64) NOT NULL,
    `updated_at` INT NOT NULL,
    PRIMARY KEY (`package_type`, `package_slug`)
)");

// The Worker is optional. If it is already installed, create/repair the
// module-managed Store task immediately; initialize.php supplies its callable.
$workerTable = TABLE_PREFIX . 'mod_worker_tasks';
$workerTableExists = wbce_store_db_value($database, "SHOW TABLES LIKE '".$database->escapeString($workerTable)."'");
if ($workerTableExists) {
    $workerNow = gmdate('Y-m-d H:i:s');
    $database->query("UPDATE `$workerTable` SET `managed`=1,`cron_expression`='*/5 * * * *',`modified_at`='$workerNow' WHERE `worker_id`='store.autoupdate'");
    $database->query("INSERT INTO `$workerTable` (`name`,`worker_id`,`cron_expression`,`configuration`,`managed`,`active`,`next_run`,`created_at`,`modified_at`) SELECT 'Store-Autoupdate','store.autoupdate','*/5 * * * *','{}',1,1,'$workerNow','$workerNow','$workerNow' FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `$workerTable` WHERE `worker_id`='store.autoupdate')");
}
