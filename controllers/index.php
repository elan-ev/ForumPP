<?php

/*
 * Copyright (C) 2011 - Till Glöggler     <tgloeggl@uos.de>
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */


/**
 * @author    tgloeggl@uos.de
 * @copyright (c) Authors
 */

//require_once ( "sphinxapi.php" );
//require_once('db/ForumPPDB.php');
//require_once('ForumPPTraversal.class.php');
// require_once('models/ForumPPEntry.class.php');
require_once 'app/controllers/studip_controller.php';
require_once 'lib/classes/AdminModules.class.php';
require_once 'lib/classes/Config.class.php';
require_once $this->trails_root .'/models/ForumPPEntry.class.php';
require_once $this->trails_root .'/models/ForumPPHelpers.class.php';
require_once $this->trails_root .'/models/ForumPPCat.class.php';

/*
if (!defined('FEEDCREATOR_VERSION')) {
    require_once( dirname(__FILE__) . '/vendor/feedcreator/feedcreator.class.php');
}
 *
 */

class IndexController extends StudipController {

    var $THREAD_PREVIEW_LENGTH = 100;
    var $POSTINGS_PER_PAGE = 10;
    var $FEED_POSTINGS = 10;
    var $OUTPUT_FORMATS = array('html' => 'html', 'feed' => 'feed');
    var $AVAILABLE_DESIGNS = array('studip', 'web20');
    var $FEED_FORMATS = array(
        'RSS0.91' => 'application/rss+xml',
        /* 'RSS1.0'  => 'application/xml',
          'RSS2.0'  => 'application/xml',
          'ATOM0.3' => 'application/atom+xml', */
        'ATOM1.0' => 'application/atom+xml'
    );

    var $rechte = false;
    var $lastlogin = 0;
    var $writable = false;
    var $editable = false;
    /**
     * defines the chosen output format, one of OUTPUT_FORMATS
     */
    var $output_format = 'html';

    /* * * * * * * * * * * * * * * * * * * * * * * * * */
    /*  V   I   E   W   -   A   C   T   I   O   N   S  */
    /* * * * * * * * * * * * * * * * * * * * * * * * * */

