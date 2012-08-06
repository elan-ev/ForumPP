<div style="float: left" id="tutorBreadcrumb">
    <?= _('Sie befinden sich hier:') ?>
    <span style="font-weight: bold">
    <? if ($section == 'search') : ?>
        <a href="<?= PluginEngine::getURL('forumpp/index/goto_page/'. $topic_id .'/'. $section 
            .'/1/?searchfor=' . $searchfor . (!empty($options) ? '&'. http_build_query($options) : '' )) ?>">
            <?= _('Suchergebnisse') ?>
        </a>
    <? elseif ($section == 'latest') : ?>
        <a href="<?= PluginEngine::getURL('forumpp/index/latest') ?>">
            <?= _('Neue Beiträge') ?>
        </a>
    <? elseif ($section == 'favorites') : ?>
        <a href="<?= PluginEngine::getURL('forumpp/index/latest') ?>">
            <?= _('Gemerkte Beiträge') ?>
        </a>        
    <? else: ?>

        <? $first = true ?>
        <? foreach (ForumPPEntry::getPathToPosting($topic_id) as $path_part) : ?>
            <? if (!$first) : ?> &gt;&gt; <? endif ?>
            <a href="<?= PluginEngine::getLink('forumpp/index/index/' . $path_part['id']) ?>">
                <?= htmlReady(ForumPPEntry::killFormat($path_part['name'])) ?>
            </a>
            <? $first = false ?>
        <? endforeach ?>
    <? endif ?>
    </span>        
</div>