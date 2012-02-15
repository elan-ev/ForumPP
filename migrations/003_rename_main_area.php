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

class RenameMainArea extends DBMigration {
    function up() {
        DBManager::get()->exec("
            UPDATE forumpp_entries
            SET name = 'Übersicht'
            WHERE topic_id = seminar_id
        ");
    }

    function down() {
    }
}