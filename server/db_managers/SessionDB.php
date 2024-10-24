<?php
namespace db_managers;

use PDO;
use PDOException;

class SessionDB extends DB
{
    public function __construct() {
        try {
            parent::__construct();
            $this->db->exec("
            CREATE TABLE IF NOT EXISTS sessions (
                `session_id` VARCHAR(255) NOT NULL,
                `password_hash` TEXT NOT NULL,
                `wallet_id` BIGINT NOT NULL,
                `uuid` TEXT NOT NULL,
                `create_time` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `login_time` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `expire_time` TIMESTAMP NOT NULL,
                `auto_login` BOOLEAN NOT NULL DEFAULT 0,
                `browser` TEXT NOT NULL,
                `user_os` TEXT NOT NULL,
                PRIMARY KEY (`session_id`)
            );");
        }catch(PDOException $e) {
            $this->createLog("SessionDB", $e);
            $this->die = true;
        }
    }
    public function createSession(string $password, int $wallet_id, string $uuid, string $browser, string $user_os): string {
        try {
            $hash_session = hash('sha3-256', "UUID_".$uuid.";login_".$wallet_id.";timestamp_".time());
            if ($this->existsSession($hash_session)) {
                return $hash_session;
            }
            $hash_password = hash('sha3-512', $password);
            $prepare = $this->db->prepare("INSERT INTO `sessions` (`session_id`, `password_hash`, `wallet_id`, `uuid`, `create_time`, `login_time`, `expire_time`, `auto_login`, `browser`, `user_os`)
            VALUES (:session, :password, :id, :uuid, DEFAULT, DEFAULT, TIMESTAMPADD(DAY, 7, CURRENT_TIMESTAMP), DEFAULT, :browser, :user_os);");
            $prepare->bindValue(':session', $hash_session);
            $prepare->bindValue(':password', $hash_password);
            $prepare->bindValue(":id", $wallet_id);
            $prepare->bindValue(':uuid', $uuid);
            $prepare->bindValue(':browser', $browser);
            $prepare->bindValue(':user_os', $user_os);
            return $prepare->execute() ? $hash_session : "err_1";
        }catch(PDOException $e) {
            $this->createLog("SessionDB", $e);
            return "err_2";
        }
    }
    public function removeSession(string $session_id): int {
        try {
            $prepare = $this->db->prepare("DELETE FROM `sessions` WHERE `session_id` = :session_id;");
            $prepare->bindValue(':session_id', $session_id);
            return $prepare->execute();
        }catch(PDOException $e) {
            $this->createLog("SessionDB", $e);
            return -1;
        }
    }
    public function removeOutdatedSessions(): int {
        try {
            return $this->db->prepare("DELETE FROM `sessions` WHERE unix_timestamp(`expire_time`) < CURRENT_TIMESTAMP;")->execute();
        }catch(PDOException $e) {
            $this->createLog("SessionDB", $e);
            return -1;
        }
    }
    public function validateSession(string $session_id, string $uuid): int {
        try {
            $prepare = $this->db->prepare("SELECT `wallet_id`,`password_hash`,unix_timestamp(`expire_time`),`uuid` FROM `sessions` WHERE `session_id` = :session_id;");
            $prepare->bindValue(':session_id', $session_id);
            if ($prepare->execute()) {
                $fetch = $prepare->fetch(PDO::FETCH_ASSOC);
                if ($fetch !== false && isset($fetch['password_hash'])) {
                    if ($uuid === $fetch['uuid']) {
                        if ($fetch['unix_timestamp(`expire_time`)'] > time()) {
                            $response_auth = (new AccountsDB())->account_auth_id($fetch['wallet_id'], $fetch['password_hash'], false);
                            return $response_auth === 0 ? $fetch['wallet_id'] : -$response_auth;
                        }else return -time();
                    }else return -4;
                }else return -5;
            }else return -6;
        }catch(PDOException $e) {
            $this->createLog("SessionDB", $e);
            return -7;
        }
    }
    public function existsSession(string $session_id): bool {
        try {
            $prepare = $this->db->prepare("SELECT `session_id` FROM `sessions` WHERE `session_id` = :session_id;");
            $prepare->bindValue(':session_id', $session_id);
            if ($prepare->execute()) {
                $fetch = $prepare->fetch(PDO::FETCH_ASSOC);
                if ($fetch !== false && isset($fetch['session_id'])) {
                    return true;
                }
            }
        }catch(PDOException $e) {
            $this->createLog("SessionDB", $e);
        }
        return false;
    }
}