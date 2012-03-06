<br>
<div id="sortable_areas">
<? foreach ($list as $category_id => $entries) : ?>
<table cellspacing="0" cellpadding="2" border="0" width="100%" class="forum" data-category-id="<?= $category_id ?>">
    <thead>
    <tr>
        <td class="forum_header" colspan="3" width="65%">
            <span class="corners-top"></span>
            <span class="heading">
                <?= _('Themen') ?>
            </span>
        </td>

        <td class="forum_header" width="5%">
            <span class="no-corner"></span>
            <span class="heading"><?= _("Beiträge") ?></span>
        </td>

        <td class="forum_header" width="30%" colspan="2">
            <span class="corners-top-right"></span>
            <span class="heading" style="float: left"><?= _("letzte Antwort") ?></span>
        </td>
    </tr>
    </thead>


    <tbody class="sortable">
    <!-- this row allows dropping on otherwise empty categories -->
    <tr class="sort-disabled">
        <td class="areaborder" style="height: 5px"colspan="7"> </td>
    </tr>

    <? if (!empty($entries)) foreach ($entries as $entry) :
        $jump_to_topic_id = ($entry['last_posting']['topic_id'] ? $entry['last_posting']['topic_id'] : $entry['topic_id']); ?>
 
    <tr data-area-id="<?= $entry['topic_id'] ?>">

        <td class="areaborder"> </td>

        <td class="areaentry icon" width="1%" valign="top" align="center">
            <? if (!ForumPPVisit::hasEntry($GLOBALS['user']->id, $entry['topic_id']) && $entry['owner_id'] != $GLOBALS['user']->id): ?>
                <?= Assets::img('icons/16/red/new/forum.png', array(
                    'title' => _('Dieser Eintrag ist neu!')
                )) ?>
            <? else : ?>
                <? $num_postings = ForumPPVisit::getCount($GLOBALS['user']->id, $entry['topic_id']) ?>
                <? $text = ForumPPHelpers::getVisitText($num_postings, $entry['topic_id'], $constraint['depth']) ?>
                <? if ($num_postings['abo'] > 0 || $num_postings['new'] > 0) : ?>
                    <?= Assets::img('icons/16/red/forum.png', array(
                        'title' => $text
                    )) ?>
                <? else : ?>
                    <?= Assets::img('icons/16/black/forum.png', array(
                        'title' => $text
                    )) ?>
                <? endif ?>
            <? endif ?>
        </td>

        <td class="areaentry" valign="top">
            <div style="position: relative;">
                <a href="<?= PluginEngine::getLink('forumpp/index/index/'. $jump_to_topic_id .'#'. $jump_to_topic_id) ?>">
                    <span class="areaname"><?= $entry['name'] ?></span>
                </a>

                <? if ($has_perms) : /* threads */?>
                <span class="action-icons">
                    <a href="javascript:STUDIP.ForumPP.moveThreadDialog('<?= $entry['topic_id'] ?>');">
                        <?= Assets::img('icons/16/blue/move_right/folder-full.png',
                            array('class' => 'move-thread', 'title' => 'Dieses Thema verschieben')) ?>
                    </a>
                    
                    <a href="<?= PluginEngine::getURL('forumpp/index/delete_entry/' . $entry['topic_id']) ?>"
                       onClick="return confirm('<?= _('Möchten Sie dieses Thema wirklich löschen?') ?>')">
                        <?= Assets::img('icons/16/blue/trash.png',
                            array('class' => 'move-thread', 'title' => 'Dieses Thema löschen')) ?>
                    </a>

                    <div id="dialog_<?= $entry['topic_id'] ?>" style="display: none" title="<?= _('Bereich, in den dieser Thread verschoben werden soll:') ?>">
                        <? $path = ForumPPEntry::getPathToPosting($entry['topic_id']);
                        $parent = array_pop(array_slice($path, sizeof($path) - 2, 1)); ?>

                        <? foreach ($areas['list'] as $area_id => $area): ?>
                        <? if ($area_id != $parent['id']) : ?>
                        <div style="font-size: 16px; margin-bottom: 5px;">
                            <a href="<?= PluginEngine::getLink('/forumpp/index/move_thread/'. $entry['topic_id'].'/'. $area_id) ?>">
                            <?= Assets::img('icons/16/yellow/arr_2right.png') ?>
                            <?= $area['name'] ?>
                            </a>
                        </div>
                        <? endif ?>
                        <? endforeach ?>
                    </div>
                </span>
                <? endif ?>

                <br/>

                <?= _("von") ?>
                <a href="<?= UrlHelper::getLink('about.php?username='. get_username($entry['owner_id'])) ?>">
                    <?= htmlReady($entry['author']) ?>
                </a>
                <?= _("am") ?> <?= strftime($time_format_string_short, (int)$entry['mkdate']) ?>
                <br>

                <? if ($entry['content_short'] && strlen($entry['content'] > strlen($entry['content_short']))) : ?>
                    <?= $entry['content_short'] ?>...
                <? else : ?>
                    <?= $entry['content_short'] ?>
                <? endif ?>
            </div>
        </td>

        <td align="center" valign="top" class="areaentry2">
            <br>
            <?= $entry['num_postings'] ?>
        </td>

        <td align="left" valign="top" class="areaentry2">
            <? if (is_array($entry['last_posting'])) : ?>
            <?= _("von") ?>
            <a href="<?= UrlHelper::getLink('about.php?username='. $entry['last_posting']['username']) ?>">
                    <?= htmlReady($entry['last_posting']['user_fullname']) ?>
            </a><br>
            <?= _("am") ?> <?= strftime($time_format_string_short, (int)$entry['last_posting']['date']) ?>
            <a href="<?= PluginEngine::getLink('/forumpp/index/index/'. $entry['last_posting']['topic_id']) ?>#<?= $entry['last_posting']['topic_id'] ?>" alt="<?= $infotext ?>" title="<?= $infotext ?>">
                <?= Assets::img('icons/16/blue/link-intern.png', array('title' => $infotext = _("Direkt zum Beitrag..."))) ?>
            </a>
            <? else: ?>
            <br>
            <?= _('keine Beiträge') ?>
            <? endif; ?>
        </td>
        <td class="areaborder"> </td>
    </tr>
    <? endforeach; ?>
    </tbody>

    <tfoot>
        <!-- bottom border -->
        <tr>
            <td class="areaborder" colspan="7">
                <span class="corners-bottom"><span></span></span>
            </td>
        </tr>
        <tr>
            <td colspan="6">&nbsp;</td>
        </tr>
    </tfoot>
</table>
<? endforeach ?>
</div>
