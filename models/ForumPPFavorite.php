<?php

class ForumPPFavorite {
    static function like($topic_id) {
        $stmt = DBManager::get()->prepare("REPLACE INTO
            forumpp_favorites (topic_id, user_id)
            VALUES (?, ?)");
        $stmt->execute(array($topic_id, $GLOBALS['user']->id));
    }
    
    static function dislike($topic_id) {
        $stmt = DBManager::get()->prepare("DELETE FROM forumpp_favorites
            WHERE topic_id = ? AND user_id = ?");
        $stmt->execute(array($topic_id, $GLOBALS['user']->id));        
    }
}