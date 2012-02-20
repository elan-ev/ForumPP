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
            /*
            if ($data['last_visitdate'] < (time() - self::LAST_VISIT_LIFETIME)) {
                // the last_visitdate is overwritten after an hour
                $stmt = DBManager::get()->prepare("UPDATE forumpp_visits
                    SET visitdate = UNIX_TIMESTAMP(), last_visitdate = ?, new_entries = 0
                    WHERE user_id = ? AND topic_id = ? AND seminar_id = ?");
                $stmt->execute(array($data['visitdate'], $user_id, $topic_id, $seminar_id));
            } else {
             * 
             */
                // visitdate is always set to the latest visit
                $stmt = DBManager::get()->prepare("UPDATE forumpp_visits
                    SET visitdate = UNIX_TIMESTAMP() /* , update_on_entry = 1 */
                    WHERE user_id = ? AND topic_id = ? AND seminar_id = ?");
                $stmt->execute(array($user_id, $topic_id, $seminar_id));
            // }
        }
    }
    
    static function enterSeminar($user_id, $seminar_id) {
        $stmt = DBManager::get()->prepare("UPDATE forumpp_visits
            SET last_visitdate = visitdate, new_entries = 0
            WHERE user_id = ? AND seminar_id = ?
             /* AND #TODO: only update those who have not been revisited */");
        $stmt->execute(array($user_id, $seminar_id));
    }

    /**
     * get the visitdate for the passed topic
     *
     * @param type $user_id
     * @param type $topic_id
     * @param type $seminar_id
     * @return int the visitdate
     */
    static function get($user_id, $topic_id, $seminar_id) {
        $stmt = DBManager::get()->prepare("SELECT last_visitdate FROM forumpp_visits
            WHERE user_id = ? AND topic_id = ? AND seminar_id = ?");

        $stmt->execute(array($user_id, $topic_id, $seminar_id));

        return $stmt->fetchColumn() ?: time() - self::LAST_VISIT_MAX;
    }

    /**
     * return number of new entries since last visit up to 3 month ago
     *
     * @param type $user_id the user_id for the entries
     * @param type $seminar_id the seminar_id for the entries
     * @return int the number of entries
     */
    static function getCount($user_id, $topic_id) {
        $topic_ids = ForumPPEntry::getChildTopicIds($topic_id);
        $constraints = ForumPPEntry::getConstraints($topic_id);
        
        if (empty($topic_ids)) {
            return 0;
        }
        
        $stmt = DBManager::get()->prepare("SELECT SUM(new_entries) FROM forumpp_visits
            WHERE user_id = :user_id AND topic_id IN (:topic_ids)
            AND visitdate > (UNIX_TIMESTAMP() - ". self::LAST_VISIT_MAX .") ");

        $stmt->bindParam(':topic_ids', $topic_ids, StudipPDO::PARAM_ARRAY);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute(array($user_id, $topic_id));
        
        $num_entries['abo'] = $stmt->fetchColumn();
       
        // get additionally the number of new entries since last visit
        $stmt = DBManager::get()->prepare($query = "SELECT COUNT(*) FROM forumpp_entries as fe
            LEFT JOIN forumpp_visits as fv ON (fe.topic_id = fv.topic_id AND fe.seminar_id = fv.seminar_id)
            WHERE fv.topic_id IS NULL AND fe.lft > ? AND fe.rgt < ? AND fe.depth = ?
                AND fe.seminar_id = ? AND fv.user_id = ?");
        $stmt->execute($data = array($constraints['lft'], $constraints['rgt'],
            $constraints['depth'] + 1, $constraints['seminar_id'], $user_id));
        
        $num_entries['new'] += $stmt->fetchColumn();

        return $num_entries;
    }

    static function entryAdded($topic_id, $seminar_id) {
        // increase the number of new entries for all users including parent topic
        $stmt = DBManager::get()->prepare("UPDATE forumpp_visits
            SET new_entries = new_entries + 1
            WHERE topic_id = ? AND user_id != ?");
        $stmt->execute(array(ForumPPEntry::getParentTopicId($topic_id), $GLOBALS['user']->id));
        
        self::set($GLOBALS['user']->id, $topic_id, $seminar_id);
    }

    static function entryDelete($topic_id) {
        // get the parent topic
        $path = ForumPPEntry::getPathToPosting($topic_id);
        array_pop($path);
        $parent = array_pop($path);
        $parent_topic_id = $parent['id'];
        $parent_constraints = ForumPPEntry::getConstraints($parent_topic_id);
        $seminar_id = $parent_constraints['seminar_id'];

        // get all topic_ids to remove
        $constraints = ForumPPEntry::getConstraints($topic_id);

        $stmt = DBManager::get()->prepare("SELECT topic_id FROM forumpp_entries
            WHERE lft >= ? AND rgt <= ? AND seminar_id = ?");
        $stmt->execute(array($constraints['lft'], $constraints['rgt'], $seminar_id));
        $topic_ids = $stmt->fetchAll(PDO::FETCH_COLUMN);

        // delete all found entries
        $stmt = DBManager::get()->prepare("DELETE FROM forumpp_visits
            WHERE topic_id IN (:topic_ids)");
        $stmt->bindParam(':topic_ids', $topic_ids, StudipPDO::PARAM_ARRAY);
        $stmt->execute();

        // recalculate the number of new entries for the parent topic
        $stmt = DBManager::get()->prepare("SELECT IF (last_visitdate != 0, last_visitdate, visitdate) as visit,
            user_id, seminar_id FROM forumpp_visits
            WHERE seminar_id = ? AND topic_id = ?");
        $stmt->execute(array($seminar_id, $parent_topic_id));

        $count_stmt  = DBManager::get()->prepare("SELECT COUNT(*) FROM forumpp_entries
            WHERE lft > ? AND rgt <? AND seminar_id = ?
                AND chdate > ? ");

        $update_stmt = DBManager::get()->prepare("UPDATE forumpp_visits
            SET new_entries = ?
            WHERE user_id = ? AND seminar_id = ? AND topic_id = ?");

        // cycle through all stored entries
        while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $count_stmt->execute(array(
                $parent_constraints['lft'],
                $parent_constraints['rgt'],
                $seminar_id,
                $data['visit']));
            $new_entries = $count_stmt->fetchColumn();

            $update_stmt->execute(array($new_entries, $data['user_id'],
                $seminar_id, $parent_topic_id));
        }
    }
    
    static function hasEntry($user_id, $topic_id) {
        $stmt = DBManager::get()->prepare("SELECT COUNT(*) FROM forumpp_visits
            WHERE user_id = ? AND topic_id = ?");
        $stmt->execute(array($user_id, $topic_id));
        
        return $stmt->fetchColumn() > 0;
    }
}
