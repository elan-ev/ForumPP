<?php

class ForumPPCat {
    static function getList($seminar_id) {
        $stmt = DBManager::get()->prepare("SELECT * FROM forumpp_categories
            WHERE seminar_id = ?
            ORDER BY pos ASC");

        $stmt->execute(array($seminar_id));

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    static function add($seminar_id, $name) {
        $stmt = DBManager::get()->prepare("INSERT INTO forumpp_categories
            (category_id, seminar_id, entry_name)
            VALUES (?, ?, ?)");

        $stmt->execute(array(md5(uniqid(rand())), $seminar_id, $name));
    }

    static function addEntry($area_id, $entry_id) {
    }

    static function setPosition($area_id, $pos) {}
}