<?php

class PreorderTreeTraversal extends Migration {
	function up() {
		DBManager::get()->query("ALTER TABLE `px_topics` ADD `lft` INT NOT NULL , ADD `rgt` INT NOT NULL");
		DBManager::get()->query("ALTER TABLE `px_topics` ADD INDEX ( `lft` , `rgt` )");

		$stmt = DBManager::get();
		$result = $stmt->query("SELECT topic_id FROM px_topics WHERE topic_id = root_id");
		while ($data = $result->fetch(PDO::FETCH_ASSOC)) {
			PreorderTreeTraversal::rebuild_tree($data['topic_id'], 1);
		}
	}

	function rebuild_tree($parent, $left) {
		// the right value of this node is the left value + 1
		$right = $left+1;

		// get all children of this node
		$result = DBManager::get()->query('SELECT topic_id FROM px_topics '.
				'WHERE parent_id="'.$parent.'" ORDER BY chdate;');
		while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
			// recursive execution of this function for each
			// child of this node
			// $right is the current right value, which is
			// incremented by the rebuild_tree function
			$right = $this->rebuild_tree($row['topic_id'], $right);
		}

		// we've got the left value, and now that we've processed
		// the children of this node we also know the right value
		DBManager::get()->query('UPDATE px_topics SET lft='.$left.', rgt='.
				$right.' WHERE topic_id = "'.$parent.'";');

		// return the right value of this node + 1
		return $right+1;
	}

	function down() {
		DBManager::get()->query("ALTER TABLE `px_topics` DROP `lft`, DROP `rgt`");
	}
}
