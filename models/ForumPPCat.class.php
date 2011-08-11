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

    static function setPosition($area_id, $pos) {

    }

    static function addArea($category_id, $area_id) {
        // #TODO: check if passed category/area exists
        $stmt = DBManager::get()->prepare("INSERT INTO
            forumpp_categories_entries
            (category_id, topic_id) VALUES (?, ?)");
        $stmt->execute(array($category_id, $ara_id));
    }

    static function setAreaPosition($area_id, $pos) {
    }
}