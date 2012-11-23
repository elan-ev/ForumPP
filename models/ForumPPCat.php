<?php
/**
 * ForumPPCat.php - Class to handle categories for areas
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 3 of
 * the License, or (at your option) any later version.
 *
 * @author      Till Glöggler <tgloeggl@uos.de>
 * @license     http://www.gnu.org/licenses/gpl-3.0.html GPL version 3
 * @category    Stud.IP
 */

class ForumPPCat {
    
    /**
     * return a list of all available categories. Empty categories are excluded 
     * by default
     * 
     * @param string $seminar_id    the seminar_id the retrieve the categories for
     * @param string $exclude_null  if false, empty categories are returned as well
     * @return array list of categories
     */
    static function getList($seminar_id, $exclude_null = true)
    {
        $stmt = DBManager::get()->prepare("SELECT * FROM forumpp_categories AS fc
            LEFT JOIN forumpp_categories_entries AS fce USING (category_id)
            WHERE seminar_id = ? "
            . ($exclude_null ? 'AND fce.topic_id IS NOT NULL ' : '')
            . "ORDER BY fc.pos ASC, fce.pos ASC");

        $stmt->execute(array($seminar_id));

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    static function getCategoryNameForArea($topic_id)
    {
        $stmt = DBManager::get()->prepare("SELECT fc.entry_name FROM forumpp_categories AS fc
            LEFT JOIN forumpp_categories_entries AS fce USING (category_id)
            WHERE fce.topic_id = ?");
        $stmt->execute(array($topic_id));
        
        return $stmt->fetchColumn();
    }


    /**
     * 
     * @param type $seminar_id
     * @param type $name
     * @return type
     */
    static function add($seminar_id, $name)
    {
        $stmt = DBManager::get()->prepare("INSERT INTO forumpp_categories
            (category_id, seminar_id, entry_name)
            VALUES (?, ?, ?)");

        $category_id = md5(uniqid(rand()));
        
        $stmt->execute(array($category_id, $seminar_id, $name));
        
        return $category_id;
    }


    /**
     * 
     * @param type $category_id
     * @param type $seminar_id
     */
    static function remove($category_id, $seminar_id)
    {
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

    
    /**
     * 
     * @param type $category_id
     * @param type $pos
     */
    static function setPosition($category_id, $pos)
    {
        $stmt = DBManager::get()->prepare("UPDATE
            forumpp_categories
            SET pos = ? WHERE category_id = ?");
        $stmt->execute(array($pos, $category_id));        
    }

    
    /**
     * 
     * @param type $category_id
     * @param type $area_id
     */
    static function addArea($category_id, $area_id)
    {
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
    
    
    /**
     * 
     * @param type $area_id
     */
    static function removeArea($area_id)
    {
        $stmt = DBManager::get()->prepare("DELETE FROM
            forumpp_categories_entries
            WHERE topic_id = ?");
        $stmt->execute(array($area_id));
    }

    
    /**
     * 
     * @param type $area_id
     * @param type $pos
     */
    static function setAreaPosition($area_id, $pos)
    {
        $stmt = DBManager::get()->prepare("UPDATE
            forumpp_categories_entries
            SET pos = ? WHERE topic_id = ?");
        $stmt->execute(array($pos, $area_id));        
    }
    
    
    /**
     * 
     * @param type $category_id
     * @param type $name
     */
    static function setName($category_id, $name)
    {
        $stmt = DBManager::get()->prepare("UPDATE
            forumpp_categories
            SET entry_name = ? WHERE category_id = ?");
        $stmt->execute(array($name, $category_id));
    }
}