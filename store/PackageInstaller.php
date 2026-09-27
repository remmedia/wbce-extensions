<?php
require_once __DIR__ . '/Language.php';

require_once __DIR__ . '/PackageInspector.php';
require_once __DIR__ . '/Compatibility.php';
require_once WB_PATH . '/framework/functions.php';

final class WbceRepositoryPackageInstaller
{
    private $database;

    public function __construct($database)
    {
        $this->database = $database;
    }

    public function install($zipPath, array $expected, $allowDowngrade = false)
    {
        $actualHash = hash_file('sha256', $zipPath);
        if (empty($expected['sha256']) || !hash_equals(strtolower($expected['sha256']), strtolower($actualHash))) {
            throw new RuntimeException(wbce_store_text('checksum_failed'));
        }
        $metadata = WbceRepositoryPackageInspector::inspect($zipPath);
        foreach (array('type', 'slug', 'version') as $field) {
            if (!isset($expected[$field]) || (string)$expected[$field] !== (string)$metadata[$field]) {
                throw new RuntimeException(wbce_store_text('metadata_mismatch'));
            }
        }
        $requiredPlatform = isset($metadata['platform']) && preg_match('/\d+(?:\.\d+){1,2}/', (string)$metadata['platform'], $platformMatch)
            ? $platformMatch[0] : '';
        if ($requiredPlatform !== '' && !wbce_store_cms_version_meets($requiredPlatform)) {
            throw new RuntimeException(wbce_store_text('requires_wbce', array('version' => $requiredPlatform)));
        }
        if (!wbce_store_any_requirement_meets(isset($metadata['requires_any']) ? $metadata['requires_any'] : '', $this->database)) {
            throw new RuntimeException(wbce_store_text('requires_dependency', array('requirements' => str_replace('|', wbce_store_text('or_separator'), $metadata['requires_any']))));
        }

        $stage = WB_PATH . '/temp/repository-' . bin2hex(random_bytes(8));
        if (!mkdir($stage, 0750, true)) throw new RuntimeException(wbce_store_text('staging_failed'));
        $archive = new PclZip($zipPath);
        $list = $archive->extract(PCLZIP_OPT_PATH, $stage);
        if (!$list) {
            rm_full_dir($stage);
            throw new RuntimeException(wbce_store_text('unpack_failed'));
        }
        $source = $metadata['root'] === '' ? $stage : $stage . '/' . $metadata['root'];
        if ($metadata['type'] === 'language') {
            try { $this->installLanguage($source, $metadata, $allowDowngrade); }
            finally { rm_full_dir($stage); }
            $this->recordInstalledPackage($metadata, $actualHash);
            return $metadata;
        }
        $base = $metadata['type'] === 'module' ? WB_PATH . '/modules' : WB_PATH . '/templates';
        $destination = $base . '/' . $metadata['slug'];
        $isUpgrade = is_dir($destination);
        $installed = $this->installedVersion($metadata['slug'], $destination, $metadata['type']);
        $isDowngrade = $installed !== '' && version_compare($metadata['version'], $installed, '<');
        if ($installed !== '' && version_compare($metadata['version'], $installed, '==')) {
            rm_full_dir($stage);
            throw new RuntimeException(wbce_store_text('version_installed', array('version' => $installed)));
        }
        if ($isDowngrade && !$allowDowngrade) {
            rm_full_dir($stage);
            throw new RuntimeException(wbce_store_text('downgrade_forbidden'));
        }

        // WBCE 1.7 is the primary installation path. It supplies the native
        // AddonService including prechecks, CodeVet, DB registration and the
        // correctly scoped lifecycle scripts. The local installer below is
        // retained only for WBCE 1.6.8 and for the explicitly requested
        // downgrade operation that the native service intentionally rejects.
        if (!$isDowngrade && wbce_store_has_native_addon_service()) {
            try {
                $this->installWithNativeService($zipPath, $metadata);
            } finally {
                wbce_store_remove_path($stage);
            }
            $this->ensureAddonRegistered($metadata, $destination);
            // Do not rely on every WBCE/Add-on combination updating the
            // addons row itself. The dashboard and update counters use this
            // value as their authoritative installed version.
            $this->synchronizeAddonVersion($metadata);
            $this->recordInstalledPackage($metadata, $actualHash);
            return $metadata;
        }

        if ($installed !== '') {
            $isUpgrade = true;
        } elseif (!mkdir($destination, 0755, true)) {
            rm_full_dir($stage);
            throw new RuntimeException(wbce_store_text('destination_failed'));
        }

        $backup = null;
        if ($isDowngrade) {
            $backup = WB_PATH . '/temp/repository-backup-' . bin2hex(random_bytes(8));
            if (!rename($destination, $backup) || !mkdir($destination, 0755, true)) {
                if (is_dir($backup) && !is_dir($destination)) @rename($backup, $destination);
                rm_full_dir($stage);
                throw new RuntimeException(wbce_store_text('downgrade_backup_failed'));
            }
        }

        try {
            $this->copyTree($source, $destination);
            clearstatcache(true, $destination . '/info.php');
            if (function_exists('opcache_invalidate')) {
                @opcache_invalidate($destination . '/info.php', true);
                @opcache_invalidate($destination . '/upgrade.php', true);
                @opcache_invalidate($destination . '/install.php', true);
            }
            if ($metadata['type'] === 'module') {
                if ($isUpgrade) {
                    upgrade_module($destination, false);
                    $this->runAddonScript($destination . '/upgrade.php');
                } else {
                    load_module($destination, false);
                    $this->runAddonScript($destination . '/install.php');
                }
            } else {
                load_template($destination);
                $script = $isUpgrade ? '/upgrade.php' : '/install.php';
                $this->runAddonScript($destination . $script);
            }
            $this->synchronizeAddonVersion($metadata);
        } catch (Throwable $error) {
            if ($backup !== null) {
                rm_full_dir($destination);
                @rename($backup, $destination);
            }
            throw $error;
        } finally {
            rm_full_dir($stage);
        }
        if ($backup !== null) rm_full_dir($backup);
        // Native WBCE 1.7 integration; WBCE 1.6.8 uses the optional Hook-Bridge.
        $this->recordInstalledPackage($metadata, $actualHash);
        if ($isDowngrade) wbce_store_emit('repository.package.downgraded', $metadata, $installed, $actualHash);
        return $metadata;
    }

