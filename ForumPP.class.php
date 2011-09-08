<?php
/*
 * ForumPP.class.php - ForumPP
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 *
 * @author      Till Glöggler <till.gloeggler@elan-ev.de>
 * @copyright   2011 ELAN e.V. <http://www.elan-ev.de>
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL version 2
 * @category    Stud.IP
 */

require_once 'vendor/trails/trails.php';
require_once 'models/ForumPPEntry.class.php';
require_once 'models/ForumPPHelpers.class.php';

class ForumPP extends StudipPlugin implements StandardPlugin
{

    /**
     * Initialize a new instance of the plugin.
     */
    function __construct()
    {
        parent::__construct();

        // do nothing if plugin is deactivated in this seminar/institute
        if (!PluginManager::isPluginActivated($this->getPluginId(), Request::get('cid', $GLOBALS['SessSemName'][1]))) return;

        $navigation = new Navigation(_('Forum'), PluginEngine::getLink('forumpp/index'));
        $navigation->setImage('icons/16/white/forum.png');

        if ($GLOBALS['perm']->have_studip_perm('tutor', Request::get('cid', $GLOBALS['SessSemName'][1]))) {
            $sub_nav = new Navigation(_("Beiträge"),
                    PluginEngine::getLink('forumpp/index'));
            $navigation->addSubNavigation('index', $sub_nav);

            $sub_nav = new Navigation(_("Bereiche administrieren"),
                    PluginEngine::getLink('forumpp/index/config_areas'));
            $navigation->addSubNavigation('config_areas', $sub_nav);

            $sub_nav = new Navigation(_("Postings administrieren"),
                    PluginEngine::getLink('forumpp/index/config_threads'));
            $navigation->addSubNavigation('config_threads', $sub_nav);
        }

        Navigation::insertItem('/course/forum', $navigation, 'members');


        $style_attributes = array(
            'rel'   => 'stylesheet',
            'href'  => PluginEngine::getLink('forumpp/index/css')
        );

        PageLayout::addHeadElement('link',  array_merge($style_attributes, array()));
    }

    /**
     * This method dispatches all actions.
     *
     * @param string   part of the dispatch path that was not consumed
     */
    function perform($unconsumed_path)
    {
        $trails_root = $this->getPluginPath();
        $dispatcher = new Trails_Dispatcher($trails_root, PluginEngine::getUrl('forumpp/index'), 'index');
        $dispatcher->dispatch($unconsumed_path);

    }

    function getIconNavigation($course_id, $last_visit) {
        //echo date('d.m.Y H:i', $lastlogin);
        $this->last_visit = object_get_visit($course_id, "forum", "visitdate");
        if (!$this->last_visit) {
            $this->last_visit = $last_visit;
        }


        $list = ForumPPEntry::getList('newest', $course_id);

        if ($list['count'] == 1) {
            $text = _("Ein neuer Beitrag vorhanden");
        } else if ($list['count'] > 1) {
            $text = sprintf(_("%s neue Beiträge vorhanden."), $list['count']);
        } else {
            $text = _("Keine neuen Beiträge.");
        }

        $navigation = new Navigation('forumpp', PluginEngine::getLink('forumpp/index/newest'));
        $navigation->setTitle($text);
        $navigation->setImage('icons/16/red/new/forum.png');

        return $navigation;
    }

    function getInfoTemplate($course_id) {
        return null;
    }
}