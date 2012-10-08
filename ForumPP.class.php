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
require_once 'models/ForumPPPerm.php';

require_once 'app/models/smiley.php';

// Notifications
NotificationCenter::addObserver('ForumPP', 'overviewDidClear', "OverviewDidClear");

class ForumPP extends StudipPlugin implements StandardPlugin
{

    /**
     * Initialize a new instance of the plugin.
     */
    function __construct()
    {
        parent::__construct();

        // do nothing if plugin is deactivated in this seminar/institute
        if (!$this->isActivated()) {
            return;
        }

        // TODO: remove development-rand from poduction-code
        PageLayout::addScript($this->getPluginURL() . '/javascript/forumpp.js?rand='. floor(time() / 100));
        PageLayout::addStylesheet($this->getPluginURL() . '/stylesheets/forumpp.css?rand='. floor(time() / 100));
        
        // JQuery-Tutor JoyRide JS and CSS
        PageLayout::addScript($this->getPluginURL() . '/javascript/jquery.joyride.js');
        PageLayout::addStylesheet($this->getPluginURL() . '/stylesheets/joyride.css');
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
    
    /* interface method */
    public function getTabNavigation($course_id)
    {
        $navigation = new Navigation(_('Forum 2'), PluginEngine::getLink('forumpp/index'));
        $navigation->setImage('icons/16/white/forum.png');

        // add main third-level navigation-item
        $navigation->addSubNavigation('index',     new Navigation(_('Beiträge'), PluginEngine::getLink('forumpp/index')));
        
        if (ForumPPPerm::has('fav_entry', $course_id)) {
            $navigation->addSubNavigation('newest', new Navigation(_("Neue Beiträge"), PluginEngine::getLink('forumpp/index/newest')));
            $navigation->addSubNavigation('latest', new Navigation(_("Letzte Beiträge"), PluginEngine::getLink('forumpp/index/latest')));
            $navigation->addSubNavigation('favorites', new Navigation(_('Gemerkte Beiträge'), PluginEngine::getLink('forumpp/index/favorites')));
        }

        return array('forum2' => $navigation);
    }

    /* interface method */
    function getIconNavigation($course_id, $last_visit, $user_id)
    {
        if (!$this->isActivated($course_id)) {
            return;
        }

        $num_entries = ForumPPVisit::getCount($course_id, ForumPPVisit::getVisit($course_id));
        
        $navigation = new Navigation('forumpp', PluginEngine::getLink('forumpp/index/enter_seminar'));
        $navigation->setBadgeNumber($num_entries);

        $text = ForumPPHelpers::getVisitText($num_entries, $course_id);

        if ($num_entries > 0) {
            $navigation->setImage('icons/16/red/new/forum.png', array('title' => $text));
        } else {
            $navigation->setImage('icons/16/grey/forum.png', array('title' => $text));
        }

        return $navigation;
    }

    /* interface method */
    function getNotificationObjects($course_id, $since, $user_id)
    {
        return array();
    }

 
    /* notification */
    function overviewDidClear($notification, $user_id)
    {
        $stmt = DBManager::get()->prepare("UPDATE forumpp_visits 
            SET visitdate = UNIX_TIMESTAMP(), last_visitdate = UNIX_TIMESTAMP()
            WHERE user_id = ?");
        $stmt->execute(array($user_id));
    }
    
    function getInfoTemplate($course_id)
    {
        return null;
    }
}