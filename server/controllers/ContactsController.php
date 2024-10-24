<?php
namespace controllers;

use db_managers\ContactsDB;
use Twig\Environment;
use Twig\Error\{LoaderError, RuntimeError, SyntaxError};

class ContactsController extends Controller
{
    private int $id = 0;
    private Environment $twig;
    public function __construct(int $id, Environment $twig)
    {
        if ($id > 0) $this->id = $id;
        $this->twig = $twig;
    }

    public function handle(): string
    {
        if ($this->id > 0) {
            try {
                $contacts_db = new ContactsDB();
                $contacts = $contacts_db->getAllContacts($this->id);
                $stop_text = ($contacts_db->getCountContacts($this->id) === 0) ? 'У вас нет контактов, но вы можете добавить их при помощи кнопки сверху!' : 'На этом ваши контакты закончились...';
                return $this->twig->render('contact-list.twig', [
                    "contacts" => $contacts,
                    "stop_text" => $stop_text
                ]);
            } catch (LoaderError|RuntimeError|SyntaxError $e) {
                $this->writeExceptionLog("ContactsController", $e);
            }
        }
        return "";
    }
}