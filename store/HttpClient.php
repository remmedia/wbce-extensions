<?php
require_once __DIR__ . '/Language.php';

final class WbceRepositoryHttpClient
{
    private static $jsonCache = array();

    public function json($url, $token = '')
    {
        $cacheKey = hash('sha256', (string)$url . "\0" . (string)$token);
        if (isset(self::$jsonCache[$cacheKey])) return self::$jsonCache[$cacheKey];
        $cacheFile = $this->catalogCacheFile($cacheKey);
        $cached = $this->readCatalogCache($cacheFile);
        // Store Servers keep the assembled catalogue server-side. A short
        // client cache therefore keeps the UI responsive without delaying a
        // newly published, activated or removed package for a full minute.
        if ($cached !== null && @filemtime($cacheFile) >= time() - 5) {
            self::$jsonCache[$cacheKey] = $cached;
            return $cached;
        }
        $body = '';
        try {
            $this->request($url, function ($chunk) use (&$body) {
                $body .= $chunk;
                return strlen($body) <= 2097152;
            }, $token, 3, 8);
        } catch (Throwable $error) {
            // A recently valid catalogue is safer and considerably more useful
            // than blocking the complete Store UI on a transient outage.
            if ($cached !== null && @filemtime($cacheFile) >= time() - 900) {
                self::$jsonCache[$cacheKey] = $cached;
                return $cached;
            }
            throw $error;
        }
        $data = json_decode($body, true);
        if (!is_array($data) || !isset($data['schema']) || (int)$data['schema'] !== 1
            || !isset($data['packages']) || !is_array($data['packages'])) {
            throw new RuntimeException(wbce_store_text('api_invalid'));
        }
        $this->writeCatalogCache($cacheFile, $body);
        self::$jsonCache[$cacheKey] = $data;
        return $data;
    }

    public function download($url, $expectedSize = 0, $token = '')
    {
        $path = tempnam(WB_PATH . '/temp', 'repo_');
        $handle = fopen($path, 'wb');
        if (!$handle) throw new RuntimeException(wbce_store_text('temp_file_failed'));
        $written = 0;
        try {
            $this->request($url, function ($chunk) use ($handle, &$written, $expectedSize) {
                $written += strlen($chunk);
                $limit = $expectedSize > 0 ? $expectedSize + 1 : 52428801;
                return $written <= $limit && fwrite($handle, $chunk) === strlen($chunk);
            }, $token, 10, 60);
        } catch (Throwable $error) {
            fclose($handle);
            @unlink($path);
            throw $error;
        }
        fclose($handle);
        if (($expectedSize > 0 && $written !== (int)$expectedSize) || $written < 1 || $written > 52428800) {
            @unlink($path);
            throw new RuntimeException(wbce_store_text('download_size_mismatch'));
        }
        return $path;
    }

    /** Return only an existing local catalogue; never performs network I/O. */
    public function cachedJson($url, $token = '', $maximumAge = 900)
    {
        $cacheKey = hash('sha256', (string)$url . "\0" . (string)$token);
        if (isset(self::$jsonCache[$cacheKey])) return self::$jsonCache[$cacheKey];
        $file = $this->catalogCacheFile($cacheKey);
        if (!is_file($file) || @filemtime($file) < time() - max(1, (int)$maximumAge)) return null;
        $cached = $this->readCatalogCache($file);
        if ($cached !== null) self::$jsonCache[$cacheKey] = $cached;
        return $cached;
    }

    public function image($url, $token = '')
    {
        $body = '';
        $this->request($url, function ($chunk) use (&$body) {
            $body .= $chunk;
            return strlen($body) <= 5242880;
        }, $token, 4, 15);
        $image = @getimagesizefromstring($body);
        $allowed = array('image/jpeg', 'image/png', 'image/webp');
        if (!$image || !isset($image['mime']) || !in_array($image['mime'], $allowed, true)
            || $image[0] < 320 || $image[1] < 200 || $image[0] > 4000 || $image[1] > 4000) {
            throw new RuntimeException(wbce_store_text('preview_invalid'));
        }
        return array('content' => $body, 'mime' => $image['mime']);
    }

