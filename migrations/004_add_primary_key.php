<?php

class AddPrimaryKey extends DBMigration {
	function up() {
		$this->db->query("ALTER TABLE `forumpp` ADD PRIMARY KEY ( `entry_id` , `seminar_id` , `entry_type` , `topic_id` ) ;");

	}

	function down() {
		$this->db->query("ALTER TABLE `forumpp` DROP PRIMARY KEY;");
	}
}
