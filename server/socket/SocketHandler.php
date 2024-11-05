<?php

namespace socket;

use controllers\AutoAuthController;
use controllers\HistoryController;
use controllers\MailController;
use db_managers\AccountsDB;
use db_managers\SessionDB;
use db_managers\TransactionsDB;
use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use SplObjectStorage;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use Egulias\EmailValidator\EmailValidator;
use Egulias\EmailValidator\Validation\DNSCheckValidation;
use Egulias\EmailValidator\Validation\MultipleValidationWithAnd;
use Egulias\EmailValidator\Validation\RFCValidation;

class SocketHandler implements MessageComponentInterface
{
    private const string CONNECTION = "x0x01";
    private const string MESSAGE = "x0x02";
    private const string RESPONSE = "x0x03";
    private const string DISCONNECTION = "x0x04";
    private const int JSON_OPTIONS = JSON_UNESCAPED_UNICODE;
    private SplObjectStorage $clients;
    private array $uuid_requests = [];

    public function __construct() {
        $this->clients = new SplObjectStorage();
    }

    public function onOpen(ConnectionInterface $conn): void
    {
        $conn->wallet_id = 0;
        $conn->authorized = false;
        $this->clients->attach($conn);
        $this->log("Новое подключение: \e[0;33m".$conn->remoteAddress."\e[0m", self::CONNECTION);
    }

