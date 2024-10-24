<?php
namespace db_managers;


use PDO;
use PDOException;
use PDOStatement;

class NewsDB extends DB
{
    public function __construct()
    {
        try {
            parent::__construct();
            @mkdir(__DIR__ . '/../../src/images/news', 0777, true);

            $this->db->exec("
            CREATE TABLE IF NOT EXISTS `news` (
                `news_id` BIGINT NOT NULL, 
                `news_title` TEXT NOT NULL, 
                `news_description` TEXT NOT NULL, 
                `news_image_src` TEXT NOT NULL, 
                PRIMARY KEY (`news_id`)
            );");
        }catch (PDOException $e){
            $this->createLog("NewsDB", $e);
            $this->die = true;
        }
    }

    public function createNews(string $image_filename, string $title, string $description): bool
    {
        try {
            $news_id = $this->getMaxId();
            switch ($news_id) {
                case -4:
                case -3:
                case -2:
                    return false;
                case -1:
                    $news_id = 1;
                    break;
                default:
                    $news_id++;
            }
            $prepare = $this->db->prepare("INSERT INTO `news` (`news_id`, `news_title`, `news_description`, `news_image_src`) VALUES (:id, :title, :desc, :image);");
            $prepare->bindValue(':id', $news_id, PDO::PARAM_INT);
            $prepare->bindValue(':title', $title);
            $prepare->bindValue(':desc', $description);
            $prepare->bindValue(':image', $image_filename);
            return $prepare->execute();
        }catch (PDOException $e){ $this->createLog("NewsDB", $e); }
        return false;
    }

    public function getAllNews(): array
    {
        $result = [];
        try {
            $stmt = $this->db->query("SELECT * FROM `news`");
            $fetch = $stmt->fetchAll();
            if ($fetch !== false && count($fetch) > 0) {
                foreach($fetch as $row){
                    $result[$row["news_id"]] = ["title" => $row["news_title"], "description" => $row["news_description"], "image" => $row["news_image_src"]];
                }
            }
        }catch(PDOException $e){ $this->createLog("NewsDB", $e); }
        return $result;
    }

    public function getNewsById(int $id): array
    {
        $result = [];
        try {
            $prepare = $this->db->prepare("SELECT * FROM `news` WHERE `news_id` = :id");
            $prepare->bindValue(':id', $id, PDO::PARAM_INT);
            $prepare->execute();
            $fetch = $prepare->fetch(PDO::FETCH_ASSOC);
            if (isset($fetch[0]['news_id'])) $result = $fetch[0];
        }catch (PDOException $e){}
        return $result;
    }

    public function removeNews(int $id): bool
    {
        try {
            $prepare = $this->db->prepare("DELETE FROM `news` WHERE `news_id` = :id");
            $prepare->bindValue(':id', $id, PDO::PARAM_INT);
            return $prepare->execute();
        }catch(PDOException $e){ $this->createLog("NewsDB", $e); }
        return false;
    }

    private function existsNews(int $id): bool {
        try {
            $prepare = $this->db->prepare("SELECT `news_id` FROM `news` WHERE `news_id` = :id");
            $prepare->execute([":id" => $id]);
            $fetch = $prepare->fetch(PDO::FETCH_ASSOC);
            return is_array($fetch) && isset($fetch['news_id']);
        }catch(PDOException $e){ $this->createLog("NewsDB", $e); }
        return false;
    }

    private function getMaxId(): int {
        try {
            $stmt = $this->db->query("SELECT MAX(`news_id`) AS 'max_id' FROM `news`;");
            if ($stmt instanceof PDOStatement) {
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                if (is_array($result)) return $result['max_id'] ?? -1;
                else return -2;
            }else return -3;
        }catch(PDOException $e) {
            $this->createLog("NewsDB", $e);
            return -4;
        }
    }
}