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

class AddAboTables extends DBMigration {
    function up() {
        DBManager::get()->exec("
            ALTER TABLE `forumpp_entries` ADD `area` TINYINT NOT NULL DEFAULT '0'
        ");
        
        DBManager::get()->exec("
            CREATE TABLE IF NOT EXISTS `forumpp_abo_users` (
                `topic_id` varchar(32) NOT NULL,
                `user_id` varchar(32) NOT NULL,
                PRIMARY KEY (`topic_id`,`user_id`)
            )
        ");
        
    }
    
    function down() {
        DBManager::get()->exec("ALTER TABLE `forumpp_entries` DROP `area`");
        DBManager::get()->exec("DROP TABLE `forumpp_abo_users`");
    }
}