    public function installDelta($deltaPath, array $expected, $installedVersion)
    {
        if (!in_array($expected['type'], array('module','template','admin_template'), true)) throw new RuntimeException('Für diesen Pakettyp ist kein Delta verfügbar.');
        if ((string)$installedVersion === '' || version_compare((string)$expected['version'], (string)$installedVersion, '<=')) throw new RuntimeException('Delta-Ausgangsversion ist ungültig.');
        require_once __DIR__.'/DeltaInstaller.php';
        $base=$expected['type']==='module'?WB_PATH.'/modules':WB_PATH.'/templates';$destination=$base.'/'.$expected['slug'];
        if(!is_dir($destination))throw new RuntimeException('Delta-Zielpaket fehlt.');
        $transaction=WbceStoreDeltaInstaller::apply($deltaPath,$destination);
        $manifest=$transaction['manifest'];
        try {
            if((string)($manifest['target']['version']??'')!==(string)$expected['version']||!hash_equals((string)$expected['sha256'],(string)($manifest['target']['sha256']??'')))throw new RuntimeException('Delta-Zielversion stimmt nicht überein.');
            if($expected['type']==='module'){upgrade_module($destination,false);$this->runAddonScript($destination.'/upgrade.php');}else{load_template($destination);$this->runAddonScript($destination.'/upgrade.php');}
            $this->synchronizeAddonVersion($expected);$this->recordInstalledPackage($expected,(string)($manifest['target']['sha256']??''));
        } catch (Throwable $error) {
            WbceStoreDeltaInstaller::restore($transaction);
            throw $error;
        }
        WbceStoreDeltaInstaller::commit($transaction);return $expected;
    }

    private function installWithNativeService($zipPath, array $metadata)
    {
        $service = new AddonService();
        $stageSignals = $service->stageFromZip($zipPath, false);
        $stagedDirectory = null;
        foreach ($stageSignals as $signal) {
            if (isset($signal['signal']) && $signal['signal'] === 'ADDON_STAGED') {
                $stagedDirectory = (string) $signal['label'];
            }
        }
        if ($service->hasError() || $stagedDirectory === null) {
            throw new RuntimeException($this->nativeServiceError($service, $stageSignals, wbce_store_text('stage_native_failed')));
        }

        $type = $metadata['type'] === 'module' ? 'module' : 'template';
        $installSignals = $service->installFromStaged($stagedDirectory, $type);
        if ($service->hasError()) {
            throw new RuntimeException($this->nativeServiceError($service, $installSignals, wbce_store_text('install_native_failed')));
        }
    }

    private function nativeServiceError($service, array $signals, $fallback)
    {
        $messages = array();
        foreach ($signals as $signal) {
            if (!isset($signal['signal']) || $service->isOkSignal($signal['signal'])) continue;
            $code = (string) $signal['signal'];
            $label = isset($signal['label']) ? (string) $signal['label'] : '';
            if ($code === 'ADDON_NOT_WRITABLE') {
                $messages[] = wbce_store_text('addon_not_writable', array('path' => $label));
                continue;
            }
            if ($code === 'ADDON_PATH_NOT_FOUND') {
                $messages[] = wbce_store_text('addon_path_not_found', array('path' => $label));
                continue;
            }
            $messages[] = $label !== '' ? $label : $code;
        }
        return $messages ? implode('; ', $messages) : $fallback;
    }

