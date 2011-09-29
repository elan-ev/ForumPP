<?
if (!is_array($highlight)) $highlight = array();
?>
<!-- Posting -->
<tr>
    <td>
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
                <span class="icons">
                    <?

                    // edit
                    if (ForumPPEntry::hasEditPerms($post['topic_id'])) {
                        $icon = array();

                        $icon['link'] = PluginEngine::getLink('forumpp/index/edit_entry/'. $post['topic_id']);
                        $icon['image'] = Assets::image_path('/images/icons/16/blue/edit.png');
                        $icon['title'] = _("Eintrag bearbeiten");
                        $icons[] = $icon;
                    }

                    // cite
                    $icon = array();
                    $icon['link'] = PluginEngine::getLink('forumpp/index/cite/'. $post['topic_id']);
                    $icon['image'] = PluginEngine::getLink('forumpp/index/image/quote', array('cid' => null));
                    $icon['title'] = _("Aus diesem Eintrag zitieren");
                    $icons[] = $icon;

                    // favorite
                    $icon = array();
                    $icon['link'] = PluginEngine::getLink('forumpp/index/switch_favorite/' . $post['topic_id']);
                    if (!$post['fav']) {
                        $icon['image'] = Assets::image_path('/images/icons/16/grey/star.png');
                        $icon['title'] = _("zu den Favoriten hinzuf&uuml;gen");
                    } else {
                        $icon['image'] = Assets::image_path('/images/icons/16/red/star.png');
                        $icon['title'] = _("aus den Favoriten entfernen");
                    }
                    $icons[] = $icon;

                    // delete
                    if ($this->has_perms) {
                        $icon = array();
                        $icon['link'] = PluginEngine::getLink('forumpp/index/delete_entry/' . $post['topic_id']);
                        $icon['image'] = Assets::image_path('/images/icons/16/blue/trash.png');
                        $icon['title'] = _("Eintrag l&ouml;schen!");
                        $icons[] = $icon;
                    }
                    ?>
                    <? if (!empty($icons)) foreach ($icons as $an_icon) : ?>
                    <a href="<?= $an_icon['link'] ?>" title="<?= $an_icon['title'] ?>" alt="<?= $an_icon['title'] ?>">
                        <img src="<?= $an_icon['image'] ?>" title="<?= $an_icon['title'] ?>">
                    </a>&nbsp;
                    <? endforeach; ?>
                </span>

                <!-- Postinginhalt -->
                <p class="content">
                    <? if ($flash['edit_entry'] == $post['topic_id']) : ?>
                    <textarea name="content" class="add_toolbar"><?= $post['content_raw'] ?></textarea>
                    <? else : ?>
                        <?= ForumPPHelpers::highlight($post['content'], $highlight) ?>
                    <? endif ?>
                </p>
            </div>

          <!-- Infobox rechts neben jedem Posting -->
          <dl class="postprofile">
            <dt>
              <a href="<?= URLHelper::getLink('about.php?username='. $post['real_username']) ?>">
                  <?= Avatar::getAvatar($owner_id)->getImageTag(Avatar::MEDIUM, array('title' => get_username($post['owner_id']))) ?>
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
          </dl>

            <? if ($flash['edit_entry'] == $post['topic_id']) : ?>
            <div class="buttons">
                <input type="image" <?= makebutton('speichern', 'src') ?> title="Beitrag erstellen" style="margin-right: 20px">
                <a href="<?= PluginEngine::getLink('forumpp/index/index/'. $topic_id) ?>">
                    <?= makebutton('abbrechen') ?>
                </a>
            </div>
            <? endif ?>

          <span class="corners-bottom"><span></span></span>
        </div>

        <? if ($flash['edit_entry'] == $post['topic_id']) : ?>
        </form>
        <? endif ?>
    </td>
</tr>

<!-- Trennzeile -->
<tr class="trenner">
  <td class="blank" style="height: 3px"></td>
</tr>