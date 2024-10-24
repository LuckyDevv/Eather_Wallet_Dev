<?php

namespace socket;

use controllers\AutoAuthController;
use controllers\HistoryController;
use db_managers\AccountsDB;
use db_managers\TransactionsDB;
use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use SplObjectStorage;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

class SocketHandler implements MessageComponentInterface
{
    private const string CONNECTION = "x0x01";
    private const string MESSAGE = "x0x02";
    private const string RESPONSE = "x0x03";
    private const string DISCONNECTION = "x0x04";
    private const int JSON_OPTIONS = JSON_UNESCAPED_UNICODE;
    private SplObjectStorage $clients;
    private int $nextId = 0;

    public function __construct() {
        $this->clients = new SplObjectStorage();
    }

    public function onOpen(ConnectionInterface $conn): void
    {
        $conn->wallet_id = 0;
        $conn->authorized = false;
        $conn->unique_id = $this->nextId;
        $this->nextId++;
        $this->clients->attach($conn);
        $this->log("Новое подключение: \e[0;33m".$conn->remoteAddress."\e[0m", self::CONNECTION);
    }

    public function onClose(ConnectionInterface $conn): void
    {
        $this->clients->detach($conn);
        $this->log("Клиент отключился: \e[0;33m".$conn->remoteAddress."\e[0m", self::DISCONNECTION);
    }

    public function onError(ConnectionInterface $conn, \Exception $e)
    {
        // TODO: Implement onError() method.
    }

    /*
    RESPONSE CODES:
    1 - Successful authorize
    2 - Successful send money
    3 - Send new transaction to history list
    4 - Send request to update balance
    */
    public function onMessage(ConnectionInterface $from, $msg): void
    {
        $this->log("Сообщение от \e[0;33m".$from->remoteAddress."\e[0m: \e[1;34m«".$msg."»\e[0m", self::MESSAGE);
        $decode = json_decode($msg, true);
        if (is_array($decode)) {
            switch ($decode["type"] ?? null) {
                case "auth":
                    if (isset($decode["session_id"])){
                        if (isset($decode["uuid"])){
                            $id = (new AutoAuthController(["session_id" => $decode["session_id"], '_usi_g%$_id$$' => $decode["uuid"]]))->handle();
                            if ($id !== null) {
                                $from->wallet_id = $id;
                                $this->sendMessage($from, "Successful authorize!", 1, false);
                            }else $this->sendMessage($from, "Unsuccessful authorize!", 3, true);
                        }else $this->sendMessage($from, "Empty user UUID!", 2, true);
                    }else $this->sendMessage($from, "Empty Session ID!", 1, true);
                    break;
                case "transfer":
                    if (isset($from->wallet_id) && $from->wallet_id > 0){
                        if (isset($decode["id"])){
                            if (is_numeric($decode["id"])){
                                $to_id = (int) $decode["id"];
                                if (isset($decode["amount"])){
                                    if (is_numeric($decode["amount"])){
                                        $amount = (int) $decode["amount"];
                                        if ($amount >= 1) {
                                            $comment = $decode["comment"] ?? "";
                                            if (mb_strlen($comment) < 256) {
                                                if ($from->wallet_id != $to_id) {
                                                    $accounts_db = new AccountsDB();
                                                    if ($accounts_db->account_exists_id($to_id)) {
                                                        $transaction_db = new TransactionsDB();
                                                        $balance_from = $accounts_db->balance_get($from->wallet_id);
                                                        $balance_to = $accounts_db->balance_get($to_id);
                                                        switch ($accounts_db->transfer($from->wallet_id, $to_id, $amount)) {
                                                            case 1:
                                                                $tr_id = $transaction_db->addTransaction($amount, 0, 0, $to_id, $from->wallet_id, $comment);
                                                                if (is_array($tr_id)) {
                                                                    $twig = new Environment(new FilesystemLoader(__DIR__.'/../resources/twig'), [
                                                                        'cache' => __DIR__.'/../resources/twig_cache',
                                                                        'autoescape' => false,
                                                                    ]);
                                                                    $transaction_id = $tr_id["transaction_id"];
                                                                    $history_controller = new HistoryController(false, $twig, $from->wallet_id, $transaction_id);
                                                                    echo "Transfer\n";
                                                                    $this->sendMessage($from, json_encode($tr_id, self::JSON_OPTIONS), 2, false);
                                                                    $this->sendMessage($from, json_encode(["transaction" => $history_controller->handle()], self::JSON_OPTIONS), 3, false);
                                                                    $this->sendMessage($from, json_encode(["amount" => -$amount], self::JSON_OPTIONS), 4, false);
                                                                    $history_controller->id = $to_id;
                                                                    $this->sendMessageByWalletId($to_id, json_encode(["transaction" => $history_controller->handle()], self::JSON_OPTIONS), 3, false);
                                                                    $this->sendMessageByWalletId($to_id, json_encode(["amount" => $amount], self::JSON_OPTIONS), 4, false);
                                                                    unset($twig);
                                                                    unset($history_transaction);
                                                                    unset($transaction_id);
                                                                }else{
                                                                    $accounts_db->transfer_abort($from->wallet_id, $to_id, $balance_from, $balance_to);
                                                                    $this->sendMessage($from, "Не удалось совершить перевод!", 13, true);
                                                                }
                                                                unset($tr_id);
                                                                break;
                                                            case -1:
                                                                $this->sendMessage($from ,"Не удалось найти ваш аккаунт в базе данных!", 13, true);
                                                                break;
                                                            case -2:
                                                                $this->sendMessage($from ,"Кошелька с таким ID не существует!", 13, true);
                                                                break;
                                                            case -3:
                                                            case -4:
                                                            case -5:
                                                            case -6:
                                                            case -7:
                                                            case -14:
                                                                $this->sendMessage($from ,"Произошла ошибка в базе данных!", 13, true);
                                                                break;
                                                            case -9:
                                                                $this->sendMessage($from ,"У вас недостаточно средств!", 13, true);
                                                                break;
                                                            case -10:
                                                                $this->sendMessage($from ,"Счёт получателя заморожен!", 13, true);
                                                                break;
                                                            case -12:
                                                                $this->sendMessage($from ,"Транзакция отменена из-за ошибки.", 13, true);
                                                                break;
                                                            case -13:
                                                                $this->sendMessage($from ,"Не удалось отменить транзакцию из-за ошибки.", 13, true);
                                                                break;
                                                        }
                                                        unset($transaction_db);
                                                        unset($balance_from);
                                                        unset($balance_to);
                                                    }else $this->sendMessage($from, "Кошелька с таким ID не существует!", 12, true);
                                                    unset($accounts_db);
                                                }else $this->sendMessage($from, "Нельзя переводить самому себе!", 11, true);
                                            }else $this->sendMessage($from, "Комментарий слишком длинный!", 10, true);
                                            unset($comment);
                                        }else $this->sendMessage($from, "Нельзя перевести меньше 5 руб.!", 9, true);
                                        unset($amount);
                                    }else $this->sendMessage($from, "Сумма должна быть числом!", 8, true);
                                }else $this->sendMessage($from, "Не введена сумма!", 7, true);
                                unset($to_id);
                            }else $this->sendMessage($from, "ID должен быть числом!", 6, true);
                        }else $this->sendMessage($from, "Не введён ID!", 5, true);
                    }else $this->sendMessage($from, "Вы не авторизованы!", 4, true);
                    break;
                default:
                    $this->sendMessage($from, "Undefined operation type", 4, true);
                    break;
            }
        }else{
            $this->sendMessage($from, "Undefined message", 5);
        }
    }

