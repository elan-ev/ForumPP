<?php

/*
 * this class allows the retrieval and handling of forum-entrys
 * @author Till Glöggler <tgloeggl@uos.de>
 */

class ForumPPEntry {
    const WITH_CHILDS = true;
    const THREAD_PREVIEW_LENGTH = 100;
    const POSTINGS_PER_PAGE = 10;
    const FEED_POSTINGS = 100;


    /* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
     * H   E   L   P   E   R   -   F   U   N   C   T   I   O   N   S *
     * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

    /**
     * is used for posting-preview. replaces all newlines with spaces
     * @param string $text the text to work on
     * @returns string
     */
    static function br2space($text) {
        return str_replace("\n", ' ', str_replace("\r", '', $text));
    }

    static function killEdit($description) {
        // wurde schon mal editiert
        if (preg_match('/^(.*)(<admin_msg.*?)$/s', $description, $match)) {
            return $match[1];
        }
        return $description;
    }

    static function appendEdit($description) {
        $edit = "<admin_msg autor=\"" . addslashes(get_fullname()) . "\" chdate=\"" . time() . "\">";
        return $description . $edit;
    }

    static function parseEdit($description) {
        // wurde schon mal editiert
        if (preg_match('/^.*(<admin_msg.*?)$/s', $description, $match)) {
            $tmp = explode('"', $match[1]);
            $append = "\n\n%%[" . _("Zuletzt editiert von") . ' ' . $tmp[1] . " - " . date("d.m.y - H:i", $tmp[3]) . "]%%";
            $description = ForumPPEntry::killEdit($description) . $append;
        }
        return $description;
    }

    static function killQuotes($description) {
        return str_replace('[/quote]', '', preg_replace("/\[quote=.*\]/U", "", $description));
    }

