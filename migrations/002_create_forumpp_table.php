<?php

class CreateForumppTable extends DBMigration {
	function up() {
		$this->db->query("
			CREATE TABLE IF NOT EXISTS `forumpp` (
				`entry_id` varchar(32) NOT NULL,
				`seminar_id` varchar(32) NOT NULL,
				`entry_type` varchar(30) NOT NULL,
				`topic_id` varchar(32) NOT NULL,
				`entry_name` varchar(255) NOT NULL,
				KEY  (`entry_id`),
				KEY `entry_type` (`entry_type`),
				KEY `topic_id` (`topic_id`)
			) ENGINE=MyISAM;
		");

	}

	function down() {
		$this->db->query("DROP TABLE forumpp;");
	}
}
