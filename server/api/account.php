<?php
require __DIR__.'/../../vendor/autoload.php';

use db_managers\AccountsDB;
use db_managers\ContactsDB;
use db_managers\SessionDB;

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

switch ($_POST['type'] ?? null)
{
    case 'reg':
        if (!isset($_POST['user_uuid'])) error("Не найден User UUID!", 10);
        $browser = $_POST['browser'] ?? "Неизвестный браузер";
        $os = $_POST['os'] ?? "Неизвестная ОС";
        if (!isset($_POST['password'])) {
            error("Не введён пароль!", 1);
        } else {
            $name = $_POST['name'] ?? 'Пользователь';
            $password = $_POST['password'];
            if (mb_strlen($name) < 3 || mb_strlen($name) > 20) {
                error('Имя должно быть длиной от 3 до 20 символов!', 2);
            }
            if (!preg_match('/[a-zа-яё\s]+$/iu', mb_strtolower($name))) {
                error('Имя должно содержать только буквы!', 3);
            }

            if (strlen($password) < 8) {
                error('Пароль должен быть длиной от 8 символов!', 4);
            }
            if(!preg_match('/[0-9A-Za-z!@#$%-]$/iu', $password)) {
                error("Пароль должен состоять из латинских букв, цифр и специальных знаков!", 5);
            }

            $accounts_db = new AccountsDB();
            if (!$accounts_db->die) {
                $response = $accounts_db->account_create($name, $password);
                switch ($response) {
                    case -1:
                        error('Не удалось выполнить запрос!', 7);
                    case -2:
                        error("Не удалось выполнить запрос!", 8);
                    case -3:
                        error('Произошла ошибка в базе данных!', 9);
                    case -4:
                        error('Произошла ошибка в базе данных!', 6);
                    default:
                        $session_db = new SessionDB();
                        $session = $session_db->createSession($password, $response, $_POST['user_uuid'], $browser, $os);
                        switch ($session) {
                            case "err_1":
                                error("nono", 17);
                            case "err_2":
                                error("nonon", 17);
                            default:
                                response($session, 0);
                        }
                }
            }else error('Произошла ошибка в базе данных! 5', 10);
        }
    case 'auth':
        if (!isset($_POST['password'])) error('Не введён пароль!', 11);
        if (!isset($_POST['id'])) error('Не введён ID!', 12);
        if (!isset($_POST['user_uuid'])) error("Не найден User UUID!", 16);
        $browser = $_POST['browser'] ?? "Неизвестный браузер";
        $os = $_POST['os'] ?? "Неизвестная ОС";
        if (!is_numeric($_POST['id'])) error('ID должен быть числом!', 13);

        $id = (int) $_POST['id'];
        $password = $_POST['password'];
        $ip = $_SERVER['REMOTE_ADDR'];
        $accounts_db = new AccountsDB();
        if (!$accounts_db->die) {
            switch ($accounts_db->account_auth_id($id, $password)) {
                case 0:
                    $session_db = new SessionDB();
                    $session = $session_db->createSession($password, $id, $_POST['user_uuid'], $browser, $os);
                    switch ($session) {
                        case "err_1":
                            error("nono", 17);
                        case "err_2":
                            error("nonon", 17);
                        default:
                            response($session, 2);
                    }
                case 1:
                    error('Неверный пароль!', 14);
                case 2:
                    error('Такого аккаунта не существует!', 15);
            }
        }else error('Произошла ошибка в базе данных!', 16);
    case 'check_id':
        if (!isset($_POST['id'])) error('Не введён ID!', 17);
        if (!is_numeric($_POST['id'])) error('ID должен быть числом!', 18);

        $id = (int) $_POST['id'];
        $accounts_db = new AccountsDB();
        if (!$accounts_db->die) {
            if ($accounts_db->account_exists_id($id)) {
                response('Аккаунт найден!', 3);
            }else error('Аккаунт не найден!', 19);
        }else error('Произошла ошибка в базе данных!', 20);
    case "contact_add":
        if (!isset($_POST['session_id'])) error('Не введён Session ID!', 27);
        if (!isset($_POST['contact_id'])) error("Не введён ID контакта!", 28);
        if (!isset($_POST['user_uuid'])) error("Не введен User UUID!", 28);
        $contact_id = $_POST['contact_id'];
        if (!is_numeric($contact_id)) error("ID должен быть числом!", 29);

        $contact_name = $_POST['contact_name'] ?? "Новый контакт";
        if (mb_strlen($contact_name) > 23) error("Имя контакта должно быть не более 23 символов!", 30);
        try {
            $id = (new SessionDB())->validateSession($_POST['session_id'], $_POST['user_uuid']);
            if ($id > 0) {
                if ($contact_id == $id) error("Нельзя добавить в контакты самого себя!", 31);
                try {
                    $contact_db = new ContactsDB();
                    switch ($contact_db->addContact($id, $contact_id, $contact_name)) {
                        case 1:
                            ob_start();
                            include(__DIR__.'/../../src/tpl/contact.tpl');
                            $contact_tpl = ob_get_clean();
                            $contact = "";
                            $contact_info = $contact_db->getContactById($id, $contact_id);
                            if (count($contact_info) > 0) {
                                $contact = str_replace(array(
                                    '{contact_photo}',
                                    '{contact_name}',
                                    '{send_button}',
                                    '{contact_id}'
                                ), array(
                                    $contact_info['image_src'],
                                    $contact_info['name'],
                                    'src/svg/arrow-right.svg',
                                    $contact_info['id'],
                                ), $contact_tpl);
                            }
                            response($contact, 5);
                        case 0:
                            error("Не удалось добавить контакт!", 32);
                        case -1:
                            error("У вас уже добавлен этот контакт!", 33);
                        case -2:
                            error("Не найден аккаунт с таким ID!", 34);
                        case -3:
                            error("Произошла ошибка в базе данных!", 35);
                    }
                }catch(Exception){
                    error("Не удалось найти ваш аккаунт в базе данных!", 36);
                }
            }else error($_POST['session_id'], 37);
        }catch(Error) {}
}