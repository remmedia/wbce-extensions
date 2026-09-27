<?php
require_once WB_PATH.'/modules/two_factor/Language.php';
require_once __DIR__.'/Cbor.php';

final class WbceWebAuthnService
{
    private $db;
    public function __construct($db)
    {
        if (!is_object($db) || !method_exists($db, 'query')) throw new RuntimeException(wbce_two_factor_t('database_unavailable', array(), 'two_factor_webauthn'));
        $this->db = $db;
    }
    public static function install($db)
    {
        if (!is_object($db) || !method_exists($db, 'query')) return false;
        $a = $db->query("CREATE TABLE IF NOT EXISTS `{TP}mod_two_factor_webauthn_credentials` (`id` int NOT NULL AUTO_INCREMENT,`user_id` int NOT NULL,`credential_id` varchar(512) NOT NULL,`public_key` text NOT NULL,`sign_count` bigint NOT NULL DEFAULT 0,`label` varchar(190) NOT NULL,`created_at` int NOT NULL,`last_used_at` int DEFAULT NULL,PRIMARY KEY(`id`),UNIQUE KEY `credential_id`(`credential_id`),KEY `user_id`(`user_id`)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        $b = $db->query("CREATE TABLE IF NOT EXISTS `{TP}mod_two_factor_webauthn_settings` (`name` varchar(80) NOT NULL,`value` text NOT NULL,PRIMARY KEY(`name`)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        return (bool)($a && $b);
    }
    public static function b64e($value) { return rtrim(strtr(base64_encode((string)$value), '+/', '-_'), '='); }
    public static function b64d($value)
    {
        $value = (string)$value;
        if ($value === '' || preg_match('/[^A-Za-z0-9_-]/', $value)) return false;
        $value = strtr($value, '-_', '+/');
        return base64_decode($value.str_repeat('=', (4 - strlen($value) % 4) % 4), true);
    }
    public function setting($name, $default = '')
    {
        $result = $this->db->query("SELECT `value` FROM `{TP}mod_two_factor_webauthn_settings` WHERE `name`='".$this->db->escapeString($name)."'");
        $row = $result ? $result->fetchRow(MYSQLI_ASSOC) : null;
        return $row ? (string)$row['value'] : $default;
    }
    public function setSetting($name, $value)
    {
        return (bool)$this->db->query("INSERT INTO `{TP}mod_two_factor_webauthn_settings` (`name`,`value`) VALUES ('".$this->db->escapeString($name)."','".$this->db->escapeString($value)."') ON DUPLICATE KEY UPDATE `value`=VALUES(`value`)");
    }
    public function rpId()
    {
        $host = strtolower((string)parse_url(WB_URL, PHP_URL_HOST));
        if ($host === '') throw new RuntimeException(wbce_two_factor_t('configuration_invalid', array(), 'two_factor_webauthn'));
        return $host;
    }
    public function origin()
    {
        $scheme = strtolower((string)parse_url(WB_URL, PHP_URL_SCHEME));
        $host = strtolower((string)parse_url(WB_URL, PHP_URL_HOST));
        $port = parse_url(WB_URL, PHP_URL_PORT);
        $local=in_array($host,array('localhost','127.0.0.1','::1'),true);
        if ($host === '' || ($scheme !== 'https' && !($scheme === 'http' && $local))) throw new RuntimeException(wbce_two_factor_t('configuration_invalid', array(), 'two_factor_webauthn'));
        return $scheme.'://'.$host.($port ? ':'.(int)$port : '');
    }
    public function usage()
    {
        $result = $this->db->query('SELECT COUNT(DISTINCT `user_id`) AS amount FROM `{TP}mod_two_factor_webauthn_credentials`');
        $row = $result ? $result->fetchRow(MYSQLI_ASSOC) : null;
        return $row ? (int)$row['amount'] : 0;
    }
    public function isEnabled($userId)
    {
        $result = $this->db->query('SELECT COUNT(*) AS amount FROM `{TP}mod_two_factor_webauthn_credentials` WHERE `user_id`='.(int)$userId);
        $row = $result ? $result->fetchRow(MYSQLI_ASSOC) : null;
        return $row && (int)$row['amount'] > 0;
    }
    public function credentials($userId)
    {
        $items = array();
        $result = $this->db->query('SELECT `id`,`credential_id`,`label`,`created_at`,`last_used_at` FROM `{TP}mod_two_factor_webauthn_credentials` WHERE `user_id`='.(int)$userId.' ORDER BY `created_at`');
        if ($result) while ($row = $result->fetchRow(MYSQLI_ASSOC)) $items[] = $row;
        return $items;
    }
    public function challenge($purpose)
    {
        if (session_status() !== PHP_SESSION_ACTIVE) throw new RuntimeException(wbce_two_factor_t('session_unavailable', array(), 'two_factor_webauthn'));
        $value = self::b64e(random_bytes(32));
        $_SESSION['WBCE_WEBAUTHN_'.$purpose] = array('value' => $value, 'expires' => time() + 300);
        return $value;
    }
    private function consumeChallenge($purpose, $given)
    {
        $key = 'WBCE_WEBAUTHN_'.$purpose;
        $saved = $_SESSION[$key] ?? null;
        unset($_SESSION[$key]);
        return is_array($saved) && (int)($saved['expires'] ?? 0) >= time() && is_string($given) && hash_equals((string)($saved['value'] ?? ''), $given);
    }
    public function registrationOptions($userId, $name, $display)
    {
        $exclude = array();
        foreach ($this->credentials($userId) as $item) $exclude[] = array('type' => 'public-key', 'id' => $item['credential_id']);
        return array('challenge' => $this->challenge('CREATE'), 'rp' => array('name' => defined('WEBSITE_TITLE') ? WEBSITE_TITLE : 'WBCE', 'id' => $this->rpId()), 'user' => array('id' => self::b64e(pack('N', (int)$userId)), 'name' => (string)$name, 'displayName' => (string)$display), 'pubKeyCredParams' => array(array('type' => 'public-key', 'alg' => -7)), 'timeout' => 60000, 'attestation' => 'none', 'authenticatorSelection' => array('residentKey' => 'preferred', 'userVerification' => $this->setting('user_verification', 'preferred')), 'excludeCredentials' => $exclude);
    }
    private function clientData($encoded, $type, $purpose)
    {
        $raw = self::b64d($encoded);
        $data = is_string($raw) ? json_decode($raw, true) : null;
        if (!is_array($data) || ($data['type'] ?? '') !== $type || ($data['crossOrigin'] ?? false) === true || !$this->consumeChallenge($purpose, $data['challenge'] ?? null) || !hash_equals($this->origin(), rtrim((string)($data['origin'] ?? ''), '/'))) throw new RuntimeException(wbce_two_factor_t('request_invalid', array(), 'two_factor_webauthn'));
        return $raw;
    }
    public function register($userId, array $payload, $label)
    {
        if (($payload['type'] ?? '') !== 'public-key') throw new RuntimeException(wbce_two_factor_t('registration_invalid', array(), 'two_factor_webauthn'));
        $this->clientData($payload['response']['clientDataJSON'] ?? '', 'webauthn.create', 'CREATE');
        $attestation = self::b64d($payload['response']['attestationObject'] ?? '');
        if (!is_string($attestation) || strlen($attestation) > 1048576) throw new RuntimeException(wbce_two_factor_t('registration_invalid', array(), 'two_factor_webauthn'));
        $offset = 0;
        $decoded = WbceWebAuthnCbor::decode($attestation, $offset);
        $auth = is_array($decoded) ? ($decoded['authData'] ?? '') : '';
        if (!is_string($auth) || strlen($auth) < 55 || !hash_equals(hash('sha256', $this->rpId(), true), substr($auth, 0, 32)) || (ord($auth[32]) & 0x41) !== 0x41) throw new RuntimeException(wbce_two_factor_t('registration_invalid', array(), 'two_factor_webauthn'));
        $offset = 53;
        $lengthData = unpack('nlength', substr($auth, $offset, 2));
        $idLength = (int)$lengthData['length'];
        $offset += 2;
        if ($idLength < 1 || $idLength > 1023 || $offset + $idLength >= strlen($auth)) throw new RuntimeException(wbce_two_factor_t('registration_invalid', array(), 'two_factor_webauthn'));
        $credentialId = substr($auth, $offset, $idLength);
        $offset += $idLength;
        $cose = WbceWebAuthnCbor::decode($auth, $offset);
        if (!is_array($cose) || ($cose[1] ?? null) !== 2 || ($cose[3] ?? null) !== -7 || ($cose[-1] ?? null) !== 1 || !is_string($cose[-2] ?? null) || strlen($cose[-2]) !== 32 || !is_string($cose[-3] ?? null) || strlen($cose[-3]) !== 32) throw new RuntimeException(wbce_two_factor_t('algorithm_unsupported', array(), 'two_factor_webauthn'));
        $point = "\x04".$cose[-2].$cose[-3];
        $pem = "-----BEGIN PUBLIC KEY-----\n".chunk_split(base64_encode(hex2bin('3059301306072A8648CE3D020106082A8648CE3D030107034200').$point), 64, "\n")."-----END PUBLIC KEY-----\n";
        $id = self::b64e($credentialId);
        if (!hash_equals($id, (string)($payload['id'] ?? ''))) throw new RuntimeException(wbce_two_factor_t('credential_mismatch', array(), 'two_factor_webauthn'));
        $label = trim((string)$label) ?: wbce_two_factor_t('default_label', array(), 'two_factor_webauthn');
        $label = function_exists('mb_substr') ? mb_substr($label,0,190,'UTF-8') : substr($label,0,190);
        $ok = $this->db->query("INSERT INTO `{TP}mod_two_factor_webauthn_credentials` (`user_id`,`credential_id`,`public_key`,`sign_count`,`label`,`created_at`) VALUES (".(int)$userId.",'".$this->db->escapeString($id)."','".$this->db->escapeString($pem)."',0,'".$this->db->escapeString($label)."',".time().")");
        if (!$ok) throw new RuntimeException(wbce_two_factor_t('credential_save_failed', array(), 'two_factor_webauthn'));
        return true;
    }
    public function assertionOptions($userId)
    {
        $allow = array();
        foreach ($this->credentials($userId) as $item) $allow[] = array('type' => 'public-key', 'id' => $item['credential_id']);
        return array('challenge' => $this->challenge('GET'), 'rpId' => $this->rpId(), 'timeout' => 60000, 'userVerification' => $this->setting('user_verification', 'preferred'), 'allowCredentials' => $allow);
    }
    public function verify($userId, array $payload)
    {
        if (($payload['type'] ?? '') !== 'public-key') return false;
        try { $clientRaw = $this->clientData($payload['response']['clientDataJSON'] ?? '', 'webauthn.get', 'GET'); } catch (Throwable $error) { return false; }
        $idValue = (string)($payload['id'] ?? '');
        if ($idValue === '' || strlen($idValue) > 1024) return false;
        $result = $this->db->query("SELECT * FROM `{TP}mod_two_factor_webauthn_credentials` WHERE `user_id`=".(int)$userId." AND `credential_id`='".$this->db->escapeString($idValue)."' LIMIT 1");
        $row = $result ? $result->fetchRow(MYSQLI_ASSOC) : null;
        if (!$row) return false;
        $auth = self::b64d($payload['response']['authenticatorData'] ?? '');
        $signature = self::b64d($payload['response']['signature'] ?? '');
        if (!is_string($auth) || !is_string($signature) || strlen($auth) < 37 || !hash_equals(hash('sha256', $this->rpId(), true), substr($auth, 0, 32)) || (ord($auth[32]) & 1) !== 1) return false;
        if ($this->setting('user_verification', 'preferred') === 'required' && (ord($auth[32]) & 4) !== 4) return false;
        $counter = unpack('Ncount', substr($auth, 33, 4));
        $count = (int)$counter['count'];
        if ((int)$row['sign_count'] > 0 && $count > 0 && $count <= (int)$row['sign_count']) return false;
        $valid = openssl_verify($auth.hash('sha256', $clientRaw, true), $signature, $row['public_key'], OPENSSL_ALGO_SHA256) === 1;
        if ($valid) $this->db->query('UPDATE `{TP}mod_two_factor_webauthn_credentials` SET `sign_count`='.(int)$count.',`last_used_at`='.time().' WHERE `id`='.(int)$row['id']);
        return $valid;
    }
    public function remove($userId, $id)
    {
        return (bool)$this->db->query('DELETE FROM `{TP}mod_two_factor_webauthn_credentials` WHERE `user_id`='.(int)$userId.' AND `id`='.(int)$id);
    }
}
