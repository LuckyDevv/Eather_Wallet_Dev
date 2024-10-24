<?php
namespace db_managers;

use PDOException;

class HistoryDB extends DB
{
    public bool $die = false;
    public function __construct()
    {
        try {

        }catch(PDOException $e){

        }
    }
}