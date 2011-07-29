<?php

class CreateForumppTable extends DBMigration {
	function up() {
		DBManager::get()->exec("
			CREATE TABLE IF NOT EXISTS `forumpp_categories` (
				`category_id` varchar(32) NOT NULL,
				`seminar_id` varchar(32) NOT NULL,
				`entry_name` varchar(255) NOT NULL,
                `pos` INT NOT NULL DEFAULT '0',
                PRIMARY KEY ( `category_id` )
			);
		");

		DBManager::get()->exec("
            CREATE TABLE IF NOT EXISTS `forumpp_categories_entries` (
				`category_id` varchar(32) NOT NULL,
				`topic_id` varchar(32) NOT NULL,
                `pos` INT NOT NULL DEFAULT '0',
                PRIMARY KEY ( `category_id` , `topic_id` )
            );
        ");
                

        DBManager::get()->exec("
			CREATE TABLE IF NOT EXISTS `forumpp_entries` (
				`topic_id` varchar(32) NOT NULL,
                `seminar_id` varchar(32) NOT NULL,
                `user_id` varchar(32) NOT NULL,
				`name` varchar(255) NOT NULL,
				`content` text NOT NULL,
				`mkdate` int(20) NOT NULL,
				`chdate` int(20) NOT NULL,
                `author` varchar(255) NOT NULL,
                `author_host` varchar(255) NOT NULL,
                `lft` int(11) NOT NULL,
                `rgt` int(11) NOT NULL,
                `depth` int(11) NOT NULL,
                `anonymous` tinyint(4) NOT NULL DEFAULT '0',
				PRIMARY KEY (`topic_id`)
			);
		");

	}

	function down() {
		$this->db->query("DROP TABLE IF EXISTS forumpp_categories;");
		$this->db->query("DROP TABLE IF EXISTS forumpp_categories_entries;");
        $this->db->query("DROP TABLE IF EXISTS forumpp_entries;");
	}
}