    private function log(string $msg, string $type): void
    {
        $prefix = "[Undefined]";
        switch ($type) {
            case self::CONNECTION:
                $prefix = "\e[1;32m[Connection]\e[0m";
                break;
            case self::MESSAGE:
                $prefix = "\e[1;36m[Message]\e[0m";
                break;
            case self::RESPONSE:
                $prefix = "\e[1;33m[Response]\e[0m";
                break;
            case self::DISCONNECTION:
                $prefix = "\e[1;31m[Disconnection]\e[0m";
        }
        $message = "\e[1;34m[".date("d.m.Y H:i:s")."]\e[0m ".$prefix." - ".$msg."\n";
        echo $message;
        file_put_contents(__DIR__."/Socket.log", preg_replace("/\033\[[^m]*m/", '', $message), FILE_APPEND);
    }
    private function findClientsByWalletId(int $wallet_id): array
    {
        $clients = [];
        foreach ($this->clients as $client) {
            if (!isset($client->wallet_id)) continue;
            if ($client->wallet_id === $wallet_id) {
                $clients[] = $client;
            }
        }
        return $clients;
    }
    private function sendMessageByWalletId(int $wallet_id, string $message, int $code=0, bool $isError=false): bool
    {
        $clients = $this->findClientsByWalletId($wallet_id);
        if (count($clients) > 0) {
            foreach ($clients as $client) {
                $this->sendMessage($client, $message, $code, $isError);
            }
            return true;
        }else{
            return false;
        }
    }
    private function findClientByUniqueId(int $unique_id)
    {
        $clients = [];
        foreach ($this->clients as $client) {
            if ($client->unique_id === $unique_id) {
                return $client;
            }
        }
        return null;
    }
    private function sendMessage(ConnectionInterface $connection, string $message, int $code=0, bool $isError=false): void
    {
        $this->log("Отправлено сообщение: \e[1;34m«".$message."»\e[0m клиенту \e[0;33m".$connection->remoteAddress."\e[0m", self::RESPONSE);
        $type = $isError ? "error" : "response";
        $message = [
            $type => [
                "message" => $message,
                "code" => $code,
                "timestamp" => time()
            ]
        ];
        $connection->send(json_encode($message, self::JSON_OPTIONS));
    }
}