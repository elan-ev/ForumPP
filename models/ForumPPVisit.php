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
            $stmt = DBManager::get()->prepare("INSERT INTO forumpp_visits
                (user_id, topic_id, seminar_id, visitdate, last_visitdate)
                VALUES (?, ?, ?, UNIX_TIMESTAMP(), ?)");

            $stmt->execute(array($user_id, $topic_id, $seminar_id, time()));
        }

        if ($data['last_visitdate'] < (time() - self::LAST_VISIT_LIFETIME)) {
            // the last_visitdate is overwritten after an hour
            $stmt = DBManager::get()->prepare("UPDATE forumpp_visits
                SET visitdate = UNIX_TIMESTAMP(), last_visitdate = ?, new_entries = 0
                WHERE user_id = ? AND topic_id = ? AND seminar_id = ?");
            $stmt->execute(array($data['visitdate'], $user_id, $topic_id, $seminar_id));
        } else {
            // visitdate is always set to the latest visit
            $stmt = DBManager::get()->prepare("UPDATE forumpp_visits
                SET visitdate = UNIX_TIMESTAMP()
                WHERE user_id = ? AND topic_id = ? AND seminar_id = ?");
            $stmt->execute(array($user_id, $topic_id, $seminar_id));
        }
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

        return $stmt->fetchColumn() ?: time();
    }

    /**
     * return number of new entries since last visit up to 3 month ago
     *
     * @param type $user_id the user_id for the entries
     * @param type $seminar_id the seminar_id for the entries
     * @return int the number of entries
     */
    static function getCountForTopic($user_id, $topic_id) {
        $stmt = DBManager::get()->prepare("SELECT new_entries FROM forumpp_visits
            WHERE user_id = ? AND topic_id = ?
            AND visitdate > (UNIX_TIMESTAMP() - ". self::LAST_VISIT_MAX .") ");

        $stmt->execute(array($user_id, $topic_id));

        return $stmt->fetchColumn();
    }
    
    /**
     * return number of new entries since last visit up to 3 month ago for the
     * passed seminar, summed up for all threads
     *
     * @param type $user_id the user_id for the entries
     * @param type $seminar_id the seminar_id for the entries
     * @return int the number of entries
     */
    static function getCountSeminar($user_id, $seminar_id) {
        $stmt = DBManager::get()->prepare("SELECT SUM(new_entries) FROM forumpp_visits
            WHERE user_id = ? AND seminar_id = ?
            AND visitdate > (UNIX_TIMESTAMP() - ". self::LAST_VISIT_MAX .") ");

        $stmt->execute(array($user_id, $seminar_id));

        return $stmt->fetchColumn();
    }

    static function entryAdded($topic_id) {
        var_dump(ForumPPEntry::getPathToPosting($topic_id));
    }
    
    static function entryDeleted($topic_id) {
        
    }
}
