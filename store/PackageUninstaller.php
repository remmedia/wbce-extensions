<?php
require_once __DIR__ . '/Language.php';
require_once __DIR__ . '/Compatibility.php';
require_once __DIR__ . '/InfoParser.php';
require_once WB_PATH . '/framework/functions.php';

final class WbceStorePackageUninstaller
{
    private $database;
    public function __construct($database) { $this->database = $database; }

    public function uninstall($type, $slug)
    {
        if (!preg_match('/^[a-z][a-z0-9_-]{1,189}$/i', $slug)) throw new RuntimeException(wbce_store_text('package_invalid'));
        if ($type === 'admin_template' && $this->isActiveAdminTheme($slug)) {
            throw new RuntimeException(wbce_store_text('active_admin_theme'));
        }
        if ($type === 'template' && $this->isActiveFrontendTemplate($slug)) {
            throw new RuntimeException(wbce_store_text('active_frontend_template'));
        }
        if ($type === 'module') {
            $dependents = $this->moduleDependents($slug);
            if ($dependents) throw new RuntimeException(wbce_store_text('bridge_required', array('modules' => implode(', ', $dependents))));
        }
        if (function_exists('wbce_hook_allows') && !wbce_hook_allows('addon.beforeUninstall', array('type' => $type, 'directory' => $slug))) {
            throw new RuntimeException(wbce_store_text('package_required'));
        }
        if ($type === 'module') wbce_store_assert_captcha_provider_removable($this->database, $slug);
        if (wbce_store_has_native_addon_service()) return $this->nativeUninstall($type, $slug);
        if ($type === 'language') return $this->language($slug);
        if ($type === 'module') return $this->module($slug);
        if ($type === 'template' || $type === 'admin_template') return $this->template($slug);
        throw new RuntimeException(wbce_store_text('package_type_unknown'));
    }

    public function bridgeDependents()
    {
        return $this->moduleDependents('wbce_hook_bridge');
    }

    public function moduleDependents($slug)
    {
        if ($slug === 'wbce_hook_bridge' && wbce_store_native_hooks_available()) return array();
        $dependents = array();
        $escapedSlug = $this->database->escapeString((string)$slug);
        $result = $this->database->query("SELECT `directory`,`name` FROM `{TP}addons` WHERE `type`='module' AND `directory`<>'".$escapedSlug."'");
        while ($result && ($row = $result->fetchRow(MYSQLI_ASSOC))) {
            $info = WB_PATH.'/modules/'.$row['directory'].'/info.php';
            if (!is_file($info) || !is_readable($info)) continue;
            try { $metadata = WbceRepositoryInfoParser::parse(file_get_contents($info)); }
            catch (Throwable $ignored) { continue; }
            $direct = trim(isset($metadata['module_dependencies']) ? (string)$metadata['module_dependencies'] : '');
            $alternative = trim(isset($metadata['module_requires_any']) ? (string)$metadata['module_requires_any'] : '');
            $quotedSlug = preg_quote((string)$slug, '/');
            $needsBridge = preg_match('/(?:^|,)\s*'.$quotedSlug.'(?:\s*>=\s*[^,]+)?\s*(?:,|$)/i', $direct) === 1;
            if (!$needsBridge && preg_match('/(?:^|\|)\s*'.$quotedSlug.'\s*>=\s*[^|]+(?:\||$)/i', $alternative)) {
                $withoutBridge = array();
                foreach (preg_split('/\s*\|\s*/', $alternative, -1, PREG_SPLIT_NO_EMPTY) as $requirement) {
                    if (!preg_match('/^'.$quotedSlug.'\s*>=/i', trim($requirement))) $withoutBridge[] = trim($requirement);
                }
                $needsBridge = !$withoutBridge || !wbce_store_any_requirement_meets(implode('|', $withoutBridge), $this->database);
            }
            if ($needsBridge) $dependents[] = !empty($row['name']) ? (string)$row['name'] : (string)$row['directory'];
        }
        natcasesort($dependents);
        return array_values(array_unique($dependents));
    }

    private function nativeUninstall($type, $slug)
    {
        if ($slug === 'store') throw new RuntimeException(wbce_store_text('store_self_uninstall'));
        $nativeType = $type === 'module' ? 'module' : (($type === 'template' || $type === 'admin_template') ? 'template' : '');
        if ($nativeType === '') throw new RuntimeException(wbce_store_text('package_type_unknown'));

        $service = new AddonService();
        $signals = $service->uninstall($slug, $nativeType, true);
        if ($service->hasError()) throw new RuntimeException($this->nativeError($service, $signals));

        // Store uninstall is explicitly permanent. WBCE 1.7 first performs a
        // recoverable soft uninstall, then the Store removes that staged copy.
        $deleteSignals = $service->delete($slug, $nativeType);
        if ($service->hasError()) throw new RuntimeException($this->nativeError($service, $deleteSignals));
        wbce_store_emit('repository.package.uninstalled', array('type' => $type, 'slug' => $slug));
        return wbce_store_text($nativeType === 'module' ? 'module_uninstalled' : 'template_uninstalled');
    }

