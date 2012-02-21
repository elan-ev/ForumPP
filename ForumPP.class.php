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
require_once 'models/ForumPPEntry.php';
require_once 'models/ForumPPHelpers.php';
require_once 'models/ForumPPVisit.php';

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

        $navigation = new Navigation(_('Forum 2'), PluginEngine::getLink('forumpp/index'));
        $navigation->setImage('icons/16/white/forum.png');

        // add main third-level navigation-item
        $navigation->addSubNavigation('index',     new Navigation(_('Beiträge'), PluginEngine::getLink('forumpp/index')));
        $navigation->addSubNavigation('favorites', new Navigation(_('Gemerkte Beiträge'), PluginEngine::getLink('forumpp/index/favorites')));
        $navigation->addSubNavigation('latest',    new Navigation(_("Beitragsstream"), PluginEngine::getLink('forumpp/index/latest')));

        // add the navigation next to the traditional forum
        Navigation::insertItem('/course/forum2', $navigation, 'members');


        $style_attributes = array(
            'rel'   => 'stylesheet',
            'href'  => PluginEngine::getLink('forumpp/index/css')
        );

        PageLayout::addHeadElement('link',  array_merge($style_attributes, array()));

        PageLayout::addScript($this->getPluginURL() . '/javascript/application.js');
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
        $num_entries = ForumPPVisit::getCount($GLOBALS['user']->id, $course_id);

        $navigation = new Navigation('forumpp', PluginEngine::getLink('forumpp/index/enter_seminar'));

        $text = ForumPPHelpers::getVisitText($num_entries, $course_id);

        if ($num_entries['abo'] > 0 || $num_entries['new'] > 0) {
            $navigation->setImage('icons/16/red/new/forum.png', array('title' => $text));
        } else {
            $navigation->setImage('icons/16/grey/forum.png', array('title' => $text));
        }

        return $navigation;
    }

    function getInfoTemplate($course_id) {
        return null;
    }
}
