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