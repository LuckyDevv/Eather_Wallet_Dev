<?php

namespace controllers;

use db_managers\AccountsDB;
use Twig\Environment;
use Twig\Error\{LoaderError, RuntimeError, SyntaxError};

class MainController extends Controller
{
    private int $id = 0;
    private Environment $twig;
    public function __construct(int $id, Environment $twig) {
        if ($id > 0) $this->id = $id;
        $this->twig = $twig;
    }
    public function handle(): ?string
    {
        if ($this->id === 0) return null;
        $user_info = (new AccountsDB())->user_get_all($this->id);
        $username = $user_info['account'][0]['name'] ?? 'Неизвестно';
        $balance = $user_info['account'][0]['balance'] ?? -1.0;
        $user_avatar = $user_info['account'][0]['image_src'] ?? 'src/images/user.jpg';
        try {
            return $this->twig->render('main.twig', [
                "user_avatar" => $user_avatar,
                "user_name" => $username,
                "real_balance" => $balance
            ]);
        }catch (LoaderError|RuntimeError|SyntaxError $e) {
            $this->writeExceptionLog("ContactsController", $e);
            return "";
        }
    }
}