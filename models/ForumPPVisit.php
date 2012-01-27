<?php

class ForumPPVisit {

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
        $stmt = DBManager::get()->prepare("SELECT visitdate FROM
            forumpp_visits WHERE user_id = ?
                AND topic_id = ? AND seminar_id = ?");
        $stmt->execute(array($user_id, $topic_id, $seminar_id));
        $visitdate = $stmt->fetchColumn();

        // give the user at least an hour, before the visitdate is overwritten
        if ($visitdate < (time() + 3600)) {
            if ($stmt->fetchColumn() > 0) {
                $stmt = DBManager::get()->prepare("UPDATE forumpp_visits
                    SET user_id = ?, topic_id = ?, seminar_id = ?,
                    visitdate = UNIX_TIMESTAMP(), last_visitdate = ?");
            } else {
                $stmt = DBManager::get()->prepare("INSERT INTO forumpp_visits
                    (user_id, topic_id, seminar_id, visitdate, last_visitdate)
                    VALUES (?, ?, ?, UNIX_TIMESTAMP(), ?)");
            }

            $stmt->execute(array($user_id, $topic_id, $seminar_id, $visitdate));
        }

        return $visitdate;
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
        $stmt = DBManager::get()->prepare("SELECT visitdate FROM forumpp_visits
            WHERE user_id = ? AND topic_id = ? AND seminar_id = ?");

        $stmt->execute(array($user_id, $topic_id, $seminar_id));

        return $stmt->fetchColumn();
    }

    /**
     * return number of new entries since last visit up to 3 month ago
     *
     * @param type $user_id the user_id for the entries
     * @param type $seminar_id the seminar_id for the entries
     * @return int the number of entries
     */
    static function getCountForUser($user_id, $seminar_id) {
        $stmt = DBManager::get()->prepare("SELECT SUM(new_entries) FROM forumpp_visits
            WHERE user_id = ? AND seminar_id = ?
            AND visitdate > (UNIX_TIMESTAMP() - 7776000) "); // 90 days

        $stmt->execute(array($user_id, $seminar_id));

        return $stmt->fetchColumn();
    }

    static function entryAdded($topic_id) {
        var__dump(ForumPPEntry::getPathToPosting($topic_id));
    }
}
