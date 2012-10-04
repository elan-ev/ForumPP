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

#require_once 'lib/statusgruppe.inc.php';

class ForumPPPerm {

    static function has($perm, $seminar_id, $user_id = null)
    {
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
        
        // take care of the not logged in user
        if ($user_id == 'nobody') {
            $status = 'user';
        }
        
        // root and admins have all possible perms
        if (in_array($status, words('root admin')) !== false) {
            return true;
        }
        
        // check the status and the passed permission
        if ($status == 'dozent' && in_array($perm,
            words('edit_category add_category remove_category sort_category '
            . 'edit_area add_area remove_area sort_area '
            . 'search edit_entry add_entry remove_entry fav_entry like_entry move_thread abo')
        ) !== false) {
            return true;
        } else if ($status == 'tutor' && in_array($perm, words('search add_entry fav_entry like_entry abo')) !== false) {
            return true;
        } else if ($status == 'autor' && in_array($perm, words('search add_entry fav_entry like_entry abo')) !== false) {
            return true;
        } else if ($status == 'user' && in_array($perm, words('search add_entry')) !== false) {
            return true;
        }
        
        // user has no permission
        return false;
    }

    function check($perm, $seminar_id, $user_id = null)
    {
        if (!self::has($perm, $seminar_id, $user_id)) {
            throw new AccessDeniedException(sprintf(
                _("Sie haben keine Berechtigung für diese Aktion! Benötigte Berechtigung: %s"),
                $perm)
            );
        }
    }
    
    /**
     * check, if the current user is allowed the edit the topic denoted by the passed id
     * 
     * @staticvar array $perms
     * 
     * @param string $topic_id the id for the topic to check for
     * 
     * @return bool true if the user has the necessary perms, false otherwise
     */
    static function hasEditPerms($topic_id)
    {
        static $perms = array();

        if (!$perms[$topic_id]) {
            // find out if the posting is the last in the thread
            $constraints = ForumPPEntry::getConstraints($topic_id);
            
            $stmt = DBManager::get()->prepare("SELECT user_id, seminar_id
                FROM forumpp_entries WHERE topic_id = ?");
            $stmt->execute(array($topic_id));

            $data = $stmt->fetch();

            $perms[$topic_id] = (($GLOBALS['user']->id == $data['user_id']) ||
                ForumPPPerm::has('edit_entry', $constraints['seminar_id']));
        }

        return $perms[$topic_id];
    }    
}
