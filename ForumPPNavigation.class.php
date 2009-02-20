<?php

class ForumPPNavigation extends PluginNavigation {
	function getLink() {
		$forum = new ForumPPPlugin();
		return PluginEngine::getLink($forum, array('cid' => md5('bulletinboard')));
	}
}
