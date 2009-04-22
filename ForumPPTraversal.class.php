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

	function addNode($topic_id) {

	}
}
