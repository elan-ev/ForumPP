<?php

class ForumPPNavigation extends PluginNavigation {
	function getLink() {
		$forum = new ForumPPPlugin();
		$forum->setId('bulletin_board');
		return PluginEngine::getLink($forum);
	}
}
