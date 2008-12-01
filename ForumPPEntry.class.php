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

	static function forumKillEdit($description) {
		// wurde schon mal editiert
		if (preg_match('/^(.*)(<admin_msg.*?)$/s',$description, $match)) {
			return $match[1];
		}    
		return $description;
	}

	static function forumAppendEdit($description) {
		$edit = "<admin_msg autor=\"".addslashes(get_fullname())."\" chdate=\"".time()."\">";
		return $description . $edit;
	}

	static function forumParseEdit($description) {
		// wurde schon mal editiert
		if (preg_match('/^.*(<admin_msg.*?)$/s',$description, $match)) {
			$tmp = explode('"',$match[1]);
			$append = "\n\n%%["._("Zuletzt editiert von"). ' '.$tmp[1]." - ".date ("d.m.y - H:i", $tmp[3])."]%%";
			$description = $this->forumKillEdit($description) . $append;
		}    
		return $description;
	}

	static function forumKillQuotes($description) {
		return str_replace('[/quote]', '', preg_replace("/\[quote=.*\]/U", "", $description));
	}

	static function getConstraints($topic_id) {
		// look up the range of postings and the root_id
		$range_stmt = DBManager::get()->prepare("SELECT lft, rgt, root_id FROM px_topics WHERE topic_id = ?");
		$range_stmt->execute(array($topic_id));
		if (!$data = $range_stmt->fetch(PDO::FETCH_ASSOC)) {
			throw new Exception("Forumentry $topic_id not found in ". __FILE__ ." on line ". __LINE__);
		}
		
		return $data;
	}

	
	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 * D   A   T   A   -   R   E   T   R   I   E   V   A   L *
	 * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
	/*
	 * retrieve the the latest posting under $parent 
	 * or false if the postings itself is the latest
	 *
	 * @param string $parent the node to lookup the childs in
	 * @return mixed the data for the latest postings or false
	 */
	static function getLatestPosting($parent) {
		$data = ForumPPEntry::getConstraints($parent);
		$stmt = DBManager::get()->prepare("SELECT * FROM px_topics 
			WHERE lft >= ? AND rgt <= ? AND root_id = ?
			LIMIT 1 ORDER BY mkdate DESC");
		$stmt->execute(array($data['lft'], $data['rgt'], $data['root_id']));

		if (!$data = $stmt->fetch(PDO::FETCH_ASSOC)) {
			return false;
		}

		return array_merge($data, ForumPPEntry::getPathToPosting($data['topic_id']));
	}

	static function getPathToPosting($topic_id) {
		$data = ForumPPEntry::getConstraints($topic_id);

		// if the topic_id matches the root_id, the requested posting is the root of the tree
		if ($topic_id == $data['root_id']) {
			return array('root_id' => $data['root_id']);
		} else {
			$stmt = DBManager::get()->prepare("SELECT * FROM px_topics WHERE lft < ? AND rgt > ? AND root_id = ? ORDER BY lft ASC");
			$stmt->execute(array($data['lft'], $data['rgt'], $data['root_id']));
			while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
				$ret[] = $data['topic_id'];
			}

			return array('root_id' => $data[0], 'thread_id' => $data[1]);
		}
	}

	static function getEntries($parent, $sem_id, $with_childs = false) {
		// calculate constraint for pagination
		$page = 1;

		if ($GLOBALS['_REQUEST']['page']) {
			$page = $GLOBALS['_REQUEST']['page'];
		}

		if ($GLOBALS['_REQUEST']['jump_to']) {
			$page = ceil(ForumPPEntry::countPostings($parent) / ForumPPEntry::POSTINGS_PER_PAGE);
		}

		$GLOBALS['_REQUEST']['page'] = $page;
		$start = ($page - 1) * ForumPPEntry::POSTINGS_PER_PAGE;

		// get entries of $parent with all childs and sub-childs
		if ($with_childs) {
			$data = ForumPPEntry::getConstraints($parent);
			$stmt = DBManager::get()->prepare("SELECT px_topics.*, ou.flag as fav FROM px_topics 
				LEFT JOIN object_user as ou ON (ou.object_id = px_topics.topic_id AND ou.user_id = ?)
				WHERE lft >= ? AND rgt <= ? AND root_id = ? AND Seminar_id = ? LIMIT ?,?
				ORDER BY mkdate");
			$stmt->execute(array($GLOBALS['user']->id, $data['lft'], $data['rgt'], $data['root_id'],
				$sem_id, $start, ForumPPEntry::POSTINGS_PER_PAGE));
			// $stmt->execute($data); // not sure if this works consistent
		} 

		// get only the next level of the tree
		else {
			$stmt = DBManager::get()->prepare("SELECT px_topics.*, ou-flag as fav FROM px_topics 
				LEFT JOIN object_user as ou ON (ou.object_id = px_topics.topic_id AND ou.user_id = ?)
				WHERE parent_id = ? AND Seminar_id = ? LIMIT ?,?
				ORDER BY mkdate");
			$stmt->execute(array($GLOBALS['user']->id, $parent, $sem_id, $start, ForumPPEntry::POSTINGS_PER_PAGE));
		}

		if (!$stmt) throw new Exception("Error while retrieving postings in ". __FILE__ ." on line ". __LINE__);

		$posting_list = array();
		// retrieve the postings
		while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
			// we throw away all formatting stuff, tags, etc, leaving the important bit of information
			$desc_short = ForumPPEntry::br2space(kill_format(strip_tags($db->f('description'))));
			if (strlen($desc_short) > (ForumPPEntry::THREAD_PREVIEW_LENGTH +2)) {
				$desc_short = substr($desc_short, 0, ForumPPEntry::THREAD_PREVIEW_LENGTH) . '...';
			} else {
				$desc_short = $desc_short;
			}

			$posting_list[] = array(
				'author' => $post['author'],
				'topic_id' => $post['topic_id'],
				'name' => formatReady($post['name']),
				'name_raw' => $post['name'];
				'description' => formatReady(ForumPPEntry::parseEdit($post['description'])),
				'description_raw' => $this->forumKillEdit($db->f('description')),
				'description_short' => $desc_short,
				'chdate' => $post['mkdate'],
				'owner_id' => $post['user_id'],
				'raw_title' => $post['name'],
				'raw_description' => ForumPPEntry::killEdit($post['description']),
				'fav' => ($post['fav'] == 'fav'),
			);

		} // retrieve the postings

		return $posting_list;
	}

	/*
	 * params: parent => topic_id, id => seminar_id / institut_id
	 */
	static function getFlatList($type, $parent, $id) {
		switch ($type) {
			case 'areas':
			case 'threads':
				$postings = ForumPPEntry::getEntries($parent, $id);
				foreach ($postings as $key => $posting) {

					if ($data = ForumPPEntry::getLatestPosting($posting['topic_id'])) {
						$last_posting['date'] = $data['mkdate'];
						$last_posting['user_id'] = $data['user_id'];
						$last_posting['user_fullname'] = $data['author'];
						$last_posting['username'] = get_username($data['user_id']);

						// we throw away all formatting stuff, tags, etc, so we have just the important bit of information
						$text = strip_tags($data['description']);
						$text = ForumPPEntry::br2space($text);
						$text = kill_format(ForumPPEntry::killQuotes($text));

						if (strlen($text) > 42) {
							$text = substr($text ,0, 40) . '...';
						}

						$last_posting['text'] = $text;
					} else {
						$last_posting = _("keine Beitr&auml;ge");
					}

					$postings[$key]['last_posting'] = $last_posting;
					$postings[$key]['num_postings'] = ForumPPEntry::countPostings($posting['topic_id']);

				}

				return $postings;
				break;

			case 'postings':
				$postings = ForumPPEntry::getEntries($parent, $id, ForumPPEntry::WITH_CHILDS);
				break;
		}
	}

	static function countPostings($parent) {
		$data = ForumPPEntry::getConstraints($parent);
		return ($data['rgt'] - $data['lft'] - 1) / 2;
	}
}
