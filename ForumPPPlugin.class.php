<?php

/*
 * Copyright (C) 2007 - Till Glöggler     <tgloeggl@uos.de>
 *                      Marcus Lunzenauer <mlunzena@uos.de>
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */


/**
 * @author    mlunzena
 * @author    tgloeggl
 * @copyright (c) Authors
 * @version   $Id: TGForumPlugin.class.php 632 2008-04-11 10:48:38Z tgloeggl $
 */

global $RELATIVE_PATH_ELEARNING_INTERFACE;
//require_once ( "sphinxapi.php" );
require_once('db/ForumPPDB.php');
require_once('ForumPPTraversal.class.php');
require_once('ForumPPEntry.class.php');
require_once('lib/classes/AdminModules.class.php');

if (!defined('FEEDCREATOR_VERSION')) {
	require_once( dirname(__FILE__) . '/vendor/feedcreator/feedcreator.class.php');
}

define ('BULLETIN_BOARD', md5('bulletinboard'));

class ForumPPPlugin extends AbstractStudIPStandardPlugin {

	var $THREAD_PREVIEW_LENGTH = 100;
	var $POSTINGS_PER_PAGE = 10;
	var $FEED_POSTINGS = 100;
	var $OUTPUT_FORMATS = array('html' => 'html', 'feed' => 'feed');

	var $FEED_FORMATS = array(
		'RSS0.91' => 'application/rss+xml',
		'RSS1.0'  => 'application/xml',
		'RSS2.0'  => 'application/xml',
		'ATOM0.3' => 'application/atom+xml',
		'ATOM1.0' => 'application/atom+xml'
	);


  var $template_factory;
	var $avatar_class = false;
	var $rechte = false;
	var $lastlogin = 0;

	var $writable = false;
	var $editable = false;

	/**
	 * defines the chosen output format, one of OUTPUT_FORMATS
	 */
	var $output_format = 'html';

	var $_ENHANCED = false;

  function ForumPPPlugin() {

    parent::AbstractStudIPStandardPlugin();

		if (!$this->getId()) { 
			$this->setId(BULLETIN_BOARD);
		} else {
			// navigation
	    $navigation =& new PluginNavigation();
	    $navigation->setDisplayname(_("Forum"));
	    $this->setNavigation($navigation);
		}

    // AbstractStudIPStandardPlugin specifics

		$this->setPluginiconname("img/pages.png");
    $this->setChangeIndicatorIconName('img/pages_red.png');

    $this->setShownInOverview(TRUE);

		//$this->seminar_class = $GLOBALS['SessSemName']['class'];
    $this->rechte = $GLOBALS['perm']->have_studip_perm('tutor', $this->getId());

		$this->check_for_enhance();
		$this->check_write_and_edit();
	}

	function check_write_and_edit() {
		global $SemSecLevelRead, $SemSecLevelWrite, $SemUserStatus;
		/*
		 * Schreibrechte
		 * 0 - freier Zugriff
		 * 1 - in Stud.IP angemeldet
		 * 2 - nur mit Passwort
		 */

		// This is a separate view on rights, nobody should not be able to edit posts from other nobodys
		$this->editable = $GLOBALS['perm']->have_studip_perm('user', $this->getId());
		if ($GLOBALS['perm']->have_studip_perm('user', $this->getId())) {
			$this->writable = true;
		} else if (isset($SemSecLevelWrite) && $SemSecLevelWrite == 0) {
			$this->writable = true;
		}
	}

	function check_token() {
		$db = DBManager::get('studip');
		$result = $db->query("SELECT forumfeed_token FROM seminare WHERE Seminar_id = '{$this->getId()}'");
		$data = $result->fetch(PDO::FETCH_ASSOC);

		if (!$data['forumfeed_token']) {
			$this->token = md5(uniqid(rand()));
			$db->query("UPDATE seminare SET forumfeed_token = '{$this->token}' WHERE Seminar_id = '{$this->getId()}'");
		} else {
			$this->token = $data['forumfeed_token'];
		}
	}

	function initialize() {
		global $_include_additional_header;

		$this->check_token();

		// we want to display the dates in german
		setlocale(LC_TIME, 'de_DE@euro', 'de_DE', 'de', 'ge');

		// the default for displaying timestamps
		$this->time_format_string = "%A %d. %B %Y, %H:%M";
		$this->time_format_string_short = "%a %d. %B %Y, %H:%M";

		$this->template_factory =
			new Flexi_TemplateFactory(dirname(__FILE__).'/templates');

		// path to plugin-pictures
		$this->picturepath = $GLOBALS['CANONICAL_RELATIVE_PATH_STUDIP'] . $this->getPluginPath() . '/img';

		$_include_additional_header =
			'<link rel="stylesheet" href="'. PluginEngine::getLink($this, array(), 'css') .'" type="text/css">' . "\n";

		$include_links = true;

		if (isset($_REQUEST['plugin_subnavi_params']))
			switch ($_REQUEST['plugin_subnavi_params']) {

				case 'last_postings':
				case 'search':
					$include_links = true;
					break;

				case 'new_postings':
				case 'favorites':
				case 'config':
				default:
					$include_links = false;
					break;

		}

		if ($include_links) {
			// set autodiscovery links
			$link_params = $_REQUEST;
			unset($link_params['source']);
			unset($link_params['Seminar_Session']);

			$params = array(
					'formats' => $this->FEED_FORMATS,
					'plugin'  => $this,
					'link_params' => $link_params,
					'token' => $this->token
					);

			$GLOBALS['_include_additional_header'] .= $this->template_factory->render('feed/links', $params);

		}
	}

	function check_for_enhance() {
		$db = new DB_Seminar("SHOW TABLES LIKE 'forumpp'");
		if ($db->num_rows() == 1) $this->_ENHANCED = true;
	}

	function getPluginname() {
		return _("Forum");
	}

	function getDisplaytitle() {
		return $this->getPluginname();
	}

	function getDesigns() {
		return array(
			array('value' => 'web20', 'name' => 'Blue Star'),
			array('value' => 'studip', 'name' => 'Safir&eacute; (Stud.IP)')
		);
	}

	function setDesign($design) {
		$_SESSION['forumpp_template'][$this->getId()] = $design;
	}

	function getDesign() {
		return $_SESSION['forumpp_template'][$this->getId()];
	}

	function actionCss () {
		if (!$this->getDesign()) {
			$this->setDesign('web20');
		}

		if ($this->getDesign() == 'studip') {
			$template_before = $this->template_factory->open('css/web20.css.php');
			$template_before->set_attribute('picturepath', $this->picturepath);

			$template = $this->template_factory->open('css/studip.css.php');
			$template->set_attribute('picturepath', $GLOBALS['ASSETS_URL'] . '/images');
		} else {
			$template = $this->template_factory->open('css/'. $this->getDesign() .'.css.php');
			$template->set_attribute('picturepath', $this->picturepath);
		}

		// this hack is necessary to disable the standard Stud.IP layout
		ob_end_clean();

		date_default_timezone_set('CET');
		$expires = date(DATE_RFC822,time()+(24*60*60));		// expires after one day
		$today = date(DATE_RFC822);
		header('Date: '.$today);
		header('Expires: '.$expires);
		header('Cache-Control: public');
		header('Content-Type: text/css');

		if (isset($template_before)) {
			echo $template_before->render();
		}
		echo $template->render();
		ob_start('discard_buffer');
		die;
	}

	function actionFeed() {
		// this hack is necessary to disable the standard Stud.IP layout
		ob_end_clean();

		if ($_REQUEST['token'] != $this->token) die;

		$this->last_visit = time();
		$this->output_format = 'feed';
		$this->POSTINGS_PER_PAGE = $this->FEED_POSTINGS;

		$this->loadView();
	}

	/*
	 * AJAX Backend-Actions
	 */
	function actionSavecats() {
		ob_end_clean();
		if (!$this->rechte) return;

		$entry_id = substr($_REQUEST['topic_id'], 9, strlen($_REQUEST['topic_id']));
		$pos = 0;
		foreach($_REQUEST['l'] as $item) {
			$topic_id = substr($item, 5, strlen($item));
			$stmt = DBManager::get()->prepare("REPLACE INTO forumpp (entry_id, seminar_id, entry_type, topic_id, entry_name, pos) VALUES (?, ?, 'area', ?, '', ?)");
			$stmt->execute($t = array($entry_id, $this->getId(), $topic_id, $pos));
			$pos++;
		}
	}

