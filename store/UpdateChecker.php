<?php
defined('WB_PATH') or die('No direct access');
require_once __DIR__.'/Compatibility.php';
require_once __DIR__.'/HttpClient.php';
require_once __DIR__.'/InfoParser.php';

final class WbceStoreUpdateChecker
{
    private static $cache = null;
    private static $dependencyPackages = null;

    public static function report($database, $cacheOnly = false)
    {
        if (is_array(self::$cache)) return self::$cache;
        $http = new WbceRepositoryHttpClient();
        $compatible = array();
        $incompatible = array();
        $catalogs = array();
        $dependencyPackages = array();
        $sources = $database->query('SELECT * FROM `{TP}mod_store_sources` WHERE `active`=1 ORDER BY `name`');
        while ($sources && ($source = $sources->fetchRow(MYSQLI_ASSOC))) {
            try {
                $catalog = $cacheOnly
                    ? $http->cachedJson($source['catalog_url'], $source['access_token'])
                    : $http->json($source['catalog_url'], $source['access_token']);
                if (!is_array($catalog)) continue;
                if (class_exists('WbceRuntimeProfiler', false)) {
                    $catalogUrl = parse_url((string)$source['catalog_url']);
                    WbceRuntimeProfiler::mark('store.catalog.loaded', array(
                        'source' => (string)($source['name'] ?? ''),
                        'host' => (string)($catalogUrl['host'] ?? ''),
                        'packages' => count((array)($catalog['packages'] ?? array())),
                        'cache_only' => (bool)$cacheOnly,
                    ));
                }
                $catalogs[] = array('source'=>$source, 'catalog'=>$catalog);
                foreach ((array)($catalog['packages'] ?? array()) as $package) {
                    if (!self::valid($package, $source['catalog_url'])) continue;
                    if ($package['type'] === 'module') $dependencyPackages[$package['slug']][] = $package;
                }
            } catch (Throwable $ignored) { }
        }
        foreach ($catalogs as $entry) {
            $source = $entry['source'];
            foreach ((array)$entry['catalog']['packages'] as $package) {
                if (!self::valid($package, $source['catalog_url'])) continue;
                if ($package['type'] === 'module' && $package['slug'] === 'wbce_hook_bridge' && wbce_store_native_hooks_available()) continue;
                    $installed = self::installed($database, $package['type'], $package['slug']);
                    if ($installed === '' || version_compare((string)$package['version'], $installed, '<=')) continue;
                    $key = $package['type'].'|'.$package['slug'];
                    $stack = array();
                    $isCompatible = self::requirementsMet($package, $database)
                        && self::dependenciesMet($package, $database, $dependencyPackages, $stack);
                    if ($isCompatible) {
                        if (!isset($compatible[$key]) || version_compare((string)$package['version'], (string)$compatible[$key]['package']['version'], '>')) {
                            $compatible[$key] = array('package'=>$package, 'source'=>$source, 'installed'=>$installed);
                        }
                    } elseif (!isset($incompatible[$key]) || version_compare((string)$package['version'], (string)$incompatible[$key]['package']['version'], '>')) {
                        $incompatible[$key] = array('package'=>$package, 'source'=>$source, 'installed'=>$installed);
                    }
            }
        }
        self::$dependencyPackages = $dependencyPackages;
        // If at least one compatible upgrade exists, the package belongs to
        // the actionable list and must not be counted a second time as blocked.
        foreach (array_keys($compatible) as $key) unset($incompatible[$key]);
        self::$cache = array('compatible'=>array_values($compatible), 'incompatible'=>array_values($incompatible));
        return self::$cache;
    }

    public static function available($database)
    {
        $report = self::report($database);
        return $report['compatible'];
    }

    public static function incompatible($database)
    {
        $report = self::report($database);
        return $report['incompatible'];
    }

    public static function reset()
    {
        self::$cache = null;
        self::$dependencyPackages = null;
    }

    /** Remove one completed installation from the request-local update result. */
    public static function forgetPackage($type, $slug)
    {
        if (!is_array(self::$cache)) return;
        foreach (array('compatible', 'incompatible') as $group) {
            if (empty(self::$cache[$group]) || !is_array(self::$cache[$group])) continue;
            self::$cache[$group] = array_values(array_filter(self::$cache[$group], static function ($entry) use ($type, $slug) {
                $package = isset($entry['package']) && is_array($entry['package']) ? $entry['package'] : array();
                return !isset($package['type'], $package['slug'])
                    || (string)$package['type'] !== (string)$type
                    || (string)$package['slug'] !== (string)$slug;
            }));
        }
    }

    public static function installable($database, array $package)
    {
        if (!self::requirementsMet($package, $database)) return false;
        if (!is_array(self::$dependencyPackages)) self::report($database);
        $stack = array();
        return self::dependenciesMet($package, $database, (array)self::$dependencyPackages, $stack);
    }

