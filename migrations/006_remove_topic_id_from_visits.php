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

class RemoveTopicIdFromVisits extends DBMigration {
    function up() {
        DBManager::get()->exec("DELETE FROM `forumpp_visits` WHERE seminar_id != topic_id");
        DBManager::get()->exec("ALTER TABLE `forumpp_visits` DROP `topic_id`");
        DBManager::get()->exec("ALTER TABLE `forumpp_visits` DROP `visited`");
    }
    
    function down() {
    }
}
