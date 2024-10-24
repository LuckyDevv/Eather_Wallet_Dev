<?php
namespace controllers;

use db_managers\NewsDB;
use Twig\Environment;
use Twig\Error\{LoaderError, RuntimeError, SyntaxError};

class NewsController extends Controller
{
    private Environment $twig;
    public function __construct(Environment $twig) {
        $this->twig = $twig;
    }
    public function handle(): array
    {
        $news = (new NewsDB())->getAllNews();
        if (count($news) > 0) {
            try {
                return [
                    "list" => $this->twig->render("news.twig", ["news" => $news]),
                    "stop_text" => 'На этом новости и предложения закончились...'
                ];
            }catch (LoaderError|RuntimeError|SyntaxError $e) {
                $this->writeExceptionLog("ContactsController", $e);
            }
        }
        return [
            "list" => "",
            "stop_text" => 'Новостей и предложений пока что нет...'
        ];
    }
}