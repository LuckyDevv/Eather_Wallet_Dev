<?php
namespace db_managers;

use PDO;
use PDOException;
use PDOStatement;

date_default_timezone_set('Europe/Moscow');

class AccountsDB extends DB
{
    public bool $die = false;
    public function __construct()
    {
        try {
            parent::__construct();
            $this->db->query("
            CREATE TABLE IF NOT EXISTS `accounts` (
                `account_id` BIGINT NOT NULL, 
                `name` TEXT NOT NULL, 
                `login` TEXT NOT NULL, 
                `password` TEXT NOT NULL, 
                `image_src` TEXT NOT NULL, 
                `balance` REAL NOT NULL,
                `contact_limit` INT NOT NULL, 
                `disable_ads` BOOLEAN NOT NULL,
                `create_date` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                 PRIMARY KEY (`account_id`)
            );");
        }catch (\PDOException $e) {
            $this->createLog("AccountsDB", $e);
            $this->die = true;
        }
    }

    public function account_create(string $name, string $password): int
    {
        if ($this->die) return -4;
        try {
            $id = $this->getMaxId();
            switch ($id) {
                case -4:
                case -3:
                case -2:
                    return $id;
                case -1:
                    $id = 1;
                    break;
                default:
                    $id++;
            }

            $prepare_stmt = $this->db->prepare("
INSERT INTO `accounts`
(`account_id`, `name`, `login`, `password`, `image_src`, `balance`, `contact_limit`, `disable_ads`, `create_date`)
VALUES (:id, :name, :login, :password, :image_src, 0.0, :contact_limit, :disable_ads, DEFAULT);");
            $prepare_stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $prepare_stmt->bindValue(':name', $name);
            $prepare_stmt->bindValue(':login', 'none');
            $prepare_stmt->bindValue(':password', hash('sha3-512', $password));
            $prepare_stmt->bindValue(':image_src', 'src/images/user.jpg');
            $prepare_stmt->bindValue(':contact_limit', 200, PDO::PARAM_INT);
            $prepare_stmt->bindValue(':disable_ads', false, PDO::PARAM_BOOL);
            if ($prepare_stmt->execute()) {
                return $id;
            }else return -2;
        }catch (PDOException $e){
            $this->createLog("AccountsDB", $e);
            return -3;
        }
    }
    public function account_auth_id(int $id, string $password, bool $encrypt=true): int
    {
        $prepare = $this->db->prepare("SELECT `password` FROM `accounts` WHERE `account_id`=:id;");
        $prepare->bindValue(':id', $id, PDO::PARAM_INT);
        $prepare->execute();
        $result = $prepare->fetch(PDO::FETCH_ASSOC);
        if (isset($result['password'])) {
            if ($encrypt) {
                $password = hash('sha3-512', $password);
            }
            if ($result['password'] == $password) {
                return 0;
            }else return 1;
        }else return 2;
    }
    public function account_auth_login(string $login, string $password, bool $encrypt=true): int
    {
        $prepare = $this->db->prepare("SELECT `password` FROM `accounts` WHERE `login`=:login;");
        $prepare->bindValue(':login', $login);
        $prepare->execute();
        $result = $prepare->fetch(PDO::FETCH_ASSOC);
        if (isset($result['password'])) {
            if ($encrypt) {
                $password = hash('sha3-512', $password);
            }
            if ($result['password'] == $password) {
                return 0;
            }else return 1;
        }else return 2;
    }
    public function account_exists_id(int $id): bool
    {
        $prepare = $this->db->prepare("SELECT * FROM `accounts` WHERE `account_id`=:id;");
        $prepare->bindValue(':id', $id, PDO::PARAM_INT);
        $prepare->execute();
        return (isset($prepare->fetch(PDO::FETCH_ASSOC)['account_id']));
    }

    public function name_get(int $id): ?string {
        if ($this->die) return null;
        try {
            $prepare = $this->db->prepare("SELECT `name` FROM `accounts` WHERE `account_id`=:id;");
            $prepare->bindValue(':id', $id, PDO::PARAM_INT);
            $prepare->execute();
            return $prepare->fetch(PDO::FETCH_ASSOC)['name'] ?? null;
        }catch(PDOException $e) {
            $this->createLog("AccountsDB", $e);
        }
        return null;
    }

    public function user_get_all(int $id): array
    {
        if ($this->die) return ['status' => 'die'];
        try {
            $prepare = $this->db->prepare("SELECT * FROM `accounts` WHERE `account_id`=:id;");
            $prepare->execute([':id' => $id]);
            $result = $prepare->fetchAll(PDO::FETCH_ASSOC);
            if ($result !== false && count($result) > 0) {
                return ['status' => 'success', 'account' => $result];
            }else{
                return ['status' => 'none'];
            }
        }catch(PDOException $e){
            $this->createLog("AccountsDB", $e);
            return ['status' => 'error'];
        }
    }

    public function avatar_get(int $id)
    {
        if ($this->die) return -3;
        try {
            $prepare = $this->db->prepare("SELECT `image_src` FROM `accounts` WHERE `account_id`=:id;");
            $prepare->execute([':id' => $id]);
            $result = $prepare->fetch(PDO::FETCH_ASSOC);
            return $result['image_src'] ?? -1;
        }catch (PDOException $e) {
            $this->createLog("AccountsDB", $e);
            return -2;
        }
    }

    public function balance_get(int $id)
    {
        if ($this->die) return -3;
        try {
            $prepare = $this->db->prepare("SELECT `balance` FROM `accounts` WHERE `account_id`=:id;");
            $prepare->execute([':id' => $id]);
            return $prepare->fetch(PDO::FETCH_ASSOC)['balance'] ?? -1;
        }catch (PDOException $e) {
            $this->createLog("AccountsDB", $e);
            return -2;
        }
    }
    public function balance_set(int $id, float $balance): int
    {
        if ($this->die) return -2;
        try {
            $prepare = $this->db->prepare("UPDATE `accounts` SET `balance`=:balance WHERE `account_id`=:id;");
            return $prepare->execute([':balance' => $balance, ':id' => $id]);
        }catch(PDOException $e){
            $this->createLog("AccountsDB", $e);
            return -1;
        }
    }

    public function transfer(int $from_id, int $to_id, int $amount) : int
    {
        if ($this->die) return -5;
        if (!$this->account_exists_id($from_id)) return -1;
        if (!$this->account_exists_id($to_id)) return -2;

        $balance_from_id = $this->balance_get($from_id);
        switch ($balance_from_id) {
            case -1:
                return -3;
            case -2:
                return -4;
        }

        $balance_to_id = $this->balance_get($to_id);
        switch ($balance_to_id) {
            case -1:
                return -6;
            case -2:
                return -7;
        }

        $new_balance_from_id = $balance_from_id - $amount;
        if ($new_balance_from_id < 0) return -9;

        $new_balance_to_id = $balance_to_id + $amount;
        if ($new_balance_to_id < 0) return -10;

        if ($this->balance_set($from_id, $new_balance_from_id)===1 && $this->balance_set($to_id, $new_balance_to_id)===1) {
            return 1;
        }else{
            return $this->transfer_abort($from_id, $to_id, $balance_from_id, $balance_to_id);
        }
    }
    public function transfer_abort(int $from_id, int $to_id, int $balance_from_id, int $balance_to_id) : int
    {
        try {
            $prepare = $this->db->prepare("UPDATE `accounts` SET `balance`=:balance WHERE `account_id`=:id;");
            $ans = $prepare->execute([':balance' => $balance_from_id, ':id' => $from_id]);

            $prepare = $this->db->prepare("UPDATE `accounts` SET `balance`=:balance WHERE `account_id`=:id;");
            $ans2 = $prepare->execute([':balance' => $balance_to_id, ':id' => $to_id]);

            return ($ans2 && $ans) ? -12 : -13;
        }catch(PDOException $e) {
            $this->createLog("AccountsDB", $e);
            return -14;
        }
    }

    private function getMaxId(): int {
        try {
            $stmt = $this->db->query("SELECT MAX(`account_id`) AS 'max_id' FROM `accounts`;");
            if ($stmt instanceof PDOStatement) {
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                if (is_array($result)) return $result['max_id'] ?? -1;
                else return -2;
            }else return -3;
        }catch(PDOException $e) {
            $this->createLog("AccountsDB", $e);
            return -4;
        }
    }
}