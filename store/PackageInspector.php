<?php

require_once __DIR__ . '/Language.php';
require_once WB_PATH . '/include/pclzip/pclzip.lib.php';
require_once __DIR__ . '/InfoParser.php';

final class WbceRepositoryPackageInspector
{
    const MAX_FILES = 20000;
    const MAX_UNPACKED_BYTES = 104857600;

    public static function inspect($zipPath)
    {
        $archive = new PclZip($zipPath);
        $list = $archive->listContent();
        if (!is_array($list) || count($list) < 1 || count($list) > self::MAX_FILES) {
            throw new RuntimeException(wbce_store_text('archive_invalid'));
        }
        $infoEntry = null;
        $root = null;
        $total = 0;
        foreach ($list as $entry) {
            $name = str_replace('\\', '/', $entry['filename']);
            self::validatePath($name);
            $total += isset($entry['size']) ? (int)$entry['size'] : 0;
            if ($total > self::MAX_UNPACKED_BYTES) {
                throw new RuntimeException(wbce_store_text('archive_too_large'));
            }
            if (basename($name) === 'info.php') {
                if ($infoEntry !== null) {
                    throw new RuntimeException(wbce_store_text('multiple_info_files'));
                }
                $infoEntry = $name;
                $root = trim(dirname($name), './');
            }
        }
        if ($infoEntry === null) {
            throw new RuntimeException(wbce_store_text('info_missing'));
        }
        $content = $archive->extract(PCLZIP_OPT_BY_NAME, $infoEntry, PCLZIP_OPT_EXTRACT_AS_STRING);
        if (!is_array($content) || empty($content[0]['content'])) {
            throw new RuntimeException(wbce_store_text('metadata_unreadable'));
        }
        $metadata = WbceRepositoryInfoParser::parse($content[0]['content']);
        if (!empty($metadata['module_directory'])) {
            $type = 'module';
            $prefix = 'module_';
        } elseif (!empty($metadata['template_directory'])) {
            $type = isset($metadata['template_function']) && $metadata['template_function'] === 'theme' ? 'admin_template' : 'template';
            $prefix = 'template_';
        } elseif (!empty($metadata['language_code'])) {
            $type = 'language';
            $prefix = 'language_';
        } else {
            throw new RuntimeException(wbce_store_text('package_type_unsupported'));
        }
        $slug = $type === 'language' ? strtolower((string)$metadata['language_code']) : (isset($metadata[$prefix . 'directory']) ? $metadata[$prefix . 'directory'] : '');
        if (!preg_match('/^[a-z][a-z0-9_-]{1,189}$/i', $slug)) {
            throw new RuntimeException(wbce_store_text('package_directory_invalid'));
        }
        $version = isset($metadata[$prefix . 'version']) ? $metadata[$prefix . 'version'] : '';
        if ($version === '' || strlen($version) > 64) {
            throw new RuntimeException(wbce_store_text('package_version_invalid'));
        }
        return array(
            'type' => $type,
            'slug' => $slug,
            'name' => isset($metadata[$prefix . 'name']) ? $metadata[$prefix . 'name'] : $slug,
            'version' => $version,
            'description' => isset($metadata[$prefix . 'description']) ? $metadata[$prefix . 'description'] : '',
            'platform' => isset($metadata[$prefix . 'platform']) ? $metadata[$prefix . 'platform'] : '',
            'dependencies' => $type === 'language' ? '' : (isset($metadata['module_dependencies']) ? $metadata['module_dependencies'] : ''),
            'requires_any' => isset($metadata['module_requires_any']) ? $metadata['module_requires_any'] : '',
            'function' => isset($metadata['module_function']) ? strtolower(trim((string)$metadata['module_function'])) : '',
            'language_code' => $type === 'language' ? strtoupper((string)$metadata['language_code']) : '',
            'root' => $root,
            'files' => count($list),
            'unpacked_size' => $total,
        );
    }

    public static function validatePath($name)
    {
        if ($name === '' || strpos($name, "\0") !== false || substr($name, 0, 1) === '/'
            || preg_match('#(^|/)\.\.(/|$)#', $name) || preg_match('#^[A-Za-z]:/#', $name)) {
            throw new RuntimeException(wbce_store_text('archive_path_unsafe'));
        }
    }
}
