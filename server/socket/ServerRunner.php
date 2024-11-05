<?php
require __DIR__."/../../vendor/autoload.php";
ini_set("error_reporting", E_ALL&~E_DEPRECATED);
date_default_timezone_set('Europe/Moscow');
use Ratchet\Http\HttpServer;
use Ratchet\Server\IoServer;
use Ratchet\WebSocket\WsServer;
use socket\SocketHandler;
$server = IoServer::factory(
    new HttpServer(
        new WsServer(
            new SocketHandler()
        )
    ), 8080);
$server->run();