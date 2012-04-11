<script>
    jQuery(document).ready(function() {
        // set seminar-id
        STUDIP.ForumPP.seminar_id = '<?= $seminar_id ?>';
        STUDIP.ForumPP.init();
    });
</script>

<!-- set a CSS "namespace" for forumpp -->
<div id="forumpp">
<? 
if (ForumPPPerm::has('search', $seminar_id)) :
    $infobox_content[] = array(
        'kategorie' => _('Suche'),
        'eintrag'   => array(
            array(
                'icon' => $section == 'search' ? 'icons/16/red/arr_1right.png' : 'icons/16/grey/arr_1right.png',
                'text' => $this->render_partial('index/_search')
            )
        )
    );
endif;

if (ForumPPPerm::has('version', $seminar_id)) :
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
endif;

if ($constraint['depth'] > 0) :
   $abo_link = '<span id="abolink">' . $this->render_partial('index/_abo_link', compact('constraint')) . '</span>';

$infobox_content[] = array(
    'kategorie' => _('Aktionen'),
    'eintrag'   => array(
        array(
            'icon' => 'icons/16/blue/link-intern.png',
            'text' => $abo_link
        )
    )
);
endif;

// show the infobox only if it contains elements
if (!empty($infobox_content)) :
    $infobox = array('picture' => 'infobox/schedules.jpg', 'content' => $infobox_content);
endif;
?>

<!-- Breadcrumb navigation -->
<?= $this->render_partial('index/_breadcrumb') ?>

<!-- Seitenw�hler (bei Bedarf) am oberen Rand anzeigen -->
<div style="float: right; padding-right: 10px;">
    <? if ($constraint['depth'] > 0 || !$breadcrumb) : ?>
    <?= $pagechooser = $GLOBALS['template_factory']->render('shared/pagechooser', array(
        'page'         => ForumPPHelpers::getPage() + 1,
        'num_postings' => $number_of_entries,
        'perPage'      => ForumPPEntry::POSTINGS_PER_PAGE,
        'pagelink'     => PluginEngine::getLink('forumpp/index/goto_page/'. $topic_id .'/'. $section .'/%s')
    )); ?>
    <? endif ?>
</div>
<br style="clear: both">

<!-- Message area -->
<div id="message_area">
    <? $this->render_partial('messages') ?>
</div>

<? if ($no_entries) : ?>
    <?= MessageBox::info(_('In dieser Ansicht befinden sich zur Zeit keine Beitr�ge.')) ?>
<? elseif ($no_search_results) : ?>
    <?= MessageBox::info(_('Es wurden keine Beitr�ge gefunden, die zu Ihren Suchkriterien passen!')) ?>
<? endif ?>

<!-- Bereiche / Themen / Beitr�ge -->
<? if (!empty($list)) : ?>
    <!-- Bereiche / Themen darstellen -->
    <? if ($constraint['depth'] == 0) : ?>
    <?= $this->render_partial('index/_areas') ?>
    <? else : ?>
    <?= $this->render_partial('index/_threads') ?>
    <? endif ?>
<? elseif ($constraint['depth'] == 0 && $section == 'forum') : ?>
    <?= MessageBox::info(_('Dieses Forum wurde noch nicht eingerichtet. '.
            'Es gibt bisher keine Bereiche, in denen man ein Thema erstellen k�nnte.')); ?>
<? endif ?>

<? if (!empty($postings)) : ?>
    <!-- Beitr�ge f�r das ausgew�hlte Thema darstellen -->
    <?= $this->render_partial('index/_postings') ?>
<? endif ?>

<!-- Seitenw�hler (bei Bedarf) am unteren Rand anzeigen -->
<? if ($pagechooser) : ?>
<div style="float: right; padding-right: 10px;">
    <?= $pagechooser ?>
</div>
<? endif ?>

<!-- Erstellen eines neuen Elements (Kateogire, Thema, Beitrag) -->
<? if ($constraint['depth'] == 0) : ?>
    <? if (ForumPPPerm::has('add_category', $seminar_id)) : ?>
        <?= $this->render_partial('index/_new_category') ?>
    <? endif ?>
<? else : ?>
    <? if (!$flash['edit_entry'] && ForumPPPerm::has('add_entry', $seminar_id)) : ?>
    <? $constraint['depth'] == 1 ? $button_face = _('Neues Thema erstellen') : $button_face = _('Antworten') ?>
    <div style="text-align: center">
        <div id="new_entry_button" <?= $this->flash['new_entry_title'] ? 'style="display: none"' : '' ?>>
            <?= Studip\Button::create($button_face) ?>
        </div>

        <div id="new_entry_box" <?= $this->flash['new_entry_title'] ? '' : 'style="display: none"' ?>>
            <br style="clear: both">
            <?= $this->render_partial('index/_new_entry') ?>
        </div>
    </div>
    <? endif ?>

<? endif ?>
</div>

<!-- Mail-Notifikationen verschicken (soweit am Ende der Seite wie m�glich!) -->
<? if ($flash['notify']) :
    ForumPPAbo::notify($flash['notify']);
endif ?>
