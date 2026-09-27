<?php
require_once WB_PATH.'/modules/two_factor/Language.php';

final class WbceWhatsAppFactorService
{
    private $db;
    public function __construct($db)
    {
        if (!is_object($db) || !method_exists($db, 'query')) throw new RuntimeException(wbce_two_factor_t('database_unavailable', array(), 'two_factor_whatsapp'));
        $this->db = $db;
    }
    public static function install($db)
    {
        if (!is_object($db) || !method_exists($db, 'query')) return false;
        $a = $db->query("CREATE TABLE IF NOT EXISTS `{TP}mod_two_factor_whatsapp_settings` (`name` varchar(80) NOT NULL,`value` text NOT NULL,PRIMARY KEY(`name`)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        $b = $db->query("CREATE TABLE IF NOT EXISTS `{TP}mod_two_factor_whatsapp_users` (`user_id` int NOT NULL,`phone` varchar(32) NOT NULL,`pending_phone` varchar(32) DEFAULT NULL,`enabled` tinyint(1) NOT NULL DEFAULT 0,`confirmed_at` int DEFAULT NULL,PRIMARY KEY(`user_id`)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        if ($b && !self::columnExists($db, 'mod_two_factor_whatsapp_users', 'pending_phone')) $db->query("ALTER TABLE `{TP}mod_two_factor_whatsapp_users` ADD `pending_phone` varchar(32) DEFAULT NULL AFTER `phone`");
        $c = $db->query("CREATE TABLE IF NOT EXISTS `{TP}mod_two_factor_whatsapp_codes` (`user_id` int NOT NULL,`code_hash` varchar(255) NOT NULL,`expires_at` int NOT NULL,`attempts` int NOT NULL DEFAULT 0,`sent_at` int NOT NULL,PRIMARY KEY(`user_id`)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        return (bool)($a && $b && $c);
    }
    private static function columnExists($db, $table, $column)
    {
        $result = $db->query("SHOW COLUMNS FROM `{TP}".$table."` LIKE '".$db->escapeString($column)."'");
        return $result && (bool)$result->fetchRow(MYSQLI_ASSOC);
    }
    private function esc($value) { return $this->db->escapeString((string)$value); }
    public function setting($name, $default = '')
    {
        $result = $this->db->query("SELECT `value` FROM `{TP}mod_two_factor_whatsapp_settings` WHERE `name`='".$this->esc($name)."' LIMIT 1");
        $row = $result ? $result->fetchRow(MYSQLI_ASSOC) : null;
        $value = $row ? (string)$row['value'] : $default;
        return $name === 'access_token' && $value !== '' ? $this->decrypt($value) : $value;
    }
    public function setSetting($name, $value)
    {
        if ($name === 'access_token' && $value !== '') $value = $this->encrypt($value);
        return (bool)$this->db->query("INSERT INTO `{TP}mod_two_factor_whatsapp_settings` (`name`,`value`) VALUES ('".$this->esc($name)."','".$this->esc($value)."') ON DUPLICATE KEY UPDATE `value`=VALUES(`value`)");
    }
    private function legacyKey() { return hash('sha256', (defined('DB_PASSWORD') ? DB_PASSWORD : '').'|'.(defined('WB_PATH') ? WB_PATH : __DIR__), true); }
    private function key()
    {
        $file = __DIR__.'/key.php';
        if (is_file($file)) {
            $encoded = include $file;
            $key = is_string($encoded) ? base64_decode($encoded, true) : false;
            if (is_string($key) && strlen($key) === 32) return $key;
        }
        $key = random_bytes(32);
        $content = "<?php\ndefined('WB_PATH') or die('No direct access');\nreturn '".base64_encode($key)."';\n";
        if (@file_put_contents($file, $content, LOCK_EX) === false) throw new RuntimeException(wbce_two_factor_t('key_save_failed', array(), 'two_factor_whatsapp'));
        @chmod($file, 0600);
        return $key;
    }
    private function encrypt($value)
    {
        if (!function_exists('openssl_encrypt')) throw new RuntimeException(wbce_two_factor_t('crypto_missing', array(), 'two_factor_whatsapp'));
        $iv = random_bytes(12); $tag = '';
        $cipher = openssl_encrypt((string)$value, 'aes-256-gcm', $this->key(), OPENSSL_RAW_DATA, $iv, $tag);
        if ($cipher === false) throw new RuntimeException(wbce_two_factor_t('encrypt_failed', array(), 'two_factor_whatsapp'));
        return 'gcm2:'.base64_encode($iv.$tag.$cipher);
    }
    private function decrypt($value)
    {
        $isCurrent = strpos($value, 'gcm2:') === 0;
        $isLegacy = strpos($value, 'gcm:') === 0;
        if (!$isCurrent && !$isLegacy) return $value;
        $data = base64_decode(substr($value, $isCurrent ? 5 : 4), true);
        if (!is_string($data) || strlen($data) < 29 || !function_exists('openssl_decrypt')) throw new RuntimeException(wbce_two_factor_t('token_corrupt', array(), 'two_factor_whatsapp'));
        $plain = openssl_decrypt(substr($data, 28), 'aes-256-gcm', $isCurrent ? $this->key() : $this->legacyKey(), OPENSSL_RAW_DATA, substr($data, 0, 12), substr($data, 12, 16));
        if (!is_string($plain)) throw new RuntimeException(wbce_two_factor_t('decrypt_failed', array(), 'two_factor_whatsapp'));
        return $plain;
    }
    public function isConfigured() { return $this->setting('phone_number_id') !== '' && $this->setting('access_token') !== '' && $this->setting('template_name') !== ''; }
    public function isEnabled($userId)
    {
        $result = $this->db->query('SELECT `enabled` FROM `{TP}mod_two_factor_whatsapp_users` WHERE `user_id`='.(int)$userId);
        $row = $result ? $result->fetchRow(MYSQLI_ASSOC) : null;
        return $row && !empty($row['enabled']);
    }
    public function usage()
    {
        $result = $this->db->query('SELECT COUNT(*) AS amount FROM `{TP}mod_two_factor_whatsapp_users` WHERE `enabled`=1');
        $row = $result ? $result->fetchRow(MYSQLI_ASSOC) : null;
        return $row ? (int)$row['amount'] : 0;
    }
    public function phone($userId, $pending = false)
    {
        $column = $pending ? 'pending_phone' : 'phone';
        $result = $this->db->query('SELECT `'.$column.'` FROM `{TP}mod_two_factor_whatsapp_users` WHERE `user_id`='.(int)$userId);
        $row = $result ? $result->fetchRow(MYSQLI_ASSOC) : null;
        return $row ? (string)($row[$column] ?? '') : '';
    }
    public function stagePhone($userId, $phone)
    {
        $phone = preg_replace('/[^0-9]/', '', (string)$phone);
        if (strlen($phone) < 8 || strlen($phone) > 15) throw new RuntimeException(wbce_two_factor_t('phone_invalid', array(), 'two_factor_whatsapp'));
        $ok = $this->db->query("INSERT INTO `{TP}mod_two_factor_whatsapp_users` (`user_id`,`phone`,`pending_phone`,`enabled`,`confirmed_at`) VALUES (".(int)$userId.",'','".$this->esc($phone)."',0,NULL) ON DUPLICATE KEY UPDATE `pending_phone`=VALUES(`pending_phone`)");
        if (!$ok) throw new RuntimeException(wbce_two_factor_t('phone_save_failed', array(), 'two_factor_whatsapp'));
    }
    public function disable($userId)
    {
        $this->db->query('DELETE FROM `{TP}mod_two_factor_whatsapp_codes` WHERE `user_id`='.(int)$userId);
        $this->db->query('DELETE FROM `{TP}mod_two_factor_whatsapp_users` WHERE `user_id`='.(int)$userId);
        require_once WB_PATH.'/modules/two_factor/Settings.php';
        if (WbceTwoFactorSettings::userProvider($this->db, $userId) === 'whatsapp') WbceTwoFactorSettings::setUserProvider($this->db, $userId, '');
    }
    public function sendCode($userId, $force = false, $pending = false)
    {
        if (!$this->isConfigured()) throw new RuntimeException(wbce_two_factor_t('not_configured', array(), 'two_factor_whatsapp'));
        $now = time();
        $result = $this->db->query('SELECT `expires_at`,`sent_at` FROM `{TP}mod_two_factor_whatsapp_codes` WHERE `user_id`='.(int)$userId);
        $old = $result ? $result->fetchRow(MYSQLI_ASSOC) : null;
        if (!$force && $old && (int)$old['expires_at'] > $now && (int)$old['sent_at'] > $now - 60) return;
        $phone = $this->phone($userId, $pending);
        if ($phone === '') throw new RuntimeException(wbce_two_factor_t('user_phone_missing', array(), 'two_factor_whatsapp'));
        $code = (string)random_int(100000, 999999);
        $this->deliver($phone, $code);
        $hash = password_hash($code, PASSWORD_DEFAULT);
        $ok = $this->db->query("INSERT INTO `{TP}mod_two_factor_whatsapp_codes` (`user_id`,`code_hash`,`expires_at`,`attempts`,`sent_at`) VALUES (".(int)$userId.",'".$this->esc($hash)."',".($now + 300).",0,$now) ON DUPLICATE KEY UPDATE `code_hash`=VALUES(`code_hash`),`expires_at`=VALUES(`expires_at`),`attempts`=0,`sent_at`=VALUES(`sent_at`)");
        if (!$ok) throw new RuntimeException(wbce_two_factor_t('code_save_failed', array(), 'two_factor_whatsapp'));
    }
    private function deliver($phone, $code)
    {
        if (!function_exists('curl_init')) throw new RuntimeException(wbce_two_factor_t('curl_missing', array(), 'two_factor_whatsapp'));
        $version = (string)$this->setting('api_version', '23.0');
        $numberId = (string)$this->setting('phone_number_id');
        if (!preg_match('/^[0-9]+(?:\.[0-9]+)?$/', $version) || !preg_match('/^[0-9]+$/', $numberId)) throw new RuntimeException(wbce_two_factor_t('config_invalid', array(), 'two_factor_whatsapp'));
        $payload = array('messaging_product'=>'whatsapp','to'=>$phone,'type'=>'template','template'=>array('name'=>$this->setting('template_name'),'language'=>array('code'=>$this->setting('template_language','de')),'components'=>array(array('type'=>'body','parameters'=>array(array('type'=>'text','text'=>$code))))));
        $handle = curl_init('https://graph.facebook.com/v'.$version.'/'.$numberId.'/messages');
        curl_setopt_array($handle, array(CURLOPT_POST=>true,CURLOPT_RETURNTRANSFER=>true,CURLOPT_CONNECTTIMEOUT=>5,CURLOPT_TIMEOUT=>15,CURLOPT_HTTPHEADER=>array('Authorization: Bearer '.$this->setting('access_token'),'Content-Type: application/json'),CURLOPT_POSTFIELDS=>json_encode($payload)));
        $body = curl_exec($handle); $status = (int)curl_getinfo($handle, CURLINFO_HTTP_CODE); $error = curl_error($handle);
        if ($body === false || $status < 200 || $status >= 300) throw new RuntimeException(wbce_two_factor_t('delivery_failed', array('details'=>$error !== '' ? ': '.$error : ' (HTTP '.$status.')'), 'two_factor_whatsapp'));
    }
    public function verify($userId, $code, $activate = false)
    {
        $result = $this->db->query('SELECT `code_hash`,`expires_at`,`attempts` FROM `{TP}mod_two_factor_whatsapp_codes` WHERE `user_id`='.(int)$userId);
        $row = $result ? $result->fetchRow(MYSQLI_ASSOC) : null;
        $code = trim((string)$code);
        if (!$row || !preg_match('/^[0-9]{6}$/', $code) || (int)$row['expires_at'] < time() || (int)$row['attempts'] >= 5) {
            if ($row && ((int)$row['expires_at'] < time() || (int)$row['attempts'] >= 5)) $this->db->query('DELETE FROM `{TP}mod_two_factor_whatsapp_codes` WHERE `user_id`='.(int)$userId);
            return false;
        }
        $this->db->query('UPDATE `{TP}mod_two_factor_whatsapp_codes` SET `attempts`=`attempts`+1 WHERE `user_id`='.(int)$userId);
        if (!password_verify($code, $row['code_hash'])) return false;
        $this->db->query('DELETE FROM `{TP}mod_two_factor_whatsapp_codes` WHERE `user_id`='.(int)$userId);
        if ($activate) {
            $ok = $this->db->query("UPDATE `{TP}mod_two_factor_whatsapp_users` SET `phone`=COALESCE(NULLIF(`pending_phone`,''),`phone`),`pending_phone`=NULL,`enabled`=1,`confirmed_at`=".time().' WHERE `user_id`='.(int)$userId);
            if (!$ok) return false;
            require_once WB_PATH.'/modules/two_factor/Settings.php';
            WbceTwoFactorSettings::setUserProvider($this->db, $userId, 'whatsapp');
        }
        return true;
    }
}