    /**
     * returns the left and the right value of the passed entry
     *
     * @param  string  $topic_id
     * @return array   array('lft' => ..., 'rgt' => ...)
     *
     * @throws Exception
     */
    static function getConstraints($topic_id) {
        // look up the range of postings
        $range_stmt = DBManager::get()->prepare("SELECT lft, rgt, depth, seminar_id
            FROM forumpp_entries WHERE topic_id = ?");
        $range_stmt->execute(array($topic_id));
        if (!$data = $range_stmt->fetch(PDO::FETCH_ASSOC)) {
            throw new Exception("Could not find entry with id >>$topic_id<< in forumpp_entries, " . __FILE__ . " on line " . __LINE__);
        }

        return $data;
    }

    /* * * * * * * * * * * * * * * * * * * * * * * * * * * * *
     * D   A   T   A   -   R   E   T   R   I   E   V   A   L *
     * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
    /*
     * retrieve the the latest posting under $parent_id
     * or false if the postings itself is the latest
     *
     * @param string $parent_id the node to lookup the childs in
     * @return mixed the data for the latest postings or false
     */

    static function getLatestPosting($parent_id) {
        $data = ForumPPEntry::getConstraints($parent_id);
        $stmt = DBManager::get()->prepare("SELECT * FROM forumpp_entries
			WHERE lft >= ? AND rgt <= ? AND Seminar_id = ?
			ORDER BY mkdate DESC LIMIT 1");
        $stmt->execute(array($data['lft'], $data['rgt'], $data['seminar_id']));

        if (!$data = $stmt->fetch(PDO::FETCH_ASSOC)) {
            return false;
        }

        return array_merge($data, ForumPPEntry::getPathToPosting($data['topic_id']));
    }

    static function getPathToPosting($topic_id) {
        $data = ForumPPEntry::getConstraints($topic_id);

        $stmt = DBManager::get()->prepare("SELECT * FROM forumpp_entries
            WHERE lft <= ? AND rgt >= ? AND Seminar_id = ? ORDER BY lft ASC");
        $stmt->execute(array($data['lft'], $data['rgt'], $data['seminar_id']));

        while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $ret[] = array(
                'id'   => $data['topic_id'],
                'name' => $data['name']
            );
        }

        return $ret;
    }

    static function parseEntries($stmt) {
        $posting_list = array();

        // retrieve the postings
        while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
            // we throw away all formatting stuff, tags, etc, leaving the important bit of information
            $desc_short = ForumPPEntry::br2space(kill_format(strip_tags($data['content'])));
            if (strlen($desc_short) > (ForumPPEntry::THREAD_PREVIEW_LENGTH + 2)) {
                $desc_short = substr($desc_short, 0, ForumPPEntry::THREAD_PREVIEW_LENGTH) . '...';
            } else {
                $desc_short = $desc_short;
            }

            $posting_list[$data['topic_id']] = array(
                'author' => $data['author'],
                'topic_id' => $data['topic_id'],
                'name' => formatReady($data['name']),
                'name_raw' => $data['name'],
                'content' => formatReady(ForumPPEntry::parseEdit($data['content'])),
                'content_raw' => ForumPPEntry::killEdit($data['content']),
                'content_short' => $desc_short,
                'chdate' => $data['chdate'],
                'mkdate' => $data['mkdate'],
                'owner_id' => $data['user_id'],
                'raw_title' => $data['name'],
                'raw_description' => ForumPPEntry::killEdit($data['content']),
                'fav' => ($data['fav'] == 'fav'),
            );
        } // retrieve the postings

        return $posting_list;
    }

    static function getEntries($parent_id, $with_childs = false, $add = '',
        $sort_order = 'DESC', $start = 0, $limit = ForumPPEntry::POSTINGS_PER_PAGE)
    {
        $constraint = self::getConstraints($parent_id);
        $seminar_id = $constraint['seminar_id'];
        $depth      = $constraint['depth'] + 1;

        if ($with_childs) {
            $stmt = DBManager::get()->prepare("SELECT forumpp_entries.*, ou.flag as fav
                    FROM forumpp_entries
                LEFT JOIN object_user as ou ON (ou.object_id = forumpp_entries.topic_id AND ou.user_id = ?)
                WHERE (forumpp_entries.Seminar_id = ?
                    AND forumpp_entries.Seminar_id != forumpp_entries.topic_id
                    AND lft > ? AND rgt < ?) "
                . ($depth > 2 ? " OR forumpp_entries.topic_id = ". DBManager::get()->quote($parent_id) : '')
                . $add
                . " ORDER BY forumpp_entries.mkdate $sort_order"
                . ($limit ? " LIMIT $start, $limit" : ''));
            $stmt->execute(array($GLOBALS['user']->id, $seminar_id, $constraint['lft'], $constraint['rgt']));

            $count_stmt = DBManager::get()->prepare("SELECT COUNT(*) FROM forumpp_entries
                LEFT JOIN object_user as ou ON (ou.object_id = forumpp_entries.topic_id AND ou.user_id = ?)
                WHERE (forumpp_entries.Seminar_id = ?
                    AND forumpp_entries.Seminar_id != forumpp_entries.topic_id
                    AND lft > ? AND rgt < ?) "
                . ($depth > 2 ? " OR forumpp_entries.topic_id = ". DBManager::get()->quote($parent_id) : '')
                . $add
                . " ORDER BY forumpp_entries.mkdate $sort_order"
                . ($limit ? " LIMIT $start, $limit" : ''));
            $count_stmt->execute(array($GLOBALS['user']->id, $seminar_id, $constraint['lft'], $constraint['rgt']));
            $count = $count_stmt->fetchColumn();

        } else {
            $stmt = DBManager::get()->prepare("SELECT forumpp_entries.*, ou.flag as fav
                    FROM forumpp_entries
                LEFT JOIN object_user as ou ON (ou.object_id = forumpp_entries.topic_id AND ou.user_id = ?)
                WHERE (depth = ? AND forumpp_entries.Seminar_id = ? "
                . ($depth > 2 ? " OR forumpp_entries.topic_id = ". DBManager::get()->quote($parent_id) : '')
                . ') '. $add
                . " ORDER BY forumpp_entries.mkdate $sort_order"
                . ($limit ? " LIMIT $start, $limit" : ''));
            $stmt->execute(array($GLOBALS['user']->id, $depth, $seminar_id));

            $count_stmt = DBManager::get()->prepare("SELECT COUNT(*) FROM forumpp_entries
                LEFT JOIN object_user as ou ON (ou.object_id = forumpp_entries.topic_id AND ou.user_id = ?)
                WHERE (depth = ? AND forumpp_entries.Seminar_id = ?
                    AND forumpp_entries.Seminar_id != forumpp_entries.topic_id"
                . ($depth > 2 ? " OR forumpp_entries.topic_id = ". DBManager::get()->quote($parent_id) : '')
                . ') '. $add
                . " ORDER BY forumpp_entries.mkdate $sort_order"
                . ($limit ? " LIMIT $start, $limit" : ''));
            $count_stmt->execute(array($GLOBALS['user']->id, $depth, $seminar_id));
            $count = $count_stmt->fetchColumn();
        }
        
        if (!$stmt) {
            throw new Exception("Error while retrieving postings in " . __FILE__ . " on line " . __LINE__);
        }

        if (ForumPPEntry::POSTINGS_PER_PAGE * $start > $count) {
            throw new Exception('The requested page does not exist!');
        }

        return array('list' => self::parseEntries($stmt), 'count' => $count);
    }

    /**
     *
     * @param <type> $type
     * @param <type> $parent
     * @param <type> $id
     * @return <type> 
     */
    static function getList($type, $parent_id) {
        $start = ForumPPHelpers::getPage() * ForumPPEntry::POSTINGS_PER_PAGE;
        $limit = ForumPPEntry::POSTINGS_PER_PAGE;

        switch ($type) {
            case 'list':
                $list = ForumPPEntry::getEntries($parent_id);
                $postings = $list['list'];

                foreach ($postings as $key => $posting) {

                    if ($data = ForumPPEntry::getLatestPosting($posting['topic_id'])) {
                        $last_posting['topic_id']      = $data['topic_id'];
                        $last_posting['date']          = $data['mkdate'];
                        $last_posting['user_id']       = $data['user_id'];
                        $last_posting['user_fullname'] = $data['author'];
                        $last_posting['username']      = get_username($data['user_id']);

                        // we throw away all formatting stuff, tags, etc, so we have just the important bit of information
                        $text = strip_tags($data['name']);
                        $text = ForumPPEntry::br2space($text);
                        $text = kill_format(ForumPPEntry::killQuotes($text));

                        if (strlen($text) > 42) {
                            $text = substr($text, 0, 40) . '...';
                        }

                        $last_posting['text'] = $text;
                    } else {
                        $last_posting = _("keine Beitr&auml;ge");
                    }

                    $postings[$key]['last_posting'] = $last_posting;
                    $postings[$key]['num_postings'] = ForumPPEntry::countEntries($posting['topic_id']);
                }

                return array('list' => $postings, 'count' => $list['count']);
                break;

            case 'postings':
                return ForumPPEntry::getEntries($parent_id, ForumPPEntry::WITH_CHILDS, '', 'ASC');
                break;

            case 'newest':
                $constraint = self::getConstraints($parent_id);
                $seminar_id = $constraint['seminar_id'];
                $depth      = $constraint['depth'] + 1;

                $last_visit = object_get_visit($seminar_id, 'forum');

                $add = 'AND forumpp_entries.mkdate >= '. DBManager::get()->quote($last_visit);

                return ForumPPEntry::getEntries($parent_id, ForumPPEntry::WITH_CHILDS, $add);
                break;

            case 'favorites':
                $add = "AND ou.flag = 'fav'";
                return ForumPPEntry::getEntries($parent_id, ForumPPEntry::WITH_CHILDS, $add);
                break;
        }
    }

    static function countEntries($parent_id) {
        $data = ForumPPEntry::getConstraints($parent_id);
        return (($data['rgt'] - $data['lft'] - 1) / 2) + 1;
    }

    static function countUserEntries($user_id) {
        static $entries;

        if (!$entries[$user_id]) {
            $stmt = DBManager::get()->prepare("SELECT COUNT(*)
                FROM forumpp_entries WHERE user_id = ?");
            $stmt->execute(array($user_id));

            $entries[$user_id] = $stmt->fetchColumn();
        }

        return $entries[$user_id];
    }

    /* * * * * * * * * * * * * * * * * * * * * * * * * * * * *
     * D   A   T   A   -   C   R   E   A   T  I   O   N    * *
     * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

    static function insert($data, $parent_id) {
        $constraint = self::getConstraints($parent_id);

        // TODO: Zusammenfassen in eine Transaktion!!!
        DBManager::get()->exec('UPDATE forumpp_entries SET rgt = rgt + 2 WHERE rgt > '. $constraint['lft']);
        DBManager::get()->exec('UPDATE forumpp_entries SET lft = lft + 2 WHERE lft > '. $constraint['lft']);

        $stmt = DBManager::get()->prepare("INSERT INTO forumpp_entries
            (topic_id, seminar_id, user_id, name, content, mkdate, chdate, author,
                author_host, lft, rgt, depth, anonymous)
            VALUES (? ,?, ?, ?, ?, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), ?, ?, ?, ?, ?, ?)");
        $stmt->execute(array($data['topic_id'], $data['seminar_id'], $data['user_id'],
            $data['name'], $data['content'], $data['author'], $data['author_host'],
            $constraint['lft'] + 1, $constraint['lft'] + 2, $constraint['depth'] + 1, 0));
    }

    function checkRootEntry($seminar_id) {
        $stmt = DBManager::get()->prepare("SELECT COUNT(*) FROM forumpp_entries
            WHERE topic_id = ? AND seminar_id = ?");
        $stmt->execute(array($seminar_id, $seminar_id));
        if ($stmt->fetchColumn() > 0) return;

        $stmt = DBManager::get()->prepare("INSERT INTO forumpp_entries
            (topic_id, seminar_id, name, mkdate, chdate, lft, rgt, depth)
            VALUES (?, ?, 'Startseite', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 1, 0)");
        $stmt->execute(array($seminar_id, $seminar_id));
    }
}
