<?php
namespace db_managers;

use PDO;
use PDOException;

date_default_timezone_set('Europe/Moscow');

class TransactionsDB extends DB {
    public const array TYPES = [0 => "Перевод"];
    public const array CATEGORIES = [0 => "Переводы"];
    public bool $die = false;
    public function __construct()
    {
        try {
            parent::__construct();
            $this->db->exec("
            CREATE TABLE IF NOT EXISTS `transactions` (
                `transaction_id` VARCHAR(255) NOT NULL, 
                `transaction_description` TEXT NOT NULL, 
                `transaction_amount` REAL NOT NULL, 
                `transaction_sort` INT NOT NULL, 
                `transaction_sort_type` INT NOT NULL,
                `transaction_type` TEXT NOT NULL,
                `transaction_name` TEXT NOT NULL,
                `transaction_to_id` BIGINT NOT NULL,
                `transaction_from_id` BIGINT NOT NULL,
                `transaction_to_name` TEXT NOT NULL,
                `transaction_from_name` TEXT NOT NULL,
                `transaction_timestamp` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`transaction_id`)
            );");
        }catch(PDOException $e) {
            $this->createLog("TransactionsDB", $e);
            $this->die = true;
        }
    }

    public function getTransactionsByUserId(int $user_id): array {
        $result = [];
        try {
            $prepare = $this->db->prepare("SELECT *,unix_timestamp(`transaction_timestamp`) AS `trans_time` FROM `transactions` WHERE `transaction_to_id`=:id OR `transaction_from_id`=:id ORDER BY unix_timestamp(`transaction_timestamp`) DESC;");
            $prepare->execute([":id" => $user_id]);
            $search = $prepare->fetchAll(PDO::FETCH_ASSOC);

            foreach ($search as $transaction) {
                $result = $this->getArr($transaction, $user_id, $result);
            }

        }catch (PDOException $e) {
            $this->createLog("TransactionsDB", $e);
        }
        return $result;
    }
    public function getTransactionById(string $transaction_id, int $user_id): array
    {
        $result = [];
        try {
            $prepare = $this->db->prepare("SELECT *,unix_timestamp(`transaction_timestamp`) AS `trans_time` FROM `transactions` WHERE `transaction_id`=:id;");
            $prepare->execute([":id" => $transaction_id]);
            $transaction = $prepare->fetch(PDO::FETCH_ASSOC);
            if ($transaction !== false && count($transaction) > 0) {
                $result = $this->getArr($transaction, $user_id, $result);
            }
        }catch(PDOException $e) {
            $this->createLog("TransactionsDB", $e);
        }
        return $result;
    }
    public function getExpenses(int $user_id): array
    {
        $result = ["real_amount" => 0, "categories" => []];
        try {
            $prepare = $this->db->prepare("SELECT * FROM `transactions` WHERE `transaction_from_id`=:user_id;");
            $result = $this->getResultStatistic($prepare, $user_id, $result);
        }catch(PDOException $e) {
            $this->createLog("TransactionsDB", $e);
        }
        return $result;
    }
    public function getReceipts(int $user_id): array
    {
        $result = ["real_amount" => 0, "categories" => []];
        try {
            $prepare = $this->db->prepare("SELECT * FROM `transactions` WHERE `transaction_to_id`=:user_id;");
            $result = $this->getResultStatistic($prepare, $user_id, $result);
        }catch(PDOException $e) {
            $this->createLog("TransactionsDB", $e);
        }
        return $result;
    }
    public function addTransaction(float $amount, int $sort, int $sort_type, int $to_id, int $from_id,  string $description=''): int|array {
        if ($amount >= 5) {
            if ($sort === 0 || $sort === 1) {
                if (isset(self::TYPES[$sort_type])) {
                    $sort_text_type = $sort_type == 0 ? "Перевод" : "";
                    $accounts = new AccountsDB();
                    if ($accounts->account_exists_id($to_id)) {
                        if ($accounts->account_exists_id($from_id)) {
                            if (mb_strlen($description) < 255) {
                                $to_hash = $from_id."_".$to_id."_".$amount."_".$sort_type."_".time()."_".rand(1, 9999);
                                $transaction_id = hash('sha3-512', $to_hash);
                                $transaction_id = substr($transaction_id, 0, 30);
                                $prepare = $this->db->prepare("
                                INSERT INTO `transactions` (
                                    `transaction_id`, 
                                    `transaction_description`, 
                                    `transaction_amount`, 
                                    `transaction_sort`, 
                                    `transaction_sort_type`,
                                    `transaction_type`,
                                    `transaction_name`,
                                    `transaction_to_id`,
                                    `transaction_from_id`,
                                    `transaction_to_name`,
                                    `transaction_from_name`,
                                    `transaction_timestamp`
                                ) VALUES (
                                    :transaction_id,
                                    :description,
                                    :amount,
                                    :sort,
                                    :sort_type,
                                    :text_sort_type,
                                    :transaction_name,
                                    :to_id,
                                    :from_id,
                                    :to_name,
                                    :from_name,
                                    DEFAULT
                                );");
                                $prepare->bindValue(":transaction_id", $transaction_id);
                                $prepare->bindValue(":description", $description);
                                $prepare->bindValue(":amount", $amount);
                                $prepare->bindValue(":sort", $sort, PDO::PARAM_INT);
                                $prepare->bindValue(":sort_type", $sort_type, PDO::PARAM_INT);
                                $prepare->bindValue(":text_sort_type", $sort_text_type, PDO::PARAM_INT);
                                $transaction_name = "Перевод";
                                $prepare->bindValue(":transaction_name", $transaction_name);
                                $prepare->bindValue(":to_id", $to_id, PDO::PARAM_INT);
                                $prepare->bindValue(":from_id", $from_id, PDO::PARAM_INT);
                                $to_name = $accounts->name_get($to_id);
                                $prepare->bindValue(":to_name", $to_name);
                                $from_name = $accounts->name_get($from_id);
                                $prepare->bindValue(":from_name", $from_name);
                                if ($prepare->execute()) {
                                    return [
                                        "transaction_id" => $transaction_id,
                                        "amount" => $amount,
                                        "from_id" => $from_id,
                                        "to_id" => $to_id,
                                        "to_name" => $to_name,
                                        "comment" => $description,
                                    ];
                                }else return 0;
                            }else return -1;
                        }else return -2;
                    }else return -3;
                }else return -4;
            }else return -5;
        }else return -6;
    }
    public function removeTransactionsByUserId(int $user_id) {}
    public function removeTransactionById(string $transaction_id) {}

    /**
     * @param mixed $transaction
     * @param int $user_id
     * @param array $result
     * @return array
     */
    public function getArr(mixed $transaction, int $user_id, array $result): array
    {
        if ($user_id === $transaction["transaction_from_id"]) {
            $invert = true;
        } else {
            $invert = false;
        }
        $result[] = [
            "trans_id" => $transaction["transaction_id"],
            "amount" => $transaction["transaction_amount"],
            "comment" => $transaction["transaction_description"],
            "from_id" => $transaction["transaction_from_id"],
            "from_name" => $transaction["transaction_from_name"],
            "to_id" => $transaction["transaction_to_id"],
            "to_name" => $transaction["transaction_to_name"],
            "sort" => $invert ? !((bool)$transaction["transaction_sort"]) : ((bool)$transaction["transaction_sort"]),
            "sort_type" => self::TYPES[$transaction["transaction_sort_type"]],
            "type" => $transaction["transaction_type"],
            "trans_name" => $transaction["transaction_name"],
            "timestamp" => $transaction["trans_time"]
        ];
        return $result;
    }

    /**
     * @param false|\PDOStatement $prepare
     * @param int $user_id
     * @param array $result
     * @return array
     */
    public function getResultStatistic(false|\PDOStatement $prepare, int $user_id, array $result): array
    {
        $prepare->execute([":user_id" => $user_id]);
        $search = $prepare->fetchAll(PDO::FETCH_ASSOC);
        if ($search !== false && count($search) > 0) {
            $last_type = -1;
            $last_type_text = "";
            $last_type_amount = 0;
            $len = count($search);
            $count = 0;
            foreach ($search as $transaction) {
                $count++;
                $result["real_amount"] += floatval($transaction["transaction_amount"]);
                if ($last_type != $transaction["transaction_sort_type"]) {
                    if ($last_type !== -1) {
                        $result["categories"][$last_type] = ["category_name" => $last_type_text, "amount" => $last_type_amount];
                    }
                    $last_type = $transaction["transaction_sort_type"];
                    $last_type_text = self::CATEGORIES[$transaction["transaction_sort_type"]];
                }
                $last_type_amount += floatval($transaction["transaction_amount"]);
                if ($len == $count) {
                    $result["categories"][$last_type] = ["category_name" => $last_type_text, "amount" => $last_type_amount];
                }
            }
        }
        return $result;
    }
}