    private function request($url, $writer, $token = '', $connectTimeout = 10, $timeout = 60)
    {
        if (!function_exists('curl_init')) throw new RuntimeException(wbce_store_text('curl_missing'));
        $parts = parse_url($url);
        if (!$parts || strtolower(isset($parts['scheme']) ? $parts['scheme'] : '') !== 'https'
            || empty($parts['host']) || isset($parts['user']) || isset($parts['pass'])
            || (isset($parts['port']) && (int)$parts['port'] !== 443)) {
            throw new RuntimeException(wbce_store_text('https_required'));
        }
        $ip = gethostbyname($parts['host']);
        if ($ip === $parts['host'] || !filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
            throw new RuntimeException(wbce_store_text('public_address_required'));
        }
        $curl = curl_init($url);
        $headers = array('Accept: application/json, application/zip;q=0.9, image/*;q=0.8', 'User-Agent: WBCE-Remote-Repository/1.0');
        if ($token !== '') $headers[] = 'Authorization: Bearer ' . trim((string)$token);
        curl_setopt_array($curl, array(
            CURLOPT_RETURNTRANSFER => false,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_CONNECTTIMEOUT => max(1, (int)$connectTimeout),
            CURLOPT_TIMEOUT => max(1, (int)$timeout),
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_PROTOCOLS => CURLPROTO_HTTPS,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_RESOLVE => array($parts['host'] . ':443:' . $ip),
            CURLOPT_WRITEFUNCTION => function ($curl, $chunk) use ($writer) {
                return $writer($chunk) ? strlen($chunk) : 0;
            },
        ));
        $ok = curl_exec($curl);
        $status = (int)curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
        $errorNumber = curl_errno($curl);
        $error = curl_error($curl);
        if (class_exists('WbceRuntimeProfiler', false)) {
            WbceRuntimeProfiler::mark('store.http.request', array(
                'host' => (string)$parts['host'],
                'status' => $status,
                'dns_ms' => (int)round((float)curl_getinfo($curl, CURLINFO_NAMELOOKUP_TIME) * 1000),
                'connect_ms' => (int)round((float)curl_getinfo($curl, CURLINFO_CONNECT_TIME) * 1000),
                'tls_ms' => (int)round((float)curl_getinfo($curl, CURLINFO_APPCONNECT_TIME) * 1000),
                'first_byte_ms' => (int)round((float)curl_getinfo($curl, CURLINFO_STARTTRANSFER_TIME) * 1000),
                'total_ms' => (int)round((float)curl_getinfo($curl, CURLINFO_TOTAL_TIME) * 1000),
            ));
        }
        // CurlHandle instances are released automatically; the former explicit
        // close call has no effect for objects and is deprecated as of PHP 8.5.
        unset($curl);
        if (!$ok && $status === 0) {
            if ($errorNumber === CURLE_OPERATION_TIMEDOUT) throw new RuntimeException(wbce_store_text('request_timeout'));
            if ($errorNumber === CURLE_COULDNT_RESOLVE_HOST) throw new RuntimeException(wbce_store_text('host_unresolved'));
            if ($errorNumber === CURLE_COULDNT_CONNECT) throw new RuntimeException(wbce_store_text('connection_failed'));
            if (defined('CURLE_PEER_FAILED_VERIFICATION') && $errorNumber === CURLE_PEER_FAILED_VERIFICATION) throw new RuntimeException(wbce_store_text('certificate_failed'));
            throw new RuntimeException(wbce_store_text('request_failed', array('details' => $error ? ': '.$error : '.')));
        }
        if ($status === 401 || $status === 403) {
            throw new RuntimeException(trim((string)$token) === ''
                ? wbce_store_text('token_missing')
                : wbce_store_text('token_invalid'));
        }
        if ($status === 404) throw new RuntimeException(wbce_store_text('store_404'));
        if ($status >= 300 && $status < 400) throw new RuntimeException(wbce_store_text('redirect_rejected'));
        if ($status >= 500) throw new RuntimeException(wbce_store_text('server_error', array('status' => $status)));
        if (!$ok || $status !== 200) throw new RuntimeException(wbce_store_text('store_http_unusable', array('status' => $status)));
    }

    private function catalogCacheFile($key)
    {
        return WB_PATH.'/temp/store-catalog-cache/'.$key.'.json';
    }

    private function readCatalogCache($file)
    {
        if (!is_file($file) || !is_readable($file)) return null;
        $body = @file_get_contents($file);
        if (!is_string($body) || $body === '' || strlen($body) > 2097152) return null;
        $data = json_decode($body, true);
        return is_array($data) && isset($data['schema'], $data['packages']) && (int)$data['schema'] === 1 && is_array($data['packages']) ? $data : null;
    }

    private function writeCatalogCache($file, $body)
    {
        $directory = dirname($file);
        if (!is_dir($directory) && !@mkdir($directory, 0750, true) && !is_dir($directory)) return;
        $temporary = $file.'.'.getmypid().'.tmp';
        if (@file_put_contents($temporary, $body, LOCK_EX) === false) return;
        @chmod($temporary, 0600);
        if (!@rename($temporary, $file)) @unlink($temporary);
    }
}
