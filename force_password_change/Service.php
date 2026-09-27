<?php

require_once __DIR__ . '/Compatibility.php';
require_once __DIR__ . '/Language.php';

final class WbceForcePasswordChangeService
{
    private $database;

    public function __construct($database)
    {
        if (!is_object($database)) throw new RuntimeException(fpc_t('database_unavailable'));
        $this->database = $database;
    }

    public static function installSchema($database)
    {
        return WbceForcePasswordChangeCompatibility::execute($database, "CREATE TABLE IF NOT EXISTS `{TP}mod_force_password_change` (
            `user_id` INT UNSIGNED NOT NULL,
            `required` TINYINT(1) UNSIGNED NOT NULL DEFAULT 1,
            `required_at` INT UNSIGNED NOT NULL DEFAULT 0,
            `required_by` INT UNSIGNED NOT NULL DEFAULT 0,
            `completed_at` INT UNSIGNED NULL,
            PRIMARY KEY (`user_id`)
        )");
    }

    public function isRequired($userId)
    {
        return (int) $userId > 0 && (int) WbceForcePasswordChangeCompatibility::value($this->database,
            'SELECT COUNT(*) FROM `{TP}mod_force_password_change` WHERE `user_id`=' . (int) $userId . ' AND `required`=1'
        ) > 0;
    }

    public function setRequired($userId, $required, $actorId)
    {
        $userId = (int) $userId;
        if ($userId < 1) return false;
        if (!$required) return $this->clear($userId);
        $now = time();
        return WbceForcePasswordChangeCompatibility::execute($this->database,
            "INSERT INTO `{TP}mod_force_password_change` (`user_id`,`required`,`required_at`,`required_by`,`completed_at`) VALUES ("
            . $userId . ",1," . $now . "," . (int) $actorId . ",NULL) ON DUPLICATE KEY UPDATE "
            . "`required`=1,`required_at`=" . $now . ",`required_by`=" . (int) $actorId . ",`completed_at`=NULL"
        );
    }

    public function clear($userId)
    {
        return WbceForcePasswordChangeCompatibility::execute($this->database,
            'UPDATE `{TP}mod_force_password_change` SET `required`=0,`completed_at`=' . time() . ' WHERE `user_id`=' . (int) $userId
        );
    }

    public function savePassword($userId, $encodedPassword)
    {
        return WbceForcePasswordChangeCompatibility::execute($this->database,
            "UPDATE `{TP}users` SET `password`='" . WbceForcePasswordChangeCompatibility::escape($this->database, $encodedPassword)
            . "' WHERE `user_id`=" . (int) $userId
        );
    }

    public function replacePasswordAndClear($userId, $encodedPassword)
    {
        if (!WbceForcePasswordChangeCompatibility::execute($this->database, 'START TRANSACTION')) return false;
        try {
            if (!$this->savePassword($userId, $encodedPassword) || !$this->clear($userId)) {
                WbceForcePasswordChangeCompatibility::execute($this->database, 'ROLLBACK');
                return false;
            }
            if (!WbceForcePasswordChangeCompatibility::execute($this->database, 'COMMIT')) {
                WbceForcePasswordChangeCompatibility::execute($this->database, 'ROLLBACK');
                return false;
            }
            return true;
        } catch (Throwable $error) {
            WbceForcePasswordChangeCompatibility::execute($this->database, 'ROLLBACK');
            return false;
        }
    }

    public function removeUser($userId)
    {
        return WbceForcePasswordChangeCompatibility::execute($this->database,
            'DELETE FROM `{TP}mod_force_password_change` WHERE `user_id`=' . (int) $userId
        );
    }
}
