<?php
require __DIR__.'/../../vendor/autoload.php';

use controllers\HistoryController;
use db_managers\SessionDB;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

date_default_timezone_set('Europe/Moscow');

const JSON_OPTIONS = JSON_UNESCAPED_UNICODE;
function error(string $message, int $code): never
{
    die(json_encode(['error' => ['message' => $message, 'code' => $code]], JSON_OPTIONS));
}
function response(string $message, int $code): never
{
    die(json_encode(['response' => ['message' => $message, 'code' => $code]], JSON_OPTIONS));
}

$loader = new FilesystemLoader(__DIR__.'/../resources/twig');
$twig = new Environment($loader, [
    'cache' => __DIR__.'/../resources/twig_cache',
    'auto_reload' => true,
    'autoescape' => false,
]);
$history_controller = new HistoryController(true, $twig);
if (!isset($_POST['session_id'])) response($history_controller->parseHistoryError(), 22);
if (!isset($_POST['user_uuid'])) response($history_controller->parseHistoryError(), 22);
$transaction_id = $_POST['transaction_id'] ?? "";
try {
    $id = (new SessionDB())->validateSession($_POST['session_id'], $_POST['user_uuid']);
    if ($id > 0) {
        $history_controller->id = $id;
        switch($_POST['type'] ?? 'none') {
            case "all":
                response($history_controller->handle(), 2);
            case "one":
                $history_controller->typeAll = false;
                $history_controller->transaction_id = $transaction_id;
                response(json_encode($history_controller->handle(), JSON_UNESCAPED_UNICODE), 2);
            case "none":
                response($history_controller->parseHistoryError(), 22);
        }
    }
}catch(Exception $e) {
    $text = "[" . date("d.m.Y H:i:s") . "] Произошла критическая ошибка в History API: ".$e->getMessage().". На строке ".$e->getLine().", файл: ".$e->getFile()."\n\n";
    file_put_contents(__DIR__."/../logs/History_API.log", $text, FILE_APPEND);
}
response($history_controller->parseHistoryError(), 22);