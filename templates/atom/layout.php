<?php
	$updated = ForumPPDB::getLastPostingTimestamp($plugin->getId())
?>
<?= '<?xml version="1.0" encoding="utf-8"?>'?>
<feed xmlns="http://www.w3.org/2005/Atom">
	<title type="text"><?= ForumPPDB::getSeminarTitle($plugin->getId()) ?></title>
	<subtitle type="html">
		<?= ForumPPDB::getSeminarSubtitle($plugin->getId()) ?>
	</subtitle>
	
	<updated></updated>
<?= $content_for_layout ?>
</feed>