    private function runAddonScript($path)
    {
        if (!is_file($path)) return;

        // WBCE normally executes these files from an admin script where these
        // variables already exist. A method has its own scope, so explicitly
        // provide the standard WBCE context expected by existing add-ons.
        $database = $this->database;
        $admin = isset($GLOBALS['admin']) ? $GLOBALS['admin'] : null;
        $MESSAGE = isset($GLOBALS['MESSAGE']) ? $GLOBALS['MESSAGE'] : array();
        $TEXT = isset($GLOBALS['TEXT']) ? $GLOBALS['TEXT'] : array();
        $HEADING = isset($GLOBALS['HEADING']) ? $GLOBALS['HEADING'] : array();
        $modulePath = dirname($path) . '/';
        require $path;
    }

    private function installedVersion($slug, $directory, $type)
    {
        $result = $this->database->query(
            "SELECT `version` FROM `{TP}addons` WHERE `directory` = '" .
            $this->database->escapeString($slug) . "' AND `type` = '" .
            $this->database->escapeString($type === 'module' ? 'module' : 'template') . "' LIMIT 1"
        );
        if ($result && ($row = $result->fetchRow(MYSQLI_ASSOC)) && !empty($row['version'])) {
            return (string)$row['version'];
        }
        if (!is_file($directory . '/info.php')) return '';
        $metadata = WbceRepositoryInfoParser::parse(file_get_contents($directory . '/info.php'));
        $key = $type === 'module' ? 'module_version' : 'template_version';
        return isset($metadata[$key]) ? (string)$metadata[$key] : '';
    }

    private function synchronizeAddonVersion(array $metadata)
    {
        $databaseType = $metadata['type'] === 'module' ? 'module' : 'template';
        $functionSql = $databaseType === 'module' && !empty($metadata['function'])
            ? ", `function`='" . $this->database->escapeString($metadata['function']) . "'"
            : '';
        $result = $this->database->query(
            "UPDATE `{TP}addons` SET `version`='" . $this->database->escapeString($metadata['version']) .
            "'" . $functionSql . " WHERE `directory`='" . $this->database->escapeString($metadata['slug']) .
            "' AND `type`='" . $databaseType . "'"
        );
        if (!$result || $this->database->is_error()) {
            throw new RuntimeException(wbce_store_text('version_sync_failed'));
        }
    }

    /**
     * A Store package is checksum-verified before its files reach the CMS.
     * Record its resulting files as the new Security Center baseline so the
     * integrity worker does not report an authorized update as an intrusion.
     */
    private function recordInstalledPackage(array $metadata, $actualHash)
    {
        $this->acknowledgeIntegrity($metadata);
        wbce_store_emit('repository.package.installed', $metadata, $actualHash);
    }

    private function acknowledgeIntegrity(array $metadata)
    {
        $type = isset($metadata['type']) ? (string)$metadata['type'] : '';
        $slug = isset($metadata['slug']) ? (string)$metadata['slug'] : '';
        if (!preg_match('/^[a-z][a-z0-9_-]{1,189}$/i', $slug)) return;

        if ($type === 'language') {
            $code = strtoupper((string)($metadata['language_code'] ?? ''));
            if (!preg_match('/^[A-Z]{2,5}$/', $code)) return;
            $relative = 'languages/' . $code . '.php';
            $paths = array($relative => WB_PATH . '/' . $relative);
        } elseif ($type === 'module' || $type === 'template') {
            $relativeRoot = ($type === 'module' ? 'modules/' : 'templates/') . $slug;
            $root = WB_PATH . '/' . $relativeRoot;
            if (!is_dir($root)) return;
            $paths = array();
            $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::LEAVES_ONLY, RecursiveIteratorIterator::CATCH_GET_CHILD);
            foreach ($iterator as $entry) {
                if (!$entry->isFile() || $entry->isLink() || $entry->getSize() > 16 * 1024 * 1024) continue;
                $relative = $relativeRoot . '/' . str_replace('\\', '/', substr($entry->getPathname(), strlen($root) + 1));
                if (!preg_match('/(?:\\.php|\\.phtml|\\.inc|\\.module|\\.js|\\.htaccess|config\\.php)$/i', $relative)) continue;
                $paths[$relative] = $entry->getPathname();
            }
        } else {
            return;
        }

        // Security Center is optional.  A failed lookup must never prevent a
        // verified package from being installed on systems without it.
        $table = $this->database->query("SHOW TABLES LIKE '{TP}mod_security_center_file_integrity'");
        if (!$table || !$table->fetchRow(MYSQLI_ASSOC)) return;

