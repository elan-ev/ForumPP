<?php

class AddFieldPos extends DBMigration {
	function up() {
		$this->db->query("ALTER TABLE `forumpp` ADD `pos` INT NOT NULL DEFAULT '0'");

	}

	function down() {
		$this->db->query("ALTER TABLE `forumpp` DROP `pos`");
	}
}
