<?php

require_once('ForumPPNavigation.class.php');
class ForumPPBulletinBoard extends AbstractStudipSystemPlugin {
	function __construct() {
		parent::AbstractStudipSystemPlugin();

		// navigation
		$navigation =& new ForumPPNavigation();
		$navigation->setDisplayname(_("Schwarzes Brett"));
		$this->setNavigation($navigation);

	}
}
