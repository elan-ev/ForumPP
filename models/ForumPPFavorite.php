<?php

class ForumPPFavorite {
    static function set($topic_id) {
        $stmt = DBManager::get()->prepare("REPLACE INTO
            forumpp_favorites (topic_id, user_id)
            VALUES (?, ?)");
        $stmt->execute(array($topic_id, $GLOBALS['user']->id));
    }
    
    static function remove($topic_id) {
        $stmt = DBManager::get()->prepare("DELETE FROM forumpp_favorites
            WHERE topic_id = ? AND user_id = ?");
        $stmt->execute(array($topic_id, $GLOBALS['user']->id));        
    }
}