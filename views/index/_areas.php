<br>
<div id="sortable_areas">
<? foreach ($list as $category_id => $entries) : ?>
<table class="forum <?= $has_perms && $category_id != $seminar_id ? 'movable' : '' ?>" data-category-id="<?= $category_id ?>">
    <thead id="tutorCategory">
    <tr>
        <td class="forum_header <?= ForumPPPerm::has('sort_category', $seminar_id) && $category_id != $seminar_id ? 'handle' : '' ?>" colspan="3">
            <a name="cat_<?= $category_id ?>"></a>
            <span class="corners-top"></span>
            <span class="heading">
                <? if (!$category_id) : ?>
                <?= _('Themen') ?>
                <? else: ?>
                <span class="category_name">
                    <?= $categories[$category_id] ?>
                </span>
                <? endif ?>
            </span>
            <span class="heading_edit" style="display: none; margin-left: 5px;">
                <input type="text" name="name" size="40" value="<?= $categories[$category_id] ?>">

                <?= Studip\LinkButton::createAccept('Kategorie speichern', "javascript:STUDIP.ForumPP.saveCategoryName('". $category_id ."')") ?>
                <?= Studip\LinkButton::createCancel('Abbrechen', "javascript:STUDIP.ForumPP.cancelEditCategoryName('". $category_id ."')") ?>
            </span>
        </td>

        <td class="forum_header">
            <span class="no-corner"></span>
            <span class="heading"><?= _("Beiträge") ?></span>
        </td>

        <td class="forum_header" colspan="2">
            <span class="corners-top-right"></span>
            <span class="heading" style="float: left"><?= _("letzte Antwort") ?></span>
            <? if (ForumPPPerm::has('edit_category', $seminar_id) || ForumPPPerm::has('remove_category', $seminar_id)) : ?>
            <span style="float: right; padding-right: 5px;" id="tutorCategoryIcons">
                <? if ($category_id == $seminar_id) : ?>
                <?= Assets::img('icons/16/blue/info.png', array(
                    'onClick' => "alert('" . _('Vordefinierte Kategorie, kann nicht bearbeitet oder gelöscht werden.' . '\n'
                        . 'Für Nutzer/innen ohne Moderationsrechte taucht diese Kategorie nur auf, wenn sie Bereiche enthält.') . "')",
                    'style'   => 'cursor: pointer')) ?>
                <? else : ?>
                    <? if (ForumPPPerm::has('edit_category', $seminar_id)) : ?>
                    <a href="javascript:STUDIP.ForumPP.editCategoryName('<?= $category_id ?>')">
                        <?= Assets::img('icons/16/blue/edit.png', array('title' => 'Name der Kategorie ändern')) ?>
                    </a>
                    <? endif ?>

                    <? if(ForumPPPerm::has('remove_category', $seminar_id)) : ?>
                    <a href="javascript:STUDIP.ForumPP.deleteCategory('<?= $category_id ?>', '<?= $categories[$category_id] ?>')">
                        <?= Assets::img('icons/16/blue/trash.png', array('title' => 'Kategorie entfernen')) ?>
                    </a>
                    <? endif ?>
                <? endif ?>
            </span>
            <? endif ?>
        </td>
    </tr>
    </thead>


    <tbody class="sortable">
    <!-- this row allows dropping on otherwise empty categories -->
    <tr class="sort-disabled">
        <td class="areaborder" style="height: 5px"colspan="7"> </td>
    </tr>

    <? if (!empty($entries)) foreach ($entries as $entry) :
        $jump_to_topic_id = $entry['topic_id']; ?>

    <tr id="tutorArea" data-area-id="<?= $entry['topic_id'] ?>" <?= ($has_perms) ? 'class="movable"' : '' ?>>

        <td class="areaborder"> </td>

        <td class="areaentry icon">
            <? if (ForumPPPerm::has('sort_area', $seminar_id)) : ?>
            <img src="<?= $picturepath ?>/move.png" class="handle" id="tutorMoveArea">
            <? endif ?>

            <? if ($entry['chdate'] >= $visitdate && $entry['owner_id'] != $GLOBALS['user']->id): ?>
                <?= Assets::img('icons/16/red/new/forum.png', array(
                    'title' => _('Dieser Eintrag ist neu!'),
                    'id'    => 'tutorNotificationIcon'
                )) ?>
            <? else : ?>
                <? $num_postings = ForumPPVisit::getCount($entry['topic_id'], $visitdate) ?>
                <? $text = ForumPPHelpers::getVisitText($num_postings, $entry['topic_id'], $constraint['depth']) ?>
                <? if ($num_postings > 0) : ?>
                    <?= Assets::img('icons/16/red/forum.png', array(
                        'title' => $text,
                        'id'    => 'tutorNotificationIcon'
                    )) ?>
                <? else : ?>
                    <?= Assets::img('icons/16/black/forum.png', array(
                        'title' => $text,
                        'id'    => 'tutorNotificationIcon'
                    )) ?>
                <? endif ?>
            <? endif ?>
        </td>
        <td class="areaentry">
            <div style="position: relative;">
                <span class="areadata">
                    <a href="<?= PluginEngine::getLink('forumpp/index/index/'. $jump_to_topic_id .'#'. $jump_to_topic_id) ?>">
                        <span class="areaname"><?= $entry['name'] ?></span>
                        <br>
                    </a>
                    <div class="areacontent"><?= htmlReady(ForumPPEntry::killEdit($entry['content_raw'])) ?></div>

                    <?= _("von") ?>
                    <a href="<?= UrlHelper::getLink('about.php?username='. get_username($entry['owner_id'])) ?>">
                        <?= htmlReady($entry['author']) ?>
                    </a>
                    <?= _("am") ?> <?= strftime($time_format_string_short, (int)$entry['mkdate']) ?>
                </span>

                <? if (ForumPPPerm::has('edit_area', $seminar_id)) : ?>
                <span class="areaname_edit" style="display: none; text-align: center;">
                    <div style="width: 90%">
                        <input type="text" name="name" size="20" style="width: 100%;" value="<?= $entry['name'] ?>" onClick="jQuery(this).focus()"><br>
                        <textarea name="content" style="height: 3em;" onClick="jQuery(this).focus()"><?= $entry['content_raw'] ?></textarea>

                        <span class="large_screen">
                            <?= Studip\LinkButton::createAccept('Speichern', "javascript:STUDIP.ForumPP.saveArea('". $entry['topic_id'] ."')") ?>
                            <?= Studip\LinkButton::createCancel('Abbrechen', "javascript:STUDIP.ForumPP.cancelEditArea('". $entry['topic_id'] ."')") ?>
                        </span>
                        
                        <span class="small_screen">
                            <?= Assets::img('icons/16/green/accept.png', array(
                                'title'   => _('Speichern'),
                                'onClick' => "STUDIP.ForumPP.saveArea('". $entry['topic_id'] ."')"
                            )) ?>
                            <?= Assets::img('icons/16/red/decline.png', array(
                                'title'   => _('Speichern'),
                                'onClick' => "STUDIP.ForumPP.cancelEditArea('". $entry['topic_id'] ."')"
                            )) ?>
                        </span>
                    </div>
                </span>
                <? endif ?>

                
                <span class="action-icons" id="tutorAreaIcons">
                    <? if (ForumPPPerm::has('edit_area', $seminar_id)) : ?>
                    <a href="javascript:STUDIP.ForumPP.editArea('<?= $entry['topic_id'] ?>');">
                        <?= Assets::img('icons/16/blue/edit.png',
                            array('class' => 'edit-area', 'title' => 'Name/Beschreibung des Bereichs ändern')) ?>
                    </a>
                    <? endif ?>
                    
                    <? if (ForumPPPerm::has('remove_area', $seminar_id)) : ?>
                    <a href="javascript:STUDIP.ForumPP.deleteArea(this, '<?= $entry['topic_id'] ?>')">
                        <?= Assets::img('icons/16/blue/trash.png',
                            array('class' => 'delete-area', 'title' => 'Bereich mitsamt allen Einträgen löschen!')) ?>
                    </a>
                    <? endif ?>
                </span>
            </div>
        </td>

        <td class="areaentry postings">
            <span id="tutorNumPostings">
                <?= ($entry['num_postings'] > 0) ? ($entry['num_postings'] - 1) : 0 ?>
            </span>
        </td>

        <td class="areaentry answer">
            <? if (is_array($entry['last_posting'])) : ?>
            <?= _("von") ?>
            <a href="<?= UrlHelper::getLink('about.php?username='. $entry['last_posting']['username']) ?>">
                    <?= htmlReady($entry['last_posting']['user_fullname']) ?>
            </a><br>
            <?= _("am") ?> <?= strftime($time_format_string_short, (int)$entry['last_posting']['date']) ?>
            <a href="<?= PluginEngine::getLink('/forumpp/index/index/'. $entry['last_posting']['topic_id']) ?>#<?= $entry['last_posting']['topic_id'] ?>" alt="<?= $infotext ?>" title="<?= $infotext ?>">
                <?= Assets::img('icons/16/blue/link-intern.png', array('title' => $infotext = _("Direkt zum Beitrag..."), 'id' => 'tutorLatestAnswer')) ?>
            </a>
            <? else: ?>
            <br>
            <?= _('keine Antworten') ?>
            <? endif; ?>
        </td>
        <td class="areaborder"> </td>
    </tr>
    <? endforeach; ?>
    </tbody>

    <tfoot>
    <? if ($category_id && ForumPPPerm::has('add_area', $seminar_id)) : ?>
    <tr class="add_area">
        <td class="areaborder" colspan="7">
            <div class="add_area" title="<?= _('Neuen Bereich zu dieser Kategorie hinzufügen.') ?>">
                <?= Assets::img('icons/16/black/plus.png', array('id' => 'tutorAddArea')) ?>
            </div>
        </td>
    </tr>

    <tr style="display: none" class="new_area">
        <td class="areaborder"></td>
        <td class="areaentry"></td>
        <td class="areaentry">
            <form class="add_area_form" style="display: bgnone" method="post" action="<?= PluginEngine::getLink('/forumpp/index/add_area/' . $category_id) ?>">
                <?= CSRFProtection::tokenTag() ?>
                <input type="text" name="name" size="50" style="width: 99%;" placeholder="<?= _('Name des neuen Bereiches') ?>" required><br>
                <textarea name="content" style="height: 3em; width: 99%;" placeholder="<?= _('Optionale Beschreibung des neuen Bereiches') ?>"></textarea>

                <?= Studip\Button::create('Bereich hinzufügen') ?>
                <?= Studip\LinkButton::createCancel('Abbrechen', "javascript:STUDIP.ForumPP.cancelAddArea()") ?>
            </form>
        </td>
        <td class="areaentry postings">0</td>
        <td class="areaentry answer"><br><?= _('keine Antworten') ?></td>
        <td class="areaborder"></td>
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
<? endforeach ?>
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

<?= $this->render_partial('joyride/areas.php') ?>