<?php
require __DIR__.'/../../vendor/autoload.php';

use db_managers\AccountsDB;
use db_managers\SessionDB;
use db_managers\TransactionsDB;

date_default_timezone_set('Europe/Moscow');

const JSON_OPTIONS = JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_BIGINT_AS_STRING;
function error(string $message, int $code): never
{
    die(json_encode(['error' => ['message' => $message, 'code' => $code]], JSON_OPTIONS));
}
function response(string $message, int $code): never
{
    die(json_encode(['response' => ['message' => $message, 'code' => $code]], JSON_OPTIONS));
}

switch ($_POST['type'] ?? 'none') {
    case 'check_id':
        if (!isset($_POST['session_id'])) error('Не введён Session ID!', 1);
        if (!isset($_POST['user_uuid'])) error("Не введен User UUID!", 2);
        if (!isset($_POST['id'])) error('Не введён ID!', 3);
        if (!is_numeric($_POST['id'])) error('ID должен быть числом!', 4);
        try {
            $id = (new SessionDB())->validateSession($_POST['session_id'], $_POST['user_uuid']);
            if ($id > 0) {
                $id_2 = (int) $_POST['id'];
                if ($id != $id_2) {
                    $accounts_db = new AccountsDB();
                    if ($accounts_db->account_exists_id($id_2)) {
                        $name = $accounts_db->name_get($id_2);
                        if ($name !== null) {
                            response($name, 0);
                        }else error("Не удалось запросить имя получателя!", 5);
                    }else error("Кошелька с таким ID не существует!", 6);
                }else error("Нельзя переводить самому себе!", 7);
            }else error("Не удалось найти ваш аккаунт в базе данных!", 8);
        }catch(Exception $e) {
            error("Не удалось найти ваш аккаунт в базе данных!", 9);
        }
}