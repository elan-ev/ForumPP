<div style="float: left">
    <?= _('Sie befinden sich hier:') ?>
    <span style="font-weight: bold">
    <? foreach (ForumPPEntry::getPathToPosting($topic_id) as $pos => $path_part) : ?>
        <? if ($pos > 0) : ?> &bullet; <? endif ?>
        <a href="<?= PluginEngine::getLink('forumpp/index/index/' . $path_part['id']) ?>"><?= $path_part['name'] ?></a>
    <? endforeach ?>
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