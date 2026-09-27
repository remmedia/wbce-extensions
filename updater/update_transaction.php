<?php

function updater_transaction_session_set($key, $value)
{
    if (class_exists('WSession') && is_callable(array('WSession', 'Set'))) {
        WSession::Set($key, $value);
        return;
    }

    if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
        session_start();
    }
    $_SESSION[$key] = $value;
}

function updater_transaction_session_get($key, $default = null)
{
    if (class_exists('WSession') && is_callable(array('WSession', 'Get'))) {
        return WSession::Get($key, $default);
    }

    if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
        session_start();
    }
    return isset($_SESSION[$key]) ? $_SESSION[$key] : $default;
}


/** Return true only for the exact currently installed source release. */
function updater_delta_is_preferred($current, $source, $target)
{
    $current = ltrim(trim((string)$current), 'v');
    $source = ltrim(trim((string)$source), 'v');
    if ($current === '' || $source === '') return false;
    // Store metadata can contain a leading v or harmless whitespace. Compare
    // semantic release values rather than requiring byte-identical strings.
    return version_compare($current, $source, '==');
}

/**
 * Old Store Server catalogues expose a delta id but no direct URL. Derive the
 * canonical same-origin API address so ready deltas remain usable after a
 * Store Server upgrade without rebuilding their catalogue entries.
 */
function updater_delta_download_url(array $delta, $catalogUrl)
{
    $url = trim((string)($delta['download_url'] ?? ''));
    if ($url !== '') return $url;
    $id = (int)($delta['id'] ?? 0);
    $parts = parse_url((string)$catalogUrl);
    if ($id < 1 || !is_array($parts) || strtolower((string)($parts['scheme'] ?? '')) !== 'https' || empty($parts['host'])) return '';
    $origin = 'https://' . $parts['host'] . (isset($parts['port']) ? ':' . (int)$parts['port'] : '');
    $path = (string)($parts['path'] ?? '');
    if (str_contains($path, '/modules/api/')) return $origin . '/modules/api/index.php?api=store&path=delta&id=' . $id;
    return $origin . '/modules/store_server/api/delta/?id=' . $id;
}

function updater_backup_download_state_path($token)
{
    return WB_PATH . '/temp/.wbce-updater-backup-download-' . hash('sha256', (string)$token) . '.json';
}

function updater_backup_download_state_set(array $state)
{
    updater_transaction_session_set('WBCE_UPDATER_BACKUP_DOWNLOAD', $state);
    $token = (string)($state['token'] ?? '');
    if ($token === '') return;
    $payload = json_encode($state, JSON_UNESCAPED_SLASHES);
    if (is_string($payload) && @file_put_contents(updater_backup_download_state_path($token), $payload, LOCK_EX) !== false) {
        @chmod(updater_backup_download_state_path($token), 0600);
    }
}

function updater_backup_download_state_get($token)
{
    $state = updater_transaction_session_get('WBCE_UPDATER_BACKUP_DOWNLOAD', array());
    $valid = is_array($state) && !empty($state['expires'])
        && (int)$state['expires'] >= time()
        && !empty($state['token'])
        && hash_equals((string)$state['token'], (string)$token);
    if ($valid) return $state;

    $path = updater_backup_download_state_path($token);
    $stored = is_file($path) ? @file_get_contents($path) : false;
    $state = is_string($stored) ? json_decode($stored, true) : null;
    $valid = is_array($state) && !empty($state['expires'])
        && (int)$state['expires'] >= time()
        && !empty($state['token'])
        && hash_equals((string)$state['token'], (string)$token);
    return $valid ? $state : array();
}

function updater_create_transaction($zipFile, $version, $source, array $metadata = array())
{
    if (!is_file($zipFile)) {
        return '';
    }
    try { $token = bin2hex(random_bytes(32)); }
    catch (Throwable $exception) { return ''; }
    $transaction = array(
        'token_hash' => hash('sha256', $token),
        'zip_hash' => hash_file('sha256', $zipFile),
        'version' => (string)$version,
        'source' => (string)$source,
        'metadata' => $metadata,
        'expires_at' => time() + 3600,
        'used' => false,
    );
    updater_transaction_session_set('WBCE_UPDATER_TRANSACTION', $transaction);
    // A prepared update can replace the CMS session implementation. Keep a
    // short-lived, token-addressed copy outside the package so the immediate
    // execution request remains valid across such session transitions.
    $path = WB_PATH . '/temp/.wbce-updater-transaction-' . hash('sha256', $token) . '.json';
    $payload = json_encode($transaction, JSON_UNESCAPED_SLASHES);
    if (!is_string($payload) || @file_put_contents($path, $payload, LOCK_EX) === false) {
        return '';
    }
    @chmod($path, 0600);
    return $token;
}

