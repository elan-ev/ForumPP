<? 
$sem = get_object_name($topic['seminar_id'], 'sem');

// remove last element (which is the entry itself)
array_pop($path);
foreach ($path as $path_part) : 
    $path_name[] = $path_part['name'];
endforeach;

printf(_('Im Forum der Veranstaltung **%s** gibt es einen neuen Beitrag unter **%s** von **%s**'),
    $sem['name'], implode(' > ', $path_name), $topic['author']) ?>


<?= $topic['name'] ? '**' . $topic['name'] ."**\n\n" : '' ?>
<?= $topic['content'] ?>


<?= UrlHelper::getUrl($GLOBALS['ABSOLUTE_URI_STUDIP'] . 'plugins.php/forumpp/index/index/'
    . $topic['topic_id'] .'?cid=' . $topic['seminar_id'] .'&again=yes#' . $topic['topic_id']) ?>