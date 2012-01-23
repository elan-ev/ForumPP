<?
if (!is_array($highlight)) $highlight = array();
$likes = ForumPPLike::getLikes($post['topic_id']);
shuffle($likes);
?>
<!-- Anker, um zu diesem Posting springen zu können -->
<a name="<?= $post['topic_id'] ?>"></a>

<? if ($flash['edit_entry'] == $post['topic_id']) : ?>
<form action="<?= PluginEngine::getLink('forumpp/index/update_entry/'. $post['topic_id']) ?>" method="post">
    <?= CSRFProtection::tokenTag() ?>
<? endif ?>

<div class="posting <?=($zebra) ? 'bg1' : 'bg2'?>">
    <span class="corners-top"><span></span></span>

    <div class="postbody">
        <div class="title">

            <? if ($flash['edit_entry'] == $post['topic_id']) : ?>
                <input type="text" name="name" value="<?= $post['name_raw'] ?>" style="width: 100%">
            <? else : ?>
                <a href="<?= PluginEngine::getLink('forumpp/index/index/' . $post['topic_id']) ?>#<?= $post['topic_id'] ?>">
                <? if ($show_full_path) : ?>
                    <? foreach (ForumPPEntry::getPathToPosting($post['topic_id']) as $pos => $path_part) : ?>
                        <? if ($pos > 1) : ?> &bullet; <? endif ?>
                        <?= ForumPPHelpers::highlight(htmlReady($path_part['name']), $highlight) ?>
                    <? endforeach ?>
                <? else : ?>
                <?= ($post['name']) ? ForumPPHelpers::highlight($post['name'], $highlight) : ''?>
                <? endif ?>
                </a>
            <? endif ?>

            <p class="author">
                von <strong><a href="<?= URLHelper::getLink('about.php?username='. $post['real_username']) ?>">
                    <?= ForumPPHelpers::highlight(htmlReady($post['author']), $highlight) ?>
                </a></strong>
                am <?= strftime($time_format_string, (int)$post['mkdate']) ?>
            </p>
        </div>

        <!-- Aktionsicons -->
        <?
        // like
        if (!in_array($GLOBALS['user']->id, $likes)) {
            $icons[] = array(
                'link' => PluginEngine::getLink('forumpp/index/like/'. $post['topic_id']),
                'title' => _('Gefällt mir!'),
                'content' => '+1'
            );
        } else {
            $icons[] = array(
                'link' => PluginEngine::getLink('forumpp/index/dislike/'. $post['topic_id']),
                'title' => _('Gefällt mir nicht mehr.'),
                'content' => '-1'
            );
        }

        // edit
        if (ForumPPEntry::hasEditPerms($post['topic_id'])) {
            $icon = array();

            $icon['link'] = PluginEngine::getLink('forumpp/index/edit_entry/'. $post['topic_id']);
            $icon['content'] = Assets::img('/images/icons/16/blue/edit.png');
            $icon['title'] = _("Eintrag bearbeiten");
            $icons[] = $icon;
        }

        // cite
        $icon = array();
        $icon['link'] = PluginEngine::getLink('forumpp/index/cite/'. $post['topic_id']);
        $icon['content'] = Assets::img('/images/icons/16/blue/chat.png');
        $icon['title'] = _("Aus diesem Eintrag zitieren");
        $icons[] = $icon;

        // favorite
        $icon = array();
        $icon['link'] = PluginEngine::getLink('forumpp/index/switch_favorite/' . $post['topic_id']);
        if (!$post['fav']) {
            $icon['content'] = Assets::img('/images/icons/16/grey/star.png');
            $icon['title'] = _("zu den Favoriten hinzuf&uuml;gen");
        } else {
            $icon['content'] = Assets::img('/images/icons/16/red/star.png');
            $icon['title'] = _("aus den Favoriten entfernen");
        }
        $icons[] = $icon;

        // delete
        if ($this->has_perms) {
            $icon = array();
            $icon['link'] = PluginEngine::getLink('forumpp/index/delete_entry/' . $post['topic_id']);
            $icon['content'] = Assets::img('/images/icons/16/blue/trash.png');
            $icon['title'] = _("Eintrag l&ouml;schen!");
            $icons[] = $icon;
        }
        ?>
        <? if (!empty($icons)) : ?>
        <span class="action-icons">
            <?foreach ($icons as $an_icon) : ?>
            <a href="<?= $an_icon['link'] ?>" title="<?= $an_icon['title'] ?>" alt="<?= $an_icon['title'] ?>">
                <?= $an_icon['content'] ?>
            </a>&nbsp;
            <? endforeach; ?>
        </span>
        <? endif ?>

        <!-- Postinginhalt -->
        <p class="content">
            <? if ($flash['edit_entry'] == $post['topic_id']) : ?>
            <textarea id="inhalt" name="content" class="add_toolbar"><?= $post['content_raw'] ?></textarea>
            <? else : ?>
                <?= ForumPPHelpers::highlight($post['content'], $highlight) ?>
            <? endif ?>
        </p>
    </div>

    <? if ($flash['edit_entry'] == $post['topic_id']) : ?>
    <dl class="postprofile">
        <dt>
            <?= $this->render_partial('index/_smiley_favorites') ?>
        </dt>
    </dl>
    <? else : ?>
    <!-- Infobox rechts neben jedem Posting -->
    <dl class="postprofile">
        <dt>
            <a href="<?= URLHelper::getLink('about.php?username='. $post['real_username']) ?>">
                <?= Avatar::getAvatar($post['owner_id'])->getImageTag(Avatar::MEDIUM,
                      array('title' => get_username($post['owner_id']))) ?>
                <br>
                <strong><?= htmlReady(get_fullname($post['owner_id'])) ?></strong>
            </a>
        </dt>
        <dd>
            <?= ForumPPHelpers::translate_perm($GLOBALS['perm']->get_studip_perm($constraint['seminar_id'], $post['owner_id']))?>
        </dd>
        <dd>&nbsp;</dd>
        <dd>
            Beiträge:
            <?= ForumPPEntry::countUserEntries($post['owner_id']) ?>
        </dd>
    </dl>
    <? endif ?>


    <? if ($flash['edit_entry'] == $post['topic_id']) : ?>
    <div class="buttons">
        <?= Studip\Button::createAccept('Änderungen speichern') ?>

        <?= Studip\LinkButton::createCancel('abbrechen', PluginEngine::getLink('forumpp/index/index/'. $topic_id)) ?>
        
        <?= Studip\LinkButton::create('Vorschau', "javascript:STUDIP.ForumPP.preview('inhalt', 'preview');") ?>

        <? /*
        <input type="image" <?= makebutton('speichern', 'src') ?> title="Beitrag erstellen" style="margin-right: 20px">
        <a href="<?= " style="margin-right: 20px">
            <?= makebutton('abbrechen') ?>
        </a>
        
        <a href="javascript:STUDIP.ForumPP.preview('inhalt', 'preview')">
            <?= makebutton('vorschau') ?>
        </a>  */ ?>       
    </div>
    <? endif ?>
        
    <!-- the likes for this post -->
    <? if (!empty($likes)) : ?>
    <br style="clear: both">
    <div style="padding-left: 5px">
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
        
    <?= _('gefällt das.') ?>
    </div>
    <? endif ?>

  <span class="corners-bottom"><span></span></span>
</div>

<? if ($flash['edit_entry'] == $post['topic_id']) : ?>
</form>

<?= $this->render_partial('index/_preview', array('preview_id' => 'preview')) ?>
<? endif ?>

<br>