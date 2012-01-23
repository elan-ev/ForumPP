<script>
    jQuery(document).ready(function() {
        // set seminar-id
        STUDIP.ForumPP.seminar_id = '<?= $seminar_id ?>';
        
        // basic initializations for the whole forum
        STUDIP.ForumPP.init();
        
        <? if ($constraint['depth'] == 0) : /* main areas */?>
        // additional initializations for the area-view
        STUDIP.ForumPP.initAreas();
        <? endif ?>
    });
</script>
<div id="forumpp">
<?
$infobox_content[] = array(
    'kategorie' => _('Ansicht'),
    'eintrag'   => array(
        array(
            'icon' => $section == 'forum' ? 'icons/16/red/arr_1right.png' : 'icons/16/grey/arr_1right.png',
            'text' => '<a href="'. PluginEngine::getLink('forumpp/index') .'">'. _('Forum') .'</a>'
        ),
        array(
            'icon' => $section == 'favorites' ? 'icons/16/red/arr_1right.png' : 'icons/16/grey/arr_1right.png',
            'text' => '<a href="'. PluginEngine::getLink('forumpp/index/favorites') .'">'. _('Favoriten') .'</a>'
        ),
        array(
            'icon' => $section == 'newest' ? 'icons/16/red/arr_1right.png' : 'icons/16/grey/arr_1right.png',
            'text' => '<a href="'. PluginEngine::getLink('forumpp/index/newest') .'">'. _('neue Beiträge') .'</a>'
        ),
        array(
            'icon' => $section == 'latest' ? 'icons/16/red/arr_1right.png' : 'icons/16/grey/arr_1right.png',
            'text' => '<a href="'. PluginEngine::getLink('forumpp/index/latest') .'">'. _('letzte Beiträge') .'</a>'
        )
    )
);

$infobox_content[] = array(
    'kategorie' => _('Suche'),
    'eintrag'   => array(
        array(
            'icon' => $section == 'search' ? 'icons/16/red/arr_1right.png' : 'icons/16/grey/arr_1right.png',
            'text' => $this->render_partial('index/_search')
        )
    )
);

$infobox_content[] = array(
    'kategorie' => _('Version'),
    'eintrag'   => array(
        array(
            'icon' => 'icons/16/grey/info.png',
            'text' => 'Installierte Version: ' . ForumPPVersion::getCurrent()
        ),

        array(
            'icon' => 'icons/16/grey/info.png',
            'text' => 'Neueste Version: ' . ForumPPVersion::getLatest()
        )        
    )
);

$infobox = array('picture' => 'infobox/schedules.jpg', 'content' => $infobox_content);
?>

<!-- Breadcrumb navigation -->
<?= $this->render_partial('index/_breadcrumb') ?>

<!-- Message area -->
<div id="message_area">
    <? $this->render_partial('messages') ?>
</div>

<? if ($no_entries) : ?>
    <?= MessageBox::info(_('In dieser Ansicht befinden sich zur Zeit keine Beiträge.')) ?>
<? elseif ($no_search_results) : ?>
    <?= MessageBox::info(_('Es wurden keine Beiträge gefunden die zu Ihren Suchkriterien passen!')) ?>
<? endif ?>

<? if (!empty($list)) : ?>
    <?= $this->render_partial('index/_list') ?>
<? elseif ($constraint['depth'] == 0 && $section == 'forum') : ?>
    <?= MessageBox::info(_('Dieses Forum wurde noch nicht eingerichtet. '.
            'Es gibt bisher keine Bereiche, in denen man ein Thema erstellen könnte.')); ?>
<? endif ?>

<? if (!empty($postings)) : ?>
    <?= $this->render_partial('index/_postings') ?>
<? endif ?>

<? if ($constraint['depth'] == 0) : ?>
    <?= $this->render_partial('index/_new_category') ?>
<? else : ?>
    <? if (!$flash['edit_entry']) : ?>
    <div style="text-align: center">
        <div id="new_entry_button" <?= $this->flash['new_entry_title'] ? 'style="display: none"' : '' ?>>
            <?= Studip\Button::create('Neues Thema erstellen', array('onClick' => "jQuery('#new_entry_button').hide();jQuery('#new_entry_box').show();")) ?>
        </div>

        <div id="new_entry_box" <?= $this->flash['new_entry_title'] ? '' : 'style="display: none"' ?>>
            <?= $this->render_partial('index/_new_entry') ?>
        </div>
    </div>
    <? endif ?>
    
<? endif ?>
</div>