<?php

/*
 * this class allows the retrieval and handling of forum-entrys
 * @author Till Glöggler <tgloeggl@uos.de>
 */

class ForumPPEntry {
    const WITH_CHILDS = true;
    const WITHOUT_CHILDS = false;
    const THREAD_PREVIEW_LENGTH = 100;
    const POSTINGS_PER_PAGE = 10;
    const FEED_POSTINGS = 100;


    /* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
     * H   E   L   P   E   R   -   F   U   N   C   T   I   O   N   S *
     * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

    /**
     * is used for posting-preview. replaces all newlines with spaces
     * 
     * @param string $text the text to work on
     * @returns string
     */
    static function br2space($text)
    {
        return str_replace("\n", ' ', str_replace("\r", '', $text));
    }

    /**
     * remove the edit-html from a posting
     *
     * @param string $description the posting-content
     * @return string the content stripped by the edit-mark
     */
    static function killEdit($description)
    {
        // wurde schon mal editiert
        if (preg_match('/^(.*)(<admin_msg.*?)$/s', $description, $match)) {
            return $match[1];
        }
        return $description;
    }

    /**
     * add the edit-html to a posting
     * 
     * @param string $description the posting-content
     * @return string the content with the edit-mark
     */
    static function appendEdit($description)
    {
        $edit = "<admin_msg autor=\"" . addslashes(get_fullname()) . "\" chdate=\"" . time() . "\">";
        return $description . $edit;
    }

    /**
     * convert the edit-html to raw text
     * 
     * @param string $description the posting-content
     * @return string the content with the raw text version of the edit-mark
     */
    static function parseEdit($description)
    {
        // wurde schon mal editiert
        if (preg_match('/^.*(<admin_msg.*?)$/s', $description, $match)) {
            $tmp = explode('"', $match[1]);
            $append = "\n\n%%[" . _("Zuletzt editiert von") . ' ' . $tmp[1] . " - " . date("d.m.y - H:i", $tmp[3]) . "]%%";
            $description = ForumPPEntry::killEdit($description) . $append;
        }
        return $description;
    }

    /**
     * remove the [quote]-tags from the passed posting
     * 
     * @param string $description the posting-content
     * @return string the posting without [quote]-tags
     */
    static function killQuotes($description)
    {
        return str_replace('[/quote]', '', preg_replace("/\[quote=.*\]/U", "", $description));
    }


    /**
     * calls Stud.IP's kill_format and additionally removes any found smiley-tag
     * 
     * @param string $text the text to parse
     * @return string the text without format-tags and without smileys
     */
    static function killFormat($text)
    {
        
        $text = kill_format($text);
        
        // find stuff which is enclosed between to colons
        preg_match('/:.*:/U', $text, $matches);
        
        // remove the match if it is a smiley
        foreach ($matches as $match) {
            if (Smiley::getByName($match) || Smiley::getByShort($match)) {
                $text = str_replace($match, '', $text);
            }
        }
        
        return $text;
    }

