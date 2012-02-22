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

<div class="posting <?=($zebra) ? 'bg1' : 'bg2'?>" style="position: relative;">
    <span class="corners-top"><span></span></span>

    <? if ($post['fav']) : ?>
    <div class="marked"></div>
    <? endif ?>

    <div class="postbody">
        <div class="title">

            <? if (isset($visitdate) && $post['mkdate'] >= $visitdate && $post['owner_id'] != $GLOBALS['user']->id) : ?>
            <span class="new_posting">
                <?= Assets::img('icons/16/red/new/forum.png', array(
                    'title' => _("Dieser Beitrag ist seit Ihrem letzten Besuch hinzugekommen.")
                )) ?>
            </span>
            <? endif ?>

            <? if ($flash['edit_entry'] == $post['topic_id']) : ?>
                <input type="text" name="name" value="<?= htmlReady($post['name_raw']) ?>" style="width: 100%">
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
                von <strong><a href="<?= URLHelper::getLink('about.php?username='. get_username($post['owner_id'])) ?>">
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
                'content' => _('Gefällt mir!')
            );
        } else {
            $icons[] = array(
                'link' => PluginEngine::getLink('forumpp/index/dislike/'. $post['topic_id']),
                'content' => _('Gefällt mir nicht mehr.')
            );
        }
        ?>

        <? if (!empty($icons)) : ?>
        <span class="action-icons">
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
            <textarea id="inhalt" name="content" class="add_toolbar"><?= htmlReady($post['content_raw']) ?></textarea>
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
            <a href="<?= URLHelper::getLink('about.php?username='. get_username($post['owner_id'])) ?>">
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

    <!-- Buttons for this Posting -->
    <? if ($section == 'index') : ?>
    <div class="buttons">
        <div class="button-group">
    <? if ($flash['edit_entry'] == $post['topic_id']) : ?>
        <?= Studip\Button::createAccept('Änderungen speichern') ?>

        <?= Studip\LinkButton::createCancel('Abbrechen', PluginEngine::getURL('forumpp/index/index/'. $topic_id)) ?>
        <?= Studip\LinkButton::create('Vorschau', "javascript:STUDIP.ForumPP.preview('inhalt', 'preview');") ?>

    <? else : ?>
        <? if (ForumPPEntry::hasEditPerms($post['topic_id'])) : ?>
            <?= Studip\LinkButton::create('Eintrag bearbeiten', PluginEngine::getURL('forumpp/index/edit_entry/'. $post['topic_id'])) ?>
        <? endif ?>
            
        <?= Studip\LinkButton::create('Zitieren', PluginEngine::getURL('forumpp/index/cite/'. $post['topic_id'] .'/#create')) ?>

        <? if ($this->has_perms) : ?>
            <?= Studip\LinkButton::create('Beitrag löschen', PluginEngine::getURL('forumpp/index/delete_entry/' . $post['topic_id'])) ?>
        <? endif ?>

        <? if (!$post['fav']) : ?>
            <?= Studip\LinkButton::create('Beitrag merken', PluginEngine::getURL('forumpp/index/set_favorite/' . $post['topic_id'])) ?>
        <? else : ?>
            <?= Studip\LinkButton::create('Beitrag vernachlässigen', PluginEngine::getURL('forumpp/index/unset_favorite/' . $post['topic_id'])) ?>
        <? endif ?>
    <? endif ?>
        </div>
    </div>
    <? endif ?>

  <span class="corners-bottom"><span></span></span>
</div>

<? if ($flash['edit_entry'] == $post['topic_id']) : ?>
</form>

<?= $this->render_partial('index/_preview', array('preview_id' => 'preview')) ?>
<? endif ?>
