<?php

/*
 * Copyright (C) 2011 - Till Gl�ggler     <tgloeggl@uos.de>
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
require_once $this->trails_root .'/models/ForumPPEntry.php';
require_once $this->trails_root .'/models/ForumPPHelpers.php';
require_once $this->trails_root .'/models/ForumPPCat.php';
require_once $this->trails_root .'/models/ForumPPLike.php';
require_once $this->trails_root .'/models/ForumPPVersion.php';

/*
if (!defined('FEEDCREATOR_VERSION')) {
    require_once( dirname(__FILE__) . '/vendor/feedcreator/feedcreator.class.php');
}
 *
 */

class IndexController extends StudipController
{

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
        $nav = Navigation::getItem('course/forum2');
        $nav->setImage('icons/16/black/forum.png');
        Navigation::activateItem('course/forum2/index');

        // check, if the root entry is present
        ForumPPEntry::checkRootEntry($this->getId());

        /* * * * * * * * * * * * * * * * * * *
         * V A R I A B L E N   F U E L L E N *
         * * * * * * * * * * * * * * * * * * */

        $this->has_perms = $GLOBALS['perm']->have_studip_perm('tutor', $this->getId());

        // if ($this->flash['new_entry']) {
            if (!$topic_id) {
                $this->has_rights = $this->rechte;
            } else {
                $this->has_rights = $this->writable;
            }
        // }

        $this->topic_id     = $topic_id ? $topic_id : $this->getId();
        $this->constraint   = ForumPPEntry::getConstraints($this->topic_id);

        // set the visitdate
        if ($this->constraint['depth'] > 1) { // are we watching a thread
            $this->visitdate = ForumPPVisit::set($GLOBALS['user']->id, $this->topic_id, $this->getId());
        }

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
        $areas = ForumPPEntry::getList('area', $this->getId());
        $this->areas = $areas['list'];

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

