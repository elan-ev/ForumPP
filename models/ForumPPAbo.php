<?php

/**
 * filename - Short description for file
 *
 * Long description for file (if any)...
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 3 of
 * the License, or (at your option) any later version.
 *
 * @author      Till Glöggler <tgloeggl@uos.de>
 * @license     http://www.gnu.org/licenses/gpl-3.0.html GPL version 3
 * @category    Stud.IP
 */

require_once('lib/messaging.inc.php');

class ForumPPAbo {
    static function add($topic_id, $user_id = null)
    {
        if (!$user_id) $user_id = $GLOBALS['user']->id;
        
        $stmt = DBManager::get()->prepare("REPLACE INTO forumpp_abo_users
            (topic_id, user_id) VALUEs (?, ?)");
        $stmt->execute(array($topic_id, $user_id));
    }
    
    static function delete($topic_id, $user_id = null)
    {
        if (!$user_id) $user_id = $GLOBALS['user']->id;
        
        $stmt = DBManager::get()->prepare("DELETE FROM forumpp_abo_users
            WHERE topic_id = ? AND user_id = ?");
        $stmt->execute(array($topic_id, $user_id));
    }
    
    static function has($topic_id, $user_id = null) {
        if (!$user_id) $user_id = $GLOBALS['user']->id;
        
        $stmt = DBManager::get()->prepare("SELECT COUNT(*) FROM forumpp_abo_users
            WHERE topic_id = ? AND user_id = ?");
        $stmt->execute(array($topic_id, $user_id));
        
        return $stmt->fetchColumn() > 0 ? true : false;
    }

    static function notify($topic_id)
    {
        // send message to all abo-users
        $db = DBManager::get();
        $messaging = new ForumPPBulkMail();
        // $messaging = new Messaging();

        // get all parent topic-ids, to find out which users to notify
        $path = ForumPPEntry::getPathToPosting($topic_id);

        // fetch all users to notify
        $stmt = $db->prepare("SELECT DISTINCT user_id
            FROM forumpp_abo_users
            WHERE topic_id IN (:topic_ids)");
        $stmt->bindParam(':topic_ids', array_keys($path), StudipPDO::PARAM_ARRAY);
        $stmt->execute();
        
        // get details for topic
        $topic = ForumPPEntry::getConstraints($topic_id);
        
        $template_factory = new Flexi_TemplateFactory(dirname(__FILE__) . '/../views');
        $template = $template_factory->open('index/_mail_notification');
        
        // notify users
        while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $user_id = $data['user_id'];

            // create subject and content
            setTempLanguage(get_userid($user_id));
            $subject = addslashes(_('[Forum]') . ' ' . ($topic['name'] ? $topic['name'] : _('Neuer Eintrag')));
            $message = addslashes($template->render(compact('user_id', 'topic', 'path')));
            restoreLanguage();
            
            // #TODO: why ist $db->quote not working here?
            $messaging->insert_message($message, get_username($user_id),
                "____%system%____", false, false, false, false, $subject);
        }
        
        $messaging->bulkSend();
    }
}