<?php
	$updated = ForumPPDB::getLastPostingTimestamp($plugin->getId());

	// if now postings where loaded, check which feed has been requested and load the postings
	if (!$postings) {

		// feed for postings in one area
		if ($_REQUEST['root_id']) {
			$postings = $plugin->getDBData('get_postings_for_feed', array('area_id' => $_REQUEST['root_id']));
		} 
		
		// feed for postings in the whole seminar
		else {
			$postings = $plugin->getDBData('get_postings_for_feed', array('id' => $plugin->getId()));
		}

	}

	$format = $_REQUEST['format'];

	/*
	if ($this->get_token() !== $course_id.$token) {
		throw new Exception(dgettext(__CLASS__, "Falsches token."));
	}
	*/

	$seminar = Seminar::getInstance($plugin->getId());

	$parameters = $_REQUEST;
	unset($paramters['source']);
	unset($paramters['Seminar_Session']);

	$rss = new UniversalFeedCreator();
	$rss->title          = _("Forum") . ': "' . $seminar->name . '"';
	$rss->description    = $seminar->subtitle;
	$rss->link           = PluginEngine::getLink($plugin, $parameters, 'feed');
	$rss->syndicationURL = htmlspecialchars($_SERVER['REQUEST_URI']);

	foreach ((array)$postings as $post) {

		$description = quotes_decode(formatReady($plugin->forumKillEdit($post['description']), TRUE, TRUE));

		$item = new FeedItem();
		$item->title = $post['name'];
		$item->link  = PluginEngine::getLink($plugin, array_merge($parameters,  array('jump_to' => $post['entry_id'])), 'feed');
		$item->description = $description;
		$item->date = (int) $post['mkdate'];
		$item->source = $GLOBALS['ABSOLUTE_URI_STUDIP'];
		$item->author = $post['author'];
		$rss->addItem($item);
	}

	$output = $rss->createFeed($format);

	header('Content-Type: '.$rss->contentType.'; charset='.$rss->encoding);
	echo $output;
	exit();


?>
