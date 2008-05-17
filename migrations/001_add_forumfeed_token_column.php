<?php

class AddForumfeedTokenColumn extends DBMigration {
	function up() {
		$this->db->query("ALTER TABLE `seminare` ADD `forumfeed_token` VARCHAR( 255 ) NOT NULL");
	}

	function down() {
		$this->db->query("ALTER TABLE `seminare` DROP `forumfeed_token`");
	}
}
