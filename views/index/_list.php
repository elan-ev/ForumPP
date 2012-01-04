<? if ($constraint['depth'] == 0) : /* main areas */?>
<script>
    jQuery(document).ready(function() {
        STUDIP.ForumPP.initAreas();
    });
</script>
<? endif ?>

<div id="sortable_areas">
<? foreach ($list as $category_id => $entries) : ?>
<table cellspacing="0" cellpadding="2" border="0" width="100%" class="forum <?= $has_perms ? 'movable' : '' ?>" data-category-id="<?= $category_id ?>">
    <thead>
    <tr>
        <td class="forum_header" colspan="3" align="left">
            <span class="corners-top"></span>
            <span class="heading">
                <? if (!$category_id) : ?>
                <?= strtoupper(_('Themen')) ?>
                <? else: ?>
                <?= strtoupper(($category_id == 'Allgemein') ? _('Allgemein') : $categories[$category_id]) ?>&nbsp;
                <? endif ?>
            </span>
        </td>

        <td class="forum_header" width="1%">
            <span class="no-corner"></span>
            <span class="heading"><?= _("BEITR&Auml;GE") ?></span>
        </td>

        <td class="forum_header" width="30%" colspan="2">
            <span class="corners-top-right"></span>
            <span class="heading" style="float: left"><?= _("LETZTE ANTWORT") ?></span>
            <? if ($has_perms) : ?>
            <span style="float: right; padding-right: 10px;">
                <a href="javascript:STUDIP.ForumPP.deleteCategory('<?= $category_id ?>', '<?= $categories[$category_id] ?>')">
                    <?= Assets::img('icons/16/blue/trash.png') ?>
                </a>
            </span>
            <? endif ?>
        </td>
    </tr>
    </thead>

    
    <tbody class="sortable">
    <? foreach ($entries as $area) :
        $topic_id = $area['topic_id'];

        if ($constraint['depth'] >= 1) :
            $topic_id = ($area['last_posting']['topic_id'] ? $area['last_posting']['topic_id'] : $area['topic_id']);
        endif ?>
    
    <tr data-area-id="<?= $topic_id ?>" <?= $has_perms ? 'class="movable"' : '' ?>>
        <td class="areaborder">&nbsp;</td>
        <td class="areaentry icon" width="1%" valign="top" align="center">
            <?= Assets::img('icons/16/black/forum.png') ?>
        </td>
        <td class="areaentry" valign="top">
            <div style="position: relative;">
                <a href="<?= PluginEngine::getLink('forumpp/index/index/'. $topic_id .'#'. $topic_id) ?>">
                    <span class="areaname"><?= $area['name'] ?></span>
                </a>

                <? if ($constraint['depth'] == 0 && $has_rights) : /* main areas */?>
                <span class="action-icons" style="position: absolute; right: 10px; top: 0px; display: none;">
                    <?= Assets::img('icons/16/blue/edit.png', array('class' => 'edit-area', 'data-area-id' => $topic_id)) ?>
                    <?= Assets::img('icons/16/blue/trash.png', array('class' => 'delete-area', 'data-area-id' => $topic_id)) ?>
                </span>
                <? endif ?>

                <br/>

                <?= _("von") ?>
                <a href="<?= UrlHelper::getLink('about.php?username='. get_username($area['owner_id'])) ?>">
                    <?= htmlReady($area['author']) ?>
                </a>
                <?= _("am") ?> <?= strftime($time_format_string_short, (int)$area['mkdate']) ?>
                <br>

                <? if ($this->constraint['depth'] == 1) : ?>
                    <? if ($area['content_short'] && strlen($area['content'] > strlen($area['content_short']))) : ?>
                        <?= $area['content_short'] ?>...
                    <? else : ?>
                        <?= $area['content_short'] ?>
                    <? endif ?>
                <? else: ?>
                <?= $area['content'] ?>
                <? endif ?>
            </div>
        </td>

        <td width="40" align="center" valign="top" class="areaentry2">
            <br>
            <?= ($area['num_postings'] > 0) ? ($area['num_postings'] - 1) : 0 ?>
        </td>

        <td width="30%" align="left" valign="top" class="areaentry2">
            <? if (is_array($area['last_posting'])) : ?>
            <?= _("von") ?>
            <a href="<?= UrlHelper::getLink('about.php?username='. $area['last_posting']['username']) ?>">
                    <?= htmlReady($area['last_posting']['user_fullname']) ?>
            </a><br>
            <?= _("am") ?> <?= strftime($time_format_string_short, (int)$area['last_posting']['date']) ?>
            <a href="<?= PluginEngine::getLink('/forumpp/index/index/'. $area['last_posting']['topic_id']) ?>#<?= $area['last_posting']['topic_id'] ?>" alt="<?= $infotext ?>" title="<?= $infotext ?>">
                <?= Assets::img('icons/16/blue/link-intern.png', array('title' => $infotext = _("Direkt zum Beitrag..."))) ?>
            </a>
            <? else: ?>
            <br>
            <?= _('keine Beiträge') ?>
            <? endif; ?>
        </td>
        <td class="areaborder">&nbsp;</td>
    </tr>
    <? endforeach; ?>
    </tbody>

    <tfoot>
    <? if ($category_id && $has_perms) : ?>
    <tr>
        <td class="areaborder" colspan="7">
            <div class="add_area">+</div>
            <form class="add_area_form" style="display: none" method="post" action="<?= PluginEngine::getLink('/forumpp/index/add_area/' . $category_id) ?>">
                <?= CSRFProtection::tokenTag() ?>
                <input type="text" name="name" size="50" placeholder="Name des neuen Bereiches" required>
                <input type="image" <?= makebutton('hinzufuegen', 'src') ?>>
                <a href="javascript:STUDIP.ForumPP.cancelAddArea()"><?= makebutton('abbrechen') ?></a>
            </form>
        </td>
    </tr>    
    <? endif ?>


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
<? endforeach; ?>
</div>

<div id="question" style="display: none">
    <span id="question_delete_area" style="display: none"><?= _('Sind sie sicher, dass Sie den Bereich <%- area %> löschen möchten? '
         . 'Es werden auch alle Beiträge in diesem Bereich gelöscht!') ?></span>
    <span id="question_delete_category" style="display: none"><?= _('Sind sie sicher, dass Sie die Kategorie <%- category %> entfernen möchten? '
         . 'Alle Bereiche werden dann nach "Allgemein" verschoben!') ?></span>
    <?= $GLOBALS['template_factory']->open('shared/question')->render(array(
        'question'        => '',
        'approvalLink'    => "javascript:STUDIP.ForumPP.approveDelete()",
        'disapprovalLink' => "javascript:STUDIP.ForumPP.disapproveDelete()"
    )) ?>
    <? /* createQuestion() */ ?>
</div>