    private function nativeError($service, array $signals)
    {
        $messages = array();
        foreach ($signals as $signal) {
            if (!isset($signal['signal']) || $service->isOkSignal($signal['signal'])) continue;
            $messages[] = isset($signal['label']) ? (string) $signal['label'] : (string) $signal['signal'];
        }
        return $messages ? implode('; ', $messages) : wbce_store_text('addon_action_failed');
    }

    private function module($slug)
    {
        if ($slug === 'store') throw new RuntimeException(wbce_store_text('store_self_uninstall'));
        $used = $this->dbValue("SELECT COUNT(*) FROM `{TP}sections` WHERE `module`='" . $this->database->escapeString($slug) . "'");
        if ((int)$used > 0) throw new RuntimeException(wbce_store_text('module_in_use'));
        $dir = WB_PATH . '/modules/' . $slug;
        if (!is_file($dir . '/info.php') || !is_writable($dir)) throw new RuntimeException(wbce_store_text('module_not_removable'));
        $module_level = '';
        include $dir . '/info.php';
        if ($module_level === 'core') throw new RuntimeException(wbce_store_text('core_module'));
        if (is_file($dir . '/uninstall.php')) require $dir . '/uninstall.php';
        if (!rm_full_dir($dir)) throw new RuntimeException(wbce_store_text('module_delete_failed'));
        $this->database->query("DELETE FROM `{TP}addons` WHERE `directory`='" . $this->database->escapeString($slug) . "' AND `type`='module'");
        return wbce_store_text('module_uninstalled');
    }

    private function template($slug)
    {
        if ((defined('DEFAULT_TEMPLATE') && $slug === DEFAULT_TEMPLATE) || (defined('DEFAULT_THEME') && $slug === DEFAULT_THEME)) {
            throw new RuntimeException(wbce_store_text('active_default_template'));
        }
        $used = $this->dbValue("SELECT COUNT(*) FROM `{TP}pages` WHERE `template`='" . $this->database->escapeString($slug) . "'");
        if ((int)$used > 0) throw new RuntimeException(wbce_store_text('template_in_use'));
        $dir = WB_PATH . '/templates/' . $slug;
        if (!is_file($dir . '/info.php') || !is_writable($dir)) throw new RuntimeException(wbce_store_text('template_not_removable'));
        if (is_file($dir . '/uninstall.php')) require $dir . '/uninstall.php';
        if (!rm_full_dir($dir)) throw new RuntimeException(wbce_store_text('template_delete_failed'));
        $this->database->query("DELETE FROM `{TP}addons` WHERE `directory`='" . $this->database->escapeString($slug) . "' AND `type`='template'");
        return wbce_store_text('template_uninstalled');
    }

    private function language($slug)
    {
        if (!preg_match('/^[a-z]{2,5}$/i', $slug)) throw new RuntimeException(wbce_store_text('package_type_unknown'));
        if (strcasecmp($slug, 'en') === 0) throw new RuntimeException(wbce_store_text('default_language'));
        $meta = WB_PATH.'/languages/.store-meta/'.strtolower($slug).'.json';
        if (!is_file($meta)) throw new RuntimeException(wbce_store_text('package_type_unknown'));
        $data = json_decode((string)file_get_contents($meta), true);
        $code = strtoupper((string)($data['code'] ?? $slug));
        if (!@unlink(WB_PATH.'/languages/'.$code.'.php') || !@unlink($meta)) throw new RuntimeException(wbce_store_text('module_delete_failed'));
        return wbce_store_text('module_uninstalled');
    }

    private function isActiveAdminTheme($slug)
    {
        $configured = trim((string)$this->dbValue(
            "SELECT `value` FROM `{TP}settings` WHERE `name`='default_theme' LIMIT 1"
        ));
        if ($configured !== '' && hash_equals($configured, (string)$slug)) return true;
        return defined('DEFAULT_THEME') && hash_equals((string)DEFAULT_THEME, (string)$slug);
    }

    private function isActiveFrontendTemplate($slug)
    {
        $configured = trim((string)$this->dbValue(
            "SELECT `value` FROM `{TP}settings` WHERE `name`='default_template' LIMIT 1"
        ));
        if ($configured !== '' && hash_equals($configured, (string)$slug)) return true;
        if (defined('DEFAULT_TEMPLATE') && hash_equals((string)DEFAULT_TEMPLATE, (string)$slug)) return true;
        return (int)$this->dbValue(
            "SELECT COUNT(*) FROM `{TP}pages` WHERE `template`='".$this->database->escapeString((string)$slug)."'"
        ) > 0;
    }

    private function dbValue($sql)
    {
        return wbce_store_db_value($this->database, $sql);
    }
}