function updater_transaction_is_valid($transaction, $token, $zipFile, $version)
{
    return is_array($transaction) && empty($transaction['used'])
        && !empty($transaction['token_hash']) && !empty($transaction['zip_hash'])
        && (int)($transaction['expires_at'] ?? 0) >= time()
        && hash_equals((string)$transaction['token_hash'], hash('sha256', (string)$token))
        && hash_equals((string)$transaction['version'], (string)$version)
        && is_file($zipFile)
        && hash_equals((string)$transaction['zip_hash'], hash_file('sha256', $zipFile));
}

function updater_consume_transaction($token, $zipFile, $version)
{
    $transaction = updater_transaction_session_get('WBCE_UPDATER_TRANSACTION', array());
    $path = WB_PATH . '/temp/.wbce-updater-transaction-' . hash('sha256', (string)$token) . '.json';
    if (!updater_transaction_is_valid($transaction, $token, $zipFile, $version) && is_file($path)) {
        $stored = @file_get_contents($path);
        $decoded = is_string($stored) ? json_decode($stored, true) : null;
        if (is_array($decoded)) $transaction = $decoded;
    }
    if (!updater_transaction_is_valid($transaction, $token, $zipFile, $version)) return false;
    $transaction['used'] = true;
    updater_transaction_session_set('WBCE_UPDATER_TRANSACTION', $transaction);
    // The token is single-use. Removing the durable copy is stronger than
    // relying on a later session write while the archive is being processed.
    @unlink($path);
    return $transaction;
}

function updater_rollback_path(string $token): string
{
    return WB_PATH . '/temp/.wbce-updater-rollback-' . hash('sha256', $token);
}
function updater_rollback_manifest_path(string $token): string
{
    return updater_rollback_path($token) . '/manifest.json';
}
function updater_rollback_begin(string $token): bool
{
    $base=updater_rollback_path($token);
    return (is_dir($base) || @mkdir($base . '/files', 0700, true)) && @file_put_contents(updater_rollback_manifest_path($token), json_encode(['state'=>'running','files'=>[]]), LOCK_EX)!==false;
}
function updater_rollback_record(string $token, string $relative, string $destination): void
{
    $manifestPath=updater_rollback_manifest_path($token); $raw=@file_get_contents($manifestPath); $manifest=is_string($raw)?json_decode($raw,true):null;
    if (!is_array($manifest) || isset($manifest['files'][$relative])) return;
    $safe=hash('sha256',$relative); $entry=['exists'=>is_file($destination),'backup'=>$safe];
    if ($entry['exists'] && !@copy($destination, updater_rollback_path($token).'/files/'.$safe)) throw new RuntimeException('Wiederherstellungspunkt konnte nicht gesichert werden: '.$relative);
    $manifest['files'][$relative]=$entry; @file_put_contents($manifestPath,json_encode($manifest,JSON_UNESCAPED_SLASHES),LOCK_EX);
}
function updater_rollback_restore(string $token): bool
{
    $base=updater_rollback_path($token); $raw=@file_get_contents($base.'/manifest.json'); $manifest=is_string($raw)?json_decode($raw,true):null; if(!is_array($manifest))return false;
    $ok=true; foreach(array_reverse((array)($manifest['files']??[]),true) as $relative=>$entry){$dest=WB_PATH.DIRECTORY_SEPARATOR.str_replace('/',DIRECTORY_SEPARATOR,$relative); if(!empty($entry['exists'])){$src=$base.'/files/'.($entry['backup']??''); if(!is_file($src)||!@copy($src,$dest))$ok=false;}elseif(is_file($dest)&&!@unlink($dest))$ok=false;}
    $manifest['state']=$ok?'rolled_back':'rollback_failed'; @file_put_contents($base.'/manifest.json',json_encode($manifest),LOCK_EX); return $ok;
}
function updater_rollback_commit(string $token): void
{
    $base=updater_rollback_path($token); if(!is_dir($base))return; $it=new RecursiveIteratorIterator(new RecursiveDirectoryIterator($base,FilesystemIterator::SKIP_DOTS),RecursiveIteratorIterator::CHILD_FIRST); foreach($it as $file){$file->isDir()?@rmdir($file->getPathname()):@unlink($file->getPathname());}@rmdir($base);
}
