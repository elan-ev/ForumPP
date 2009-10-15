<?php

class ForumPPTraversal  {

	static function recreate($parent, $seminar_id, $left) {
		// the right value of this node is the left value + 1
		$right = $left+1;

		// get all children of this node
		$result = DBManager::get()->query("SELECT topic_id FROM px_topics
				WHERE parent_id= '$parent' AND Seminar_id = '$seminar_id'
				ORDER BY chdate;");
		while ($row = $result->fetch()) {
			// recursive execution of this function for each
			// child of this node
			// $right is the current right value, which is
			// incremented by the rebuild_tree function
			$right = ForumPPTraversal::recreate($row['topic_id'], $seminar_id, $right);
		}   

		// we've got the left value, and now that we've processed
		// the children of this node we also know the right value
		DBManager::get()->query('UPDATE px_topics SET lft='.$left.', rgt='.
				$right.' WHERE topic_id = "'.$parent.'";');

		// return the right value of this node + 1
		return $right+1;
	}

	/*
	function addNode($topic_id) {

	}
	*/

	static function repair_root_ids( $seminar_id ) {
		global $count;

		echo '<pre>';
		$stmt = DBManager::get()->query("SELECT * FROM px_topics WHERE parent_id = '0' AND Seminar_id = '$seminar_id'");
		while ($data = $stmt->fetch()) {
			echo $data['name'] . '<br>';
			DBManager::get()->query("UPDATE px_topics SET root_id = topic_id WHERE topic_id = '". $data['topic_id'] ."'");

		}

		echo '<hr>';

		$count = 0;
		$stmt = DBManager::get()->query("SELECT * FROM px_topics WHERE topic_id = root_id AND parent_id = '0' AND Seminar_id = '$seminar_id'");

		function set_rootid_for_childs($parent_id, $root_id) {
			global $count;
			$count++;

			DBManager::get()->query("UPDATE px_topics SET root_id = '$root_id' WHERE parent_id = '$parent_id'");

			$stmt = DBManager::get()->query("SELECT * FROM px_topics WHERE parent_id = '$parent_id'");
			while ($data = $stmt->fetch()) {
				set_rootid_for_childs($data['topic_id'], $root_id);
			}   
		}

		while ($data = $stmt->fetch()) {
			echo $data['name'];
			set_rootid_for_childs($data['topic_id'], $data['root_id']);
			echo ' ('.$count.')<br/>';
			$count = 0;
		}

		echo '</pre>';
	}
}
