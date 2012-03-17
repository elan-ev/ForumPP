<?
if (!is_array($highlight)) $highlight = array();
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
        <span class="action-icons likes" id="like_<?= $post['topic_id'] ?>">
            <?= $this->render_partial('index/_like', array('topic_id' => $post['topic_id'])) ?>
        </span>

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
        <dd class="online-status">
            <? switch(ForumPPHelpers::getOnlineStatus($post['owner_id'])) :
                case 'available': ?>
                    <img src="<?= $picturepath ?>/community.png">
                    <?= _('Online') ?>
                <? break; ?>

                <? case 'offline': ?>
                    <?= Assets::img('icons/16/black/community.png') ?>
                    <?= _('Offline') ?>
                <? break; ?>
            <? endswitch ?>
        </dd>
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
        <!-- Buttons für den Bearbeitungsmodus -->
        <?= Studip\Button::createAccept('Änderungen speichern') ?>

        <?= Studip\LinkButton::createCancel('Abbrechen', PluginEngine::getURL('forumpp/index/index/'. $post['topic_id'])) ?>
        <?= Studip\LinkButton::create('Vorschau', "javascript:STUDIP.ForumPP.preview('inhalt', 'preview');") ?>

    <? else : ?>
        <!-- Aktions-Buttons für diesen Beitrag -->
        <? if (ForumPPEntry::hasEditPerms($post['topic_id']) || ForumPPPerm::has('edit_entry', $seminar_id)) : ?>
            <?= Studip\LinkButton::create('Beitrag bearbeiten', PluginEngine::getURL('forumpp/index/edit_entry/'. $post['topic_id'])) ?>
        <? endif ?>
            
        <? if (ForumPPPerm::has('add_entry', $seminar_id)) : ?>
        <?= Studip\LinkButton::create('Zitieren', PluginEngine::getURL('forumpp/index/cite/'. $post['topic_id'] .'/#create')) ?>
        <? endif ?>

        <? if (ForumPPEntry::hasEditPerms($post['topic_id']) || ForumPPPerm::has('remove_entry', $seminar_id)) : ?>
            <? if ($constraint['depth'] == $post['depth']) : /* this is not only a posting, but a thread */ ?>
                <?= Studip\LinkButton::create('Beitrag löschen', PluginEngine::getURL('forumpp/index/delete_entry/' . $post['topic_id']),
                    array('onClick' => "return confirm('". _('Wenn Sie diesen Beitrag löschen wird ebenfalls das gesamte Thema gelöscht.\n'
                            . ' Sind Sie sicher, dass Sie das tun möchten?') ."')")) ?>
            <? else : ?>
                <?= Studip\LinkButton::create('Beitrag löschen', PluginEngine::getURL('forumpp/index/delete_entry/' . $post['topic_id']),
                    array('onClick' => "return confirm('". _('Möchten Sie diesen Beitrag wirklich löschen?') ."')")) ?>
            <? /* "javascript:STUDIP.ForumPP.deleteEntry('{$post['topic_id']}');" */ ?>
            <? endif ?>
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