<?php
require_once WB_PATH.'/modules/two_factor/Language.php';

require_once __DIR__ . '/Totp.php';

final class WbceTotpService
{
    private $database;
    public function __construct($database)
    {
        if (!is_object($database)) {
            throw new RuntimeException(wbce_two_factor_t('database_unavailable',array(),'two_factor_totp'));
        }
        $this->database = $database;
    }

    public function isEnabled($userId)
    {
        $result = $this->database->query(sprintf(
            'SELECT COUNT(*) AS `amount` FROM `{TP}mod_two_factor_totp` WHERE `user_id` = %d AND `enabled` = 1',
            (int)$userId
        ));
        $row = $result ? $result->fetchRow(MYSQLI_ASSOC) : null;
        return $row && (int)$row['amount'] > 0;
    }

    public function countEnabled()
    {
        $result = $this->database->query('SELECT COUNT(*) AS `amount` FROM `{TP}mod_two_factor_totp` WHERE `enabled` = 1');
        if (!$result) {
            return 0;
        }
        $row = $result->fetchRow(MYSQLI_ASSOC);
        return $row ? (int)$row['amount'] : 0;
    }

    public function enable($userId, $secret)
    {
        $userId = (int)$userId;
        $encrypted = $this->database->escapeString($this->encrypt($secret));
        $now = time();
        $saved = $this->database->query("INSERT INTO `{TP}mod_two_factor_totp`
            (`user_id`, `secret`, `enabled`, `last_counter`, `created_at`, `confirmed_at`)
            VALUES ($userId, '$encrypted', 1, -1, $now, $now)
            ON DUPLICATE KEY UPDATE `secret`='$encrypted', `enabled`=1,
                `last_counter`=-1, `created_at`=$now, `confirmed_at`=$now");
        if (!$saved || !$this->isEnabled($userId)) {
            throw new RuntimeException(wbce_two_factor_t('data_save_failed',array(),'two_factor_totp'));
        }
        return $this->replaceRecoveryCodes($userId);
    }

    public function disable($userId)
    {
        $userId = (int)$userId;
        $this->database->query("DELETE FROM `{TP}mod_two_factor_totp_recovery` WHERE `user_id` = $userId");
        $this->database->query("DELETE FROM `{TP}mod_two_factor_totp` WHERE `user_id` = $userId");
    }

    public function verify($userId, $code)
    {
        $userId = (int)$userId;
        if (!$this->canAttempt($userId)) {
            return false;
        }
        $result = $this->database->query("SELECT `secret`, `last_counter` FROM `{TP}mod_two_factor_totp` WHERE `user_id` = $userId AND `enabled` = 1");
        $row = $result ? $result->fetchRow(MYSQLI_ASSOC) : null;
        if ($row) {
            $counter = null;
            try {
                if (WbceTotp::verify($this->decrypt($row['secret']), $code, null, 1, $counter)
                    && $counter > (int)$row['last_counter']) {
                    $this->database->query("UPDATE `{TP}mod_two_factor_totp` SET `last_counter` = " . (int)$counter . " WHERE `user_id` = $userId");
                    $this->clearAttempts($userId);
                    return true;
                }
            } catch (Throwable $exception) {
                // Do not turn an invalid or no longer decryptable factor into a
                // blank login page. Recovery codes remain usable below.
            }
        }
        if ($this->consumeRecoveryCode($userId, $code)) {
            $this->clearAttempts($userId);
            return true;
        }
        $this->recordFailedAttempt($userId);
        return false;
    }

    public function replaceRecoveryCodes($userId)
    {
        $userId = (int)$userId;
        $this->database->query("DELETE FROM `{TP}mod_two_factor_totp_recovery` WHERE `user_id` = $userId");
        $codes = array();
        for ($i = 0; $i < 10; $i++) {
            $raw = strtoupper(substr(bin2hex(random_bytes(5)), 0, 5) . '-' . substr(bin2hex(random_bytes(5)), 0, 5));
            $codes[] = $raw;
            $inserted = $this->database->insertRow('{TP}mod_two_factor_totp_recovery', array(
                'user_id' => $userId,
                'code_hash' => password_hash($raw, PASSWORD_DEFAULT),
                'used_at' => null,
            ));
            if ($inserted !== true) {
                throw new RuntimeException(wbce_two_factor_t('backup_save_failed',array(),'two_factor_totp'));
            }
        }
        return $codes;
    }

    private function consumeRecoveryCode($userId, $code)
    {
        $result = $this->database->query(sprintf(
            'SELECT `id`, `code_hash` FROM `{TP}mod_two_factor_totp_recovery` WHERE `user_id` = %d AND `used_at` IS NULL',
            (int)$userId
        ));
        while ($result && ($row = $result->fetchRow(MYSQLI_ASSOC))) {
            if (password_verify(strtoupper(trim((string)$code)), $row['code_hash'])) {
                $this->database->query(sprintf(
                    'UPDATE `{TP}mod_two_factor_totp_recovery` SET `used_at` = %d WHERE `id` = %d AND `used_at` IS NULL',
                    time(), (int)$row['id']
                ));
                return true;
            }
        }
        return false;
    }

    private function canAttempt($userId)
    {
        $result = $this->database->query(sprintf(
            'SELECT `window_start`, `attempts` FROM `{TP}mod_two_factor_totp_attempt` WHERE `user_id` = %d',
            (int)$userId
        ));
        $row = $result ? $result->fetchRow(MYSQLI_ASSOC) : null;
        return !$row || (int)$row['window_start'] < time() - 300 || (int)$row['attempts'] < 5;
    }

    private function recordFailedAttempt($userId)
    {
        $userId = (int)$userId;
        $now = time();
        $result = $this->database->query("SELECT `window_start` FROM `{TP}mod_two_factor_totp_attempt` WHERE `user_id` = $userId");
        $row = $result ? $result->fetchRow(MYSQLI_ASSOC) : null;
        if (!$row) {
            $this->database->insertRow('{TP}mod_two_factor_totp_attempt', array(
                'user_id' => $userId, 'window_start' => $now, 'attempts' => 1,
            ));
        } elseif ((int)$row['window_start'] < $now - 300) {
            $this->database->query("UPDATE `{TP}mod_two_factor_totp_attempt` SET `window_start` = $now, `attempts` = 1 WHERE `user_id` = $userId");
        } else {
            $this->database->query("UPDATE `{TP}mod_two_factor_totp_attempt` SET `attempts` = `attempts` + 1 WHERE `user_id` = $userId");
        }
    }

    private function clearAttempts($userId)
    {
        $this->database->query(sprintf(
            'DELETE FROM `{TP}mod_two_factor_totp_attempt` WHERE `user_id` = %d',
            (int)$userId
        ));
    }

    private function encrypt($plaintext)
    {
        $key = $this->key();
        if (function_exists('sodium_crypto_secretbox')) {
            $nonce = random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
            return 'sodium:' . base64_encode($nonce . sodium_crypto_secretbox($plaintext, $nonce, $key));
        }
        if (!function_exists('openssl_encrypt')) {
            throw new RuntimeException(wbce_two_factor_t('encryption_unavailable',array(),'two_factor_totp'));
        }
        $iv = random_bytes(12);
        $tag = '';
        $ciphertext = openssl_encrypt($plaintext, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag);
        return 'openssl:' . base64_encode($iv . $tag . $ciphertext);
    }

    private function decrypt($payload)
    {
        if (!is_string($payload) || strpos($payload, ':') === false) {
            throw new RuntimeException(wbce_two_factor_t('decrypt_failed',array(),'two_factor_totp'));
        }
        list($method, $encoded) = explode(':', $payload, 2);
        $data = base64_decode($encoded, true);
        if (!is_string($data)) {
            throw new RuntimeException(wbce_two_factor_t('decrypt_failed',array(),'two_factor_totp'));
        }
        $key = $this->key();
        if ($method === 'sodium') {
            if (!function_exists('sodium_crypto_secretbox_open') || strlen($data) <= SODIUM_CRYPTO_SECRETBOX_NONCEBYTES) {
                throw new RuntimeException(wbce_two_factor_t('decrypt_failed',array(),'two_factor_totp'));
            }
            $nonceLength = SODIUM_CRYPTO_SECRETBOX_NONCEBYTES;
            $plain = sodium_crypto_secretbox_open(substr($data, $nonceLength), substr($data, 0, $nonceLength), $key);
        } elseif ($method === 'openssl' && function_exists('openssl_decrypt') && strlen($data) > 28) {
            $plain = openssl_decrypt(substr($data, 28), 'aes-256-gcm', $key, OPENSSL_RAW_DATA, substr($data, 0, 12), substr($data, 12, 16));
        } else {
            $plain = false;
        }
        if (!is_string($plain)) {
            throw new RuntimeException(wbce_two_factor_t('decrypt_failed',array(),'two_factor_totp'));
        }
        return $plain;
    }

    private function key()
    {
        $file = __DIR__ . '/key.php';
        $key = false;
        if (is_file($file)) {
            try {
                $key = base64_decode((string) require $file, true);
            } catch (Throwable $ignored) {
                $key = false;
            }
        }
        $fileKeyValid = is_string($key) && strlen($key) === 32;
        // Migrate the legacy database key without changing existing secrets.
        if (!is_string($key) || strlen($key) !== 32) {
            $result = $this->database->query("SELECT `value` FROM `{TP}mod_two_factor_totp_settings` WHERE `name`='encryption_key' LIMIT 1");
            $row = $result ? $result->fetchRow(MYSQLI_ASSOC) : null;
            $legacy = $row ? base64_decode((string)$row['value'], true) : false;
            if (is_string($legacy) && strlen($legacy) === 32) {
                $key = $legacy;
            }
        }
        if (!is_string($key) || strlen($key) !== 32) {
            $key = random_bytes(32);
        }
        $contents = "<?php\ndefined('WB_PATH') or die('No direct access');\nreturn '" . base64_encode($key) . "';\n";
        if (!$fileKeyValid && file_put_contents($file, $contents, LOCK_EX) === false) {
            throw new RuntimeException(wbce_two_factor_t('key_invalid',array(),'two_factor_totp'));
        }
        @chmod($file, 0600);
        $persisted = is_file($file) ? base64_decode((string) require $file, true) : false;
        if (is_string($persisted) && strlen($persisted) === 32 && hash_equals($key, $persisted)) {
            $this->database->query("DELETE FROM `{TP}mod_two_factor_totp_settings` WHERE `name`='encryption_key'");
        } else {
            throw new RuntimeException(wbce_two_factor_t('key_invalid',array(),'two_factor_totp'));
        }
        if (!is_string($key) || strlen($key) !== 32) {
            throw new RuntimeException(wbce_two_factor_t('key_invalid',array(),'two_factor_totp'));
        }
        return $key;
    }

    public static function installStorage($database)
    {
        $tables = array(
            "CREATE TABLE IF NOT EXISTS `{TP}mod_two_factor_totp` (`user_id` INT NOT NULL, `secret` TEXT NOT NULL, `enabled` TINYINT(1) NOT NULL DEFAULT 0, `last_counter` BIGINT NOT NULL DEFAULT -1, `created_at` INT NOT NULL, `confirmed_at` INT NULL, PRIMARY KEY (`user_id`))",
            "CREATE TABLE IF NOT EXISTS `{TP}mod_two_factor_totp_recovery` (`id` INT NOT NULL AUTO_INCREMENT, `user_id` INT NOT NULL, `code_hash` VARCHAR(255) NOT NULL, `used_at` INT NULL, PRIMARY KEY (`id`), INDEX `user_id_unused` (`user_id`, `used_at`))",
            "CREATE TABLE IF NOT EXISTS `{TP}mod_two_factor_totp_attempt` (`user_id` INT NOT NULL, `window_start` INT NOT NULL, `attempts` INT NOT NULL DEFAULT 0, PRIMARY KEY (`user_id`))",
            "CREATE TABLE IF NOT EXISTS `{TP}mod_two_factor_totp_settings` (`name` VARCHAR(80) NOT NULL, `value` TEXT NOT NULL, PRIMARY KEY (`name`))",
        );
        foreach ($tables as $sql) {
            if (!$database->query($sql)) {
                throw new RuntimeException(wbce_two_factor_t('tables_missing',array(),'two_factor_totp'));
            }
        }
    }
}
