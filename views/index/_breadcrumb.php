<? if ($breadcrumb) : ?>
<div style="float: left">
    <?= _('Sie befinden sich hier:') ?>
    <span style="font-weight: bold">
    <? foreach (ForumPPEntry::getPathToPosting($topic_id) as $pos => $path_part) : ?>
        <? if ($pos > 0) : ?> &gt;&gt; <? endif ?>
        <a href="<?= PluginEngine::getLink('forumpp/index/index/' . $path_part['id']) ?>"><?= htmlReady($path_part['name']) ?></a>
    <? endforeach ?>
    <? if ($section == 'search') : ?>
        &gt;&gt;
        <a href="<?= PluginEngine::getLink('forumpp/index/index/search') ?>"><?= _('Suche') ?></a>
    <? endif ?>
    </span>
</div>
<? endif ?>

<? if (!$section) $section = 'index'; ?>
<div style="float: right; padding-right: 10px;">
    <? if ($constraint['depth'] > 0 || !$breadcrumb) : ?>
    <?= $GLOBALS['template_factory']->render('shared/pagechooser', array(
        'page'         => ForumPPHelpers::getPage() + 1,
        'num_postings' => $number_of_entries,
        'perPage'      => ForumPPEntry::POSTINGS_PER_PAGE,
        'pagelink'     => PluginEngine::getLink('forumpp/index/goto_page/'. $topic_id .'/'. $section .'/%s')
    )); ?>
    <? endif ?>
</div>
<br style="clear: both">