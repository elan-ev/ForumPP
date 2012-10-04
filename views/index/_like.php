<?
if (!ForumPPPerm::has('like_entry', $seminar_id)) return;

$likes = ForumPPLike::getLikes($topic_id);
shuffle($likes);
?>

<!-- the likes for this post -->
<? if (!empty($likes)) : ?>
<? // set the current user to the front
$pos = array_search($GLOBALS['user']->id, $likes);
if ($pos !== false) :
    unset($likes[$pos]);
    array_unshift($likes, $GLOBALS['user']->id);
endif;

$i = 0;
foreach ($likes as $user_id) :
    if ($i > 4) break;

    if ($user_id == $GLOBALS['user']->id) :
        $name = 'Dir';
    else :
        $name = get_fullname($user_id);
    endif;

    $username = get_username($user_id);
    $links[] = '<a href="'. URLHelper::getLink('about.php?username='. $username) .'">'. $name .'</a>';
    $i++;
endforeach ?>

<? if (sizeof($likes) > 4) : ?>
    <?= implode(', ', $links) ?>
    <? if ((sizeof($likes) - 4) > 1) : ?>
    und <?= sizeof($likes) - 4 ?> weiteren
    <? else: ?>
    und einem weiteren
    <? endif ?>
<? else : ?>
    <? if (sizeof($links) > 1) : ?>
    <?= implode(', ', array_slice($links, 0, sizeof($links) - 1)) ?>
    und
    <? endif ?>

    <?= end($links) ?>
<? endif ?>

<?= _('gefällt das.') ?> |
<? endif ?>


<!-- like/dislike links -->
<? if (!in_array($GLOBALS['user']->id, $likes)) : ?>
    <a href="<?= PluginEngine::getLink('forumpp/index/like/'. $topic_id) ?>" onClick="jQuery('#like_<?= $topic_id ?>').load('<?= PluginEngine::getLink('forumpp/index/like/'. $topic_id) ?>'); return false;">
        <?= _('Gefällt mir!'); ?>
    </a>
<? else : ?>
    <a href="<?= PluginEngine::getLink('forumpp/index/dislike/'. $topic_id) ?>" onClick="jQuery('#like_<?= $topic_id ?>').load('<?= PluginEngine::getLink('forumpp/index/dislike/'. $topic_id) ?>'); return false;">
        <?= _('Gefällt mir nicht mehr!'); ?>
    </a>
<? endif ?>