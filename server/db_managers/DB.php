<?php
namespace db_managers;

use controllers\ConfigController;
use PDO;
use PDOException;

class DB {
    protected \PDO $db;
    public bool $die = false;
    public function __construct()
    {
        @mkdir(__DIR__.'/../logs/');
        try {
            $settings = ConfigController::MYSQL_CONNECTION;
            $this->db = new PDO("mysql:host=" . $settings['ip'], $settings['user'], $settings['password']);
            $this->db->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, false);
            $this->db->query("CREATE DATABASE IF NOT EXISTS `wallet_db`;");
            $this->db->query("SET NAMES utf8;");
            $this->db->exec("USE `wallet_db`;");
        }catch (\PDOException $e) {
            throw new \PDOException($e->getMessage(), (int)$e->getCode());
        }
    }
    protected function createLog(string $className, PDOException $exception): void
    {
        $text = "[" . date("d.m.Y H:i:s") . "] Произошла ошибка в ".$className.": ".$exception->getMessage().". На строке ".$exception->getLine().", файл: ".$exception->getFile()."\n\n";
        file_put_contents(__DIR__.'/../logs/'.$className.'_Errors.log', $text, FILE_APPEND);
    }
}