    public function onClose(ConnectionInterface $conn): void
    {
        $this->clients->detach($conn);
        $this->log("Клиент отключился: \e[0;33m".$conn->remoteAddress."\e[0m", self::DISCONNECTION);
        unset($conn);
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
    5 - Expenses statistic chart
    6 - Receipts statistic chart
    */
    public function onMessage(ConnectionInterface $from, $msg): void
    {
        $decode = json_decode($msg, true);
        if (is_array($decode)) {
            $twig = new Environment(new FilesystemLoader(__DIR__.'/../resources/twig'), [
                'cache' => __DIR__.'/../resources/twig_cache',
                'autoescape' => false,
            ]);
            switch ($decode["type"] ?? null) {
                case "auth":
                    $this->log("Запрос на авторизацию от \e[0;33m".$from->remoteAddress."\e[0m", self::MESSAGE);
                    if (isset($decode["session_id"])){
                        if (isset($decode["uuid"])){
                            $id = (new AutoAuthController(["session_id" => $decode["session_id"], '_usi_g%$_id$$' => $decode["uuid"]]))->handle();
                            if ($id !== null) {
                                $from->wallet_id = $id;
                                $this->sendMessage($from, "Successful authorize!", 1, false, false);
                                $this->log("Успешная авторизация \e[0;33m".$from->remoteAddress."\e[0m (ID: ".$id.")", self::RESPONSE);
                            }else {
                                $this->sendMessage($from, "Unsuccessful authorize!", 3, true);
                                $this->log("Неудачная авторизация \e[0;33m".$from->remoteAddress."\e[0m", self::RESPONSE);
                            }
                        }else $this->sendMessage($from, "Empty user UUID!", 2, true);
                    }else $this->sendMessage($from, "Empty Session ID!", 1, true);
                    break;
                case "transfer":
                    $this->log("Запрос на перевод средств от \e[0;33m".$from->remoteAddress."\e[0m", self::MESSAGE);
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
                                                                    $transaction_id = $tr_id["transaction_id"];
                                                                    $history_controller = new HistoryController(false, $twig, $from->wallet_id, $transaction_id);
                                                                    $this->sendMessage($from, json_encode($tr_id, self::JSON_OPTIONS), 2, false, false);
                                                                    $this->sendMessageByWalletId($from->wallet_id, json_encode(["transaction" => $history_controller->handle()], self::JSON_OPTIONS), 3, false, false);
                                                                    $this->sendMessageByWalletId($from->wallet_id, json_encode(["amount" => -$amount], self::JSON_OPTIONS), 4, false, false);
                                                                    $history_controller->id = $to_id;
                                                                    $this->sendMessageByWalletId($to_id, json_encode(["transaction" => $history_controller->handle()], self::JSON_OPTIONS), 3, false, false);
                                                                    $this->sendMessageByWalletId($to_id, json_encode(["amount" => $amount], self::JSON_OPTIONS), 4, false, false);
                                                                    $this->log("Успешный перевод от \e[0;33m".$from->remoteAddress."\e[0m (ID ".$from->wallet_id.") к ID ".$to_id, self::RESPONSE);
                                                                    unset($twig);
                                                                    unset($history_transaction);
                                                                    unset($transaction_id);
                                                                }else{
                                                                    $accounts_db->transfer_abort($from->wallet_id, $to_id, $balance_from, $balance_to);
                                                                    $this->sendMessage($from, "Не удалось совершить перевод!", 13, true);
                                                                    $this->log("Неудачный перевод от \e[0;33m".$from->remoteAddress."\e[0m (ID ".$from->wallet_id.") к ID ".$to_id, self::RESPONSE);
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
                                        }else $this->sendMessage($from, "Нельзя перевести меньше 1 руб.!", 9, true);
                                        unset($amount);
                                    }else $this->sendMessage($from, "Сумма должна быть числом!", 8, true);
                                }else $this->sendMessage($from, "Не введена сумма!", 7, true);
                                unset($to_id);
                            }else $this->sendMessage($from, "ID должен быть числом!", 6, true);
                        }else $this->sendMessage($from, "Не введён ID!", 5, true);
                    }else $this->sendMessage($from, "Вы не авторизованы!", 4, true);
                    break;
                case "statistic_chart":
                    $this->log("Запрос статистики от \e[0;33m".$from->remoteAddress."\e[0m", self::MESSAGE);
                    if (isset($from->wallet_id)) {
                        if (isset($decode["subtype"])) {
                            if ($decode["subtype"] == "expenses") {
                                if (!isset($from->expensesStat) || $from->expensesStat == "update") {
                                    $expenses = (new TransactionsDB())->getExpenses($from->wallet_id);
                                    $from->expensesStat = $expenses;
                                }
                                $this->sendMessage($from, json_encode($from->expensesStat, self::JSON_OPTIONS), 5, false, false);
                                $this->log("Отправлена статистика расходов \e[0;33m".$from->remoteAddress."\e[0m (ID ".$from->wallet_id.")", self::RESPONSE);
                            }elseif($decode["subtype"] == "receipts") {
                                if (!isset($from->receiptsStat) || $from->receiptsStat == "update") {
                                    $receipts = (new TransactionsDB())->getReceipts($from->wallet_id);
                                    $from->receiptsStat = $receipts;
                                }
                                $this->sendMessage($from, json_encode($from->receiptsStat, self::JSON_OPTIONS), 6, false, false);
                                $this->log("Отправлена статистика доходов \e[0;33m".$from->remoteAddress."\e[0m (ID ".$from->wallet_id.")", self::RESPONSE);
                            }else $this->sendMessage($from, "Не", 16, true);
                        }else $this->sendMessage($from, "Не", 15, true);
                    }else $this->sendMessage($from, "Не", 14, true);
                    break;
                case "reg_check":
                    if (isset($decode['email'], $decode["uuid"])) {
                        $email = $decode['email'];
                        if (mb_strlen($email) < 255) {
                            $validator = new EmailValidator();
                            $multipleValidations = new MultipleValidationWithAnd([
                                new RFCValidation(),
                                new DNSCheckValidation()
                            ]);
                            if ($validator->isValid($email, $multipleValidations)) {
                                echo $email."\n";
                                if ((new AccountsDB())->account_exists_email($email)) {
                                    $this->sendMessage($from, "Аккаунт с такой почтой уже зарегестрирован!", 17, true);
                                    return;
                                }
                                if (isset($decode['name'])) {
                                    $name = $decode['name'];
                                    if (mb_strlen($name) > 2 && mb_strlen($name) < 21) {
                                        if (isset($decode['password'])) {
                                            if (isset($decode['password_confirm'])) {
                                                $password = $decode['password'];
                                                if (strlen($password) >= 8) {
                                                    if(preg_match('/[0-9A-Za-z!@#$%-]$/iu', $password)) {
                                                        if ($password == $decode['password_confirm']) {
                                                            $confirm_code = "";
                                                            for ($i = 0; $i < 6; $i++) {
                                                                $confirm_code .= rand(0, 9);
                                                            }
                                                            $mail_controller = new MailController($email, $name);
                                                            if ($mail_controller->sendCode($confirm_code, 0)) {
                                                                $this->uuid_requests[$decode["uuid"]] = [
                                                                    "confirm_reg_email" => $confirm_code,
                                                                    "reg_email" => $email,
                                                                    "reg_password" => $password,
                                                                    "reg_name" => $name
                                                                ];
                                                                $this->sendMessage($from, "На вашу почту был отправлен код подтверждения. Введите его в поле ниже.",7);
                                                            }else $this->sendMessage($from, "Не удалось отправить код подтверждения!", 17, true);
                                                        }else $this->sendMessage($from ,"Подтверждение пароля не совпадает с паролем!", 19, true);
                                                    }else $this->sendMessage($from ,"Пароль должен содержать латиницу,специальные символы и цифры!", 19, true);
                                                }else $this->sendMessage($from ,"Пароль должен состоять не менее 8 символов!", 19, true);
                                            }else $this->sendMessage($from ,"Не введено подтверждение пароля!", 19, true);
                                        }else $this->sendMessage($from ,"Не введён пароль!", 19, true);
                                    }else $this->sendMessage($from ,"Имя должно быть не менее 3 и не более 20 символов!", 18, true);
                                }else $this->sendMessage($from ,"Не введено имя", 18, true);
                            }else $this->sendMessage($from ,"Введена неверная почта!", 17, true);
                        }else $this->sendMessage($from ,"Почта слишком длинная!", 17, true);
                    }else $this->sendMessage($from ,"Не введёна почта!", 17, true);
                    break;
                case "reg_confirm":
                    if (isset($decode["user_uuid"])) {
                        $browser = $decode["browser"] ?? "Неизвестный браузер";
                        $os = $decode["os"] ?? "Неизвестная ОС";
                        if (isset($decode["code"], $this->uuid_requests[$decode["user_uuid"]])) {
                            if (isset($this->uuid_requests[$decode["user_uuid"]]["confirm_reg_email"], $this->uuid_requests[$decode["user_uuid"]]["reg_email"], $this->uuid_requests[$decode["user_uuid"]]["reg_password"], $this->uuid_requests[$decode["user_uuid"]]["reg_name"])) {
                                if ($this->uuid_requests[$decode["user_uuid"]]["confirm_reg_email"] == trim($decode["code"])) {
                                    $accounts_db = new AccountsDB();
                                    $id = $accounts_db->account_create($this->uuid_requests[$decode["user_uuid"]]["reg_email"], $this->uuid_requests[$decode["user_uuid"]]["reg_name"], $this->uuid_requests[$decode["user_uuid"]]["reg_password"]);
                                    switch ($id) {
                                        case -1:
                                        case -2:
                                            $this->sendMessage($from, "Не удалось выполнить запрос!", 20, true);
                                            break;
                                        case -3:
                                        case -4:
                                            $this->sendMessage($from, "Произошла ошибка в базе данных!", 20, true);
                                            break;
                                        default:
                                            $session_db = new SessionDB();
                                            $session = $session_db->createSession($this->uuid_requests[$decode["user_uuid"]]["reg_password"], $id, $decode["user_uuid"], $browser, $os);
                                            switch ($session) {
                                                case "err_2":
                                                case "err_1":
                                                    $this->sendMessage($from, "Неверный код подтверждения!", 20, true);
                                                    break;
                                                default:
                                                    $this->log("Успешная регистрация \e[0;33m".$from->remoteAddress."\e[0m (ID ".$id.")", self::RESPONSE);
                                                    $this->sendMessage($from, $session, 8, false, false);
                                                    return;
                                            }
                                            break;
                                    }
                                    $this->log("Неудачная регистрация \e[0;33m".$from->remoteAddress."\e[0m (ID ".$id.")", self::RESPONSE);
                                }else $this->sendMessage($from, "Неверный код подтверждения!", 20, true);
                            }else $this->sendMessage($from, "Не создан запрос на регистрацию!", 20, true);
                        }else $this->sendMessage($from, "Не введён код подтверждения!", 20, true);
                    }else $this->sendMessage($from, "Не передан UUID!", 20, true);
                    break;
                case "auth_check_email":
                    if (isset($decode["email"], $decode["uuid"])) {
                        $accounts_db = new AccountsDB();
                        if ($accounts_db->account_exists_email($decode["email"])) {
                            $this->sendMessage($from, "Email exists!", 9);
                            $this->uuid_requests[$decode["uuid"]] = ["auth_email" => $decode["email"]];
                        }else $this->sendMessage($from, "Аккаунт не найден!", 21, true);
                    }else $this->sendMessage($from, "Не введена почта!", 21, true);
                    break;
                case "auth_check":
                    if (!isset($decode["uuid"])) {
                        $this->sendMessage($from, "Не введён пароль!", 22, true);
                        return;
                    }
                    if (isset($this->uuid_requests[$decode["uuid"]], $this->uuid_requests[$decode["uuid"]]["auth_email"])) {
                        if (isset($decode["password"])) {
                            $accounts_db = new AccountsDB();
                            $id = $accounts_db->account_auth_email($this->uuid_requests[$decode["uuid"]]["auth_email"], $decode["password"], true);
                            switch ($id) {
                                case -1:
                                    $this->sendMessage($from, "Неверный пароль.", 22, true);
                                    break;
                                case -2:
                                    $this->sendMessage($from, "Неверная почта.", 22, true);
                                    break;
                                default:
                                    // TODO: Get OTP (2FA) auth method and use it
                                    $confirm_code = "";
                                    for ($i = 0; $i < 6; $i++) {
                                        $confirm_code .= rand(0, 9);
                                    }
                                    $mail_controller = new MailController($this->uuid_requests[$decode["uuid"]]["auth_email"], $accounts_db->name_get_email($this->uuid_requests[$decode["uuid"]]["auth_email"]) ?? "Пользователь");
                                    if ($mail_controller->sendCode($confirm_code, 1)) {
                                        $this->uuid_requests[$decode["uuid"]]["auth_id"] = $id;
                                        $this->uuid_requests[$decode["uuid"]]["auth_code"] = $confirm_code;
                                        $this->uuid_requests[$decode["uuid"]]["auth_password"] = $decode["password"];
                                        $this->sendMessage($from, "На вашу почту был отправлен код подтверждения. Введите его в поле ниже.", 10);
                                    }else $this->sendMessage($from, "Не удалось отправить код.", 22, true);
                                    break;
                            }
                        }else $this->sendMessage($from, "Не введён пароль!", 22, true);
                    }else $this->sendMessage($from, "Не введена почта!", 22, true);
                    break;
                case "auth_confirm":
                    if (isset($decode["user_uuid"])) {
                        $uuid = $decode["user_uuid"];
                        if (isset($this->uuid_requests[$uuid], $this->uuid_requests[$uuid]["auth_email"], $this->uuid_requests[$uuid]["auth_id"], $this->uuid_requests[$uuid]["auth_code"], $this->uuid_requests[$uuid]["auth_password"])) {
                            if (isset($decode["code"])) {
                                $browser = $decode["browser"] ?? "Неизвестный браузер";
                                $os = $decode["os"] ?? "Неизвестная ОС";
                                if (trim($decode["code"]) == $this->uuid_requests[$uuid]["auth_code"]) {
                                    $session_db = new SessionDB();
                                    $session = $session_db->createSession($this->uuid_requests[$uuid]["auth_password"], $this->uuid_requests[$uuid]["auth_id"], $decode["user_uuid"], $browser, $os);
                                    switch ($session) {
                                        case "err_2":
                                        case "err_1":
                                            $this->sendMessage($from, "Не удалось создать сессию!", 23, true);
                                            break;
                                        default:
                                            $this->sendMessage($from, $session, 11, false, false);
                                            $this->log("Успешная авторизация \e[0;33m".$from->remoteAddress."\e[0m (ID ".$this->uuid_requests[$uuid]["auth_id"].")", self::RESPONSE);
                                            return;
                                    }
                                    $this->log("Неудачная авторизация \e[0;33m".$from->remoteAddress."\e[0m (ID ".$this->uuid_requests[$uuid]["auth_id"].")", self::RESPONSE);
                                }else $this->sendMessage($from, "Неверный код подтверждения!", 23, true);
                            }else $this->sendMessage($from, "Вы не ввели код подтверждения!", 23, true);
                        }else $this->sendMessage($from, "Запрос на авторизацию не создан!", 23, true);
                    }else $this->sendMessage($from, "Не передан UUID!", 23, true);
                    break;
                case "history":
                    if (isset($from->wallet_id) && $from->wallet_id !== 0) {
                        $history_controller = new HistoryController(true, $twig, $from->wallet_id);
                        $this->sendMessage($from, $history_controller->handle(), 12,false, false);
                        $this->log("Отправлена история транзакций \e[0;33m".$from->remoteAddress."\e[0m (ID ".$from->wallet_id.")", self::RESPONSE);
                    }else $this->sendMessage($from, "Не авторизован!", 24, true);
                    break;
                case "user_info":
                    $user_info = (new AccountsDB())->user_get_all(1);
                    $username = $user_info['account'][0]['name'] ?? 'Неизвестно';
                    $balance = $user_info['account'][0]['balance'] ?? -1.0;
                    $user_avatar = $user_info['account'][0]['image_src'] ?? 'src/images/user.jpg';
                    $user_avatar = "https://wallet.eather.ru/" . $user_avatar;

                    $this->sendMessage($from, json_encode([
                        "username" => $username,
                        "balance" => $balance,
                        "avatar" => $user_avatar,
                    ], self::JSON_OPTIONS), 13, false, false);
                    $this->log("Отправлено сообщение мобильному устройству", self::RESPONSE);
                    break;
                case "ping":
                    $this->sendMessage($from, "", 1190, false, false);
                    break;
                default:
                    $this->log("Неизвестный запрос от \e[0;33m".$from->remoteAddress."\e[0m", self::MESSAGE);
                    $this->sendMessage($from, "Undefined operation type", 4, true);
                    break;
            }
        }else{
            $this->log("Сообщение от \e[0;33m".$from->remoteAddress."\e[0m: \e[1;34m«".$msg."»\e[0m", self::MESSAGE);
            $this->sendMessage($from, "Undefined message", 25);
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
    private function sendMessageByWalletId(int $wallet_id, string $message, int $code=0, bool $isError=false, bool $log=true): void
    {
        $clients = $this->findClientsByWalletId($wallet_id);
        if (count($clients) > 0) {
            foreach ($clients as $client) {
                $this->sendMessage($client, $message, $code, $isError, $log);
            }
        }
    }
    private function sendMessage(ConnectionInterface $connection, string $message, int $code=0, bool $isError=false, bool $log=true): void
    {
        if ($code == 3 || $code == 4) {
            if (isset($connection->receiptsStat)) $connection->receiptsStat = "update";
            if (isset($connection->expensesStat)) $connection->expensesStat = "update";
        }
        if ($log) {
            $this->log("Отправлено сообщение: \e[1;34m«".$message."»\e[0m клиенту \e[0;33m".$connection->remoteAddress."\e[0m", self::RESPONSE);
        }
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