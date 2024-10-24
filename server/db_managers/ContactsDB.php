<?php
namespace db_managers;

use PDO;
use PDOException;

class ContactsDB extends DB
{
    public function __construct() {
        try {
            parent::__construct();
            $this->db->exec("
            CREATE TABLE IF NOT EXISTS `contacts` (
                `contact_owner` BIGINT NOT NULL,
                `contact_id` BIGINT NOT NULL,
                `contact_name` TEXT NOT NULL,
                `contact_image_src` TEXT NOT NULL,
                `contact_create` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`contact_owner`, `contact_id`)
            );");
        }catch(PDOException $e) {
            $this->createLog("ContactsDB", $e);
            $this->die = true;
        }
    }

    public function getAllContacts(int $owner_id): array {
        $result = [];
        try {
            $prepare = $this->db->prepare("SELECT * FROM `contacts` WHERE `contact_owner`=:owner_id;");
            if($prepare->execute([":owner_id" => $owner_id])){
                $fetch = $prepare->fetchAll(PDO::FETCH_ASSOC);
                if ($fetch !== false && count($fetch) > 0) {
                    foreach ($fetch as $row) {
                        $result[] = ["id" => $row["contact_id"], "name" => $row["contact_name"], "image_src" => $row["contact_image_src"]];
                    }
                }
            }
        }catch(PDOException $e) {
            $this->createLog("ContactsDB", $e);
        }
        return $result;
    }
    public function getContactById(int $owner_id, int $search_id): array {
        $result = [];
        try {
            $prepare = $this->db->prepare("SELECT * FROM `contacts` WHERE `contact_owner`=:owner_id AND `contact_id`=:search_id;");
            $prepare->bindValue(":owner_id", $owner_id, PDO::PARAM_INT);
            $prepare->bindValue(":search_id", $search_id, PDO::PARAM_INT);
            if ($prepare->execute()) {
                $fetch = $prepare->fetchAll(PDO::FETCH_ASSOC);
                if ($fetch !== false && count($fetch) > 0) {
                    foreach ($fetch as $row) {
                        $result = ["id" => $row["contact_id"], "name" => $row["contact_name"], "image_src" => $row["contact_image_src"]];
                    }
                }
            }
        }catch(PDOException $e) {
            $this->createLog("ContactsDB", $e);
        }
        return $result;
    }
    /*public function searchByName(string $name): array {
        $all = $this->config->getAll();
        $result_search = [];
        foreach ($all as $contact_id => $contact_info) {
            if (str_contains(mb_strtolower($contact_info['name']), mb_strtolower($name))) {
                $result_search[] = $contact_info;
            }
        }
        return $result_search;
    }*/

    public function addContact(int $owner_id, int $id_contact, string $name_contact): int {
        try {
            if ($this->existsContact($owner_id, $id_contact)) return -1;

            $accounts_db = new AccountsDB();
            if (!($accounts_db)->account_exists_id($id_contact)) return -2;

            $prepare = $this->db->prepare("INSERT INTO `contacts` (`contact_owner`, `contact_id`, `contact_name`, `contact_image_src`, `contact_create`) VALUES (:owner, :id, :name, :image_src, DEFAULT);");
            $prepare->bindValue(":owner", $owner_id, PDO::PARAM_INT);
            $prepare->bindValue(":id", $id_contact, PDO::PARAM_INT);
            $prepare->bindValue(":name", $name_contact);
            $prepare->bindValue(":image_src", $accounts_db->avatar_get($id_contact));
            return $prepare->execute();
        }catch(PDOException $e) {
            $this->createLog("ContactsDB", $e);
            return -3;
        }
    }
    /*public function removeContact(int $id_contact): int {
        if ($this->config->exists($id_contact)) {
            $this->config->remove($id_contact);
            return $this->config->save();
        }else return -1;
    }
    public function removeAllContacts(): bool {
        $this->config->setAll([]);
        return $this->config->save();
    }
*/
    public function getCountContacts($owner_id): int {
        try {
            $prepare = $this->db->prepare("SELECT COUNT(`contact_id`) AS `count_contacts` FROM `contacts` WHERE `contact_owner`=:id;");
            $prepare->bindValue(":id", $owner_id, PDO::PARAM_INT);
            if ($prepare->execute()) {
                return $prepare->fetch(PDO::FETCH_ASSOC)["count_contacts"] ?? 0;
            }
        }catch(PDOException $e) {
            $this->createLog("ContactsDB", $e);
        }
        return 0;
    }
    public function existsContact(int $owner, int $id): bool {
        try {
            $prepare = $this->db->prepare("SELECT `contact_id` FROM `contacts` WHERE `contact_owner` = :owner AND `contact_id` = :id");
            $prepare->bindValue(":owner", $owner, PDO::PARAM_INT);
            $prepare->bindValue(":id", $id, PDO::PARAM_INT);
            if ($prepare->execute()) {
                $fetch = $prepare->fetch(PDO::FETCH_NUM);
                return ($fetch !== false && count($fetch) > 0);
            }
        }catch(PDOException $e) {
            $this->createLog("ContactsDB", $e);
        }
        return false;
    }
}
