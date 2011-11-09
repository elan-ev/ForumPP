<?php

class ForumPPLike {
    static function like($topic_id) {
        $stmt = DBManager::get()->prepare("REPLACE INTO
            forumpp_likes (topic_id, user_id)
            VALUES (?, ?)");
        $stmt->execute(array($topic_id, $GLOBALS['user']->id));
    }
    
    static function getLikes($topic_id) {
        $stmt = DBManager::get()->prepare("SELECT 
            auth_user_md5.user_id FROM forumpp_likes
            LEFT JOIN auth_user_md5 USING (user_id)
            LEFT JOIN user_info USING (user_id)
            WHERE topic_id = ?");
        $stmt->execute(array($topic_id));
        
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
}