<?php
/**
 * ForumPPVisit - Functions for visit-dates for threads
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

class ForumPPVisit {

    /**
     * This is the maximum number of seconds that unread entries are
     * marked as new.
     */
    const LAST_VISIT_MAX = 7776000; // 90 days

    /**
     * return number of new entries since last visit up to 3 month ago
     *
     * @param string $seminar_id the seminar_id for the entries
     * @param string $visitdate count all entries newer than this timestamp
     * 
     * @return int the number of entries
     */
    static function getCount($seminar_id, $visitdate)
    {
        if ($visitdate < time() - ForumPPVisit::LAST_VISIT_MAX) {
            $visitdate = time() - ForumPPVisit::LAST_VISIT_MAX;
        }

        $constraints = ForumPPEntry::getConstraints($seminar_id);

        $stmt = DBManager::get()->prepare("SELECT COUNT(*) FROM forumpp_entries
            WHERE lft >= :lft AND rgt <= :rgt AND user_id != :user_id
                AND seminar_id = :seminar_id
                AND chdate > :lastvisit");
        
        $stmt->bindParam(':user_id', $GLOBALS['user']->id);
        $stmt->bindParam(':lft', $constraints['lft']);
        $stmt->bindParam(':rgt', $constraints['rgt']);
        $stmt->bindParam(':seminar_id', $constraints['seminar_id']);
        $stmt->bindParam(':lastvisit', $visitdate);
        
        $stmt->execute();
        
        return $stmt->fetchColumn();
    }
    
    static function enter_seminar($seminar_id) {
        $stmt = DBManager::get()->prepare("REPLACE INTO forumpp_visits
            (user_id, seminar_id, visitdate, last_visitdate)
            VALUES (?, ?, UNIX_TIMESTAMP(), visitdate)");
        $stmt->execute(array($GLOBALS['user']->id, $seminar_id));
    }

    static function getLastVisit($seminar_id)
    {
        static $last_visit = array();
        
        if (!$last_visit[$seminar_id]) {
            // $last_visit[$seminar_id] = object_get_visit($seminar_id, 'sem');
            $stmt = DBManager::get()->prepare("SELECT last_visitdate FROM forumpp_visits
                WHERE seminar_id = ? AND user_id = ?");
            $stmt->execute(array($seminar_id, $GLOBALS['user']->id));
            $last_visit[$seminar_id] = $stmt->fetchColumn();
            
            if ($last_visit[$seminar_id] < time() - ForumPPVisit::LAST_VISIT_MAX) {
                $last_visit[$seminar_id] = time() - ForumPPVisit::LAST_VISIT_MAX;
            }
        }
        
        return $last_visit[$seminar_id];
    }
}
