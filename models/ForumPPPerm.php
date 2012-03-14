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

class ForumPPPerm {
    static function has($perm, $seminar_id, $user_id = null) {
        static $permissions = array();

        // if no user-id is passed, use the current user (for your convenience)
        if (!$user_id) {
            $user_id = $GLOBALS['user']->id;
        }
        
        // get the status for the user in the passed seminar
        if (!$permissions[$seminar_id][$user_id]) {
            $permissions[$seminar_id][$user_id] = $GLOBALS['perm']->get_studip_perm($seminar_id, $user_id);
        }
        
        $status = $permissions[$seminar_id][$user_id];
        
        // root and admins have all possible perms
        if (in_array($status, words('root admin')) !== false) {
            return true;
        }
        
        // check the status and the passed permission
        if ($status == 'dozent' && in_array($perm,
            words('edit_category add_category remove_category sort_category '
            . 'edit_area add_area remove_area sort_area '
            . 'search edit_entry add_entry remove_entry move_thread')
        ) !== false) {
            return true;
        } else if ($status == 'tutor' && in_array($perm, words('search add_entry')) !== false) {
            return true;
        } else if ($status == 'autor' && in_array($perm, words('search add_entry')) !== false) {
            return true;
        } else if ($status == 'user' && in_array($perm, words('')) !== false) {
            return true;
        }
        
        // user has no permission
        return false;
    }
    
    function check($perm, $seminar_id, $user_id = null) {
        if (!self::has($perm, $seminar_id, $user_id)) {
            throw new AccessDeniedException(sprintf(
                _("Sie haben keine Berechtigung für diese Aktion! Benötigte Berechtigung: %s"),
                $perm)
            );
        }
    }
}