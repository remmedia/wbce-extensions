<?php

namespace TrustComponent\TrustCaptcha;

use Exception;

require_once __DIR__ . '/model/VerificationToken.php';
require_once __DIR__ . '/model/VerificationResult.php';

class TrustCaptcha {

    const LIBRARY_VERSION = '3.0.0';
    const LIBRARY_LANGUAGE = 'php';
    const DEFAULT_API_HOST = 'https://api.trustcomponent.com';
    const DEFAULT_CONNECT_TIMEOUT_MS = 3000;
    const DEFAULT_READ_TIMEOUT_MS = 5000;

    private string $apiKey;
    private string $apiHost;
    private int $connectTimeoutMs;
    private int $readTimeoutMs;
    private ?array $proxy;

    /**
     * @param string $apiKey  Required api key.
     * @param array<string,mixed> $options  Optional configuration:
     *   - apiHost (string)
     *   - connectTimeoutMs (int)
     *   - readTimeoutMs (int)
     *   - proxy (array{proxy:string, username?:string, password?:string})
     */
    public function __construct(string $apiKey, array $options = []) {
        $apiKey = trim($apiKey);
        if ($apiKey === '') {
            throw new \InvalidArgumentException('apiKey must not be empty');
        }
        $this->apiKey = $apiKey;
        $this->apiHost = $options['apiHost'] ?? self::DEFAULT_API_HOST;
        $this->connectTimeoutMs = $options['connectTimeoutMs'] ?? self::DEFAULT_CONNECT_TIMEOUT_MS;
        $this->readTimeoutMs = $options['readTimeoutMs'] ?? self::DEFAULT_READ_TIMEOUT_MS;
        $this->proxy = $options['proxy'] ?? null;
    }

    public function getVerificationResult(string $base64verificationToken): VerificationResult {

        $verificationToken = self::parseVerificationToken($base64verificationToken);

        $query = $verificationToken->clientFailover ? "?clientFailover=true" : "";
        $url = "{$this->apiHost}/v2/verifications/{$verificationToken->verificationId}/results{$query}";
        $headers = [
            "Authorization: Bearer {$this->apiKey}",
            "User-Agent: " . self::buildUserAgent(),
        ];

        // Modified for WBCE: fail safely when cURL cannot be initialized and
        // restrict transport and response size for shared-hosting environments.
        $ch = curl_init($url);
        if ($ch === false) {
            throw new ServerUnreachableException();
        }
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_CUSTOMREQUEST  => 'GET',
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_MAXREDIRS      => 0,
            CURLOPT_PROTOCOLS      => CURLPROTO_HTTPS,
            CURLOPT_CONNECTTIMEOUT_MS => $this->connectTimeoutMs,
            CURLOPT_TIMEOUT_MS     => $this->readTimeoutMs,
        ]);

        // Optional CA bundle override: only used if Composer's ca-bundle package is installed.
        // Without it we rely on the system default CA store, which works on virtually all
        // modern Linux distributions, macOS and Windows out of the box.
        if (class_exists('Composer\\CaBundle\\CaBundle')) {
            $ca = \Composer\CaBundle\CaBundle::getSystemCaRootBundlePath();
            if (is_dir($ca)) {
                curl_setopt($ch, CURLOPT_CAPATH, $ca);
            } else {
                curl_setopt($ch, CURLOPT_CAINFO, $ca);
            }
        }

        if ($this->proxy !== null) {
            if (isset($this->proxy['proxy'])) {
                $proxy = $this->proxy['proxy'];
                if (strpos($proxy, 'tcp://') === 0) {
                    $proxy = 'http://' . substr($proxy, strlen('tcp://'));
                }
                curl_setopt($ch, CURLOPT_PROXY, $proxy);
            }
            if (isset($this->proxy['username'], $this->proxy['password'])) {
                curl_setopt($ch, CURLOPT_PROXYUSERPWD, "{$this->proxy['username']}:{$this->proxy['password']}");
            }
        }

        $response = curl_exec($ch);
        $httpStatusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if ($response === false) {
            throw new ServerUnreachableException();
        }
        if (!is_string($response) || strlen($response) > 1048576) {
            throw new Exception("Verification response is invalid or too large.");
        }

        if ($httpStatusCode === 403) {
            throw new ApiKeyInvalidException("The provided api key is invalid. Please verify the api key from your captcha settings.");
        } elseif ($httpStatusCode === 404) {
            throw new VerificationNotFoundException("No verification could be found for the given verification token.");
        } elseif ($httpStatusCode === 423) {
            throw new VerificationNotFinishedException("The verification is not yet completed. Please wait until the user has finished solving the captcha before requesting the result.");
        } elseif ($httpStatusCode === 410) {
            throw new VerificationResultExpiredException("The verification result has expired and can no longer be retrieved.");
        } elseif ($httpStatusCode === 412) {
            throw new ClientReportedServerUnreachableException();
        } elseif ($httpStatusCode === 429) {
            throw new VerificationResultRetrievalLimitReachedException("The verification result has reached its maximum retrieval count and can no longer be retrieved.");
        } elseif ($httpStatusCode < 200 || $httpStatusCode >= 300) {
            throw new Exception("Failed to retrieve verification result: HTTP $httpStatusCode");
        }

        return new VerificationResult($response);
    }

    private static function parseVerificationToken(string $verificationToken): VerificationToken {
        // Modified for WBCE: reject malformed Base64 and unsafe path identifiers early.
        $decoded = base64_decode($verificationToken, true);
        if ($decoded === false) {
            throw new VerificationTokenInvalidException("The verification token is not valid base64 and could not be decoded.");
        }
        $data = json_decode($decoded);
        if (!is_object($data) || !isset($data->verificationId) || !is_string($data->verificationId) || !preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[1-8][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/Di', $data->verificationId)) {
            throw new VerificationTokenInvalidException("The verification token is malformed: required field 'verificationId' is missing.");
        }
        $clientFailover = isset($data->clientFailover) && $data->clientFailover === true;
        return new VerificationToken($data->verificationId, $clientFailover);
    }

    private static function buildUserAgent(): string {
        $payload = [
            'language' => self::LIBRARY_LANGUAGE,
            'version'  => self::LIBRARY_VERSION,
        ];
        $encoded = base64_encode(json_encode($payload, JSON_UNESCAPED_SLASHES));
        return "Trustcaptcha/$encoded";
    }
}

class ApiKeyInvalidException extends Exception {}
class VerificationTokenInvalidException extends Exception {}
class VerificationNotFoundException extends Exception {}
class VerificationNotFinishedException extends Exception {}
class VerificationResultExpiredException extends Exception {}
class VerificationResultRetrievalLimitReachedException extends Exception {}

abstract class FailoverException extends Exception {}
class ServerUnreachableException extends FailoverException {
    public function __construct() {
        parent::__construct("Could not reach the TrustCaptcha server. Please check your network connection and consider implementing a failover mechanism.");
    }
}
class ClientReportedServerUnreachableException extends FailoverException {
    public function __construct() {
        parent::__construct("The client reported it could not reach the TrustCaptcha server, but the gateway has no record of a recent outage. Treat this with caution: a malicious client may be claiming a failover that did not happen.");
    }
}
