<?php
namespace controllers;

use Error;
use Exception;

abstract class Controller
{
    protected function writeExceptionLog(string $className, Exception $exception): void
    {
        $text = "[" . date("d.m.Y H:i:s") . "] Произошло исключение в ".$className.": ".$exception->getMessage().". На строке ".$exception->getLine().", файл: ".$exception->getFile()."\n\n";
        file_put_contents(__DIR__.'/../logs/'.$className.'_Errors.log', $text, FILE_APPEND);
    }
    protected function writeErrorLog(string $className, Error $error): void
    {
        $text = "[" . date("d.m.Y H:i:s") . "] Произошла критическая ошибка в ".$className.": ".$error->getMessage().". На строке ".$error->getLine().", файл: ".$error->getFile()."\n\n";
        file_put_contents(__DIR__.'/../logs/'.$className.'_Errors.log', $text, FILE_APPEND);
    }
    public abstract function handle();
}