<?php
/**
 * ForumPPVersion.php - Contains functions to retrieve version of ForumPP
 *
 * This class has some static functions to retrieve the version for currently
 * installed and remotely availabe version of ForumPP
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

class ForumPPVersion {
    
    /**
     * returns the version of the currently installed ForumPP
     * 
     * @return string the version of the installed ForumPP
     */
    static function getCurrent() {
        $ini_file = parse_ini_file(dirname(__FILE__) .'/../plugin.manifest');
        return $ini_file['version'];
    }
    
    /**
     * returns the version of the latest ForumPP available
     * 
     * @return string the version if the latest ForumPP
     */
    static function getLatest() {
        $cache = StudipCacheFactory::getCache();
        $cache_key = 'forumpp/latest_version';
        
        $version = $cache->read($cache_key);

        if (!$version) {
            $ini_file = parse_ini_string(file_get_contents('https://raw.github.com/tgloeggl/ForumPP/master/plugin.manifest'));
            $version = $ini_file['version'];
            $cache->write($cache_key, $version, 3600);
        }
        
        return $version;
    }
}