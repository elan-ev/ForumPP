<? if ($breadcrumb) : ?>
<div style="float: left">
    <?= _('Sie befinden sich hier:') ?>
    <span style="font-weight: bold">
    <? $first = true ?>
    <? foreach (ForumPPEntry::getPathToPosting($topic_id) as $path_part) : ?>
        <? if (!$first) : ?> &gt;&gt; <? endif ?>
        <a href="<?= PluginEngine::getLink('forumpp/index/index/' . $path_part['id']) ?>"><?= htmlReady($path_part['name']) ?></a>
        <? $first = false ?>
    <? endforeach ?>
    <? if ($section == 'search') : ?>
        &gt;&gt;
        <a href="<?= PluginEngine::getLink('forumpp/index/index/search') ?>"><?= _('Suche') ?></a>
    <? endif ?>
    </span>
</div>
<? endif ?>