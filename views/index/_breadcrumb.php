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