<?php

/**
 * filename - Short description for file
 *
 * Long description for file (if any)...
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

class AddTables extends DBMigration {
    function up() {
        DBManager::get()->exec("
            CREATE TABLE IF NOT EXISTS `forumpp_visits` (
                user_id varchar(32) NOT NULL,
                seminar_id varchar(32) NOT NULL,
                topic_id varchar(32) NOT NULL,
                visitdate int(11) NOT NULL,
                last_visitdate int(11) NOT NULL,
                new_entries int(11) NOT NULL,
                visited tinyint(1) NOT NULL DEFAULT 0,
                PRIMARY KEY ( `user_id` , `seminar_id`, `topic_id` )
            );
        ");
        
        DBManager::get()->exec("
            CREATE TABLE IF NOT EXISTS `forumpp_favorites` (
                user_id varchar(32) NOT NULL,
                topic_id varchar(32) NOT NULL,
                PRIMARY KEY ( `user_id` , `topic_id` )
            );
        ");
    }
    
    function down() {
         $this->db->query("DROP TABLE IF EXISTS forumpp_visits;");
         $this->db->query("DROP TABLE IF EXISTS forumpp_favorites;");
    }
}