        $now = gmdate('Y-m-d H:i:s');
        foreach ($paths as $relative => $path) {
            $hash = @hash_file('sha256', $path);
            $size = @filesize($path);
            $mtime = @filemtime($path);
            if (!is_string($hash) || $size === false || $mtime === false) continue;
            $critical = preg_match('~^(?:admin|framework|include|install)/|(?:^|/)config\\.php$|(?:^|/)\\.htaccess$~i', $relative) ? 1 : 0;
            $this->database->query("REPLACE INTO `{TP}mod_security_center_file_integrity` (`path_hash`,`file_path`,`sha256`,`size_bytes`,`modified_at`,`critical`,`checked_at`) VALUES ('" . hash('sha256', $relative) . "','" . $this->database->escapeString($relative) . "','" . $hash . "'," . (int)$size . ',' . (int)$mtime . ',' . $critical . ",'$now')");
        }

        $prefix = $type === 'language' ? array_key_first($paths) : (($type === 'module' ? 'modules/' : 'templates/') . $slug . '/');
        $escaped = $this->database->escapeString($prefix);
        $condition = $type === 'language' ? "`file_path`='$escaped'" : "`file_path` LIKE '{$escaped}%'";
        $this->database->query("DELETE FROM `{TP}mod_security_center_findings` WHERE `scan_id`='integrity' AND `rule_id` IN ('integrity_changed','integrity_created') AND $condition");
    }

    private function ensureAddonRegistered(array $metadata, $destination)
    {
        $databaseType = $metadata['type'] === 'module' ? 'module' : 'template';
        $slug = $this->database->escapeString($metadata['slug']);
        $registered = $this->database->query(
            "SELECT `directory` FROM `{TP}addons` WHERE `directory`='$slug' AND `type`='$databaseType' LIMIT 1"
        );
        if ($registered && $registered->fetchRow(MYSQLI_ASSOC)) return;

        if ($metadata['type'] === 'module') load_module($destination, false);
        else load_template($destination);

        $verified = $this->database->query(
            "SELECT `directory` FROM `{TP}addons` WHERE `directory`='$slug' AND `type`='$databaseType' LIMIT 1"
        );
        if (!$verified || !$verified->fetchRow(MYSQLI_ASSOC)) {
            throw new RuntimeException(wbce_store_text('version_sync_failed'));
        }
    }

    private function copyTree($source, $destination)
    {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($source, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );
        foreach ($iterator as $item) {
            if ($item->isLink()) throw new RuntimeException(wbce_store_text('symlink_forbidden'));
            $relative = substr($item->getPathname(), strlen($source) + 1);
            WbceRepositoryPackageInspector::validatePath(str_replace('\\', '/', $relative));
            $target = $destination . '/' . $relative;
            if ($item->isDir()) {
                if (!is_dir($target) && !mkdir($target, 0755, true)) throw new RuntimeException(wbce_store_text('directory_failed'));
            } elseif (!copy($item->getPathname(), $target)) {
                throw new RuntimeException(wbce_store_text('copy_failed'));
            }
        }
    }

    private function installLanguage($source, array $metadata, $allowDowngrade)
    {
        $code = strtoupper((string)$metadata['language_code']);
        if (!preg_match('/^[A-Z]{2,5}$/', $code)) throw new RuntimeException(wbce_store_text('package_directory_invalid'));
        $file = $source.'/languages/'.$code.'.php';
        if (!is_file($file)) throw new RuntimeException(wbce_store_text('info_missing'));
        $metaDir = WB_PATH.'/languages/.store-meta';
        $metaFile = $metaDir.'/'.strtolower($code).'.json';
        $installed = is_file($metaFile) ? (string)(json_decode((string)file_get_contents($metaFile), true)['version'] ?? '') : '';
        if ($installed !== '' && version_compare($metadata['version'], $installed, '==')) throw new RuntimeException(wbce_store_text('version_installed', array('version'=>$installed)));
        if ($installed !== '' && version_compare($metadata['version'], $installed, '<') && !$allowDowngrade) throw new RuntimeException(wbce_store_text('downgrade_forbidden'));
        if (!is_dir($metaDir) && !mkdir($metaDir,0755,true)) throw new RuntimeException(wbce_store_text('destination_failed'));
        if (!copy($file, WB_PATH.'/languages/'.$code.'.php')) throw new RuntimeException(wbce_store_text('copy_failed'));
        if (file_put_contents($metaFile, json_encode(array('version'=>$metadata['version'],'code'=>$code), JSON_UNESCAPED_SLASHES)."\n", LOCK_EX) === false) throw new RuntimeException(wbce_store_text('copy_failed'));
    }
}
