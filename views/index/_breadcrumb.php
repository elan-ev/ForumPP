<div style="float: left">
    <?= _('Sie befinden sich hier:') ?>
    <span style="font-weight: bold">
    <? foreach (ForumPPEntry::getPathToPosting($topic_id) as $pos => $path_part) : ?>
        <? if ($pos > 0) : ?> &bullet; <? endif ?>
        <a href="<?= PluginEngine::getLink('forumpp/index/index/' . $path_part['id']) ?>"><?= htmlReady($path_part['name']) ?></a>
    <? endforeach ?>
    <? if ($section) :
        switch ($section) :
            case 'favorites': $section_name = _('Favoriten');break;
            case 'newest':    $section_name = _('neuste Beiträge');break;
            case 'latest':    $section_name = _('letzte Beiträge');break;
            case 'search':    $section_name = _('Suche');break;
        endswitch;

        if ($section_name) : ?>
        &bullet;
        <a href="<?= PluginEngine::getLink('forumpp/index/index/' . $section) ?>"><?= htmlReady($section_name) ?></a>
        <? endif ?>
    <? endif ?>
    </span>
</div>

<div style="float: right; padding-right: 10px;">
    <? if ($constraint['depth'] > 0) : ?>
    <?= $GLOBALS['template_factory']->render('shared/pagechooser', array(
        'page'         => ForumPPHelpers::getPage() + 1,
        'num_postings' => $number_of_entries,
        'perPage'      => ForumPPEntry::POSTINGS_PER_PAGE,
        'pagelink'     => PluginEngine::getLink('forumpp/index/goto_page/'. $topic_id .'/%s')
    )); ?>
    <? endif ?>
</div>
<br style="clear: both"><br>