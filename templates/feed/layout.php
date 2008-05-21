<?php

if ($_REQUEST['thread_id']) {
	$area = ', '. _("Bereich") .': '. $plugin->getDBData('entry_name', array('entry_id' => $_REQUEST['root_id'])) ;
	$area .= ', '. _("Thema") .': '. $plugin->getDBData('entry_name', array('entry_id' => $_REQUEST['thread_id'])) ;
}

// if now postings where loaded, check which feed has been requested and load the postings
if (!$postings && $_REQUEST['plugin_subnavi_params'] != 'search') {

	// feed for postings in one area
	if ($_REQUEST['root_id']) {
		$area = ', '. _("Bereich") .': '. $plugin->getDBData('entry_name', array('entry_id' => $_REQUEST['root_id'])) ;
		$postings = $plugin->getDBData('get_postings_for_feed', array('area_id' => $_REQUEST['root_id']));
	} 
	
	// feed for postings in the whole seminar
	else {
		$postings = $plugin->getDBData('get_postings_for_feed', array('id' => $plugin->getId()));
	}
}

$format = $_REQUEST['format'];

$seminar = Seminar::getInstance($plugin->getId());

$parameters = $_REQUEST;
unset($paramters['source']);
unset($paramters['Seminar_Session']);

$rss = new UniversalFeedCreator();
$rss->title          = _("Forum") . ': "' . $seminar->name . '"' . $area;
$rss->description    = $seminar->subtitle;
$rss->link           = $GLOBALS['ABSOLUTE_URI_STUDIP'] . PluginEngine::getLink($plugin, $parameters, 'feed');
$rss->syndicationURL = htmlspecialchars($_SERVER['REQUEST_URI']);

foreach ((array)$postings as $post) {

	$item = new FeedItem();

	// if available, give further information on where the posting is located
	$name = '';
	if ($post['area_name']) $name .= $post['area_name'] . ' >> ';
	if ($post['thread_name']) $name .= $post['thread_name']. ' >> ';
	$name .= $post['name'];

	$item->title = $name;


	$link = PluginEngine::getUrl($plugin, array('root_id' => $post['root_id'], 'thread_id' => $post['thread_id'], 'jump_to' => $post['topic_id'])) . '#' . $post['topic_id'];
	$item->link  = $GLOBALS['ABSOLUTE_URI_STUDIP'] . $link;


	$description = quotes_decode($post['description']);
	$item->description = $description;

	$item->date = (int) $post['mkdate'];
	$item->source = $GLOBALS['ABSOLUTE_URI_STUDIP'];
	$item->author = $post['author'];
	$rss->addItem($item);
}

$output = $rss->createFeed($format);

header('Content-Type: '.$rss->contentType.'; charset='.$rss->encoding);
echo str_replace('<summary>', '<summary type="html">', $output);
die;