    function index_action($topic_id = null, $page = null)
    {
        $nav = Navigation::getItem('course/forum');
        $nav->setImage('icons/16/black/forum.png');
        Navigation::activateItem('course/forum/index');

        // check, if the root entry is present
        ForumPPEntry::checkRootEntry($this->getId());

        /* * * * * * * * * * * * * * * * * * *
         * V A R I A B L E N   F U E L L E N *
         * * * * * * * * * * * * * * * * * * */

        $this->has_perms = $GLOBALS['perm']->have_studip_perm('tutor', $this->getId()) ? true : false;
        $this->section = 'forum';

        if ($this->flash['new_entry']) {
            if (!$topic_id) {
                $this->has_rights = $this->rechte;
            } else {
                $this->has_rights = $this->writable;
            }
        }

        $this->topic_id     = $topic_id ? $topic_id : $this->getId();
        $this->constraint   = ForumPPEntry::getConstraints($this->topic_id);

        // set page to which we shall jump
        if ($page) {
            ForumPPHelpers::setPage($page);
        }

        // we do not crawl deeper than level 2, we show a page chooser instead
        if ($this->constraint['depth'] > 2) {
            ForumPPHelpers::setPage(ForumPPEntry::getPostingPage($this->topic_id));

            $path               = ForumPPEntry::getPathToPosting($this->topic_id);
            $this->child_topic  = $this->topic_id;
            $this->topic_id     = $path[2]['id'];
            $this->constraint   = ForumPPEntry::getConstraints($this->topic_id);
        }


        /* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
         * B E R E I C H E / T H R E A D S / P O S T I N G S   L A D E N *
         * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

        // TODO: Kategorien berücksichtigen
        if ($this->constraint['depth'] > 1) {   // POSTINGS
            $list = ForumPPEntry::getList('postings', $this->topic_id);
            if (!empty($list['list'])) {
                $this->postings          = $list['list'];
                $this->number_of_entries = $list['count'];
            }
        } else {
            if ($this->constraint['depth'] == 0) {  // BEREICHE
                $list = ForumPPEntry::getList('area', $this->topic_id);
            } else {
                $list = ForumPPEntry::getList('list', $this->topic_id);
            }

            if (!empty($list['list'])) {
                if ($this->constraint['depth'] == 0) {  // BEREICHE
                    $new_list = array();
                    foreach (ForumPPCat::getList($this->getId()) as $category) {
                        $new_list[$category['entry_name']][$category['topic_id']] = $list['list'][$category['topic_id']];
                        unset($list['list'][$category['topic_id']]);
                    }

                    if (!empty($list['list'])) {
                        $new_list['Allgemein'] = $list['list'];
                    }

                    $this->list = $new_list;
                } else if ($this->constraint['depth'] == 1) {   // THREADS
                    $this->list = array($list['list']);
                }
                $this->number_of_entries = $list['count'];
            }
        }


        /* * * * * * * * * * * * * * * * * * * * * * * * * * * * *
         * A K T I O N S L I N K S   I N   D E R   I N F O B O X *
         * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

        if ($this->constraint['depth'] == 0) {
            if ($this->writable && $this->rechte) {
                $this->aktionen[] = array(
                    'title' => _('Neuen Bereich erstellen'),
                    'link'  => PluginEngine::getLink('forumpp/index/new_entry/'. $this->topic_id)
                );
            }
        } else if ($this->constraint['depth'] == 1) {
            if ($this->writable) {
                $this->aktionen[] = array(
                    'title' => _('Neues Thema erstellen'),
                    'link'  => PluginEngine::getLink('forumpp/index/new_entry/'. $this->topic_id)
                );
            }
        } else if ($this->constraint['depth'] > 1) {
            if ($this->writable) {
                $this->aktionen[] = array(
                    'title' => _('Neuen Beitrag erstellen'),
                    'link'  => PluginEngine::getLink('forumpp/index/new_entry/'. $this->topic_id)
                );
            }
        }
    }

    function latest_action()
    {
        $nav = Navigation::getItem('course/forum');
        $nav->setImage('icons/16/black/forum.png');
        Navigation::activateItem('course/forum/index');

        $this->section = 'latest';

        $this->topic_id = $this->getId();
        $this->constraint = ForumPPEntry::getConstraints($this->topic_id);

        $list = ForumPPEntry::getList('latest', $this->topic_id);
        $this->postings          = $list['list'];
        $this->number_of_entries = $list['count'];
        $this->show_full_path    = true;

        // set default layout
        $layout = $GLOBALS['template_factory']->open('layouts/base');
        $this->set_layout($layout);

        if (empty($this->postings)) {
            $this->no_entries = true;
        }

        $this->render_action('index');
    }

    function newest_action()
    {
        $nav = Navigation::getItem('course/forum');
        $nav->setImage('icons/16/black/forum.png');
        Navigation::activateItem('course/forum/index');

        $this->section = 'newest';

        $this->topic_id = $this->getId();
        $this->constraint = ForumPPEntry::getConstraints($this->topic_id);
        $list = ForumPPEntry::getList('newest', $this->topic_id);
        $this->postings          = $list['list'];
        $this->number_of_entries = $list['count'];
        $this->show_full_path    = true;

        // set default layout
        $layout = $GLOBALS['template_factory']->open('layouts/base');
        $this->set_layout($layout);

        if (empty($this->postings)) {
            $this->no_entries = true;
        }

        $this->render_action('index');
    }

    function favorites_action()
    {
        $nav = Navigation::getItem('course/forum');
        $nav->setImage('icons/16/black/forum.png');
        Navigation::activateItem('course/forum/index');

        $this->section = 'favorites';

        $this->topic_id = $this->getId();
        $this->constraint = ForumPPEntry::getConstraints($this->topic_id);
        $list = ForumPPEntry::getList('favorites', $this->topic_id);
        $this->postings          = $list['list'];
        $this->number_of_entries = $list['count'];
        $this->show_full_path    = true;

        // set default layout
        $layout = $GLOBALS['template_factory']->open('layouts/base');
        $this->set_layout($layout);

        if (empty($this->postings)) {
            $this->no_entries = true;
        }

        $this->render_action('index');
    }

    function search_action()
    {
        $nav = Navigation::getItem('course/forum');
        $nav->setImage('icons/16/black/forum.png');
        Navigation::activateItem('course/forum/index');

        $this->section = 'search';

        $this->topic_id = $this->getId();
        $this->constraint = ForumPPEntry::getConstraints($this->topic_id);
        $list = ForumPPEntry::getList('search', $this->getId());
        $this->postings          = $list['list'];
        $this->number_of_entries = $list['count'];
        $this->highlight         = $list['highlight'];
        $this->show_full_path    = true;

        if (empty($this->postings)) {
            $this->no_search_results = true;
        }

        // set default layout
        $layout = $GLOBALS['template_factory']->open('layouts/base');
        $this->set_layout($layout);

        $this->render_action('index');
    }


    /* * * * * * * * * * * * * * * * * * * * * * * * * */
    /* * * *   P O S T I N G - A C T I O N S     * * * */
    /* * * * * * * * * * * * * * * * * * * * * * * * * */

    function new_entry_action($topic_id)
    {
        $this->flash['new_entry'] = true;
        $this->redirect(PluginEngine::getLink('forumpp/index/index/'. $topic_id));
    }

    function add_entry_action($topic_id)
    {
        if (!Request::option('parent')) {
            throw new Exception('missing seminar_id/topic_id while adding a new entry!');
        }

        $new_id = md5(uniqid(rand()));

        ForumPPEntry::insert(array(
            'topic_id'    => $new_id,
            'seminar_id'  => $this->getId(),
            'user_id'     => $GLOBALS['user']->id,
            'name'        => Request::get('name', _('Kein Titel')),
            'content'     => Request::get('content', _('Keine Beschreibung')),
            'author'      => get_fullname($GLOBALS['user']->id),
            'author_host' => getenv('REMOTE_ADDR')
        ), Request::option('parent'));

        $this->redirect(PluginEngine::getLink('forumpp/index/index/' . $topic_id .'#'. $new_id));
    }

    function delete_entry_action($topic_id) {
        $path = ForumPPEntry::getPathToPosting($topic_id);
        array_pop($path);
        $parent = array_pop($path);
        ForumPPEntry::delete($topic_id);

        $this->flash['message'] = 'Der Eintrag wurde gelöscht!';

        $this->redirect(PluginEngine::getLink('forumpp/index/index/' . $parent['id']));
    }

    function edit_entry_action($topic_id) {
        if (ForumPPEntry::hasEditPerms($topic_id)) {
            $this->flash['edit_entry'] = $topic_id;
        }

        $this->redirect(PluginEngine::getLink('forumpp/index/index/' . $topic_id .'#'. $topic_id));
    }

    function update_entry_action($topic_id) {
        if (ForumPPEntry::hasEditPerms($topic_id)) {
            ForumPPEntry::update($topic_id,
                Request::get('name', _('Kein Titel')),
                Request::get('content', _('Keine Beschreibung'))
            );
        }

        $this->redirect(PluginEngine::getLink('forumpp/index/index/' . $topic_id .'#'. $topic_id));
    }

    function cite_action($topic_id)
    {
        if ($entry = ForumPPEntry::getEntry($topic_id)) {
            $content  = htmlReady(quotes_encode(ForumPPEntry::killEdit($entry['content']), $entry['author']));
            $content .= "\n\n";

            $this->flash['new_entry'] = true;
            $this->flash['new_entry_content'] = $content;
            $this->flash['new_entry_title'] = _('Re:') . ' ' . $entry['name'];
        }

        $this->redirect(PluginEngine::getLink('forumpp/index/index/' . $topic_id));
    }

    function switch_favorite_action($topic_id)
    {
        object_switch_fav($topic_id);
        $this->redirect(PluginEngine::getLink('forumpp/index/index/' . $topic_id .'#'. $topic_id));
    }

    function goto_page_action($topic_id, $page) {
        $this->redirect(PluginEngine::getLink('forumpp/index/index/' . $topic_id .'/'. $page .'#'. $topic_id));
    }


    /* * * * * * * * * * * * * * * * * * * * * * * * * */
    /* * * *     C O N F I G - A C T I O N S     * * * */
    /* * * * * * * * * * * * * * * * * * * * * * * * * */

    function config_areas_action() {
        $nav = Navigation::getItem('course/forum');
        $nav->setImage('icons/16/black/forum.png');
        Navigation::activateItem('course/forum/config_areas');

        $areas = ForumPPEntry::getList('area', $this->getId());

        foreach (ForumPPCat::getList($this->getId(), false) as $category) {
            $new_list[$category['entry_name']]['cat'] = $category;
            if ($areas['list'][$category['topic_id']]) {
                $new_list[$category['entry_name']]['areas'][] = $areas['list'][$category['topic_id']];
                unset($areas['list'][$category['topic_id']]);
            }
        }

        $this->categories = $new_list;
        $this->areas      = $areas['list'];
    }

    function add_category_action() {
        ForumPPCat::add($this->getId(), Request::get('category'));

        $this->redirect(PluginEngine::getLink('forumpp/index/config_areas'));
    }
    
    function add_areas_action() {
        if (!$this->rechte) {
            return;
        }

        foreach (Request::getArray('areas') as $area_id) {
            ForumPPCat::addArea(Request::option('cat_id'), $area_id);
        }

        $this->redirect(PluginEngine::getLink('forumpp/index/config_areas'));
    }

    function remove_area_action($area_id) {
        if (!$this->rechte) {
            return;
        }
        
        ForumPPCat::removeArea($area_id);
        $this->redirect(PluginEngine::getLink('forumpp/index/config_areas'));
    }
    
    function remove_category_action($category_id) {
          if (!$this->rechte) {
            return;
        }
        
        ForumPPCat::remove($category_id);
        $this->redirect(PluginEngine::getLink('forumpp/index/config_areas'));      
    }

    function edit_area_action() { // #TODO
        return;
        if (!$this->rechte)
            return;

        $stmt = DBManager::get()->prepare("UPDATE forumpp SET entry_name = ?
            WHERE entry_id = ?");
        $stmt->execute(array($_REQUEST['new_name'], $_REQUEST['cat_id']));
    }

    function savecats_action() {
        if (!$this->rechte) {
            return;
        }

        $pos = 0;
        foreach (Request::getArray('categories') as $category_id) {
            ForumPPCat::setPosition($category_id, $pos);
            $pos++;
        }
        
        $this->render_nothing();
    }

    function saveareas_action() {
        if (!$this->rechte) {
            return;
        }

        $pos = 0;
        foreach (Request::getArray('areas') as $area_id) {
            ForumPPCat::setAreaPosition($area_id, $pos);
            $pos++;
        }
        
        $this->render_nothing();
    }
    
    /* * * * * * * * * * * * * * * * * * * * * * * * * */
    /* * * * * * * I M A G E   A C T I O N * * * * * * */
    /* * * * * * * * * * * * * * * * * * * * * * * * * */

    function image_action($image) {
        switch ($image) {
            case 'quote':
                $data = file_get_contents(realpath(dirname(__FILE__) . '/../img/icons/quote.png'));
                break;
        }

        header('Content-Type: image/png');
        $this->render_text($data);
    }

    /* * * * * * * * * * * * * * * * * * * * * * * * * */
    /* * * * * H E L P E R   F U N C T I O N S * * * * */
    /* * * * * * * * * * * * * * * * * * * * * * * * * */
    function getId()
    {
        if ($SessSemName[1])     return $SessSemName[1];
        if (Request::get('cid')) return Request::get('cid');

        return false;
    }

    /**
     * Common code for all actions: set default layout and page title.
     */
    function before_filter(&$action, &$args)
    {
        $this->validate_args($args, array('option', 'int'));

        parent::before_filter($action, $args);

        $this->flash = Trails_Flash::instance();

        // set default layout
        $layout = $GLOBALS['template_factory']->open('layouts/base');
        $this->set_layout($layout);

        PageLayout::setTitle(getHeaderLine($this->getId()) .' - '. _('Forum'));
        if ($this->flash['message']) {
            PageLayout::postMessage(MessageBox::success($this->flash['message']));
        }


        $this->AVAILABLE_DESIGNS = array('web20', 'studip');
        $this->picturepath = $GLOBALS['CANONICAL_RELATIVE_PATH_STUDIP'] .'/'. $this->dispatcher->trails_root . '/img';

        // we want to display the dates in german
        setlocale(LC_TIME, 'de_DE@euro', 'de_DE', 'de', 'ge');

        // the default for displaying timestamps
        $this->time_format_string = "%A %d. %B %Y, %H:%M";
        $this->time_format_string_short = "%a %d. %B %Y, %H:%M";

        $this->rechte = $GLOBALS['perm']->have_studip_perm('tutor', $this->getId());

        $this->template_factory =
            new Flexi_TemplateFactory(dirname(__FILE__) . '/../templates');

        //$this->check_token();
        $this->check_write_and_edit();

        object_set_visit($this->getId(), 'forum');
    }

    function check_write_and_edit() {
        global $SemSecLevelRead, $SemSecLevelWrite, $SemUserStatus;
        /*
         * Schreibrechte
         * 0 - freier Zugriff
         * 1 - in Stud.IP angemeldet
         * 2 - nur mit Passwort
         */

        $seminar = Seminar::getInstance($this->getId());

        // This is a separate view on rights, nobody should not be able to edit posts from other nobodys
        $this->editable = $GLOBALS['perm']->have_studip_perm('user', $this->getId());
        if ($GLOBALS['perm']->have_studip_perm('user', $this->getId())) {
            $this->writable = true;
        } else if ($seminar->write_level == 0) {
            $this->writable = true;
        }
    }

    function initialize() {
        /*
        $include_links = true;

        if ($include_links) {
            // set autodiscovery links
            $link_params = $_REQUEST;
            unset($link_params['source']);
            unset($link_params['Seminar_Session']);

            $params = array(
                'formats' => $this->FEED_FORMATS,
                'plugin' => $this,
                'link_params' => $link_params,
                'token' => $this->token
            );

            $GLOBALS['_include_additional_header'] .= $this->template_factory->render('feed/links', $params);
        }
         *
         */
    }

    function getDesigns()
    {
        $designs = array(
            'web20' => array('value' => 'web20', 'name' => 'Blue Star'),
            'studip' => array('value' => 'studip', 'name' => 'Safir&eacute; (Stud.IP)')
        );

        foreach ($this->AVAILABLE_DESIGNS as $design) {
            $ret[] = $designs[$design];
        }

        return $ret;
    }

    function setDesign($design)
    {
        $_SESSION['forumpp_template'][$this->getId()] = $design;
    }

    function getDesign()
    {
        if (in_array($_SESSION['forumpp_template'][$this->getId()], $this->AVAILABLE_DESIGNS) === false) {
            $_SESSION['forumpp_template'][$this->getId()] = $this->AVAILABLE_DESIGNS[0];
        }
        return $_SESSION['forumpp_template'][$this->getId()];
    }

    function css_action()
    {
        if (!$this->getDesign()) {
            $this->setDesign('web20');
        }

        if ($this->getDesign() == 'studip') {
            $template_before = $this->template_factory->open('css/web20.css.php');
            $template_before->set_attribute('picturepath', $this->picturepath);

            $template = $this->template_factory->open('css/studip.css.php');
            $template->set_attribute('picturepath', $GLOBALS['ASSETS_URL'] . '/images');
        } else {
            $template = $this->template_factory->open('css/' . $this->getDesign() . '.css.php');
            $template->set_attribute('picturepath', $this->picturepath);
        }

        // this hack is necessary to disable the standard Stud.IP layout
        ob_end_clean();

        date_default_timezone_set('CET');
        $expires = date(DATE_RFC822, time() + (24 * 60 * 60));  // expires after one day
        $today = date(DATE_RFC822);
        header('Date: ' . $today);
        header('Expires: ' . $expires);
        header('Cache-Control: public');
        header('Content-Type: text/css');

        if (isset($template_before)) {
            echo $template_before->render();
        }
        echo $template->render();
        ob_start('discard_buffer');
        die;
    }

    function feed_action()
    {
        // this hack is necessary to disable the standard Stud.IP layout
        ob_end_clean();

        if ($_REQUEST['token'] != $this->token)
            die;

        header('Content-Type: ' . $this->FEED_FORMATS[Request::get('format')]);
        // $this->last_visit = time();
        $this->output_format = 'feed';
        $this->POSTINGS_PER_PAGE = $this->FEED_POSTINGS;

        $this->loadView();
    }

    /*
     * AJAX Backend-Actions
     */

    /*
     * this function changes the parent node of a child node, correcting the root_id
     */

    /*
    function changeParent_action() {
        ob_end_clean();
        if (!$this->rechte)
            return;

        $new_parent = $_REQUEST['new_parent'];
        $topic_id = $_REQUEST['topic_id'];

        // find out the root_id for the new node
        $stmt = DBManager::get()->prepare("SELECT * FROM forumpp_entries WHERE topic_id = ?");
        $stmt->execute(array($new_parent));
        if (!$data = $stmt->fetch(PDO::FETCH_ASSOC))
            die;
        $new_root_id = $data['root_id'];
        $new_left = $data['lft'];

        // remove the entry from the categories if becomes a root node
        if ($new_parent == '0') {
            $stmt = DBManager::get()->prepare("DELETE FROM forumpp WHERE topic_id = ? AND seminar_id = ?");
            $stmt->execute(array($topic_id, $this->getId()));
        }

        // get the left pott
        $stmt = DBManager::get()->prepare("SELECT pxb.topic_id, pxb.lft
            FROM forumpp_entries as pxa
            LEFT JOIN forumpp_entries pxb ON (pxa.parent_id = pxb.topic_id)
            WHERE pxa.topic_id = ?");
        $stmt->execute(array($topic_id));
        if (!$data = $stmt->fetch(PDO::FETCH_ASSOC))
            die;
        $old_left = $data['lft'];
        $old_parent = $data['topic_id'];

        // set the new parent and root for the submitted node
        $stmt = DBManager::get()->prepare("UPDATE forumpp_entries SET parent_id = ?, root_id = ?, chdate = ? WHERE topic_id = ?");
        $stmt->execute(array($new_parent, $new_root_id, time(), $topic_id));

        // rebuild the two sub-trees
        ForumPPTraversal::recreate($new_parent, $this->getId(), $new_left);
        if (!isset($old_parent)) {
            $old_parent = 0;
            $old_left = 0;
        }
        ForumPPTraversal::recreate($old_parent, $this->getId(), $old_left);
        ForumPPTraversal::repair_root_ids($this->getId());
    }
     *
     */

    /*
    function loadChilds_action() {
        ob_end_clean();
        if (!$this->rechte)
            return;

        if ($this->rechte) {
            //$childs = $this->getDBData('get_child_postings', array('parent_id' => $_REQUEST['area_id']));

            $childs = ForumPPEntry::getEntries(Request::get('area_id'), $this->getId(), false, false);
            echo '<ul>';
            foreach ($childs as $entry) {
                echo '<li id="area_' . $entry['topic_id'] . '">';

                if (ForumPPEntry::getEntries($entry['topic_id'], $this->getId())) {
                    // if ($entry['has_childs']) {
                    echo '<a href="javascript:loadChilds(\'' . $entry['topic_id'] . '\')" ';
                    echo 'onMouseOver="showTooltip(\'area_' . $entry['topic_id'] . '\', \'' . preg_replace(array("/'/", '/"/', '/&#039;/'), array("\\'", '&quot;', "\\'"), $entry['description']) . '\')" ';
                    echo 'onMouseOut="hideTooltip()">';
                    echo $entry['name'] . '</a>';
                } else {
                    echo '<span ';
                    echo 'onMouseOver="showTooltip(\'area_' . $entry['topic_id'] . '\', \'' . preg_replace(array("/'/", '/"/', '/&#039;/'), array("\\'", '&quot;', "\\'"), $entry['description']) . '\')" ';
                    echo 'onMouseOut="hideTooltip()">';
                    echo $entry['name'];
                    echo '</span>';
                }

                echo '&nbsp;&nbsp;';
                echo '<a href="javascript:choose(\'' . $entry['topic_id'] . '\')" title="Diskussionsstrang ausschneiden"><img id="' . $entry['topic_id'] . '" src="' . $this->picturepath . '/icons/cut.png"></a>';
                echo '&nbsp; &nbsp;';
                echo '<a href="javascript:paste(\'' . $entry['topic_id'] . '\')" title="Diskussionsstrang hier einfügen"><img src="' . $this->picturepath . '/icons/paste_plain.png"></a>';
                echo '</li>';
            }
            echo '</ul>';
        }
        die;
    }
     *
     */

    /*
     * Main action
     */

    /* * * * * * * * * * * * * * * * * * *
     * C O M M A N D - F U N C T I O N S *
     * * * * * * * * * * * * * * * * * * */

    /*
     * modifys an existing posting, setting the new title and the new description
     */

    /*
    function edit_posting() {

        $db = new DB_Seminar("SELECT * FROM forumpp_entries WHERE topic_id = '" . $_REQUEST['posting_id'] . "'");
        if ($db->next_record()) {
            if ($this->rechte || $db->f('user_id') == $GLOBALS['user']->id) {
                // add the new edit-remark
                $inhalt = ForumPPEntry::appendEdit($_REQUEST['posting_data']);

                new DB_Seminar("UPDATE forumpp_entries SET name = '" . $_REQUEST['posting_title'] . "', description = '$inhalt' WHERE topic_id = '" . $_REQUEST['posting_id'] . "'");
            }
        }
    }
     *
     */


    /* * * * * * * * * * * * * * * * * * * * * * * * * *
     * D A T A - R E T R I E V A L - F U N C T I O N S *
     * * * * * * * * * * * * * * * * * * * * * * * * * */

    /**
     * this functions reads postings and returns them as an array
     * @param string $type type of retrieval, is one of get_all_for_parent / entry_name
     * @returns mixed
     */
    /*
    function getDBData($type = null, $data = array()) {
        if ($type == null)
            return FALSE;

        $ret = array();

        switch ($type) {
            case 'search_indexed':
                if ($data['page'] && $data['page'] > 1) {
                    $limit_start = ($data['page'] - 1) * $this->POSTINGS_PER_PAGE;
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
                        $val = str_replace('"', '', str_replace("'", '', $val));

                        $search_string[] .= "name LIKE '%$val%'";
                        $search_string[] .= "description LIKE '%$val%'";
                        $search_string[] .= "author LIKE '%$val%'";
                    }
                }


                $ids = array();

                $cl = new SphinxClient ();
                $cl->SetMatchMode(SPH_MATCH_EXTENDED);

                $res = $cl->Query($data['searchfor'], 'forum');
                if (!$res) {
                    die("ERROR: " . $cl->GetLastError() . ".\n");
                } else {

                    foreach ($res['matches'] as $id => $data) {
                        $ids[] = $id;
                        //$db->query("SELECT * FROM forumpp_entries WHERE num = '$id'");
                    }
                }

                if (sizeof($ids) > 0) {
                    $query = "SELECT px.*, ou.flag as fav FROM forumpp_entries as px
                        LEFT JOIN object_user as ou ON (ou.object_id = px.topic_id AND ou.user_id = '{$GLOBALS['user']->id}')
                        WHERE seminar_id = '" . $this->getId() . "' AND num IN(" . implode(', ', $ids) . ")
                        ORDER BY mkdate DESC LIMIT $limit_start, " . $this->POSTINGS_PER_PAGE;

                    $query2 = "SELECT COUNT(*) as c FROM forumpp_entries as px
                        WHERE seminar_id = '" . $this->getId() . "' AND num IN(" . implode(', ', $ids) . ")";

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


            // * * * * * * * * * * * * * * * * * * * * * *
            // * S T A N D A R D - F O R U M S S U C H E *
            // * * * * * * * * * * * * * * * * * * * * * *
            case 'search':
                if ($data['page'] && $data['page'] > 1) {
                    $limit_start = ($data['page'] - 1) * $this->POSTINGS_PER_PAGE;
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

                        if ($_REQUEST['search_title'])
                            $search_string[] .= "name LIKE '%$val%'";
                        if ($_REQUEST['search_content'])
                            $search_string[] .= "description LIKE '%$val%'";
                        if ($_REQUEST['search_author'])
                            $search_string[] .= "author LIKE '%$val%'";
                    }
                }

                // get the postings that match
                if ($this->output_format != 'html') {
                    $query = "SELECT * FROM forumpp_entries
                        WHERE seminar_id = '" . $this->getId() . "' AND (" . implode(' OR ', $search_string) . ")
                        ORDER BY mkdate DESC LIMIT $limit_start, " . $this->POSTINGS_PER_PAGE;
                } else {
                    $query = "SELECT px.*, ou.flag as fav FROM forumpp_entries as px
                        LEFT JOIN object_user as ou ON (ou.object_id = px.topic_id AND ou.user_id = '{$GLOBALS['user']->id}')
                        WHERE seminar_id = '" . $this->getId() . "' AND (" . implode(' OR ', $search_string) . ")
                        ORDER BY mkdate DESC LIMIT $limit_start, " . $this->POSTINGS_PER_PAGE;
                }

                $query2 = "SELECT COUNT(*) as c FROM forumpp_entries as px
                    WHERE seminar_id = '" . $this->getId() . "' AND (" . implode(' OR ', $search_string) . ")";

                $db = new DB_Seminar($query);
                $db2 = new DB_Seminar($query2);
                $db2->next_record();

                return array(
                    'highlight' => $_searchfor,
                    'num_postings' => $db2->f('c'),
                    'postings' => $this->_dbdataFillArray($db)
                );
                break;
        }
    }
     * /*
     */

    /* * * * * * * * * * * * * * * * * * *
     * H E L P E R - F U N C T I O N S *
     * * * * * * * * * * * * * * * * * * */

    /**
     * alternative makebutton-function, to enable use of buttons which are not included in Stud.IP
     * @param string $name name of the button
     * @param string $type one of full / src. full returns the button-image with button-tags surrounded
     * @returns string
     */
    /*
    function makebutton($name, $type = 'full') {
        $img = $this->getPluginPath() . '/buttons/' . $GLOBALS['_language_path'] . '/' . $name . '-button.png';
        switch ($type) {
            case 'src':
                return ' src="' . $img . '" ';
                break;

            case ' full':
            default:
                return '<button><img src="' . $img . '"></button>';
                break;
        }
    }
     *
     */


    /* * * * * * * * * * * * * * * *
     * M A I N - F U N C T I O N S *
     * * * * * * * * * * * * * * * */
/*

    function configShow($page = 'area_admin') {
        if (!$this->rechte)
            return;

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
                        $stmt = DBManager::get()->prepare("INSERT INTO forumpp
                            (entry_id, seminar_id, entry_type, topic_id, entry_name)
                            VALUES (?, ?, 'category', '', ?)");
                        $stmt->execute(array(md5(uniqid(rand())), $this->getId(), $_REQUEST['category']));
                    }

                    if (isset($_REQUEST['add_area'])) {
                        new DB_Seminar("INSERT INTO forumpp
                            (entry_id, seminar_id, entry_type, topic_id, entry_name)
                            VALUES ('{$_REQUEST['add_area']}', '" . $this->getId() . "', 'area', '" . $_REQUEST['cat_' . $_REQUEST['add_area']] . "', '')");
                    }
                    break;

                case 'delete_area':
                    new DB_Seminar($query = "DELETE FROM forumpp WHERE entry_id = '{$_REQUEST['category_id']}' AND topic_id = '{$_REQUEST['area_id']}' AND seminar_id = '" . $this->getId() . "'");
                    break;

                case 'delete_category':
                    new DB_Seminar($query = "DELETE FROM forumpp WHERE entry_id = '{$_REQUEST['category_id']}' AND seminar_id = '" . $this->getId() . "'");
                    break;
            }


        $categories = $this->getDBData('get_categories');
        $areas = ForumPPEntry::getFlatList('areas_no_limit', '0', $this->getId());

        $infobox = $this->getInfobox('config');
        $infobox->set_attribute('default_forum', $default_forum);
        $plugin = $this;
        $picturepath = $this->picturepath;

        if ($page == 'thread_admin') {
            $template = & $this->template_factory->open('html/config_threads.php');
        } else {
            $template = & $this->template_factory->open('html/config.php');
        }

        $template->set_layout('html/layout');
        $template->set_attributes(compact('categories', 'areas', 'infobox', 'default_forum', 'plugin', 'picturepath'));

        echo $template->render();
    }
*/

    /*
    function searchShow() {
        global $_REQUEST;

        $infobox = $this->getInfobox('search');

        if (!$_REQUEST['searchfor']) {
            $info_message = _("Bitte geben Sie einen oder mehrere Suchbegriffe ein!");
        } else {
            $search = $this->getDBData($_REQUEST['backend'], array('searchfor' => $_REQUEST['searchfor'], 'page' => $_REQUEST['page']));
            $postings = $search['postings'];
            $num_postings = $search['num_postings'];

            if ($num_postings == 0)
                $info_message = _("Es wurden keine mit ihrer Suchanfrage &uuml;bereinstimmenden Beitr&auml;ge gefunden!");
        }

        $plugin = $this;
        $template = & $this->template_factory->open($this->output_format . '/show_posting_list');
        $template->set_layout($this->output_format . '/layout');
        $template->set_attributes(compact('postings', 'num_postings', 'plugin', 'infobox', 'info_message'));
        $template->set_attribute('highlight', $search['highlight']);

        echo $template->render();
    }
*/


    /*
    function forumShow() {
        global $_REQUEST;
        // $this->gc();

        // show messages if any
        $this->showMessages();

        $aktionen = array();

        $infobox = & $this->template_factory->open($this->output_format . '/infobox');
        $standard_infobox = & $GLOBALS['template_factory']->open('infobox/infobox_raumzeit');
        $infobox->set_attribute('standard_infobox', $standard_infobox);
        $infobox->set_attribute('picture', 'infobox/messages.jpg');
        $infobox->set_attribute('plugin', $this);


        // postings
        if (isset($_REQUEST['thread_id'])) {
            $area_name = $this->getDBData('entry_name', array('entry_id' => $_REQUEST['root_id']));
            $thread_name = $this->getDBData('entry_name', array('entry_id' => $_REQUEST['thread_id']));
            $postings = ForumPPEntry::getFlatList('postings', $_REQUEST['thread_id'], $this->getId());
            $postings_count = ForumPPEntry::countPostings($_REQUEST['thread_id']);
            $plugin = $this;
            $menubar = $this->show_menubar('thread', $area_name, $thread_name);

            if ($GLOBALS['section'] != 'create_posting' && $this->writable) {
                $answer_link = PluginEngine::getLink($this, array('section' => 'create_posting', 'thread_id' => $_REQUEST['thread_id'], 'root_id' => $_REQUEST['root_id'], 'page' => $_REQUEST['page'], 'time' => time()));
            }

            $infobox->set_attribute('section', 'postings');
            $infobox->set_attribute('area_name', $area_name);
            $infobox->set_attribute('thread_name', $thread_name);

            $template = & $this->template_factory->open($this->output_format . '/show_postings');
            $template->set_layout($this->output_format . '/layout');
            $template->set_attributes(compact('area_name', 'thread_name', 'postings', 'postings_count', 'plugin', 'infobox', 'standard_infobox', 'menubar', 'answer_link'));
            echo $template->render();
        }


        // threads
        else if (isset($_REQUEST['root_id'])) {
            $area_name = $this->getDBData('entry_name', array('entry_id' => $_REQUEST['root_id']));
            $threads = ForumPPEntry::getFlatList('threads', $_REQUEST['root_id'], $this->getId());
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
                    'link' => PluginEngine::getLink($this, array('section' => 'create_thread', 'root_id' => $_REQUEST['root_id'])) . '#create_thread',
                    'anchor' => 'create_thread'
                );
            }

            $infobox->set_attribute('section', 'threads');
            $infobox->set_attribute('area_name', $area_name);
            $infobox->set_attribute('aktionen', $aktionen);

            $template = & $this->template_factory->open($this->output_format . '/show_threads');
            $template->set_layout($this->output_format . '/layout');
            $template->set_attributes(compact('area_name', 'threads', 'plugin', 'infobox', 'standard_infobox', 'menubar'));
            echo $template->render();
        }


        // areas
        else {
            $areas = ForumPPEntry::getFlatList('list', $this->getId());
            $plugin = $this;
            $menubar = $this->show_menubar();

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

            $template = & $this->template_factory->open($this->output_format . '/show_areas');
            $template->set_layout($this->output_format . '/layout');
            $template->set_attributes(compact('areas', 'plugin', 'infobox', 'standard_infobox', 'menubar', 'show_area_edit', 'edit_area'));
            echo $template->render();
        }

        // require_once('lib/raumzeit/QueryMeasure.class.php');
        // if ($GLOBALS['query_measure']) {
        // echo $GLOBALS['query_measure']->showDataCompact();
        // }
    }
    */

}