    /**
     * returns the entry for the passed topic_id
     *
     * @param  string  $topic_id
     * @return array   array('lft' => ..., 'rgt' => ..., seminar_id => ...)
     *
     * @throws Exception
     */
    static function getConstraints($topic_id)
    {
        // look up the range of postings
        $range_stmt = DBManager::get()->prepare("SELECT *
            FROM forumpp_entries WHERE topic_id = ?");
        $range_stmt->execute(array($topic_id));
        if (!$data = $range_stmt->fetch(PDO::FETCH_ASSOC)) {
            return false;
            // throw new Exception("Could not find entry with id >>$topic_id<< in forumpp_entries, " . __FILE__ . " on line " . __LINE__);
        }
        
        if ($data['depth'] == 1) {
            $data['area'] = 1;
        }

        return $data;
    }
    
    /**
     * return the topic_id of the parent element, false if there is none (ie the
     * passed topic_id is already the upper-most node in the tree)
     * 
     * @param string $topic_id the topic_id for which the parent shall be found
     * 
     * @return string the topic_id of the parent element or false
     */
    static function getParentTopicId($topic_id)
    {
        $path = ForumPPEntry::getPathToPosting($topic_id);
        array_pop($path);
        $data = array_pop($path);
        
        return $data['id'] ?: false;
    }
    
    
    /**
     * get the topic_ids of all childs of the passed topic including itself
     * 
     * @param string $topic_id the topic_id to find the childs for
     * @return array a list if topic_ids
     */
    static function getChildTopicIds($topic_id)
    {
        $constraints = ForumPPEntry::getConstraints($topic_id);
        
        $stmt = DBManager::get()->prepare("SELECT topic_id
            FROM forumpp_entries WHERE lft >= ? AND rgt <= ?");
        $stmt->execute(array($constraints['lft'], $constraints['rgt']));
        
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    /* * * * * * * * * * * * * * * * * * * * * * * * * * * * *
     * D   A   T   A   -   R   E   T   R   I   E   V   A   L *
     * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

     /**
      * get the page the passed posting is on
      *
      * @param  string  $topic_id
      * @return  int
      */
    static function getPostingPage($topic_id)
    {
        $constraint = ForumPPEntry::getConstraints($topic_id);
        if ($parent_id = ForumPPEntry::getParentTopicId($topic_id)) {
            $parent_constraint = ForumPPEntry::getConstraints($parent_id);

            return ceil((($constraint['lft'] - $parent_constraint['lft'] + 3) / 2) / ForumPPEntry::POSTINGS_PER_PAGE);
        }

        return 0;
    }

    static function getLastUnread($parent_id)
    {
        $constraint = ForumPPEntry::getConstraints($parent_id);
        
        // take users visitdate into account
        $visitdate = ForumPPVisit::getLastVisit($constraint['seminar_id']);
        
        // get the first unread entry
        $stmt = DBManager::get()->prepare("SELECT * FROM forumpp_entries
            WHERE lft > ? AND rgt < ? AND seminar_id = ?
                AND mkdate >= ?
            ORDER BY mkdate ASC LIMIT 1");
        $stmt->execute(array($constraint['lft'], $constraint['rgt'], $constraint['seminar_id'], $visitdate));
        $last_unread = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $last_unread ? $last_unread['topic_id'] : null;
    }

    /*
     * retrieve the the latest posting under $parent_id
     * or false if the postings itself is the latest
     *
     * @param string $parent_id the node to lookup the childs in
     * @return mixed the data for the latest postings or false
     */
    static function getLatestPosting($parent_id)
    {
        $constraint = ForumPPEntry::getConstraints($parent_id);

        // get last entry
        $stmt = DBManager::get()->prepare("SELECT * FROM forumpp_entries
            WHERE lft > ? AND rgt < ? AND seminar_id = ?
            ORDER BY mkdate DESC LIMIT 1");
        $stmt->execute(array($constraint['lft'], $constraint['rgt'], $constraint['seminar_id']));

        if (!$data = $stmt->fetch(PDO::FETCH_ASSOC)) {
            return false;
        }
        
        return $data;
    }

    /**
     * returns a hashmap with arrays containing id and name with the entries
     * which lead to the passed topic
     * 
     * @param string $topic_id the topic to get the path for
     * 
     * @return array
     */
    static function getPathToPosting($topic_id)
    {
        $data = ForumPPEntry::getConstraints($topic_id);

        $stmt = DBManager::get()->prepare("SELECT * FROM forumpp_entries
            WHERE lft <= ? AND rgt >= ? AND seminar_id = ? ORDER BY lft ASC");
        $stmt->execute(array($data['lft'], $data['rgt'], $data['seminar_id']));

        while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $ret[$data['topic_id']] = array(
                'id'   => $data['topic_id'],
                'name' => $data['name']
            );
        }

        return $ret;
    }
    
    /**
     * returns a hashmap where key is topic_id and value a posting-titel from the
     * entries which lead to the passed topic.
     * 
     * WARNING: This function ommits postings with an empty titel. For a full
     * list please use getPathToPosting in the same class!
     * 
     * @param string $topic_id the topic to get the path for
     * 
     * @return array
     */    
    static function getFlatPathToPosting($topic_id)
    {
        $postings = self::getPathToPosting($topic_id);
        
        // var_dump($postings);
        
        foreach ($postings as $post) {
            if ($post['name']) {
                $ret[$post['id']] = $post['name'];
            }
        }
        
        return $ret;
    }

    /**
     * fill the passed postings with additional data
     *
     * @param  array $postings
     * @return array
     */
    static function parseEntries($postings) {
        $posting_list = array();

        // retrieve the postings
        foreach ($postings as $data) {
            // we throw away all formatting stuff, tags, etc, leaving the important bit of information
            $desc_short = ForumPPEntry::br2space(ForumPPEntry::killFormat(strip_tags($data['content'])));
            if (strlen($desc_short) > (ForumPPEntry::THREAD_PREVIEW_LENGTH + 2)) {
                $desc_short = substr($desc_short, 0, ForumPPEntry::THREAD_PREVIEW_LENGTH) . '...';
            } else {
                $desc_short = $desc_short;
            }

            $posting_list[$data['topic_id']] = array(
                'author'          => $data['author'],
                'topic_id'        => $data['topic_id'],
                'name'            => formatReady($data['name']),
                'name_raw'        => $data['name'],
                'content'         => formatReady(ForumPPEntry::parseEdit($data['content'])),
                'content_raw'     => ForumPPEntry::killEdit($data['content']),
                'content_short'   => $desc_short,
                'chdate'          => $data['chdate'],
                'mkdate'          => $data['mkdate'],
                'owner_id'        => $data['user_id'],
                'raw_title'       => $data['name'],
                'raw_description' => ForumPPEntry::killEdit($data['content']),
                'fav'             => ($data['fav'] == 'fav'),
                'depth'           => $data['depth']
            );
        } // retrieve the postings

        return $posting_list;
    }

    static function getEntries($parent_id, $with_childs = false, $add = '',
        $sort_order = 'DESC', $start = 0, $limit = ForumPPEntry::POSTINGS_PER_PAGE)
    {
        $constraint = ForumPPEntry::getConstraints($parent_id);
        $seminar_id = $constraint['seminar_id'];
        $depth      = $constraint['depth'] + 1;

        // count the entries and set correct page if necessary
        if ($with_childs) {
            $count_stmt = DBManager::get()->prepare("SELECT COUNT(*) FROM forumpp_entries
                LEFT JOIN forumpp_favorites as ou ON (ou.topic_id = forumpp_entries.topic_id AND ou.user_id = ?)
                WHERE (forumpp_entries.seminar_id = ?
                    AND forumpp_entries.seminar_id != forumpp_entries.topic_id
                    AND lft > ? AND rgt < ?) "
                . ($depth > 2 ? " OR forumpp_entries.topic_id = ". DBManager::get()->quote($parent_id) : '')
                . $add
                . " ORDER BY forumpp_entries.mkdate $sort_order");
            $count_stmt->execute(array($GLOBALS['user']->id, $seminar_id, $constraint['lft'], $constraint['rgt']));
            $count = $count_stmt->fetchColumn();
        } else {
            $count_stmt = DBManager::get()->prepare("SELECT COUNT(*) FROM forumpp_entries
                LEFT JOIN forumpp_favorites as ou ON (ou.topic_id = forumpp_entries.topic_id AND ou.user_id = ?)
                WHERE ((depth = ? AND forumpp_entries.seminar_id = ?
                    AND forumpp_entries.seminar_id != forumpp_entries.topic_id
                    AND lft > ? AND rgt < ?) "
                . ($depth > 2 ? " OR forumpp_entries.topic_id = ". DBManager::get()->quote($parent_id) : '')
                . ') '. $add
                . " ORDER BY forumpp_entries.mkdate $sort_order");
            $count_stmt->execute(array($GLOBALS['user']->id, $depth, $seminar_id, $constraint['lft'], $constraint['rgt']));
            $count = $count_stmt->fetchColumn();            
        }

        // use the last page if the requested page does not exist
        if ($start > $count) {
            $page = ceil($count / ForumPPEntry::POSTINGS_PER_PAGE);
            ForumPPHelpers::setPage($page);
            $start = max(1, $page - 1) * ForumPPEntry::POSTINGS_PER_PAGE;
        }
        
        if ($with_childs) {
            $stmt = DBManager::get()->prepare("SELECT forumpp_entries.*, IF(ou.topic_id IS NOT NULL, 'fav', NULL) as fav
                    FROM forumpp_entries
                LEFT JOIN forumpp_favorites as ou ON (ou.topic_id = forumpp_entries.topic_id AND ou.user_id = ?)
                WHERE (forumpp_entries.seminar_id = ?
                    AND forumpp_entries.seminar_id != forumpp_entries.topic_id
                    AND lft > ? AND rgt < ?) "
                . ($depth > 2 ? " OR forumpp_entries.topic_id = ". DBManager::get()->quote($parent_id) : '')
                . $add
                . " ORDER BY forumpp_entries.mkdate $sort_order"
                . ($limit ? " LIMIT $start, $limit" : ''));
            $stmt->execute(array($GLOBALS['user']->id, $seminar_id, $constraint['lft'], $constraint['rgt']));
        } else {
            $stmt = DBManager::get()->prepare("SELECT forumpp_entries.*, IF(ou.topic_id IS NOT NULL, 'fav', NULL) as fav
                    FROM forumpp_entries
                LEFT JOIN forumpp_favorites as ou ON (ou.topic_id = forumpp_entries.topic_id AND ou.user_id = ?)
                WHERE ((depth = ? AND forumpp_entries.seminar_id = ?
                    AND lft > ? AND rgt < ?) "
                . ($depth > 2 ? " OR forumpp_entries.topic_id = ". DBManager::get()->quote($parent_id) : '')
                . ') '. $add
                . " ORDER BY forumpp_entries.mkdate $sort_order"
                . ($limit ? " LIMIT $start, $limit" : ''));
            $stmt->execute(array($GLOBALS['user']->id, $depth, $seminar_id, $constraint['lft'], $constraint['rgt']));
        }

        if (!$stmt) {
            throw new Exception("Error while retrieving postings in " . __FILE__ . " on line " . __LINE__);
        }

        return array('list' => ForumPPEntry::parseEntries($stmt->fetchAll(PDO::FETCH_ASSOC)), 'count' => $count);
    }


    function getLastPostings($postings) {
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
                $text = ForumPPEntry::killFormat(ForumPPEntry::killQuotes($text));

                if (strlen($text) > 42) {
                    $text = substr($text, 0, 40) . '...';
                }

                $last_posting['text'] = $text;
            }

            $postings[$key]['last_posting'] = $last_posting;            
            if (!$postings[$key]['last_unread']  = ForumPPEntry::getLastUnread($posting['topic_id'])) {
                $postings[$key]['last_unread'] = $last_posting['topic_id'];
            }
            $postings[$key]['num_postings'] = ForumPPEntry::countEntries($posting['topic_id']);

            unset($last_posting);
        }

        return $postings;
    }

    /**
     * get a list of postings of a special type
     * 
     * @param string $type one of 'area', 'list', 'postings', 'latest', 'favorites'
     * @param string $parent_id the are to fetch from
     * @return array array('list' => ..., 'count' => ...);
     */
    static function getList($type, $parent_id) {
        $start = ForumPPHelpers::getPage() * ForumPPEntry::POSTINGS_PER_PAGE;

        switch ($type) {
            case 'area':
                $list = ForumPPEntry::getEntries($parent_id, ForumPPEntry::WITHOUT_CHILDS, '', 'DESC', 0, 1000);
                $postings = $list['list'];

                $postings = ForumPPEntry::getLastPostings($postings);
                return array('list' => $postings, 'count' => $list['count']);

                break;

            case 'list':
                $constraint = ForumPPEntry::getConstraints($parent_id);

                // purpose of the following query is to retrieve the threads
                // for an area ordered by the mkdate of their latest posting
                $stmt = DBManager::get()->prepare("SELECT fe.topic_id as en_topic_id,
                        IF (
                            (SELECT MAX(f1.mkdate) FROM forumpp_entries as f1
                                WHERE fe.seminar_id = :seminar_id
                                AND f1.lft > fe.lft AND f1.rgt < fe.rgt) IS NULL,
                            fe.mkdate, (SELECT MAX(f1.mkdate)
                                FROM forumpp_entries as f1
                                WHERE fe.seminar_id = :seminar_id
                                    AND f1.lft > fe.lft AND f1.rgt < fe.rgt)
                            ) as en_mkdate, f2.*, IF(ou.topic_id IS NOT NULL, 'fav', NULL) as fav
                    FROM forumpp_entries AS fe
                    LEFT JOIN forumpp_entries f2 USING (topic_id)
                    LEFT JOIN forumpp_favorites as ou ON (ou.topic_id = f2.topic_id AND ou.user_id = :user_id)
                    WHERE fe.seminar_id = :seminar_id AND fe.lft > :left
                        AND fe.rgt < :right AND fe.depth = 2
                    ORDER BY en_mkdate DESC
                    LIMIT $start, ". ForumPPEntry::POSTINGS_PER_PAGE);
                $stmt->bindParam(':seminar_id', $constraint['seminar_id']);
                $stmt->bindParam(':left', $constraint['lft']);
                $stmt->bindParam(':right', $constraint['rgt']);
                $stmt->bindParam(':user_id', $GLOBALS['user']->id);
                $stmt->execute();

                $postings = $stmt->fetchAll(PDO::FETCH_ASSOC);

                $postings = ForumPPEntry::parseEntries($postings);
                $postings = ForumPPEntry::getLastPostings($postings);

                $stmt_count = DBManager::get()->prepare("SELECT COUNT(*) FROM forumpp_entries
                    WHERE seminar_id = ? AND lft > ? AND rgt < ? AND depth = 2");
                $stmt_count->execute(array($constraint['seminar_id'], $constraint['lft'], $constraint['rgt']));

                return array('list' => $postings, 'count' => $stmt_count->fetchColumn());
                break;

            case 'postings':
                return ForumPPEntry::getEntries($parent_id, ForumPPEntry::WITH_CHILDS, '', 'ASC', $start);
                break;

            case 'newest':
                $constraint = ForumPPEntry::getConstraints($parent_id);

                // get postings
                $stmt = DBManager::get()->prepare($query = "SELECT forumpp_entries.*, IF(ou.topic_id IS NOT NULL, 'fav', NULL) as fav
                    FROM forumpp_entries
                    LEFT JOIN forumpp_favorites as ou ON (ou.topic_id = forumpp_entries.topic_id AND ou.user_id = :user_id)
                    WHERE seminar_id = :seminar_id AND lft > :left
                        AND rgt < :right AND mkdate >= :mkdate
                    ORDER BY mkdate ASC
                    LIMIT $start, ". ForumPPEntry::POSTINGS_PER_PAGE);
                
                $stmt->bindParam(':seminar_id', $constraint['seminar_id']);
                $stmt->bindParam(':left', $constraint['lft']);
                $stmt->bindParam(':right', $constraint['rgt']);
                $stmt->bindParam(':mkdate', ForumPPVisit::getLastVisit($constraint['seminar_id']));
                $stmt->bindParam(':user_id', $GLOBALS['user']->id);
                $stmt->execute();
                
                $postings = $stmt->fetchAll(PDO::FETCH_ASSOC);

                $postings = ForumPPEntry::parseEntries($postings);
                // var_dump($postings);

                // count found postings
                $stmt_count = DBManager::get()->prepare("SELECT COUNT(*)
                    FROM forumpp_entries
                    WHERE seminar_id = :seminar_id AND lft > :left
                        AND rgt < :right AND mkdate >= :mkdate
                    ORDER BY mkdate ASC");
                
                $stmt_count->bindParam(':seminar_id', $constraint['seminar_id']);
                $stmt_count->bindParam(':left', $constraint['lft']);
                $stmt_count->bindParam(':right', $constraint['rgt']);
                $stmt_count->bindParam(':mkdate', ForumPPVisit::getLastVisit($constraint['seminar_id']));
                $stmt_count->execute();


                // return results
                return array('list' => $postings, 'count' => $stmt_count->fetchColumn());
                break;

            case 'latest':
                return ForumPPEntry::getEntries($parent_id, ForumPPEntry::WITH_CHILDS, '', 'DESC', $start);
                break;

            case 'favorites':
                $add = "AND ou.topic_id IS NOT NULL";
                return ForumPPEntry::getEntries($parent_id, ForumPPEntry::WITH_CHILDS, $add, 'DESC', $start);
                break;
        }
    }

    /**
     ** returns a list of postings for the passed search-term
     * 
     * @param string $parent_id the area to search in (can be a whole seminar)
     * @param string $_searchfor the term to search for
     * @param array $options filter-options: search_title, search_content, search_author
     * @return array array('list' => ..., 'count' => ...);
     */
    static function getSearchResults($parent_id, $_searchfor, $options) {
        $start = ForumPPHelpers::getPage() * ForumPPEntry::POSTINGS_PER_PAGE;

        // if there are quoted parts, they should not be separated
        $suchmuster = '/".*"/U';
        preg_match_all($suchmuster, $_searchfor, $treffer);

        // remove the quoted parts from $_searchfor
        $_searchfor = preg_replace($suchmuster, '', $_searchfor);

        // split the searchstring $_searchfor at every space
        $_searchfor = array_merge(explode(' ', trim($_searchfor)), $treffer[0]);

        // make an SQL-statement out of the searchstring
        $search_string = array();
        foreach ($_searchfor as $key => $val) {
            if (!$val) {
                unset($_searchfor[$key]);
            } else {
                $search_word = '%'. $val .'%';
                if ($options['search_title']) {
                    $search_string[] .= "name LIKE " . DBManager::get()->quote($search_word);
                }

                if ($options['search_content']) {
                    $search_string[] .= "content LIKE " . DBManager::get()->quote($search_word);
                }

                if ($options['search_author']) {
                    $search_string[] .= "author LIKE " . DBManager::get()->quote($search_word);
                }
            }
        }

        if (!empty($search_string)) {
            $add = "AND (" . implode(' OR ', $search_string) . ")";
            return array_merge(
                array('highlight' => $_searchfor),
                ForumPPEntry::getEntries($parent_id, ForumPPEntry::WITH_CHILDS, $add, 'DESC', $start)
            );
        }
        return array('num_postings' => 0, 'list' => array());
    }

    /**
     * returns the entry for the passed topic_id
     * 
     * @param string $topic_id
     * @return array hash-array with the entries fields
     */
    static function getEntry($topic_id)
    {
        return ForumPPEntry::getConstraints($topic_id);
    }

    static function countEntries($parent_id) {
        $data = ForumPPEntry::getConstraints($parent_id);
        return max((($data['rgt'] - $data['lft'] - 1) / 2) + 1, 0);
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
     *   D   A   T   A   -   C   R   E   A   T   I   O   N   *
     * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

    /**
     * insert a node into the table
     *
     * @param type $data an array containing the following fields:
     *     topic_id     the id of the new topic
     *     seminar_id   the id of the seminar to add the topic to
     *     user_id      the id of the user who created the topic
     *     name         the title of the entry
     *     content      the content of the entry
     *     author       the author's name as a plaintext string
     *     author_host  ip-address of creator
     * @param type $parent_id the node to add the topic to
     *
     * @return void
     */
    static function insert($data, $parent_id) {
        $constraint = ForumPPEntry::getConstraints($parent_id);

        // #TODO: Zusammenfassen in eine Transaktion!!!
        DBManager::get()->exec('UPDATE forumpp_entries SET lft = lft + 2
            WHERE lft > '. $constraint['rgt'] ." AND seminar_id = '". $constraint['seminar_id'] ."'");
        DBManager::get()->exec('UPDATE forumpp_entries SET rgt = rgt + 2
            WHERE rgt >= '. $constraint['rgt'] ." AND seminar_id = '". $constraint['seminar_id'] ."'");

        $stmt = DBManager::get()->prepare("INSERT INTO forumpp_entries
            (topic_id, seminar_id, user_id, name, content, mkdate, chdate, author,
                author_host, lft, rgt, depth, anonymous)
            VALUES (? ,?, ?, ?, ?, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), ?, ?, ?, ?, ?, ?)");
        $stmt->execute(array($data['topic_id'], $data['seminar_id'], $data['user_id'],
            $data['name'], $data['content'], $data['author'], $data['author_host'],
            $constraint['rgt'], $constraint['rgt'] + 1, $constraint['depth'] + 1, 0));
        
        NotificationCenter::postNotification('ForumPPAfterInsert', $data['topic_id'], $data);
    }


    /**
     * update the passed topic
     *
     * @param type $topic_id the id of the topic to update
     * @param type $name the new name
     * @param type $content the new content
     *
     * @return void
     */
    static function update($topic_id, $name, $content) {
        $content = ForumPPEntry::appendEdit($content);

        $stmt = DBManager::get()->prepare("UPDATE forumpp_entries
            SET name = ?, content = ?
            WHERE topic_id = ?");
        $stmt->execute(array($name, $content, $topic_id));
    }

    /**
     * delete an entry and all his descendants from the mptt-table
     *
     * @param type $topic_id the id of the entry to delete
     *
     * @return void
     */
    function delete($topic_id) {
        NotificationCenter::postNotification('ForumPPBeforeDelete', $topic_id);
        
        $constraints = ForumPPEntry::getConstraints($topic_id);

        // #TODO: Zusammenfassen in eine Transaktion!!!
        // get all entry-ids to delete them from the category-reference-table
        $stmt = DBManager::get()->prepare("SELECT topic_id FROM forumpp_entries
            WHERE seminar_id = ? AND lft >= ? AND rgt <= ? AND depth = 1");
        $stmt->execute(array($constraints['seminar_id'], $constraints['lft'], $constraints['rgt']));
        $ids = $stmt->fetch(PDO::FETCH_COLUMN);

        if (strlen($ids) == 32 && !is_array($ids)) $ids = array($ids);        

        if (!empty($ids)) {
            $stmt = DBManager::get()->prepare("DELETE FROM forumpp_categories_entries
                WHERE topic_id IN (:ids)");
            $stmt->bindParam(':ids', $ids, StudipPDO::PARAM_ARRAY);
            $stmt->execute();
        }

        // delete all entries
        $stmt = DBManager::get()->prepare("DELETE FROM forumpp_entries
            WHERE seminar_id = ? AND lft >= ? AND rgt <= ?");

        $stmt->execute(array($constraints['seminar_id'], $constraints['lft'], $constraints['rgt']));

        // update lft and rgt
        $diff = $constraints['rgt'] - $constraints['lft'] + 1;
        $stmt = DBManager::get()->prepare("UPDATE forumpp_entries SET lft = lft - $diff
            WHERE lft > ? AND seminar_id = ?");
        $stmt->execute(array($constraints['rgt'], $constraints['seminar_id']));

        $stmt = DBManager::get()->prepare("UPDATE forumpp_entries SET rgt = rgt - $diff
            WHERE rgt > ? AND seminar_id = ?");
        $stmt->execute(array($constraints['rgt'], $constraints['seminar_id']));
    }

    /**
     * move the passed topic to the passed area
     *
     * @param type $topic_id the topic to move
     * @param type $destination the area_id where the topic is moved to
     *
     * @return void
     */
    function move($topic_id, $destination) {
        // #TODO: Zusammenfassen in eine Transaktion!!!
        $constraints = ForumPPEntry::getConstraints($topic_id);

        // move the affected entries "outside" the tree
        $stmt = DBManager::get()->prepare("UPDATE forumpp_entries
            SET lft = lft * -1, rgt = (rgt * -1)
            WHERE seminar_id = ? AND lft >= ? AND rgt <= ?");
        $stmt->execute(array($constraints['seminar_id'], $constraints['lft'], $constraints['rgt']));

        // update the lft and rgt values of the parent to reflect the "deletion"
        $diff = $constraints['rgt'] - $constraints['lft'] + 1;
        $stmt = DBManager::get()->prepare("UPDATE forumpp_entries SET lft = lft - $diff
            WHERE lft > ? AND seminar_id = ?");
        $stmt->execute(array($constraints['rgt'], $constraints['seminar_id']));

        $stmt = DBManager::get()->prepare("UPDATE forumpp_entries SET rgt = rgt - $diff
            WHERE rgt > ? AND seminar_id = ?");
        $stmt->execute(array($constraints['rgt'], $constraints['seminar_id']));

        // make some space by updating the lft and rgt values of the target node
        $constraints_destination = ForumPPEntry::getConstraints($destination);
        $size = $constraints['rgt'] - $constraints['lft'] + 1;

        DBManager::get()->exec("UPDATE forumpp_entries SET lft = lft + $size
            WHERE lft > ". $constraints_destination['rgt'] ." AND seminar_id = '". $constraints_destination['seminar_id'] ."'");
        DBManager::get()->exec("UPDATE forumpp_entries SET rgt = rgt + $size
            WHERE rgt >= ". $constraints_destination['rgt'] ." AND seminar_id = '". $constraints_destination['seminar_id'] . "'");

        //move the entries from "outside" the tree to the target node
        $constraints_destination = ForumPPEntry::getConstraints($destination);

        $diff = ($constraints_destination['rgt'] - ($constraints['rgt'] - $constraints['lft'])) - 1 - $constraints['lft'];

        DBManager::get()->exec("UPDATe forumpp_entries
            SET lft = (lft * -1) + $diff, rgt = (rgt * -1) + $diff
            WHERE seminar_id = '". $constraints_destination['seminar_id'] ."'
                AND lft < 0");
    }

    /**
     * check, if the default root-node for this seminar exists and make sure
     * the default category exists as well
     *
     * @param type $seminar_id
     *
     * @return void
     */
    function checkRootEntry($seminar_id) {
        // check, if the root entry in the topic tree exists
        $stmt = DBManager::get()->prepare("SELECT COUNT(*) FROM forumpp_entries
            WHERE topic_id = ? AND seminar_id = ?");
        $stmt->execute(array($seminar_id, $seminar_id));
        if ($stmt->fetchColumn() == 0) {
            $stmt = DBManager::get()->prepare("INSERT INTO forumpp_entries
                (topic_id, seminar_id, name, mkdate, chdate, lft, rgt, depth)
                VALUES (?, ?, 'Übersicht', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 1, 0)");
            $stmt->execute(array($seminar_id, $seminar_id));
        }

        // make sure, that the category "Allgemein" exists
        $stmt = DBManager::get()->prepare("REPLACE INTO forumpp_categories
            (category_id, seminar_id, entry_name) VALUES (?, ?, 'Allgemein')");
        $stmt->execute(array($seminar_id, $seminar_id));
    }
}
