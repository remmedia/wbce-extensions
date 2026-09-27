<?php

defined('WB_PATH') or die('No direct access');

require_once __DIR__ . '/Compatibility.php';
require_once __DIR__ . '/HttpClient.php';
require_once __DIR__ . '/PackageInstaller.php';
require_once __DIR__ . '/DeltaUpdate.php';

final class WbceStoreAutoUpdate
{
    public static function run(array $configuration = array())
    {
        // The Worker closes its HTTP response before invoking this callable.
        // In web mode, limit the detached request to one installation so it
        // releases its PHP worker promptly without forbidden process calls.
        return self::runNow(PHP_SAPI === 'cli' ? 0 : 1);
    }

    private static function runNow($maximumUpdates = 0)
    {
        global $database;
        $lockName = $database->escapeString('wbce.store.autoupdate');
        $lockResult = $database->query("SELECT GET_LOCK('$lockName',0) AS acquired");
        $lockRow = $lockResult ? $lockResult->fetchRow(MYSQLI_ASSOC) : null;
        if (!$lockRow || (int)$lockRow['acquired'] !== 1) {
            return array('message' => 'Ein Store-Autoupdate läuft bereits.');
        }
        try {
        $http = new WbceRepositoryHttpClient();
        $updated = array();
        $skipped = array();
        $origins = $database->query(
            'SELECT o.*,s.name,s.catalog_url,s.access_token,s.active,s.auto_update FROM `{TP}mod_store_origins` o ' .
            'INNER JOIN `{TP}mod_store_sources` s ON s.id=o.source_id WHERE s.active=1 AND s.auto_update=1 ORDER BY (o.package_slug=\'store\'),o.package_type,o.package_slug'
        );
        $catalogs = array();
        while ($origins && ($origin = $origins->fetchRow(MYSQLI_ASSOC))) {
            $sourceId = (int)$origin['source_id'];
            try {
                if (!isset($catalogs[$sourceId])) {
                    $catalogs[$sourceId] = $http->json($origin['catalog_url'], $origin['access_token']);
                }
                $installed = self::installed($database, $origin['package_type'], $origin['package_slug']);
                if ($installed === '') continue;
                $candidate = null;
                foreach ((array)($catalogs[$sourceId]['packages'] ?? array()) as $package) {
                    if (($package['type'] ?? '') !== $origin['package_type'] || ($package['slug'] ?? '') !== $origin['package_slug']) continue;
                    if (!self::valid($package, $origin['catalog_url'], $database)) continue;
                    if (version_compare((string)$package['version'], $installed, '<=')) continue;
                    if ($candidate === null || version_compare((string)$package['version'], (string)$candidate['version'], '>')) $candidate = $package;
                }
                if ($candidate === null) continue;
                WbceStoreDeltaUpdate::install($http, new WbceRepositoryPackageInstaller($database), $origin, $candidate, $installed);
                self::remember($database, $candidate, $origin);
                $updated[] = $candidate['name'] . ' ' . $candidate['version'];
                if ((int)$maximumUpdates > 0 && count($updated) >= (int)$maximumUpdates) break;
            } catch (Throwable $error) {
                $skipped[] = $origin['package_slug'] . ': ' . $error->getMessage();
            }
        }
        $message = $updated ? 'Aktualisiert: ' . implode(', ', $updated) : 'Keine Store-Updates verfügbar.';
        if ($skipped) $message .= ' Übersprungen: ' . implode(' | ', $skipped);
        return array('message' => $message);
        } finally {
            $database->query("SELECT RELEASE_LOCK('$lockName')");
        }
    }

    private static function installed($database, $type, $slug)
    {
        $dbType = $type === 'module' ? 'module' : 'template';
        $result = $database->query("SELECT `version` FROM `{TP}addons` WHERE `directory`='".$database->escapeString($slug)."' AND `type`='".$dbType."' LIMIT 1");
        $row = $result ? $result->fetchRow(MYSQLI_ASSOC) : null;
        return $row ? (string)$row['version'] : '';
    }

    private static function valid(array $package, $catalogUrl, $database)
    {
        foreach (array('slug','type','version','sha256','download_url','size') as $field) if (!isset($package[$field])) return false;
        if (!preg_match('/^[a-f0-9]{64}$/i', (string)$package['sha256']) || (int)$package['size'] < 1 || (int)$package['size'] > 536870912) return false;
        if (parse_url($catalogUrl, PHP_URL_HOST) !== parse_url($package['download_url'], PHP_URL_HOST) || parse_url($package['download_url'], PHP_URL_SCHEME) !== 'https') return false;
        $platform = preg_match('/\d+(?:\.\d+){1,2}/', (string)($package['platform'] ?? ''), $match) ? $match[0] : '';
        return wbce_store_cms_version_meets($platform) && wbce_store_any_requirement_meets((string)($package['requires_any'] ?? ''), $database);
    }

    private static function remember($database, array $package, array $source)
    {
        $database->query("UPDATE `{TP}mod_store_origins` SET `source_name`='".$database->escapeString($source['name'])."',`catalog_url`='".$database->escapeString($source['catalog_url'])."',`package_version`='".$database->escapeString($package['version'])."',`updated_at`=".time()." WHERE `package_type`='".$database->escapeString($package['type'])."' AND `package_slug`='".$database->escapeString($package['slug'])."'");
    }
}
