<?php
/** Applies signed Store deltas and retains a short-lived rollback journal. */
final class WbceStoreDeltaInstaller
{
    public static function applicable(array $package, $installed)
    {
        foreach ((array) ($package['deltas'] ?? array()) as $delta) {
            if ((string) ($delta['source_version'] ?? $delta['from_version'] ?? '') === (string) $installed && filter_var($delta['download_url'] ?? '', FILTER_VALIDATE_URL)) return $delta;
        }
        return null;
    }
    public static function apply($archive, $destination)
    {
        if (!class_exists('ZipArchive')) throw new RuntimeException('ZIP-Deltas werden von diesem PHP nicht unterstützt.');
        $zip = new ZipArchive(); if ($zip->open($archive) !== true) throw new RuntimeException('Delta-Archiv ist ungültig.');
        $raw = $zip->getFromName('manifest.json'); $manifest = is_string($raw) ? json_decode($raw, true) : null;
        if (!is_array($manifest) || ($manifest['schema'] ?? 0) !== 1 || ($manifest['type'] ?? '') !== 'wbce-store-delta') { $zip->close(); throw new RuntimeException('Delta-Manifest ist ungültig.'); }
        $journal = self::journal();
        try {
            foreach ((array) ($manifest['changed'] ?? array()) as $path => $entry) {
                self::validPath($path); $target = $destination . '/' . $path; $current = is_file($target) ? hash_file('sha256', $target) : ''; $expected = (string) ($entry['source_sha256'] ?? '');
                if ($expected !== '' && !hash_equals($expected, $current)) throw new RuntimeException('Ausgangsdatei stimmt nicht mit dem Delta überein.');
                $content = $zip->getFromName('files/' . $path);
                if (!is_string($content) || !hash_equals((string) ($entry['sha256'] ?? ''), hash('sha256', $content))) throw new RuntimeException('Delta-Datei ist beschädigt.');
                self::backup($journal, $target, $path);
                if (!is_dir(dirname($target)) && !mkdir(dirname($target), 0755, true)) throw new RuntimeException('Delta-Zielordner konnte nicht angelegt werden.');
                if (file_put_contents($target, $content, LOCK_EX) === false) throw new RuntimeException('Delta-Datei konnte nicht geschrieben werden.');
            }
            foreach ((array) ($manifest['deleted'] ?? array()) as $path) { self::validPath($path); $target = $destination . '/' . $path; self::backup($journal, $target, $path); if (is_file($target) && !unlink($target)) throw new RuntimeException('Delta-Datei konnte nicht gelöscht werden.'); }
            $zip->close(); return array('manifest' => $manifest, 'journal' => $journal, 'destination' => $destination);
        } catch (Throwable $error) { $zip->close(); self::restoreJournal($journal, $destination); throw $error; }
    }
    public static function commit(array $transaction) { self::remove((string) ($transaction['journal'] ?? '')); }
    public static function restore(array $transaction) { self::restoreJournal((string) ($transaction['journal'] ?? ''), (string) ($transaction['destination'] ?? '')); }
    private static function journal() { $path = WB_PATH . '/temp/store-delta-rollback-' . bin2hex(random_bytes(8)); if (!mkdir($path . '/files', 0750, true)) throw new RuntimeException('Delta-Sicherung konnte nicht angelegt werden.'); return $path; }
    private static function validPath($path) { if (!is_string($path) || $path === '' || str_starts_with($path, '/') || str_contains($path, "\0") || preg_match('#(?:^|/)\.{1,2}(?:/|$)#', $path)) throw new RuntimeException('Delta-Pfad ist ungültig.'); }
    private static function backup($journal, $target, $path) { $marker = $journal . '/files/' . $path . '.state'; if (file_exists($marker)) return; if (!is_dir(dirname($marker)) && !mkdir(dirname($marker), 0750, true)) throw new RuntimeException('Delta-Sicherung konnte nicht vorbereitet werden.'); if (is_file($target)) { if (!copy($target, $journal . '/files/' . $path)) throw new RuntimeException('Delta-Sicherung konnte nicht erstellt werden.'); file_put_contents($marker, 'file', LOCK_EX); } else file_put_contents($marker, 'missing', LOCK_EX); }
    private static function restoreJournal($journal, $destination) { if ($journal === '' || $destination === '' || !is_dir($journal . '/files')) return; $states = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($journal . '/files', FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::LEAVES_ONLY); foreach ($states as $state) { if (substr($state->getFilename(), -6) !== '.state') continue; $relative = substr(str_replace('\\', '/', substr($state->getPathname(), strlen($journal . '/files/'))), 0, -6); $target = $destination . '/' . $relative; if (trim((string) file_get_contents($state->getPathname())) === 'file') { if (!is_dir(dirname($target))) @mkdir(dirname($target), 0755, true); @copy($journal . '/files/' . $relative, $target); } elseif (is_file($target)) @unlink($target); } self::remove($journal); }
    private static function remove($path) { if ($path === '' || !is_dir($path)) return; if (function_exists('rm_full_dir')) { rm_full_dir($path); return; } $entries = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST); foreach ($entries as $entry) $entry->isDir() ? @rmdir($entry->getPathname()) : @unlink($entry->getPathname()); @rmdir($path); }
}
