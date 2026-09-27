<?php
defined('WB_PATH') or die('No direct access');

final class WbceLanguageRepository
{
    private const ROOTS = array('languages', 'admin/languages', 'modules');
    private const METADATA_KEYS = array('language_code','language_name','language_version','language_platform','language_author','language_license');

    public function languages(): array
    {
        $languages = array();
        foreach ($this->files() as $file) {
            $code = strtoupper(pathinfo($file['path'], PATHINFO_FILENAME));
            if (preg_match('/^[A-Z]{2,5}$/', $code)) $languages[$code] = $code;
        }
        $names = array('DE'=>'Deutsch','EN'=>'English','FR'=>'Français','ES'=>'Español','IT'=>'Italiano','NL'=>'Nederlands','PL'=>'Polski','PT'=>'Português','RU'=>'Русский','TR'=>'Türkçe');
        ksort($languages, SORT_NATURAL);
        foreach ($languages as $code => $unused) $languages[$code] = $names[$code] ?? $code;
        return $languages;
    }

    public function files(string $language = ''): array
    {
        $out = array();
        $language = strtoupper($language);
        foreach ($this->roots() as $root) {
            if (!is_dir($root)) continue;
            $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS));
            foreach ($iterator as $entry) {
                if (!$entry->isFile() || strtolower($entry->getExtension()) !== 'php') continue;
                $path = $entry->getPathname();
                if (!preg_match('~/languages(?:/|$)~', str_replace('\\', '/', $path))) continue;
                $base = strtoupper(pathinfo($path, PATHINFO_FILENAME));
                if (!preg_match('/^[A-Z]{2,5}$/', $base) || $base === 'INDEX') continue;
                if ($language !== '' && $base !== $language) continue;
                $id = $this->id($path);
                if ($id === null) continue;
                $out[$id] = array('id'=>$id, 'label'=>$this->label($id), 'path'=>$path);
            }
        }
        uasort($out, static fn($a,$b) => strnatcasecmp($a['label'], $b['label']));
        return array_values($out);
    }

    public function document(string $id, string $targetLanguage, string $cmsLanguage): array
    {
        $target = $this->pathForId($id);
        if ($target === null) throw new RuntimeException('Die Sprachdatei wurde nicht gefunden.');
        $current = $this->read($target);
        $cmsPath = $this->alternateLanguagePath($target, $cmsLanguage);
        $englishPath = $this->alternateLanguagePath($target, 'EN');
        $cms = $cmsPath && is_readable($cmsPath) ? $this->read($cmsPath) : array();
        $english = $englishPath && is_readable($englishPath) ? $this->read($englishPath) : array();
        $rows = array();
        foreach ($current['values'] as $key => $value) {
            if ($this->isMetadataKey($key)) continue;
            $base = $cms['values'][$key] ?? ($english['values'][$key] ?? $key);
            $en = $english['values'][$key] ?? '';
            $rows[] = array('key'=>$key, 'base'=>(string)$base, 'english'=>(string)$en, 'value'=>(string)$value);
        }
        return array('id'=>$id, 'label'=>$this->label($id), 'language'=>$targetLanguage, 'rows'=>$rows);
    }

    public function exportLanguage(string $language): array
    {
        $language = strtoupper($language);
        if (!preg_match('/^[A-Z]{2,5}$/', $language)) throw new RuntimeException('Ungültige Sprache.');
        $files = array();
        foreach ($this->files($language) as $file) {
            $document = $this->read($file['path']);
            $files[] = array('id'=>$file['id'], 'data'=>$document['data'], 'return'=>$document['return'], 'overrides'=>$this->overridesFor($file['id']));
        }
        return array('format'=>'wbce-language-export', 'version'=>2, 'language'=>$language, 'created_at'=>gmdate('c'), 'files'=>$files);
    }

    public function save(string $id, string $key, string $value): void
    {
        $path = $this->pathForId($id);
        if ($path === null) throw new RuntimeException('Die Sprachdatei wurde nicht gefunden.');
        $document = $this->read($path);
        if (!array_key_exists($key, $document['values'])) throw new RuntimeException('Der Sprachschlüssel wurde nicht gefunden.');
        if ($this->isMetadataKey($key)) throw new RuntimeException('Sprach-Metadaten können nicht übersetzt werden.');
        $this->rememberOverride($id, $key, (string)$document['values'][$key], $value);
        $this->setNested($document['data'], explode('.', $key), $value);
        $this->write($path, $document);
    }

    /** Restores user translations after an addon update replaced a file by its recorded original value. */
    public function restoreOverrides(): int
    {
        $catalog = $this->catalog(); $restored = 0;
        foreach ((array)($catalog['files'] ?? array()) as $id => $fileOverrides) {
            $path = $this->pathForId((string)$id);
            if ($path === null) continue;
            $document = $this->read($path); $changed = false;
            foreach ((array)($fileOverrides['entries'] ?? array()) as $key => $entry) {
                if (!is_array($entry) || !array_key_exists('original', $entry) || !array_key_exists('value', $entry)
                    || !array_key_exists($key, $document['values']) || $this->isMetadataKey((string)$key)) continue;
                if ((string)$document['values'][$key] !== (string)$entry['original']) continue;
                if ((string)$entry['value'] === (string)$entry['original']) continue;
                $this->setNested($document['data'], explode('.', (string)$key), (string)$entry['value']);
                $changed = true; $restored++;
            }
            if ($changed) $this->write($path, $document);
        }
        return $restored;
    }

    private function write(string $path, array $document): void
    {
        $content = "<?php\n\n";
        if ($document['return']) {
            $content .= 'return ' . var_export($document['data'], true) . ";\n";
        } else {
            foreach ($document['data'] as $name => $data) {
                if (!preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', (string)$name)) continue;
                $content .= '$' . $name . ' = ' . var_export($data, true) . ";\n\n";
            }
        }
        $temporary = $path . '.languages-' . bin2hex(random_bytes(6)) . '.tmp';
        if (@file_put_contents($temporary, $content, LOCK_EX) === false || !@rename($temporary, $path)) {
            @unlink($temporary);
            throw new RuntimeException('Die Sprachdatei konnte nicht gespeichert werden.');
        }
        @chmod($path, 0644);
    }

    private function isMetadataKey(string $key): bool
    {
        return !str_contains($key, '.') && in_array(strtolower($key), self::METADATA_KEYS, true);
    }

    private function catalogPath(): string { return WB_PATH . '/var/modules/languages/translation-overrides.json'; }

    private function catalog(): array
    {
        $path = $this->catalogPath();
        if (!is_file($path)) return array('format'=>'wbce-language-overrides','version'=>1,'files'=>array());
        $data = json_decode((string)@file_get_contents($path), true);
        return is_array($data) && is_array($data['files'] ?? null) ? $data : array('format'=>'wbce-language-overrides','version'=>1,'files'=>array());
    }

    private function overridesFor(string $id): array
    {
        $catalog = $this->catalog();
        return (array)($catalog['files'][$id]['entries'] ?? array());
    }

    private function rememberOverride(string $id, string $key, string $original, string $value): void
    {
        $path = $this->catalogPath(); $directory = dirname($path);
        if (!is_dir($directory) && !@mkdir($directory, 0755, true) && !is_dir($directory)) throw new RuntimeException('Der Übersetzungsspeicher konnte nicht angelegt werden.');
        $handle = @fopen($path, 'c+');
        if (!$handle || !@flock($handle, LOCK_EX)) throw new RuntimeException('Der Übersetzungsspeicher ist gesperrt.');
        try {
            $raw = stream_get_contents($handle); $catalog = json_decode((string)$raw, true);
            if (!is_array($catalog)) $catalog = array('format'=>'wbce-language-overrides','version'=>1,'files'=>array());
            $catalog['format'] = 'wbce-language-overrides'; $catalog['version'] = 1;
            if (!isset($catalog['files']) || !is_array($catalog['files'])) $catalog['files'] = array();
            if ($value === $original) {
                unset($catalog['files'][$id]['entries'][$key]);
                if (empty($catalog['files'][$id]['entries'])) unset($catalog['files'][$id]);
            } else {
                $old = $catalog['files'][$id]['entries'][$key] ?? array();
                $catalog['files'][$id]['entries'][$key] = array(
                    'original'=>(string)($old['original'] ?? $original), 'value'=>$value, 'updated_at'=>gmdate('c')
                );
            }
            $catalog['updated_at'] = gmdate('c'); $encoded = json_encode($catalog, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
            if (!is_string($encoded)) throw new RuntimeException('Der Übersetzungsspeicher konnte nicht geschrieben werden.');
            rewind($handle); if (!ftruncate($handle, 0) || fwrite($handle, $encoded."\n") === false || !fflush($handle)) throw new RuntimeException('Der Übersetzungsspeicher konnte nicht geschrieben werden.');
        } finally { @flock($handle, LOCK_UN); fclose($handle); }
    }

    private function roots(): array
    {
        return array(WB_PATH . '/languages', WB_PATH . '/admin/languages', WB_PATH . '/modules');
    }

    private function id(string $path): ?string
    {
        $real = realpath($path);
        if ($real === false) return null;
        $prefix = realpath(WB_PATH);
        if ($prefix === false || !str_starts_with($real, $prefix . DIRECTORY_SEPARATOR)) return null;
        return str_replace(DIRECTORY_SEPARATOR, '/', substr($real, strlen($prefix) + 1));
    }

    private function pathForId(string $id): ?string
    {
        $id = str_replace('\\', '/', $id);
        foreach ($this->files() as $file) if (hash_equals($file['id'], $id)) return $file['path'];
        return null;
    }

    private function alternateLanguagePath(string $path, string $language): ?string
    {
        $language = strtoupper($language);
        if (!preg_match('/^[A-Z]{2,5}$/', $language)) return null;
        return preg_replace('~/[A-Z]{2,5}\\.php$~', '/' . $language . '.php', str_replace('\\', '/', $path));
    }

    private function label(string $id): string
    {
        $parts = explode('/', $id);
        if (($parts[0] ?? '') === 'modules') return 'Modul: ' . ($parts[1] ?? '') . ' / ' . implode('/', array_slice($parts, 2));
        return 'CMS: ' . $id;
    }

    private function read(string $path): array
    {
        $loader = static function (string $file): array {
            $before = array_keys(get_defined_vars());
            ob_start();
            $returned = include $file;
            ob_end_clean();
            $variables = get_defined_vars();
            foreach ($before as $name) unset($variables[$name]);
            unset($variables['before'], $variables['returned'], $variables['variables']);
            return array($returned, $variables);
        };
        [$returned, $variables] = $loader($path);
        $isReturn = is_array($returned) && !$variables;
        $data = $isReturn ? $returned : $variables;
        $values = array();
        $this->flatten($data, array(), $values);
        return array('return'=>$isReturn, 'data'=>$data, 'values'=>$values);
    }

    private function flatten($value, array $path, array &$out): void
    {
        if (is_array($value)) {
            foreach ($value as $key => $item) $this->flatten($item, array_merge($path, array((string)$key)), $out);
            return;
        }
        if (is_scalar($value) || $value === null) $out[implode('.', $path)] = (string)$value;
    }

    private function setNested(array &$data, array $path, string $value): void
    {
        $cursor =& $data;
        foreach ($path as $index => $key) {
            if ($index === count($path) - 1) { $cursor[$key] = $value; return; }
            if (!isset($cursor[$key]) || !is_array($cursor[$key])) $cursor[$key] = array();
            $cursor =& $cursor[$key];
        }
    }
}