            if ($this->constraint['depth'] == 0) {  // BEREICHE
                $new_list = array();
                foreach ($categories = ForumPPCat::getList($this->getId(), false) as $category) {
                    if ($category['topic_id']) {
                        $new_list[$category['category_id']][$category['topic_id']] = $list['list'][$category['topic_id']];
                        unset($list['list'][$category['topic_id']]);
                    } else if ($this->has_perms) {
                        $new_list[$category['category_id']] = array();
                    }
                    $this->categories[$category['category_id']] = $category['entry_name'];
                }

                if (!empty($list['list'])) {
                    $new_list[$this->getId()] = $list['list'];
                }

                // put 'Allgemein' always to the end of the list
                if (isset($new_list[$this->getId()])) {
                    $allgemein = $new_list[$this->getId()];
                    unset($new_list[$this->getId()]);
                    $new_list[$this->getId()] = $allgemein;
                }

                $this->list = $new_list;
            } else if ($this->constraint['depth'] == 1) {   // THREADS
                if (!empty($list['list'])) {
                    $this->list = array($list['list']);
                }
            }
            $this->number_of_entries = $list['count'];
        }

        $this->seminar_id = $this->getId();
        $this->breadcrumb = true;
    }

    function latest_action()
    {
        $nav = Navigation::getItem('course/forum2');
        $nav->setImage('icons/16/black/forum.png');
        Navigation::activateItem('course/forum2/latest');

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
        $nav = Navigation::getItem('course/forum2');
        $nav->setImage('icons/16/black/forum.png');
        Navigation::activateItem('course/forum2/newest');

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
        $nav = Navigation::getItem('course/forum2');
        $nav->setImage('icons/16/black/forum.png');
        Navigation::activateItem('course/forum2/favorites');

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
        $nav = Navigation::getItem('course/forum2');
        $nav->setImage('icons/16/black/forum.png');
        Navigation::activateItem('course/forum2/index');

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

    /**
     * this action renders a preview of the submitted text
     */
    function preview_action() {
        if (Request::isAjax()) {
            $this->set_content_type('text/html; charset=UTF-8');
            $this->render_text(studip_utf8encode(formatReady(studip_utf8decode(Request::get('posting')))));
        } else {
            $this->render_text(formatReady(ForumPPEntry::parseEdit(Request::get('posting'))));
        }
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

        $this->redirect(PluginEngine::getLink('forumpp/index/index/' . $new_id .'#'. $new_id));
    }

    function add_area_action($category_id)
    {
        $new_id = md5(uniqid(rand()));

        ForumPPEntry::insert(array(
            'topic_id'    => $new_id,
            'seminar_id'  => $this->getId(),
            'user_id'     => $GLOBALS['user']->id,
            'name'        => Request::get('name', _('Kein Titel')),
            'content'     => '',
            'author'      => get_fullname($GLOBALS['user']->id),
            'author_host' => getenv('REMOTE_ADDR')
        ), $this->getId());

        ForumPPCat::addArea($category_id, $new_id);

        $this->redirect(PluginEngine::getLink('forumpp/index/index/'));
    }

    function delete_entry_action($topic_id)
    {
        if (ForumPPEntry::hasEditPerms($topic_id)) {
            $path = ForumPPEntry::getPathToPosting($topic_id);
            $topic  = array_pop($path);
            $parent = array_pop($path);
            ForumPPEntry::delete($topic_id);

            $this->flash['messages'] = array('success' => sprintf(_('Der Eintrag %s wurde gel�scht!'), $topic['name']));
        }

        if (Request::isAjax()) {
            $this->render_template('messages');
        } else {
            $this->redirect(PluginEngine::getLink('forumpp/index/index/' . $parent['id']));
        }
    }

    function edit_entry_action($topic_id)
    {
        if (ForumPPEntry::hasEditPerms($topic_id)) {
            $this->flash['edit_entry'] = $topic_id;
        }

        $this->redirect(PluginEngine::getLink('forumpp/index/index/' . $topic_id .'#'. $topic_id));
    }

    function update_entry_action($topic_id)
    {
        if (ForumPPEntry::hasEditPerms($topic_id)) {
            ForumPPEntry::update($topic_id,
                Request::get('name', _('Kein Titel')),
                Request::get('content', _('Keine Beschreibung'))
            );
        }

        $this->redirect(PluginEngine::getLink('forumpp/index/index/' . $topic_id .'#'. $topic_id));
    }

    function move_thread_action($thread_id, $destination) {
        ForumPPEntry::move($thread_id, $destination);

        $this->redirect(PluginEngine::getLink('forumpp/index/index/' . $thread_id .'#'. $thread_id));
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

    function goto_page_action($topic_id, $page)
    {
        $this->redirect(PluginEngine::getLink('forumpp/index/index/' . $topic_id .'/'. (int)$page .'#'. $topic_id));
    }

    function like_action($topic_id)
    {
        ForumPPLike::like($topic_id);

        $this->redirect(PluginEngine::getLink('forumpp/index/index/' . $topic_id .'#'. $topic_id));
    }

    function dislike_action($topic_id)
    {
        ForumPPLike::dislike($topic_id);

        $this->redirect(PluginEngine::getLink('forumpp/index/index/' . $topic_id .'#'. $topic_id));
    }

    /* * * * * * * * * * * * * * * * * * * * * * * * * */
    /* * * *     C O N F I G - A C T I O N S     * * * */
    /* * * * * * * * * * * * * * * * * * * * * * * * * */

    function add_category_action()
    {
        ForumPPCat::add($this->getId(), Request::get('category'));

        $this->redirect(PluginEngine::getLink('forumpp/index'));
    }

    function add_areas_action()
    {
        if (!$this->rechte) {
            die;
        }

        foreach (Request::getArray('areas') as $area_id) {
            ForumPPCat::addArea(Request::option('cat_id'), $area_id);
        }

        $this->redirect(PluginEngine::getLink('forumpp/index/config_areas'));
    }

    function remove_area_action($area_id)
    {
        if (!$this->rechte) {
            die;
        }

        ForumPPCat::removeArea($area_id);
        $this->redirect(PluginEngine::getLink('forumpp/index/config_areas'));
    }

    function remove_category_action($category_id)
    {
        if (!$this->rechte) {
            $this->flash['messages'] = array('error' => _('Sie besitzen nicht gen�gend Rechte um Kategorien zu l�schen!'));
        } else {
            $this->flash['messages'] = array('success' => _('Die Kategorie wurde gel�scht!'));
            ForumPPCat::remove($category_id, $this->getId());
        }

        if (Request::isAjax()) {
            $this->render_template('messages');
        } else {
            $this->redirect(PluginEngine::getLink('forumpp/index/index'));
        }

    }

    function edit_area_action($area_id)
    {
        if (!$this->rechte) {
            die;
        }

        if (Request::isAjax()) {
            $name = utf8_decode(Request::get('name'));
        } else {
            $name = Request::get('name');
        }

        ForumPPEntry::update($area_id, $name, '');

        $this->render_nothing();
    }

    function edit_category_action($category_id) {
        if (!$this->rechte) {
            die;
        }

        if (Request::isAjax()) {
            $name = utf8_decode(Request::get('name'));
        } else {
            $name = Request::get('name');
        }

        ForumPPCat::setName($category_id, $name);
        $this->render_nothing();
    }

    function savecats_action()
    {
        if (!$this->rechte) {
            die;
        }

        $pos = 0;
        foreach (Request::getArray('categories') as $category_id) {
            ForumPPCat::setPosition($category_id, $pos);
            $pos++;
        }

        $this->render_nothing();
    }

    function saveareas_action()
    {
        if (!$this->rechte) {
            die;
        }

        foreach (Request::getArray('areas') as $category_id => $areas) {
            $pos = 0;
            foreach ($areas as $area_id) {
                ForumPPCat::addArea($category_id, $area_id);
                ForumPPCat::setAreaPosition($area_id, $pos);
                $pos++;
            }
        }

        $this->render_nothing();
    }

    /* * * * * * * * * * * * * * * * * * * * * * * * * */
    /* * * * * * * I M A G E   A C T I O N * * * * * * */
    /* * * * * * * * * * * * * * * * * * * * * * * * * */

    function image_action($image)
    {
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
        if (!Request::option('cid')) {
            if ($GLOBALS['SessionSeminar']) {
                URLHelper::bindLinkParam('cid', $GLOBALS['SessionSeminar']);
                return $GLOBALS['SessionSeminar'];
            }

            return false;
        }

        return Request::option('cid');
    }

    /**
     * Common code for all actions: set default layout and page title.
     *
     * @param type $action
     * @param type $args
     */
    function before_filter(&$action, &$args)
    {
        $this->validate_args($args, array('option', 'option'));

        parent::before_filter($action, $args);

        $this->flash = Trails_Flash::instance();

        // set default layout
        $layout = $GLOBALS['template_factory']->open('layouts/base');
        $this->set_layout($layout);

        PageLayout::setTitle(getHeaderLine($this->getId()) .' - '. _('Forum'));

        $this->AVAILABLE_DESIGNS = array('web20', 'studip');
        if ($GLOBALS['CANONICAL_RELATIVE_PATH_STUDIP'] && $GLOBALS['CANONICAL_RELATIVE_PATH_STUDIP'] != '/') {
            $this->picturepath = $GLOBALS['CANONICAL_RELATIVE_PATH_STUDIP'] .'/'. $this->dispatcher->trails_root . '/img';
        } else {
            $this->picturepath = '/'. $this->dispatcher->trails_root . '/img';
        }

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

    function check_write_and_edit()
    {
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
        // #TODO: make it work
        return;

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
}