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
<? elseif (empty($list) && empty($postings) && !$flash['new_entry']) : ?>
    <? /* if ($constraint['depth'] == 0) : ?>
    <?= MessageBox::info(sprintf(_('Es existieren bisher keine Bereiche. '
            . 'Möchten Sie einen neuen Bereich %serstellen%s?'),
            '<a href="'. PluginEngine::getLink('forumpp/index/new_entry/'. $topic_id) .'">', '</a>')); ?>
    <? elseif ($constraint['depth'] == 1) : ?>
    <?= MessageBox::info(sprintf(_('Es existieren bisher keine Themen in diesem Bereich. '
            . 'Möchten Sie ein neues Thema %serstellen%s?'),
            '<a href="'. PluginEngine::getLink('forumpp/index/new_entry/'. $topic_id) .'">', '</a>')); ?>
    <? endif */ ?>
<? endif ?>

<? if (!empty($list)) : ?>
    <?= $this->render_partial('index/_list') ?>
<? elseif ($constraint['depth'] == 0) : ?>
    <?= MessageBox::info(_('Dieses Forum wurde noch nicht eingerichtet. '.
            'Es gibt bisher keine Bereiche, in denen man ein Thema erstellen könnte.')); ?>
<? endif ?>

<? if (!empty($postings)) : ?>
    <?= $this->render_partial('index/_postings') ?>
<? endif ?>

<? if ($constraint['depth'] == 0) : ?>
    <?= $this->render_partial('index/_new_category') ?>
<? else : ?>
    <?= $this->render_partial('index/_new_entry') ?>
<? endif ?>