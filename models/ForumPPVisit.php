<?php
/**
 * ForumPPVisit - Functions for visit-dates for threads
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 3 of
 * the License, or (at your option) any later version.
 *
 * @author      Till Gl�ggler <tgloeggl@uos.de>
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
    
    static function setVisit($seminar_id) {
        if (self::getLastVisit($seminar_id) < object_get_visit($seminar_id, 'sem', false, false)) {
            self::setVisitDates($seminar_id);
        }
    }

    /**
     * Stores the visitdate in last_visitdate and sets the current time for as new visitdate
     * 
     * @param string $seminar_id the seminar that has been entered
     */
    static function setVisitdates($seminar_id) {
        $stmt = DBManager::get()->prepare('SELECT visitdate FROM forumpp_visits
            WHERE user_id = ? AND seminar_id = ?');
        $stmt->execute(array($GLOBALS['user']->id, $seminar_id));
        $visitdate = $stmt->fetchColumn();
        
        $stmt = DBManager::get()->prepare("REPLACE INTO forumpp_visits
            (user_id, seminar_id, visitdate, last_visitdate)
            VALUES (?, ?, UNIX_TIMESTAMP(), ?)");
        $stmt->execute(array($GLOBALS['user']->id, $seminar_id, $visitdate));
        
    }

    
    /**
     * returns visitdate and last_visitdate for the passed seminar and the
     * currently logged in user
     * 
     * @staticvar array $visit
     * 
     * @param string $seminar_id the seminar to fetch the visitdates for
     * @return mixed an array containing visitdate and last_visitdate
     */
    private static function getVisitDates($seminar_id)
    {
        static $visit = array();
        
        if (!$visit[$seminar_id]) {
            $stmt = DBManager::get()->prepare("SELECT visitdate, last_visitdate FROM forumpp_visits
                WHERE seminar_id = ? AND user_id = ?");
            $stmt->execute(array($seminar_id, $GLOBALS['user']->id));
            $visit[$seminar_id] = $stmt->fetch(PDO::FETCH_ASSOC);

            // no entry for this seminar yet present, create a new one
            if (!$visit[$seminar_id]) { 
                $stmt = DBManager::get()->prepare("INSERT INTO forumpp_visits
                    (seminar_id, user_id, visitdate, last_visitdate) VALUES
                    (?, ?, UNIX_TIMESTAMP(), UNIX_TIMESTAMP())");
                $stmt->execute(array($seminar_id, $GLOBALS['user']->id));
            
                // set visitdate to current time
                $visit[$seminar_id] = array(
                    'visit'      => time() - ForumPPVisit::LAST_VISIT_MAX,
                    'last_visit' => time() - ForumPPVisit::LAST_VISIT_MAX
                );
            }
            
            // prevent visit-dates from being older than LAST_VISIT_MAX allows
            foreach ($visit[$seminar_id] as $type => $date) {
                if ($date < time() - ForumPPVisit::LAST_VISIT_MAX) {
                    $visit[$seminar_id][$type] = time() - ForumPPVisit::LAST_VISIT_MAX;
                }
            }
        }
        
        return $visit[$seminar_id];
    }
      
    /**
     * return the last_visitdate for the passed seminar and currently logged in user
     * 
     * @param type $seminar_id the seminar to get the last_visitdate for
     * @return int a timestamp 
     */
    static function getLastVisit($seminar_id)
    { 
        $visit = self::getVisitDates($seminar_id);
        return $visit['last_visitdate'];
    }
    
    /**
     * return the visitdate for the passed seminar and currently logged in user
     * 
     * @param type $seminar_id the seminar to get the visitdate for
     * @return int a timestamp 
     */
    static function getVisit($seminar_id)
    {
        $visit = self::getVisitDates($seminar_id);
        return $visit['visitdate'];
    }
}