    private static function installed($database, $type, $slug)
    {
        return self::installedVersion($database, $type, $slug);
    }

    /**
     * Return the version that is actually present on disk. Older WBCE 1.7
     * installation paths can leave addons.version stale although the update
     * files were copied successfully. Repair that row while reading it so the
     * dashboard cannot continue offering an already completed update.
     */
    public static function installedVersion($database, $type, $slug)
    {
        if ($type === 'language') {
            $meta = WB_PATH.'/languages/.store-meta/'.strtolower($slug).'.json';
            $data = is_file($meta) ? json_decode((string)file_get_contents($meta), true) : null;
            return is_array($data) && !empty($data['version']) ? (string)$data['version'] : '';
        }
        if (!in_array($type, array('module','template','admin_template'), true)) return '';
        if (!preg_match('/^[a-z][a-z0-9_-]{1,189}$/i', (string)$slug)) return '';
        $dbType = $type === 'module' ? 'module' : 'template';
        $escapedSlug = $database->escapeString($slug);
        $databaseVersion = '';
        $result = $database->query("SELECT `version` FROM `{TP}addons` WHERE `directory`='$escapedSlug' AND `type`='$dbType'");
        while ($result && ($row = $result->fetchRow(MYSQLI_ASSOC))) {
            $candidate = trim((string)$row['version']);
            if ($candidate !== '' && ($databaseVersion === '' || version_compare($candidate, $databaseVersion, '>'))) $databaseVersion = $candidate;
        }

        $base = $type === 'module' ? WB_PATH.'/modules/' : WB_PATH.'/templates/';
        $infoPath = $base.$slug.'/info.php';
        if (!is_file($infoPath) || !is_readable($infoPath)) return $databaseVersion;
        try {
            clearstatcache(true, $infoPath);
            $metadata = WbceRepositoryInfoParser::parse(file_get_contents($infoPath));
            $key = $type === 'module' ? 'module_version' : 'template_version';
            $fileVersion = isset($metadata[$key]) ? trim((string)$metadata[$key]) : '';
            if ($fileVersion === '') return $databaseVersion;
            if ($fileVersion !== $databaseVersion && $databaseVersion !== '') {
                $database->query("UPDATE `{TP}addons` SET `version`='".$database->escapeString($fileVersion)."' WHERE `directory`='$escapedSlug' AND `type`='$dbType'");
            }
            return $fileVersion;
        } catch (Throwable $ignored) {
            return $databaseVersion;
        }
    }

    private static function valid(array $package, $catalogUrl)
    {
        foreach (array('slug','type','version','sha256','download_url','size') as $field) if (!isset($package[$field])) return false;
        if (!preg_match('/^[a-z][a-z0-9_-]{1,189}$/i', (string)$package['slug']) || !preg_match('/^[a-f0-9]{64}$/i', (string)$package['sha256'])) return false;
        if ((int)$package['size'] < 1 || (int)$package['size'] > 536870912) return false;
        return parse_url($catalogUrl, PHP_URL_HOST) === parse_url($package['download_url'], PHP_URL_HOST)
            && parse_url($package['download_url'], PHP_URL_SCHEME) === 'https';
    }

    private static function requirementsMet(array $package, $database)
    {
        $platform = preg_match('/\d+(?:\.\d+){1,2}/', (string)($package['platform'] ?? ''), $match) ? $match[0] : '';
        return wbce_store_cms_version_meets($platform)
            && wbce_store_any_requirement_meets((string)($package['requires_any'] ?? ''), $database);
    }

    private static function dependenciesMet(array $package, $database, array $available, array &$stack)
    {
        $raw = trim((string)($package['dependencies'] ?? ''));
        if ($raw === '') return true;
        foreach (preg_split('/\s*,\s*/', $raw, -1, PREG_SPLIT_NO_EMPTY) as $dependency) {
            if (!preg_match('/^([a-z][a-z0-9_-]{1,189})(?:\s*>=\s*([0-9][0-9A-Za-z._-]*))?$/i', $dependency, $match)) return false;
            $slug = $match[1];
            $minimum = isset($match[2]) ? $match[2] : '0';
            if ($slug === 'wbce_hook_bridge' && wbce_store_native_hooks_available()) continue;
            $installed = self::installed($database, 'module', $slug);
            if ($installed !== '' && version_compare($installed, $minimum, '>=')) continue;
            if (isset($stack[$slug])) return false;
            $stack[$slug] = true;
            $found = false;
            foreach ((array)($available[$slug] ?? array()) as $candidate) {
                if (version_compare((string)$candidate['version'], $minimum, '<')) continue;
                if (!self::requirementsMet($candidate, $database)) continue;
                if (!self::dependenciesMet($candidate, $database, $available, $stack)) continue;
                $found = true;
                break;
            }
            unset($stack[$slug]);
            if (!$found) return false;
        }
        return true;
    }
}
