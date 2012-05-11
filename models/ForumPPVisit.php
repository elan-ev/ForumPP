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
     * Number of seconds before the last_visitdate will be overwritten if a
     * thread / area is opened
     */
    const LAST_VISIT_LIFETIME = 3600;

    /**
     * update the visitdate or set it initially for the passed topic
     * and return the last stored visitdate
     *
     * @param type $user_id
     * @param type $topic_id
     * @param type $seminar_id
     */
    static function set($user_id, $topic_id, $seminar_id) {
        // update visits older than one hour. Not the optimal place here
        self::updateVisitedEntries($user_id, $seminar_id, time() - self::LAST_VISIT_LIFETIME);

        // check, if there is already an entry in the db
        $stmt = DBManager::get()->prepare("SELECT visitdate, last_visitdate FROM
            forumpp_visits WHERE user_id = ?
                AND topic_id = ? AND seminar_id = ?");
        $stmt->execute(array($user_id, $topic_id, $seminar_id));
        $data = $stmt->fetch(PDO::FETCH_ASSOC);

        // if no entry exists, create one. Future visits will then mark new entries as unread
        if (!$data) {
            $visitdate = time() - self::LAST_VISIT_MAX;
            // use the parents last_visitdate as a starting point (if any)
            if ($parent_topic_id = ForumPPEntry::getParentTopicId($topic_id)) {
                $visitdate = self::get($user_id, $parent_topic_id, $seminar_id);
            }
            
            $stmt = DBManager::get()->prepare("INSERT INTO forumpp_visits
                (user_id, topic_id, seminar_id, visitdate, last_visitdate)
                VALUES (?, ?, ?, UNIX_TIMESTAMP(), ?)");

            $stmt->execute($new_data = array($user_id, $topic_id, $seminar_id, $visitdate));
        } else {
           // visitdate is always set to the latest visit
            $stmt = DBManager::get()->prepare("UPDATE forumpp_visits
                SET visitdate = UNIX_TIMESTAMP() , visited = 1
                WHERE user_id = ? AND topic_id = ? AND seminar_id = ?");
            $stmt->execute(array($user_id, $topic_id, $seminar_id));
        }
    }
    
    /**
     * updates all last_visitdates, which are used to display new postings
     * in the posting-view
     * 
     * @param string $user_id
     * @param string $seminar_id
     * @param int $since optional, constraint the update to entries older then this
     */
    static function updateVisitedEntries($user_id, $seminar_id, $since = false) {
        if ($since) {
            $stmt = DBManager::get()->prepare("UPDATE forumpp_visits
                SET last_visitdate = visitdate
                WHERE user_id = ? AND seminar_id = ?
                    AND visited = 1 AND last_visitdate <= ". $since);
        } else {
            $stmt = DBManager::get()->prepare("UPDATE forumpp_visits
                SET last_visitdate = visitdate, visited = 0
                WHERE user_id = ? AND seminar_id = ?
                    AND visited = 1");
        }
        $stmt->execute(array($user_id, $seminar_id));
    }

    /**
     * get the (last_)visitdate for the passed topic
     *
     * @param type $user_id
     * @param type $topic_id
     * @param type $seminar_id
     * @param bool $last_visitdate defaults to false, returns the last_visitdate
     *                             if true, visitdate if false
     * @return int the visitdate
     */
    static function get($user_id, $topic_id, $seminar_id, $last_visitdate = false) {
        $stmt = DBManager::get()->prepare("SELECT last_visitdate, visitdate FROM forumpp_visits
            WHERE user_id = ? AND topic_id = ? AND seminar_id = ?");

        $stmt->execute(array($user_id, $topic_id, $seminar_id));
        $dates = $stmt->fetch();
        
        if (!$dates) {
            return time() - self::LAST_VISIT_MAX;
        }

        return $last_visitdate ? $dates['last_visitdate'] : $dates['visitdate'];
    }

    /**
     * return number of new entries since last visit up to 3 month ago
     *
     * @param type $user_id the user_id for the entries
     * @param type $seminar_id the seminar_id for the entries
     * @return int the number of entries
     */
    static function getCount($user_id, $topic_id) {
        $constraints = ForumPPEntry::getConstraints($topic_id);
        
        $stmt = DBManager::get()->prepare("SELECT COUNT(*) FROM forumpp_entries
            WHERE lft > ? AND rgt < ? AND mkdate >= ? AND user_id != ?
                AND seminar_id = ?");
        $stmt->execute(array($constraints['lft'], $constraints['rgt'], 
            self::get($user_id, $topic_id, $constraints['seminar_id']),
            $user_id, $constraints['seminar_id']));
        
        $num_entries['abo'] = $stmt->fetchColumn();
       
        // get additionally the number of new entries since last visit
        if ($constraints['depth'] <= 2) {
            $stmt = DBManager::get()->prepare("SELECT COUNT(*) FROM forumpp_entries as fe
                LEFT JOIN forumpp_visits as fv ON (fe.topic_id = fv.topic_id 
                    AND fe.seminar_id = fv.seminar_id 
                    AND fv.user_id = ?)
                WHERE fv.topic_id IS NULL 
                    AND fe.lft > ? 
                    AND fe.rgt < ? 
                    AND fe.depth = ?
                    AND fe.seminar_id = ?
                    AND mkdate >= fv.last_visitdate");
            $stmt->execute(array($user_id, $constraints['lft'], $constraints['rgt'],
                $constraints['depth'] + 1, $constraints['seminar_id']));
        }
        
        $num_entries['new'] = $stmt->fetchColumn();

        return $num_entries;
    }

    /**
     * delete all entries in forumpp_visits for the passed topic and all childs
     * 
     * @param type $topic_id 
     */
    static function entryDelete($topic_id) {
        // get all topic_ids to remove
        $constraints = ForumPPEntry::getConstraints($topic_id);

        $stmt = DBManager::get()->prepare("SELECT topic_id FROM forumpp_entries
            WHERE lft >= ? AND rgt <= ? AND seminar_id = ?");
        $stmt->execute(array($constraints['lft'], $constraints['rgt'], $constraints['seminar_id']));
        $topic_ids = $stmt->fetchAll(PDO::FETCH_COLUMN);

        // delete all found entries
        $stmt = DBManager::get()->prepare("DELETE FROM forumpp_visits
            WHERE topic_id IN (:topic_ids)");
        $stmt->bindParam(':topic_ids', $topic_ids, StudipPDO::PARAM_ARRAY);
        $stmt->execute();
    }
    
    /**
     * returns true if there is an entry in the db for the passed user + topic
     * 
     * @param type $user_id
     * @param type $topic_id
     * @return bool
     */
    static function hasEntry($user_id, $topic_id) {
        $stmt = DBManager::get()->prepare("SELECT COUNT(*) FROM forumpp_visits
            WHERE user_id = ? AND topic_id = ?");
        $stmt->execute(array($user_id, $topic_id));
        
        return $stmt->fetchColumn() > 0;
    }
}
