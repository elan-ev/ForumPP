<?php

require_once('lib/classes/Seminar.class.php');

class ForumPPDB {

	static function getSeminarTitle($seminar_id) {
		$seminar = Seminar::getInstance($seminar_id);
		return $seminar->getName();
	}

	static function getSeminarSubtitle($seminar_id) {
		$seminar = Seminar::getInstance($seminar_id);
		return $seminar->subtitle;
	}

	static function getLastPostingTimestamp($seminar_id) {

	}

	static function 
}