	/*
	 * this function changes the parent node of a child node, correcting the root_id
	 */
	function actionChangeParent() {
		ob_end_clean();
		if (!$this->rechte) return;

		$new_parent = $_REQUEST['new_parent'];
		$topic_id = $_REQUEST['topic_id'];

		// find out the root_id for the new node
		$stmt = DBManager::get()->prepare("SELECT * FROM px_topics WHERE topic_id = ?");
		$stmt->execute(array($new_parent));
		if (!$data = $stmt->fetch(PDO::FETCH_ASSOC)) die;
		$new_root_id = $data['root_id'];
		$new_left = $data['lft'];

		// remove the entry from the categories if becomes a root node
		if ($new_parent == '0'){
			$stmt = DBManager::get()->prepare("DELETE FROM forumpp WHERE topic_id = ? AND seminar_id = ?");
			$stmt->execute(array($topic_id, $this->getId()));
		}

		// get the left pott
		$stmt = DBManager::get()->prepare("SELECT pxb.topic_id, pxb.left 
			FROM px_topics as pxa 
			LEFT JOIN px_topics pxb ON (pxa.parent_id = pxb.topic_id) 
			WHERE pxa.topic_id = ?");
		$stmt->execute(array($topic_id));
		if (!$data = $stmt->fetch(PDO::FETCH_ASSOC)) die;
		$old_left = $data['lft'];
		$old_parent = $data['topic_id'];

		// set the new parent and root for the submitted node
		$stmt = DBManager::get()->prepare("UPDATE px_topics SET parent_id = ?, root_id = ?, chdate = ? WHERE topic_id = ?");
		$stmt->execute(array($new_parent, $new_root_id, time(), $topic_id));

		// rebuild the two sub-trees
		ForumPPTraversal::recreate($new_parent, $new_left);
		ForumPPTraversal::recreate($old_parent, $old_left);
	}

	function actionLoadChilds() {
		ob_end_clean();
		if (!$this->rechte) return;

		if ($this->rechte) {
			$childs = $this->getDBData('get_child_postings', array('parent_id' => $_REQUEST['area_id']));
			echo '<ul>';
			foreach ($childs as $entry) {
				echo '<li id="area_'. $entry['topic_id'] .'">';

				if ($entry['has_childs']) {
					echo '<a href="javascript:loadChilds(\''. $entry['topic_id'] .'\')" ';
					echo 'onMouseOver="showTooltip(\'area_'. $entry['topic_id'] .'\', \''.  preg_replace(array("/'/", '/"/', '/&#039;/'), array("\\'", '&quot;', "\\'"), $entry['description']) .'\')" ';
					echo 'onMouseOut="hideTooltip()">';
					echo $entry['name'] .'</a>';
				} else {
					echo '<span ';
					echo 'onMouseOver="showTooltip(\'area_'. $entry['topic_id'] .'\', \''.  preg_replace(array("/'/", '/"/', '/&#039;/'), array("\\'", '&quot;', "\\'"), $entry['description']) .'\')" ';
					echo 'onMouseOut="hideTooltip()">';
					echo $entry['name'];
					echo '</span>';
				}

				echo '&nbsp;&nbsp;';
				echo '<a href="javascript:choose(\''. $entry['topic_id'] .'\')" title="Diskussionsstrang ausschneiden"><img id="'. $entry['topic_id'] .'" src="'. $this->picturepath .'/icons/cut.png"></a>';
				echo '&nbsp; &nbsp;';
				echo '<a href="javascript:paste(\''. $entry['topic_id'] .'\')" title="Diskussionsstrang hier einfügen"><img src="'. $this->picturepath .'/icons/paste_plain.png"></a>';
				echo '</li>';
			}
			echo '</ul>';
		}
		die;
	}

	/*
	 * Main action
	 */
  function actionShow() {

		if ($this->getId() != BULLETIN_BOARD) {
			// check for SeminarSession and set visit
			checkObject();
			// checkObjectModule("forum");

			$this->last_visit = object_get_visit($this->getId(), "forum");
			if (!$this->last_visit) $this->last_visit = time();

			object_set_visit_module("forum");
		}

    if (isset($_REQUEST['subcmd'])) {
      switch ($_REQUEST['subcmd']) {

        case 'create_area':
					if ($this->writable && $this->rechte)
          $this->create_area();
          break;

        case 'create_thread':
					if ($this->writable)
          $this->create_thread();
          break;

        case 'create_posting':
					if ($this->writable)
          $this->create_posting();
          break;

        case 'delete':
					if ($this->writable)
          $this->delete_posting();
          break;

				case 'do_edit_posting':
					if ($this->writable && $this->editable)
					$this->edit_posting();
					break;

				case 'delete_area':
					if ($this->writable)
					$this->delete_area();
					break;

				case 'fav':
					if ($this->writable)
					object_switch_fav($_REQUEST['entryid']);
					break;

				case 'set_design':
					$this->setDesign($_REQUEST['template']);
					break;
      }
    }

		echo '<div class="forumpp">';
		$this->loadView();
		echo '</div>';
	}

	function loadView() {
		if (isset($_REQUEST['plugin_subnavi_params'])) {
			switch ($_REQUEST['plugin_subnavi_params']) {

				case 'last_postings':
					$this->lastPostingsShow();
					break;

				case 'new_postings':
					$this->newPostingsShow();
					break;

				case 'favorites':
					$this->favoritesShow();
					break;

				case 'search':
					$this->searchShow();
					break;

				case 'config':
					$this->configShow('area_admin');
					break;

				case 'config2':
					$this->configShow('thread_admin');
					break;

				default:
					$this->forumShow();
					break;
			}
		} else {
			//if ($_REQUEST['source'] == 'va') {
		    $this->forumShow();
			/*
			} else {
				if ($this->getDBData('get_new_postings_count') > 0) {
					$this->newPostingsShow();
				} else {
					$this->forumShow();
				}
			}
			*/
		}
	}


	function setPluginPath($newpath) {
		parent::setPluginPath($newpath);
		$this->buildMenu();
	}

	function buildMenu() {
		$navigation = new PluginNavigation();
		$navigation->setDisplayname('Forum');
		$navigation->addLinkParam('source', 'va');
		$navigation->setActive();


		if ($this->rechte) {
			$sub_nav = new PluginNavigation();
			$sub_nav->setDisplayname(_("Bereiche administrieren"));
			$sub_nav->addLinkParam('plugin_subnavi_params', 'config');
			$navigation->addSubmenu($sub_nav);

			$sub_nav2 = new PluginNavigation();
			$sub_nav2->setDisplayname(_("Postings administrieren"));
			$sub_nav2->addLinkParam('plugin_subnavi_params', 'config2');
			$navigation->addSubmenu($sub_nav2);

		}

		$this->setNavigation($navigation);
	}

  function actionShowAdministrationPage() {
    printf("in %s:%s\n", __CLASS__, __FUNCTION__);
  }


  // AbstractStudIPStandardPlugin specifics

  function hasChanged($lastlogin) {
		//echo date('d.m.Y H:i', $lastlogin);
		$this->last_visit = object_get_visit($this->getId(), "forum", "visitdate");
		if (!$this->last_visit) {
			$this->last_visit = $lastlogin;
		}
		return $this->getDBData('get_new_postings_count');
  }

	function getOverviewMessage($has_changed = FALSE) {
		if ($has_changed) {
			$c = $this->getDBData('get_new_postings_count');
			if ($c == 1) {
				return _("Ein neuer Beitrag vorhanden");
			} else {
				return sprintf(_("%s neue Beiträge vorhanden."), $c);
			}
		};

		return _("Keine neuen Beiträge.");
	}


	/*
	function getChangeMessages($lastlogin, $ids) {
		return array();
	}
	*/

	function getScore() {
		return 1;
	}


	/* * * * * * * * * * * * * * * * * * *
	 * C O M M A N D - F U N C T I O N S *
	 * * * * * * * * * * * * * * * * * * */

	/**
	 * creates a new entry in px_topics
	 * @param array of values to overwrite the defaults with.
	 * @returns the topic_id of the newly created entry
	 */
	function insert_entry($data) {
		// first: we set some useful defaults
		$topic_id = md5(uniqid(rand()));

		$defaults = array (
			'topic_id' => $topic_id,
			'name' => 'Kein Titel',
			'description' => 'Keine Beschreibung',
			'parent_id' => 0,
			'root_id' => $topic_id,
			'author' => get_fullname($GLOBALS['user']->id),
			'author_host' => getenv('REMOTE_ADDR'),
			'Seminar_id' => $this->getId(),
			'user_id' => $GLOBALS['user']->id,
			'mkdate' => time(),
			'chdate' => time()
		);

		// second: we overwrite the defaults with specified data
		foreach ($data as $field => $value) {
			$defaults[$field] = $value;
		}

		// third: we build the query and execute it
		$query = "INSERT INTO px_topics (".implode(', ', array_keys($defaults)).") VALUES ('".implode("', '", $defaults)."')";

		new DB_Seminar($query);

		if ($defaults['parent_id'] == 0) {
			ForumPPTraversal::recreate($defaults['topic_id'], 0);
		} else {
			$data = ForumPPEntry::getConstraints($defaults['parent_id']);
			ForumPPTraversal::recreate($defaults['parent_id'], $data['lft']);
		}
		return $topic_id;
	}

	/**
	 * creates a new top-level entry
	 */
  function create_area() {

		if ($_REQUEST['title']) {
			$data = array (
				'name' => $GLOBALS['_REQUEST']['title'],
				'description' => $GLOBALS['_REQUEST']['data']
			);
			$GLOBALS['_REQUEST']['root_id'] = $this->insert_entry($data);
		}

  }

	/**
	 * creates a new thread in an area
	 */
  function create_thread() {
		global $_REQUEST;

		$data = array (
			'root_id' => $_REQUEST['root_id'],
			'parent_id' => $_REQUEST['root_id'],
			'name' => $_REQUEST['title'],
			'description' => $_REQUEST['data']
		);

		$GLOBALS['_REQUEST']['thread_id'] = $this->insert_entry($data);
  }


	/**
	 * add a posting to a thread
	 */
  function create_posting() {
		global $_REQUEST;

		$data = array (
			'root_id' => $_REQUEST['root_id'],
			'parent_id' => $_REQUEST['thread_id'],
			'name' => $_REQUEST['title'],
			'description' => $_REQUEST['data']
		);

		$this->insert_entry($data);
  }


	/**
	 * is a helper function of {@link delete_posting()} to delete all childs of a posting (if any)
	 * @param string $parent the parent-id, to find all childs with that parent
	 */
	function delete_child_postings($parent) {
		$db = new DB_Seminar("SELECT * FROM px_topics WHERE parent_id = '$parent'");

		while ($db->next_record()) {
			$this->delete_child_postings($db->f('topic_id'));
			new DB_Seminar($query = "DELETE FROM px_topics WHERE topic_id = '" .$db->f('topic_id'). "'");
		}
	}

	/**
	 * deletes a posting or a thread an all subpostings
	 * @param string $posting_id optional, if not given $_REQUEST['entryid'] is used as posting-id.
	 */
	function delete_posting($posting_id = null) {

		if ($this->rechte) {
			if ($posting_id) {
				$topic_id = $posting_id;
			} else {
				$topic_id = $GLOBALS['_REQUEST']['entryid'];
			}

			// unset these variables, because maybe we can't jump to that thread anymore
			unset($GLOBALS['_REQUEST']['entryid']);
			unset($GLOBALS['_REQUEST']['thread_id']);

			$db = new DB_Seminar("SELECT * FROM px_topics WHERE topic_id = '$topic_id'");
			if (!$db->next_record() || $db->num_rows() == '0') return;

			// this denotes the area we are in
			$GLOBALS['_REQUEST']['root_id'] = $db->f('root_id');

			if ($db->f('parent_id') == $db->f('root_id')) {
				$this->delete_child_postings($db->f('topic_id'));
			} else {
				// we did not delete a thread, only a single posting (+childs), so we can jump to the thread
				$GLOBALS['_REQUEST']['thread_id'] = $GLOBALS['_REQUEST']['jumpid'];
			}

			// don't forget to delete the main posting
			new DB_Seminar($query = "DELETE FROM px_topics WHERE topic_id = '". $db->f('topic_id') ."'");
		}
  }

	/**
	 * deletes a whole area with all threads and postings in there
	 */
	function delete_area() {

		if ($this->rechte) {
			$db = new DB_Seminar("DELETE FROM px_topics WHERE root_id = '". $_REQUEST['area_id'] ."'");
			$this->addMessage(sprintf(_("Es wurden %s Eintr&auml;ge gelöscht!"), $db->affected_rows()), 'msg');
		}
	}


	/*
	 * modifys an existing posting, setting the new title and the new description
	 */
	function edit_posting () {

		$db = new DB_Seminar("SELECT * FROM px_topics WHERE topic_id = '". $_REQUEST['posting_id'] ."'");
		if ($db->next_record()) {
			if ($this->rechte || $db->f('user_id') == $GLOBALS['user']->id) {
				// add the new edit-remark
				$inhalt = $this->forumAppendEdit($_REQUEST['posting_data']);

				new DB_Seminar("UPDATE px_topics SET name = '". $_REQUEST['posting_title'] ."', description = '$inhalt' WHERE topic_id = '". $_REQUEST['posting_id'] ."'");
			}
		}
	}


	/**
	 * Shows buttons for the creation of areas/threads/postings or the respective input formula.
	 * Shows as well the button to delete an area.
	 * @param string $part one of main / area / thread, depending on were we are
	 */
	function show_menubar($part = 'main', $area_name = '', $thread_name = '') {
		global $_REQUEST;

		$has_rights = $this->rechte;

		switch ($part) {
		case 'main':
			$has_rights = ($this->writable && $this->rechte);
			$title = _("Neuen Bereich erstellen");
			$name = _("Bereichsname");
			$content = _("Beschreibung");
			$subcmd = 'create_area';
			$rows = 10;
			break;

		case 'area':
			$has_rights = $this->writable;
			$title = _("Neues Thema erstellen");
			$name = _("Titel");
			$content = _("Inhalt");
			$subcmd = 'create_thread';
			$rows = 10;
			break;

		case 'thread':
			$has_rights = $this->writable;
			$title = _("Neuen Beitrag erstellen");
			$name = _("Titel");
			$content = _("Inhalt");
			$subcmd = 'create_posting';
			$rows = 10;

			$inhalt = '';

			$db = new DB_Seminar("SELECT * FROM px_topics WHERE topic_id = '". $_REQUEST['thread_id'] ."'");
			$db->next_record();
			$name_value = 'Re: '. htmlReady($db->f('name'));

			if ($_REQUEST['subcmd'] == 'cite_posting') {
				## TODO: request-Variable nicht ungeprüft übernehmen!
				$db = new DB_Seminar("SELECT * FROM px_topics WHERE topic_id = '". $_REQUEST['posting_id'] ."'");

				if ($db->next_record()) {
					$content_value = htmlReady(quotes_encode(ForumPPEntry::killEdit($db->f('description')), $db->f('author')));
					$content_value .= "\n\n";
				}
			}
			break;

		}


		ob_start();

		// show navigation
		?>
		<span class="areaname">
			&nbsp;&nbsp;<a href="<?= PluginEngine::getLink($this, array('source' => 'va')) ?>">Forum</a>
			<? if ($_REQUEST['root_id']) : ?>
				&bull;&nbsp;<a href="<?= PluginEngine::getLink($this, array('root_id' => $_REQUEST['root_id'])) ?>"><?= $area_name ?></a>
			<? endif; if ($_REQUEST['thread_id']) : ?>
				&bull;&nbsp;<a href="<?= PluginEngine::getLink($this, array('root_id' => $_REQUEST['root_id'], 'thread_id' => $_REQUEST['thread_id'])) ?>"><?= $thread_name ?></a>
			<? endif; ?>
		</span>
		<br/>
		<br/>
		<center>
		<?
		if ($has_rights && $_REQUEST['section'] == $subcmd) { ?>
			<a name="<?= $subcmd ?>"></a>
			<form action="" method="post">
			<input type="hidden" name="section" value="">
			<input type="hidden" name="cmd" value="show">
			<input type="hidden" name="subcmd" value="<?= $subcmd ?>">
			<div class="posting bg2">
			<span class="corners-top"><span></span></span>

			<div class="postbody">
			<span class="title"><?= $title ?></span><br/>

			<p class="content" style="margin-bottom: 0pt">
			<strong><?= $name ?>:</strong><br/>
			<input type="text" name="title" style="width: 100%" value="<?= $name_value ?>"><br/>
			<br/>

			<?= $this->show_textedit_buttons() ?>
			</p>
			</div>

			<div class="postbody">
			<textarea id="inhalt" name="data" style="width: 100%" rows="<?= $rows ?>"><?= $content_value ?></textarea><br/>
			</div>

			<dl class="postprofile">
			<dt>
			<?= $this->show_smiley_favorites() ?>
			</dt>
			</dl>

			<div class="buttons">
			<input type="image" <?= makebutton('erstellen', 'src') ?>>&nbsp;&nbsp;&nbsp;
			<?
			if ($_REQUEST['thread_id']) $params['thread_id'] = $_REQUEST['thread_id'];
			if ($_REQUEST['root_id']) $params['root_id'] = $_REQUEST['root_id'];
			$link = PluginEngine::getLink($this, array_merge((array)$params, array('page' => $_REQUEST['page'])));
			?>
			<a href="<?= $link ?>"><img border="0" <?= makebutton('abbrechen', 'src') ?>></a>
			</div>

			<span class="corners-bottom"><span></span></span>
			</div>
			<?= $this->get_hidden_fields(array('thread_id', 'root_id', 'page', 'plugin_subnavi_params')) ?>
			</form>
			<br/>
			<?
		}/* else {
			if ($part == 'thread') {
				$link = PluginEngine::getLink($this, array('section' => 'create_posting', 'thread_id' => $_REQUEST['thread_id'], 'root_id' => $_REQUEST['root_id'], 'page' => $_REQUEST['page'], 'time' => time()));
				echo '<a href="'. $link .'#create_posting"><img border="0" '. makebutton('antworten', 'src') .'</a>';
			}
		}*/

		echo '</center>';
		return ob_get_clean();
	}


	/**
	 * displays one posting with all belonging gui-elements, like delete, edit, cite.
	 * @param string $username this is the db-field author of px_topics
	 * @param int $datum timestamp of posting-creation
	 * @param string $titel the formatted thread/posting-title
	 * @param string $inhalt the formatted text of the posting
	 * @param string $entryid	the topic_id of the posting
	 * @param string $jumpid is the area-id, if this is the first posting of the thread, the thread-id otherwise
	 * @param string $owner_id the id of user who posted this
	 * @param string $raw_title unformatted thread/posting-title
	 * @param string $raw_description unformatted text of the posting
	 */
	function show_entry($username, $datum, $titel, $inhalt, $entryid, $jumpid, $owner_id, $raw_title, $raw_description, $fav = false, $last = false, $highlight = false) {
		global $_REQUEST;

		$template =& $this->template_factory->open($this->output_format . '/posting');

		$tmpl_inhalt = '';
		$tmpl_icons = '';
		$tmpl_buttons = '';


		// the posting itself
		// if this posting is selected to edit, show edit fields
		if ($_REQUEST['subcmd'] == 'edit_posting' && $entryid == $_REQUEST['posting_id']) {
			$tmpl_inhalt .= '<input type="text" style="width: 100%;" name="posting_title" value="'. htmlReady($raw_title) .'"><br/>';
			$tmpl_inhalt .= '<br/>' . $this->show_textedit_buttons() . '<br/>';
			$tmpl_inhalt .= '<textarea id="inhalt" name="posting_data" style="width: 100%;" rows="8">'. htmlReady($raw_description) .'</textarea>';
		} else {
			if (is_array($highlight)) {
				$inhalt = $this->highlight($inhalt, $highlight);
				$titel = $this->highlight($titel, $highlight);
			}

     	//if ($titel) $tmpl_inhalt .= "<b>$titel</b><br/><br/>";
     	$tmpl_inhalt .= quotes_decode($inhalt);
		}

		// the action icons
		$tmpl_icons = array();

		// icon dor deleting a post
		if ($this->rechte && $_REQUEST['plugin_subnavi_params'] != 'last_postings') {
			$icon = '';
			$icon['link'] = PluginEngine::getLink($this, array('subcmd' => 'delete', 'entryid' => $entryid, 'jumpid' => $jumpid, 'page' => $_REQUEST['page']));
			$icon['image'] = $this->picturepath .'/icons/delete.png';
			$icon['title'] = _("Eintrag l&ouml;schen!");
			$tmpl_icons[4] = $icon;
		}

		// icon for adding / removing a post to / from the favorites
		if ($this->editable) {
			$icon = '';
			$icon['link'] = PluginEngine::getLink($this, array('subcmd' => 'fav', 'entryid' => $entryid, 'root_id' => $_REQUEST['root_id'], 'thread_id' => $_REQUEST['thread_id'], 'page' => $_REQUEST['page'], 'plugin_subnavi_params' => $_REQUEST['plugin_subnavi_params'])) .'#'. $entryid;
			if (!$fav) {
				$icon['image'] = $this->picturepath .'/icons/not_a_favorite.png';
				$icon['title'] = _("zu den Favoriten hinzuf&uuml;gen");
			} else {
				$icon['image'] = $this->picturepath .'/icons/favorite.png';
				$icon['title'] = _("aus den Favoriten entfernen");
			}
			$tmpl_icons[3] = $icon;
		}


		// the buttonbar
		if ($_REQUEST['plugin_subnavi_params'] == 'last_postings'
				|| $_REQUEST['plugin_subnavi_params'] == 'new_postings'
				|| $_REQUEST['plugin_subnavi_params'] == 'favorites') {
			$tmpl_buttons = '';
		} else {
			if ($_REQUEST['subcmd'] == 'edit_posting' && $entryid == $_REQUEST['posting_id']) {
				// posting is being edited right now
				$tmpl_buttons .= '<input type="image" '. makebutton('speichern', 'src') .'>' . "\n&nbsp;&nbsp;&nbsp;\n";
				$tmpl_buttons .= '<a href="'. PluginEngine::getLink($this, array('root_id' => $_REQUEST['root_id'], 'thread_id' => $_REQUEST['thread_id'], 'page' => $_REQUEST['page'])) .'#'. $entryid .'">';
				$tmpl_buttons .= '<img border="0" '. makebutton('abbrechen', 'src') .'></a>' . "\n";
				$tmpl_buttons .= '<input type="hidden" name="subcmd" value="do_edit_posting">';
				$tmpl_buttons .= '<input type="hidden" name="root_id" value="'. $_REQUEST['root_id'] .'">' ."\n";
				$tmpl_buttons .= '<input type="hidden" name="thread_id" value="'. $_REQUEST['thread_id'] .'">' ."\n";
				$tmpl_buttons .= '<input type="hidden" name="posting_id" value="'. $_REQUEST['posting_id'] .'">' ."\n";

			} else {
				// show icons for editing and citing
				//if ($last && ($owner_id == $GLOBALS['user']->id || $this->rechte) && (is_array($highlight) === FALSE)) {
				if (($owner_id == $GLOBALS['user']->id || $this->rechte) && (is_array($highlight) === FALSE) && $this->editable) {
					$icon = '';
					$icon['link'] = PluginEngine::getLink($this, array('subcmd' => 'edit_posting', 'root_id' => $_REQUEST['root_id'], 'thread_id' => $_REQUEST['thread_id'], 'posting_id' => $entryid, 'page' => $_REQUEST['page'])) .'#'. $entryid;
					$icon['image'] = $this->picturepath .'/icons/edit.png';
					$icon['title'] = _("Eintrag bearbeiten");
					$tmpl_icons[1] = $icon;
				}

				$icon = '';
				$icon['link'] = PluginEngine::getLink($this, array('subcmd' => 'cite_posting', 'section' => 'create_posting', 'root_id' => $_REQUEST['root_id'], 'thread_id' => $_REQUEST['thread_id'], 'posting_id' => $entryid, 'page' => $_REQUEST['page'])) .'#create_posting';
				$icon['image'] = $this->picturepath .'/icons/quote.png';
				$icon['title'] = _("Aus diesem Eintrag zitieren");
				$tmpl_icons[2] = $icon;
			}
		}

		// the user-picture of the poster
		//if ($this->avatar_class) {
		if (is_callable(array('Avatar', 'getAvatar'))) {
			$tmpl_picture = Avatar::getAvatar($owner_id)->getImageTag(Avatar::MEDIUM, get_username($owner_id));
		} else {
			if (!file_exists($GLOBALS['DYNAMIC_CONTENT_PATH'].'/user/'. $entry['owner_id'] .'.jpg')) {
				if (file_exists($GLOBALS['DYNAMIC_CONTENT_PATH'].'/user/nobody.jpg')) {   // switch for backwards-compatibility
					$tmpl_picture = '<img src="'.$GLOBALS['DYNAMIC_CONTENT_URL'].'/user/nobody.jpg" width="80" height="100" ';
				} else {
					$tmpl_picture = '<img src="'.$GLOBALS['DYNAMIC_CONTENT_URL'].'/user/nobody_medium.png" ';
				}
				$tmpl_picture .= tooltip(_("kein pers&ouml;nliches Bild vorhanden")).'>';
			} else {
				$tmpl_picture = '<img src="'.$GLOBALS['DYNAMIC_CONTENT_URL'].'/user/'. $owner_id .'.jpg" border="0" width="75" ';
				$tmpl_picture .= tooltip($GLOBALS['user']->name).'>';
			}
		}

		ksort($tmpl_icons);

		// fill values for the template and show it
		$entry = array(
			'owner_id' => $owner_id,
			'datum' => 		$datum,
			'username' => $username,
			'real_username' => get_username($owner_id),
			'userrights' => $this->translate_perm($GLOBALS['perm']->get_studip_perm($this->getId(), $owner_id)),
			'userpicture' => $tmpl_picture,
			'userpostings' => $this->count_userpostings($owner_id),
			'inhalt' => 	$tmpl_inhalt,
			'titel' =>		$titel,
			'icons' => 		$tmpl_icons,
			'buttons' =>	$tmpl_buttons,
			'plugin_path' => 	$this->getPluginPath(),
			'id' => $entryid
		);

		$template->set_attribute('entry', $entry);
		$template->set_attribute('plugin', $this);
		echo $template->render();
	}


	/* * * * * * * * * * * * * * * * * * * * * * * * * *
	 * D A T A - R E T R I E V A L - F U N C T I O N S *
	 * * * * * * * * * * * * * * * * * * * * * * * * * */

	function getThreadIdCached($topic_id) {

	}

	function _dbdataFillArray($db) {
		$ret = array();
		while ($db->next_record()) {
			$path = ForumPPEntry::getPathToPosting($db->f('topic_id'));

			$ret[] = array(
				'has_childs' => true,
				'author' => $db->f('author'),
				'topic_id' => $db->f('topic_id'),
				'thread_id' => $path['thread_id']['id'],
				'root_id' => $db->f('root_id'),
				'area_name' => $path['root_id']['name'],
				'thread_name' => $path['thread_id']['name'],
				'name' => formatReady($db->f('name')),
				'description' => formatReady(ForumPPEntry::parseEdit($db->f('description'))),
				'chdate' => $db->f('chdate'),
				'owner_id' => $db->f('user_id'),
				'raw_title' => $db->f('name'),
				'raw_description' => ForumPPEntry::killEdit($db->f('description')),
				'fav' => ($db->f('fav') == 'fav')
			);
		}

		return $ret;
	}

	function appendEntry(&$list, $post) {
			$thread_id = $this->getThreadIdCached($post['topic_id']);

			if (!$_REQUEST['root_id'] && !$_REQUEST['thread_id']) {
				$post['thread_name'] = $this->getDBData('entry_name', array('entry_id' => $thread_id));
				$post['area_name'] = $this->getDBData('entry_name', array('entry_id' => $post['root_id']));
			} else if (!$_REQUEST['thread_id']) {
				$post['thread_name'] = $this->getDBData('entry_name', array('entry_id' => $thread_id));
			}

			$list[$post['topic_id']] = array(
				'author' => $post['author'],
				'topic_id' => $post['topic_id'],
				'name' => formatReady($post['name']),
				'description' => formatReady($this->forumParseEdit($post['description'])),
				'chdate' => $post['chdate'],
				'mkdate' => $post['mkdate'],
				'owner_id' => $post['user_id'],
				'raw_title' => $post['name'],
				'raw_description' => ForumPPEntry::killEdit($post['description']),
				'area_name' => $post['area_name'],
				'thread_name' => $post['thread_name'],
				'thread_id' => $thread_id,
				'root_id' => $post['root_id']
			);

	}

	/**
	 * this functions reads postings and returns them as an array
	 * @param string $type type of retrieval, is one of get_all_for_parent / entry_name
	 * @returns mixed
	 */
	function getDBData($type = null, $data = array()) {

    if ($type == null) return FALSE;

    $ret = array();

    switch ($type) {
			case 'get_postings_for_feed':
				if ($data['id']) {
					$postings = array();

					$db = new DB_Seminar("SELECT * FROM px_topics WHERE Seminar_id = '{$data['id']}' ORDER BY chdate DESC LIMIT " . $this->FEED_POSTINGS);
					while ($db->next_record()) {
						$this->appendEntry($postings, $db->Record);
					}

					return $postings;
				}

				else if ($data['area_id']) {
					$postings = array();

					$db = new DB_Seminar("SELECT * FROM px_topics WHERE Seminar_id = '{$this->getId()} ' AND root_id = '{$data['area_id']}'ORDER BY chdate DESC LIMIT ". $this->FEED_POSTINGS);
					while ($db->next_record()) {
						$this->appendEntry($postings, $db->Record);
					}

					return $postings;
				}
				break;

			// retrieves the formatted title of one posting
			case 'entry_name':
				static $entry_name_cache;
				if (!isset($entry_name_cache[$data['entry_id']])) {
					$db = new DB_Seminar($query = "SELECT * FROM px_topics WHERE topic_id = '".$data['entry_id']."'");
					$db->next_record();
					$entry_name_cache[$data['entry_id']] = formatReady($db->f('name'));
				}
				return $entry_name_cache[$data['entry_id']];
			break;

			case 'get_child_postings':
				$db = new DB_Seminar($query = "SELECT px.* FROM px_topics as px
					WHERE px.Seminar_id =  '". $this->getId() ."' AND px.parent_id = '{$data['parent_id']}'
					ORDER BY mkdate DESC");

				$data = $this->_dbdataFillArray($db);
				return $data;
				break;

			case 'get_last_postings':
				if ($data['page'] && $data['page'] > 1) {
					$limit_start = ($data['page']-1) * $this->POSTINGS_PER_PAGE;
				} else {
					$limit_start = 0;
				}

				$db = new DB_Seminar($query = "SELECT px.*, ou.flag as fav  FROM px_topics as px
					LEFT JOIN object_user as ou ON (ou.object_id = px.topic_id AND ou.user_id = '{$GLOBALS['user']->id}')
					WHERE Seminar_id =  '". $this->getId() ."' AND parent_id != '0'
					ORDER BY mkdate DESC LIMIT $limit_start, ". $this->POSTINGS_PER_PAGE);

				return $this->_dbdataFillArray($db);
				break;

			case 'get_favorite_postings':
				if ($data['page'] && $data['page'] > 1) {
					$limit_start = ($data['page']-1) * $this->POSTINGS_PER_PAGE;
				} else {
					$limit_start = 0;
				}

				$db = new DB_Seminar("SELECT pt.*, ou.flag as fav FROM object_user as ou
					LEFT JOIN px_topics as pt ON (ou.object_id = pt.topic_id AND ou.user_id = '{$GLOBALS['user']->id}')
					WHERE ou.user_id = '". $GLOBALS['user']->id ."'
					AND ou.flag = 'fav'
					AND pt.Seminar_id = '". $this->getId() ."'
					ORDER BY mkdate DESC LIMIT $limit_start, ". $this->POSTINGS_PER_PAGE);

				return $this->_dbdataFillArray($db);
				break;

			case 'get_new_postings':
				if ($data['page'] && $data['page'] > 1) {
					$limit_start = ($data['page']-1) * $this->POSTINGS_PER_PAGE;
				} else {
					$limit_start = 0;
				}

				$db = new DB_Seminar($query = "SELECT * FROM px_topics
					WHERE Seminar_id =  '". $this->getId() ."' AND mkdate >= {$this->last_visit}
					ORDER BY mkdate DESC LIMIT $limit_start, ". $this->POSTINGS_PER_PAGE);

				return $this->_dbdataFillArray($db);
				break;

			case 'get_new_postings_count':
				$db = new DB_Seminar("SELECT COUNT(*) as c FROM px_topics
					WHERE Seminar_id =  '". $this->getId() ."' AND mkdate >= {$this->last_visit}");
				$db->next_record();
				return $db->f('c');
			 	break;

			case 'get_favorite_postings_count':
				$db = new DB_Seminar("SELECT COUNT(*) as c FROM object_user as ou
					LEFT JOIN px_topics as pt ON (ou.object_id = pt.topic_id AND ou.user_id = '{$GLOBALS['user']->id}')
					WHERE ou.user_id = '". $GLOBALS['user']->id ."'
					AND ou.flag = 'fav'
					AND pt.Seminar_id = '". $this->getId() ."'");
				$db->next_record();
				return $db->f('c');
			 	break;

			case 'get_last_postings_count':
				$db = new DB_Seminar("SELECT COUNT(*) as c FROM px_topics as px
					WHERE Seminar_id =  '". $this->getId() ."' AND parent_id != '0'");
				$db->next_record();
				return $db->f('c');
			 	break;

			case 'search_indexed':
				if ($data['page'] && $data['page'] > 1) {
					$limit_start = ($data['page']-1) * $this->POSTINGS_PER_PAGE;
				} else {
					$limit_start = 0;
				}

				$ids = array();

				$cl = new SphinxClient ();
				$cl->SetMatchMode( SPH_MATCH_EXTENDED );

				$res = $cl->Query ( $data['searchfor'], 'forum');
				if ( !$res )
				{
					die ( "ERROR: " . $cl->GetLastError() . ".\n" );
				} else
				{

					foreach ($res['matches'] as $id => $data) {
						$ids[] = $id;
						//$db->query("SELECT * FROM px_topics WHERE num = '$id'");
					}
				}

				if (sizeof($ids) > 0) {
					$query = "SELECT px.*, ou.flag as fav FROM px_topics as px
						LEFT JOIN object_user as ou ON (ou.object_id = px.topic_id AND ou.user_id = '{$GLOBALS['user']->id}')
						WHERE seminar_id = '". $this->getId() ."' AND num IN(". implode(', ', $ids) .")
						ORDER BY mkdate DESC LIMIT $limit_start, ". $this->POSTINGS_PER_PAGE;

					$query2 = "SELECT COUNT(*) as c FROM px_topics as px
						WHERE seminar_id = '". $this->getId() ."' AND num IN(". implode(', ', $ids) .")";

					$db = new DB_Seminar($query);
					$db2 = new DB_Seminar($query2);
					$db2->next_record();

					return array(
						'highlight' => $_searchfor,
						'num_postings' => $db2->f('c'),
						'postings' => $this->_dbdataFillArray($db)
					);
				} else {
					return array(
						'highlight' => $_searchfor,
						'num_postings' => 0,
						'postings' => array()
					);
				}

				break;


			/* * * * * * * * * * * * * * * * * * * * * *
			 * S T A N D A R D - F O R U M S S U C H E *
			 * * * * * * * * * * * * * * * * * * * * * */
			case 'search':
				if ($data['page'] && $data['page'] > 1) {
					$limit_start = ($data['page']-1) * $this->POSTINGS_PER_PAGE;
				} else {
					$limit_start = 0;
				}

				// parse searchstring
				$_searchfor = stripslashes($data['searchfor']);

				// if there are quoted parts, they should not be separated
				$suchmuster = '/".*"/U';
				preg_match_all($suchmuster, $_searchfor, $treffer);

				// remove the quoted parts from $_searchfor
				$_searchfor = preg_replace($suchmuster, '', $_searchfor);

				// split the searchstring $_searchfor at every space
				$_searchfor = array_merge(explode(' ', trim($_searchfor)), $treffer[0]);

				// make an SQL-statement out of the searchstring
				$search_string = array();
				foreach ($_searchfor as $key => $val) {
					if (!$val) {
						unset($_searchfor[$key]);
					} else {
						$_searchfor[$key] = str_replace('"', '', str_replace("'", '', $val));
						$val = trim(str_replace('"', '', str_replace("'", '', $val)));

						if ($_REQUEST['search_title']) $search_string[] .= "name LIKE '%$val%'";
						if ($_REQUEST['search_content']) $search_string[] .= "description LIKE '%$val%'";
						if ($_REQUEST['search_author']) $search_string[] .= "author LIKE '%$val%'";
					}
				}

				// get the postings that match
				if ($this->output_format != 'html') {
					$query = "SELECT * FROM px_topics
						WHERE seminar_id = '". $this->getId() ."' AND (". implode(' OR ', $search_string) .")
						ORDER BY mkdate DESC LIMIT $limit_start, ". $this->POSTINGS_PER_PAGE;
				} else {
					$query = "SELECT px.*, ou.flag as fav FROM px_topics as px
						LEFT JOIN object_user as ou ON (ou.object_id = px.topic_id AND ou.user_id = '{$GLOBALS['user']->id}')
						WHERE seminar_id = '". $this->getId() ."' AND (". implode(' OR ', $search_string) .")
						ORDER BY mkdate DESC LIMIT $limit_start, ". $this->POSTINGS_PER_PAGE;
				}

				$query2 = "SELECT COUNT(*) as c FROM px_topics as px
					WHERE seminar_id = '". $this->getId() ."' AND (". implode(' OR ', $search_string) .")";

				$db = new DB_Seminar($query);
				$db2 = new DB_Seminar($query2);
				$db2->next_record();

				return array(
					'highlight' => $_searchfor,
					'num_postings' => $db2->f('c'),
					'postings' => $this->_dbdataFillArray($db)
				);
				break;

			// _ENHANCED
			case 'get_categories':
				$db = new DB_Seminar("SELECT * FROM forumpp WHERE entry_type = 'category' AND seminar_id = '". $this->getId() ."' ORDER BY pos ASC");
				if ($db->num_rows() == 0) {
					return array();
				}

				$ret = array();
				while ($db->next_record()) {
					$zw = array();
					$zw['name'] = $db->f('entry_name');
					$zw['areas'] = array();
					$db2 = new DB_Seminar("SELECT * FROM forumpp
						WHERE entry_type = 'area' AND seminar_id = '". $this->getId() ."' AND entry_id = '". $db->f('entry_id') ."'
						ORDER BY pos ASC");
					while ($db2->next_record()) {
						$zw['areas'][] = $db2->f('topic_id');
					}
					$ret[$db->f('entry_id')] = $zw;
				}

				return $ret;
				break;

			default:
				echo '<pre>';
				echo "data-retrieval-method $type (". print_r($data, true) .") not found!";
				print_r(debug_backtrace());
				echo '</pre>';
				die;
				break;
    }
  }

	/* the page chooser for ar list of postings */
	function get_page_chooser_NP($num_postings) {
		$pages = ceil($num_postings / $this->POSTINGS_PER_PAGE);
		if ($pages <= 1) return;

		if ($_REQUEST['page']) $cur_page = $_REQUEST['page']; else $cur_page = 1;

		$run = true;
		$add_dots = false;

		// show additional text over thread-postings
		$ret .= "$num_postings ". _("Beitr&auml;ge") . " &bull; " . _("Seite") . " $cur_page von $pages &bull; ";

		for ($i = 1; $i <= $pages; $i++) {

			if ($pages >= 6) {
				$add_dots = false;
				// show the two first and the two last pages
				if ($cur_page == -1) {
					if (($pages - 2) >= $i && (2 < $i)) {
						$run = false;
					} else {
						$run = true;
					}

					if ($i == 3) {
						$add_dots = true;
					}
				}

				// show the first and the last page, as well as the two pages before and after
				else {
					$run= false;

					if ($cur_page < 3) {
						$start = 1;
						$end = 5;
					} else if ($cur_page > ($pages - 3)) {
						$start = $pages - 4;
						$end = $pages;
					} else {
						$start = $cur_page -2;
						$end = $cur_page + 2;
					}

					if ($start != 1 && $i == 1) {
						$run = true;
					}

					if ($start > 2 && $i == 2) $add_dots = true;

					if ($end != $pages && $i == $pages) {
						$run = true;
						if ($end < $pages - 1) $add_dots = true;
					}

					if ($i >= $start && $i <= $end) {
						$run = true;
					}
				}
			}

			if ($add_dots) {
				$ret .= ' &hellip;';
			}

			// only show pages to choose if they are meant to be shown
			if ($run) {

				if ($i > 1) $ret .= '&nbsp;';
				if ($cur_page == $i) {
					//$ret .= ' <b>'. $i .'</b>';
					$ret .= '<span class="page selected">'. $i.'</span>';
				} else {
					$ret .= '<span class="page"><a href="'. PluginEngine::getLink($this, array('plugin_subnavi_params' => $_REQUEST['plugin_subnavi_params'], 'page' => $i, 'searchfor' => $_REQUEST['searchfor'])) .'">'. $i .'</a></span>';
				}
			}
		}

		return $ret;
	}

	function pages($num_entries, $link_params) {
		$pages = ceil($num_entries / $this->POSTINGS_PER_PAGE);
		if (!$_REQUEST['page']) {
			$cur_page = 1;
		} else {
			$cur_page = $_REQUEST['page'];
		}

		$link_params['page'] = $cur_page;

		$run = true;
		$add_dots = false;

		for ($i = 1; $i <= $pages; $i++) {

			if ($pages >= 6) {
				$add_dots = false;
				// show the two first and the two last pages
				if ($cur_page == -1) {
					if (($pages - 2) >= $i && (2 < $i)) {
						$run = false;
					} else {
						$run = true;
					}

					if ($i == 3) {
						$add_dots = true;
					}
				}

				// show the first and the last page, as well as the two pages before and after
				else {
					$run= false;

					if ($cur_page < 3) {
						$start = 1;
						$end = 5;
					} else if ($cur_page > ($pages - 3)) {
						$start = $pages - 4;
						$end = $pages;
					} else {
						$start = $cur_page -2;
						$end = $cur_page + 2;
					}

					if ($start != 1 && $i == 1) {
						$run = true;
					}

					if ($start > 2 && $i == 2) $add_dots = true;

					if ($end != $pages && $i == $pages) {
						$run = true;
						if ($end < $pages - 1) $add_dots = true;
					}

					if ($i >= $start && $i <= $end) {
						$run = true;
					}
				}
			}

			if ($add_dots) {
				$ret .= '&nbsp;&hellip;';
			}

			// only show pages to choose if they are meant to be shown
			if ($run) {
				if ($i > 1) $ret .= '&nbsp;';
				if ($cur_page == $i) {
					//$ret .= ' <b>'. $i .'</b>';
					$ret .= '<span class="page selected">'. $i.'</span>';
				} else {
					$link_params['page'] = $i;
					$ret .= '<span class="page"><a href="'. PluginEngine::getLink($this, $link_params) .'" '. tooltip(_("Gehe zu Seite")." $i") .'>'. $i .'</a></span>';
				}
			}
		}

		return $ret;

	}

	/*
	 * the page chooser for the thread-overview */
	function get_page_chooser($area_id, $thread_id, $show_text = true) {
		$num_postings = ForumPPEntry::countPostings($thread_id);

		$pages = ceil($num_postings / $this->POSTINGS_PER_PAGE);
		if ($pages == 1) return;

		if ($show_text) {
			// show additional text over thread-postings
			if ($_REQUEST['page']) $cur_page = $_REQUEST['page']; else $cur_page = 1;
			$ret .= $num_postings .' '. _("Beitr&auml;ge") .' &bull; '. _("Seite") .' '. $cur_page .' von '. (($pages) ? $pages : 1) .' &bull; ';
		} else {
			// page icon in thread-overview
			$info = _("Seite ausw&auml;hlen");
			$ret .= '<img src="'. $this->picturepath .'/pages.png" align="absbottom" alt="'. $info .' title="'. $info .'">';
		}

		$run = true;
		$add_dots = false;

		for ($i = 1; $i <= $pages; $i++) {

			if ($pages >= 6) {
				$add_dots = false;
				// show the two first and the two last pages
				if ($cur_page == -1) {
					if (($pages - 2) >= $i && (2 < $i)) {
						$run = false;
					} else {
						$run = true;
					}

					if ($i == 3) {
						$add_dots = true;
					}
				}

				// show the first and the last page, as well as the two pages before and after
				else {
					$run= false;

					if ($cur_page < 3) {
						$start = 1;
						$end = 5;
					} else if ($cur_page > ($pages - 3)) {
						$start = $pages - 4;
						$end = $pages;
					} else {
						$start = $cur_page -2;
						$end = $cur_page + 2;
					}

					if ($start != 1 && $i == 1) {
						$run = true;
					}

					if ($start > 2 && $i == 2) $add_dots = true;

					if ($end != $pages && $i == $pages) {
						$run = true;
						if ($end < $pages - 1) $add_dots = true;
					}

					if ($i >= $start && $i <= $end) {
						$run = true;
					}
				}
			}

			if ($add_dots) {
				$ret .= '&nbsp;&hellip;';
			}

			// only show pages to choose if they are meant to be shown
			if ($run) {
				if ($i > 1) $ret .= '&nbsp;';
				if ($cur_page == $i) {
					//$ret .= ' <b>'. $i .'</b>';
					$ret .= '<span class="page selected">'. $i.'</span>';
				} else {
					$ret .= '<span class="page"><a href="'. PluginEngine::getLink($this, array('root_id' => $area_id, 'thread_id' => $thread_id, 'page' => $i)) .'" '. tooltip(_("Gehe zu Seite")." $i") .'>'. $i .'</a></span>';
				}
			}
		}

		return $ret;
	}

	/* * * * * * * * * * * * * * * * * * *
	 * H E L P E R - F U N C T I O N S *
	 * * * * * * * * * * * * * * * * * * */

	/**
	 * callback-function for usort, sorts by array-field sort_criteria
	 */
	function sort_threads_by_date($a, $b) {
		if ($a['sort_criteria'] == $b['sort_criteria']) return 0;

		return ($a['sort_criteria'] < $b['sort_criteria']) ? 1 : -1;
	}

	/**
	 * alternative makebutton-function, to enable use of buttons which are not included in Stud.IP
	 * @param string $name name of the button
	 * @param string $type one of full / src. full returns the button-image with button-tags surrounded
	 * @returns string
	 */
	function makebutton($name, $type = 'full') {
		$img = $this->getPluginPath() .'/buttons/'. $GLOBALS['_language_path'] .'/'. $name .'-button.png';
		switch ($type) {
			case 'src':
				return ' src="'. $img .'" ';
				break;

			case ' full':
			default:
				return '<button><img src="'. $img .'"></button>';
				break;
		}
	}

	/**
	 * add a message to the message stack. Is used to display informational- or error-messages.
	 * @param string $msg the message to be added to the stack
	 * @param string $type one of msg / info / error
	 */
	function addMessage($msg, $type) {
		$this->messages[] = $type.'§'.$msg.'§';
	}

	/**
	 * displays the messages laying on the message-stack.
	 */
	function showMessages() {
		if (!is_array($this->messages)) return;
		echo '<table>';
		foreach ($this->messages as $msg) {
			parse_msg($msg);
		}
		echo '</table>';
	}

	function show_textedit_buttons() {
		// define the possible tags
		$buttons = array (
			array('name' => '<strong>B</strong>', 'open' => '**', 'close' => '**', 'info' => 'fett'),
			array('name' => '<i>i</i>', 'open' => '%%', 'close' => '%%', 'info' => 'kursiv' ),
			array('name' => '<u>u</u>', 'open' => '__', 'close' => '__', 'info' => 'unterstrichen'),
			array('name' => '<del>u</del>', 'open' => '{-', 'close' => '-}', 'info' => 'durchgestrichen'),
			array('name' => 'Code', 'open' => '[code]', 'close' => '[/code]', 'info' => 'Programmcode'),
			array('name' => 'A+', 'open' => '++', 'close' => '++', 'info' => 'gr&ouml;ßere Schrift'),
			array('name' => 'A-', 'open' => '--', 'close' => '--', 'info' => 'kleinere Schrift')
		);

		// get all open and close tags for ease of use
		foreach ($buttons as $button) {
			$tags[] = $button['open'];
			$tags[] = $button['close'];
		}

		$ret = '<script>' . "\n";
		$ret .= 'var tags = new Array(\''. implode("' , '", $tags) . '\');' . "\n";
		$ret .= "
			var browser = navigator.userAgent.toLowerCase();
			var is_ie = ((browser.indexOf('msie') != -1) && (browser.indexOf('opera') == -1));

			function addTag(num) {
				doAddTag(tags[num], tags[num+1]);
				return false;
			}

			function doAddTag(open, close) {
				textarea = document.getElementById('inhalt');
				textarea.focus();

				if (is_ie) {
					var selection = document.selection.createRange().text;

					if (selection) {
						document.selection.createRange().text = open + selection + close;
						textarea.focus();
						return;
					}
				}

				addAroundSelected(textarea, open, close);
				textarea.focus();

				return;
			}

			function addAroundSelected(txtarea, open, close) {
				var selLength = txtarea.textLength;
				var selStart = txtarea.selectionStart;
				var selEnd = txtarea.selectionEnd;
				var scrollTop = txtarea.scrollTop;

				if (selEnd == 1 || selEnd == 2)
				{
					selEnd = selLength;
				}

				var s1 = (txtarea.value).substring(0,selStart);
				var s2 = (txtarea.value).substring(selStart, selEnd)
					var s3 = (txtarea.value).substring(selEnd, selLength);

				txtarea.value = s1 + open + s2 + close + s3;
				txtarea.selectionStart = selEnd + open.length + close.length;
				txtarea.selectionEnd = txtarea.selectionStart;
				txtarea.focus();
				txtarea.scrollTop = scrollTop;

				return;
			}
		";

		$ret .=  '</script>' . "\n";

		foreach ($buttons as $key => $button) {
			$ret .= '<button type="button" onClick="addTag('.($key*2).')" title="'. $button['info'] .'" alt="'. $button['info'] .'">';
			$ret .= $button['name'];
			$ret .= '</button>' . "\n";
		}

		return $ret;
	}

	function show_smiley_favorites() {
		require_once('lib/classes/smiley.class.php');

		if (get_config("EXTERNAL_HELP")) {
			$help_url = format_help_url("Basis.VerschiedenesFormat");
		} else {
			$help_url = "help/index.php?help_page=ix_forum6.htm";
		}

		echo '<center><div>';

		$sm = new smiley(false);
		if ($sm->read_favorite() && sizeof($sm->my_smiley) > 0) {
			foreach ($sm->my_smiley as $smile => $value) {
				echo '<img src="' . $GLOBALS['DYNAMIC_CONTENT_URL'] . '/smile/' . $smile . '.gif" ';
				echo 'style="cursor: pointer;" onClick="$(\'inhalt\').value += \' :'. $smile .':\'">&nbsp;';
			}
		}

		echo '<br/>';
		echo '<a href="show_smiley.php" target="new">'. _("Smileys") .'</a> | ';
		echo '<a href="'. $help_url .'" target="new">'. _("Formatierungshilfen") .'</a>';
		echo '<br/>';

		echo '</div></center>';
	}


	function translate_perm($perm) {
		switch($perm) {
			case 'root':
				return _("Chef im Ring");
				break;
			case 'admin':
				return _("Administrator/In");
				break;
			case 'dozent':
				return _("Dozent/In");
				break;
			case 'tutor':
				return _("Tutor/In");
				break;

			case 'autor':
				return _("Autor/In");
				break;

			default:
				return '';
				break;
		}
	}

	function get_hidden_fields($fields) {
		global $_REQUEST;

		foreach ($fields as $name) {
			if (isset($_REQUEST[$name])) {
				echo '<input type="hidden" name="'. $name .'" value="'. $_REQUEST[$name] .'">', "\n";
			}
		}
	}

	/**
	 * Counts how many entries a user has posted.
	 *
	 * This function returns the number of entries a user has poste. The function caches the results in a static variable
	 * @param $owner_id the id of the user for which shall be counted
	 * @return int the number of postings
	 */
	function count_userpostings($owner_id) {
		static $posting_counter;

		if (!$posting_counter[$owner_id]) {
			$db = new DB_Seminar("SELECT COUNT(*) as c FROM px_topics WHERE user_id = '$owner_id' AND Seminar_id = '". $this->getId() ."'");
			$db->next_record();
			$posting_counter[$owner_id] = $db->f('c');
		}

		return $posting_counter[$owner_id];
	}

	/*
	 * helper_function for highlight($text, $highlight)
	 */
	function do_highlight($text, $highlight) {
		$text = preg_replace($highlight, '####${1}####', $text);
		$text = preg_replace('/####(.*)####/U', '<span class="highlight">${1}</span>', $text);
		return $text;
	}

	/**
	 * This function highlights Text HTML-safe
	 * (tags or words in tags are not highlighted, words between tags ARE highlighted)
	 * @param string $text the text where to words shall be highlighted, may contain tags
	 * @param array $highlight an array of words to be highlighted
	 * @return string the highlighted text
	 */
	function highlight($text, $highlight) {
		$unsafe_symbols = array('/\./', '/\*/', '/\?/', '/\+/');
		$unsafe_replace = array('\\.', '\\*', '\\?', '\\+');

		foreach ($highlight as $key => $val) {
			$highlight[$key] = '/('. preg_replace($unsafe_symbols, $unsafe_replace, $val).')/i';
		}

		$data = array();
		$treffer = array();

		// split text at every tag
		$pattern = '/<[^<]*>/U';
		preg_match_all($pattern, $text, $treffer, PREG_OFFSET_CAPTURE);

		if (sizeof($treffer[0]) == 0) {
			return $this->do_highlight($text, $highlight);
		}

		$last_pos = 0;
		foreach ($treffer[0] as $taginfo) {
			$size = strlen($taginfo[0]);
			if ($taginfo[1] != 0) {
				$data[] = $this->do_highlight(substr($text, $last_pos, $taginfo[1] - $last_pos), $highlight);
			}

			$data[] = substr($text, $taginfo[1], $size);
			$last_pos = $taginfo[1] + $size;
		}

		// don't miss the last portion of a posting
		if ($last_pos < strlen($text)) {
			$data[] = substr($text, $last_pos, strlen($text) - $last_pos);
		}

		return implode('', $data);
	}


	function getInfobox($section) {
		global $_REQUEST;

		// The configuation has a different infobox
		if ($section == 'config') {
			$infobox =& $this->template_factory->open('html/infobox_config');
			$standard_infobox =& $GLOBALS['template_factory']->open('infobox/infobox_raumzeit');
			$infobox->set_attribute('standard_infobox', $standard_infobox);
			$infobox->set_attribute('picture', 'sms3.jpg');
			$infobox->set_attribute('plugin', $this);
		}

		// the default infobox for the forum
		else {
			$infobox =& $this->template_factory->open($this->output_format . '/infobox');
			$standard_infobox =& $GLOBALS['template_factory']->open('infobox/infobox_raumzeit');
			$infobox->set_attribute('standard_infobox', $standard_infobox);
			$infobox->set_attribute('picture', 'sms3.jpg');
			$infobox->set_attribute('plugin', $this);
			$infobox->set_attribute('section', $section);
			$infobox->set_attribute('_REQUEST', $_REQUEST);
		}

		return $infobox;
	}


	/* * * * * * * * * * * * * * * *
	 * M A I N - F U N C T I O N S *
	 * * * * * * * * * * * * * * * */

	function configShow($page = 'area_admin') {
		if (!$this->rechte) return;

		$admin_modules = new AdminModules();
		$bitmask = $admin_modules->getBin($this->getId());

		// Standardforum deaktivieren
		if ($_REQUEST['deactivate'] == 'deactivate') {
			$admin_modules->clearBit($bitmask, $admin_modules->registered_modules['forum']['id']);
			$admin_modules->writeBin($this->getId(), $bitmask);
		}

		// Standardforum aktivieren
		else if ($_REQUEST['activate'] == 'activate') {
			$admin_modules->setBit($bitmask, $admin_modules->registered_modules['forum']['id']);
			$admin_modules->writeBin($this->getId(), $bitmask);
		}

		$default_forum = $admin_modules->isBit($bitmask, $admin_modules->registered_modules['forum']['id']);

		if ($_REQUEST['action'])
			switch ($_REQUEST['action']) {
				case 'administrate':
					if (isset($_REQUEST['create_category'])) {
						new DB_Seminar("INSERT INTO forumpp
							(entry_id, seminar_id, entry_type, topic_id, entry_name)
							VALUES ('". md5(uniqid(rand())) ."', '". $this->getId() ."', 'category', '', '{$_REQUEST['category']}')");
					}

					if (isset($_REQUEST['add_area'])) {
						new DB_Seminar("INSERT INTO forumpp
							(entry_id, seminar_id, entry_type, topic_id, entry_name)
							VALUES ('{$_REQUEST['add_area']}', '". $this->getId() ."', 'area', '". $_REQUEST['cat_'.$_REQUEST['add_area']] ."', '')");
					}
					break;

				case 'delete_area':
					new DB_Seminar($query = "DELETE FROM forumpp WHERE entry_id = '{$_REQUEST['category_id']}' AND topic_id = '{$_REQUEST['area_id']}' AND seminar_id = '". $this->getId() ."'");
					break;

				case 'delete_category':
					new DB_Seminar($query = "DELETE FROM forumpp WHERE entry_id = '{$_REQUEST['category_id']}' AND seminar_id = '". $this->getId() ."'");
					break;
			}


		$categories = $this->getDBData('get_categories');
		$areas = ForumPPEntry::getFlatList('areas', '0', $this->getId());

		$infobox = $this->getInfobox('config');
		$infobox->set_attribute('default_forum', $default_forum);
    $plugin = $this;
		$picturepath = $this->picturepath;

		if ($page == 'thread_admin') {
			$template =& $this->template_factory->open('html/config_threads.php');
		} else {
			$template =& $this->template_factory->open('html/config.php');
		}

		$template->set_layout('html/layout');
    $template->set_attributes(compact('areas', 'categories', 'infobox', 'default_forum', 'plugin', 'picturepath'));

		echo $template->render();
	}

	function searchShow() {
		global $_REQUEST;

		$infobox = $this->getInfobox('search');

		if (!$_REQUEST['searchfor']) {
			$info_message = _("Bitte geben Sie einen oder mehrere Suchbegriffe ein!");
		} else {
			$search = $this->getDBData($_REQUEST['backend'], array('searchfor' => $_REQUEST['searchfor'], 'page' => $_REQUEST['page']));
			$postings = $search['postings'];
			$num_postings = $search['num_postings'];

			if ($num_postings == 0) $info_message = _("Es wurden keine mit ihrer Suchanfrage &uuml;bereinstimmenden Beitr&auml;ge gefunden!");
		}

    $plugin = $this;
		$template =& $this->template_factory->open($this->output_format . '/show_posting_list');
		$template->set_layout($this->output_format . '/layout');
    $template->set_attributes(compact('postings', 'num_postings', 'plugin', 'infobox', 'info_message'));
		$template->set_attribute('highlight', $search['highlight']);

		echo $template->render();
	}

	function favoritesShow() {
		$infobox = $this->getInfobox('favorites');

		$postings = $this->getDBData('get_favorite_postings', array('page' => $_REQUEST['page']));
		$num_postings = $this->getDBData('get_favorite_postings_count');

		if ($num_postings == 0) {
			$info_message = _("Sie haben bisher keine Beitr&auml;ge als Favoriten eingetragen!");
		}

    $plugin = $this;
		$template =& $this->template_factory->open($this->output_format . '/show_posting_list');
		$template->set_layout($this->output_format . '/layout');
    $template->set_attributes(compact('postings', 'num_postings', 'plugin', 'infobox', 'info_message'));
		echo $template->render();
	}

	function newPostingsShow() {
		$infobox = $this->getInfobox('new_postings');

		$postings = $this->getDBData('get_new_postings', array('page' => $_REQUEST['page']));
		$num_postings = $this->getDBData('get_new_postings_count');

		if ($num_postings == 0) {
			$info_message = _("Seit ihrem letzten Besuch wurden keine neuen Beitr&auml;ge erstellt!");
		}

    $plugin = $this;
		$template =& $this->template_factory->open($this->output_format . '/show_posting_list');
		$template->set_layout($this->output_format . '/layout');
    $template->set_attributes(compact('postings', 'num_postings', 'plugin', 'infobox', 'info_message'));
		echo $template->render();

	}

	function lastPostingsShow() {
		$infobox = $this->getInfobox('last_postings');

		$postings = $this->getDBData('get_last_postings', array('page' => $_REQUEST['page']));
		$num_postings = $this->getDBData('get_last_postings_count');

		$plugin = $this;
		$template =& $this->template_factory->open($this->output_format . '/show_posting_list');
		$template->set_layout($this->output_format . '/layout');
		$template->set_attributes(compact('postings', 'num_postings', 'plugin', 'infobox'));
		echo $template->render();
	}

	/*
	 * This function does a garbage collect on the table forumpp. It is only needed,
	 * until deleted areas delete their entry in here for them self.
	 */
	function gc() {
		$stmt = DBManager::get()->prepare("SELECT DISTINCT forumpp.entry_id FROM forumpp LEFT JOIN px_topics ON (forumpp.topic_id = px_topics.topic_id) WHERE px_topics.topic_id IS NULL AND forumpp.seminar_id = ? AND forumpp.topic_id IS NOT NULL AND forumpp.topic_id != ''");
		$stmt->execute(array($this->getId()));

		$ids = array();

		while ($data = $stmt->fetch()) {
			$ids[] = $data['entry_id'];
		}

		DBManager::get()->query("DELETE FROM forumpp WHERE entry_id IN ('". implode("', '", $ids) ."')");
	}


	function forumShow() {
		global $_REQUEST;
		$this->gc();

		// show messages if any
		$this->showMessages();

		$aktionen = array();

		$infobox =& $this->template_factory->open($this->output_format . '/infobox');
		$standard_infobox =& $GLOBALS['template_factory']->open('infobox/infobox_raumzeit');
		$infobox->set_attribute('standard_infobox', $standard_infobox);
		$infobox->set_attribute('picture', 'sms3.jpg');
		$infobox->set_attribute('plugin', $this);


		// postings
		if (isset($_REQUEST['thread_id'])) {
			$area_name = $this->getDBData('entry_name', array('entry_id' => $_REQUEST['root_id']));
			$thread_name = $this->getDBData('entry_name', array('entry_id' => $_REQUEST['thread_id']));
			$postings = ForumPPEntry::getFlatList('postings',$_REQUEST['thread_id'], $this->getId());
			$postings_count = ForumPPEntry::countPostings($_REQUEST['thread_id']);
			$plugin = $this;
			$menubar = $this->show_menubar('thread', $area_name, $thread_name);

			if ($GLOBALS['section'] != 'create_posting' && $this->writable) {
				$answer_link = PluginEngine::getLink($this, array('section' => 'create_posting', 'thread_id' => $_REQUEST['thread_id'], 'root_id' => $_REQUEST['root_id'], 'page' => $_REQUEST['page'], 'time' => time()));
			}

			$infobox->set_attribute('section', 'postings');
			$infobox->set_attribute('area_name', $area_name);
			$infobox->set_attribute('thread_name', $thread_name);

			$template =& $this->template_factory->open($this->output_format . '/show_postings');
			$template->set_layout($this->output_format . '/layout');
			$template->set_attributes(compact('area_name', 'thread_name', 'postings', 'postings_count', 'plugin', 'infobox', 'standard_infobox', 'menubar', 'answer_link'));
			echo $template->render();
		}


		// threads
		else if (isset($_REQUEST['root_id'])) {
			$area_name = $this->getDBData('entry_name', array('entry_id' => $_REQUEST['root_id']));
			$threads = ForumPPEntry::getFlatList('threads', $_REQUEST['root_id'], $this->getId());
			$num_threads = ForumPPEntry::countChilds($_REQUEST['root_id']);
			$plugin = $this;
			$menubar = $this->show_menubar('area', $area_name);

			if ($this->rechte && $this->writable) {
				$aktionen[] = array(
				'name' => 'Bereich l&ouml;schen',
				'link' => PluginEngine::getLink($this, array('subcmd' => 'delete_area', 'area_id' => $_REQUEST['root_id']))
				);
			}

			if ($this->writable) {
				$aktionen[] = array(
				'name' => 'neues Thema',
				'link' => PluginEngine::getLink($this, array('section' => 'create_thread', 'root_id' => $_REQUEST['root_id'])).'#create_thread',
				'anchor' => 'create_thread'
				);
			}

			$infobox->set_attribute('section', 'threads');
			$infobox->set_attribute('area_name', $area_name);
			$infobox->set_attribute('aktionen', $aktionen);

			$template =& $this->template_factory->open($this->output_format . '/show_threads');
			$template->set_layout($this->output_format . '/layout');
			$template->set_attributes(compact('area_name', 'threads', 'plugin', 'infobox', 'standard_infobox', 'menubar', 'num_threads'));
			echo $template->render();
		}


		// areas
		else {
			$areas = ForumPPEntry::getFlatList('areas', '0', $this->getId());
			$plugin = $this;
			$menubar = $this->show_menubar();

			if ($this->_ENHANCED) {
				$categories = $this->getDBData('get_categories');
				foreach($categories as $cat_id => $cat) {
					$new_areas = array();
					foreach ($cat['areas'] as $id) {
						$new_areas[] = $areas[$id];
						unset($areas[$id]);
					}
					if (sizeof($new_areas) == 0) {
						unset($categories[$cat_id]);
					} else {
						$categories[$cat_id]['areas'] = $new_areas;
					}
				}

				// All ares which are not assigned to a predefined category are collected now
				$unconnected['areas'] = $areas;
				$unconnected['name'] = 'Keiner Kategorie zugeordnet';
				$categories[] = $unconnected;

			} else {
				$categories = array (
					array (
						'name' => 'Allgemeine Kategorie',
						'areas' => $areas
					)
				);
			}


			$show_area_edit = false;
			$edit_area = false;

			if ($this->writable && $this->rechte) {
				$aktionen[] = array(
					'name' => 'Bereich erstellen',
					'link' => PluginEngine::getLink($this, array('section' => 'create_area')),
					'anchor' => 'create_area'
				);

				if ($_REQUEST['subcmd'] == 'edit_area') {
					$edit_area = $_REQUEST['area_id'];
				}
				$show_area_edit = true;
			}

			$infobox->set_attribute('section', 'areas');
			$infobox->set_attribute('aktionen', $aktionen);

			$template =& $this->template_factory->open($this->output_format . '/show_areas');
			$template->set_layout($this->output_format . '/layout');
			$template->set_attributes(compact('categories', 'plugin', 'infobox', 'standard_infobox', 'menubar', 'show_area_edit', 'edit_area'));
			echo $template->render();
		}

		/*
		require_once('lib/raumzeit/QueryMeasure.class.php');
		if ($GLOBALS['query_measure']) {
			echo $GLOBALS['query_measure']->showDataCompact();
		}
		*/
	}

}
