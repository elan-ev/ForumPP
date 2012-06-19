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

// Notifications
NotificationCenter::addObserver('ForumPP', 'coursesDidClearVisits', "CoursesDidClearVisits");

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

        NotificationCenter::addObserver('ForumPPVisit', 'addEntry', 'ForumPPAfterInsert');
        NotificationCenter::addObserver('ForumPPVisit', 'deleteEntry', 'ForumPPBeforeDelete');

        // TODO: remove development-rand from poduction-code
        PageLayout::addScript($this->getPluginURL() . '/javascript/forumpp.js?rand='. floor(time() / 100));
        PageLayout::addStylesheet($this->getPluginURL() . '/stylesheets/forumpp.css?rand='. floor(time() / 100));
    }
	
	public function getTabNavigation($course_id) {
		$navigation = new Navigation(_('Forum 2'), PluginEngine::getLink('forumpp/index'));
        $navigation->setImage('icons/16/white/forum.png');

        // add main third-level navigation-item
        $navigation->addSubNavigation('index',     new Navigation(_('Beiträge'), PluginEngine::getLink('forumpp/index')));
        $navigation->addSubNavigation('favorites', new Navigation(_('Gemerkte Beiträge'), PluginEngine::getLink('forumpp/index/favorites')));
        $navigation->addSubNavigation('latest',    new Navigation(_("Neueste Beiträge"), PluginEngine::getLink('forumpp/index/latest')));
		return array('forum2' => $navigation);
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

    function getIconNavigation($course_id, $last_visit)
    {
        $num_entries = ForumPPVisit::getCount($GLOBALS['user']->id, $course_id);

        $navigation = new Navigation('forumpp', PluginEngine::getLink('forumpp/index/enter_seminar'));
        $navigation->setBadgeNumber($num_entries['abo'] + $num_entries['new']);

        $text = ForumPPHelpers::getVisitText($num_entries, $course_id);

        if ($num_entries['abo'] > 0 || $num_entries['new'] > 0) {
            $navigation->setImage('icons/16/red/new/forum.png', array('title' => $text));
        } else {
            $navigation->setImage('icons/16/grey/forum.png', array('title' => $text));
        }

        return $navigation;
    }

    
    function coursesDidClearVisits($notification, $user_id)
    {
        $stmt = DBManager::get()->prepare("UPDATE forumpp_visits 
            SET visitdate = UNIX_TIMESTAMP()
            WHERE user_id = ?");
        $stmt->execute(array($user_id));
    }
    
    function getInfoTemplate($course_id)
    {
        return null;
    }
}
