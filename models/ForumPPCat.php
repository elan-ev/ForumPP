<?php

class ForumPPCat {
    static function getList($seminar_id, $exclude_null = true) {
        $stmt = DBManager::get()->prepare($query = "SELECT * FROM forumpp_categories AS fc
            LEFT JOIN forumpp_categories_entries AS fce USING (category_id)
            WHERE seminar_id = ? "
            . ($exclude_null ? 'AND fce.topic_id IS NOT NULL ' : '')
            . "ORDER BY fc.pos ASC, fce.pos ASC");

        $stmt->execute(array($seminar_id));

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    static function add($seminar_id, $name) {
        $stmt = DBManager::get()->prepare("INSERT INTO forumpp_categories
            (category_id, seminar_id, entry_name)
            VALUES (?, ?, ?)");

        $stmt->execute(array(md5(uniqid(rand())), $seminar_id, $name));
    }

    static function remove($category_id, $seminar_id) {
        // delete the category itself
        $stmt = DBManager::get()->prepare("DELETE FROM
            forumpp_categories
            WHERE category_id = ?");
        $stmt->execute(array($category_id));
        
        // set all entries to default category
        $stmt = DBManager::get()->prepare("UPDATE
            forumpp_categories_entries
            SET category_id = ?, pos = 999
            WHERE category_id = ?");
        $stmt->execute(array($seminar_id, $category_id));
    }

    static function setPosition($category_id, $pos) {
        $stmt = DBManager::get()->prepare("UPDATE
            forumpp_categories
            SET pos = ? WHERE category_id = ?");
        $stmt->execute(array($pos, $category_id));        
    }

    static function addArea($category_id, $area_id) {
        // remove area from all other categories
        $stmt = DBManager::get()->prepare("DELETE FROM
            forumpp_categories_entries
            WHERE topic_id = ?");
        $stmt->execute(array($area_id));

        // add area to this category, make sure it is at the end
        $stmt = DBManager::get()->prepare("SELECT COUNT(*) FROM
            forumpp_categories_entries
            WHERE category_id = ?");
        $stmt->execute(array($category_id));
        $new_pos = $stmt->fetchColumn() + 1;

        $stmt = DBManager::get()->prepare("REPLACE INTO
            forumpp_categories_entries
            (category_id, topic_id, pos) VALUES (?, ?, ?)");
        $stmt->execute(array($category_id, $area_id, $new_pos));
    }
    
    static function removeArea($area_id) {
        $stmt = DBManager::get()->prepare("DELETE FROM
            forumpp_categories_entries
            WHERE topic_id = ?");
        $stmt->execute(array($area_id));
    }

    static function setAreaPosition($area_id, $pos) {
        $stmt = DBManager::get()->prepare("UPDATE
            forumpp_categories_entries
            SET pos = ? WHERE topic_id = ?");
        $stmt->execute(array($pos, $area_id));        
    }
    
    static function setName($category_id, $name) {
        $stmt = DBManager::get()->prepare("UPDATE
            forumpp_categories
            SET entry_name = ? WHERE category_id = ?");
        $stmt->execute(array($name, $category_id));
    }
}