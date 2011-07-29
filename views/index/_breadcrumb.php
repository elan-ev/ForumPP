<?= _('Sie befinden sich hier:') ?>
<span style="font-weight: bold">
<? foreach (ForumPPEntry::getPathToPosting($topic_id) as $pos => $path_part) : ?>
    <? if ($pos > 0) : ?> &bullet; <? endif ?>
    <a href="<?= PluginEngine::getLink('forumpp/index/index/' . $path_part['id']) ?>"><?= $path_part['name'] ?></a>
<? endforeach ?>
</span>
<br><br>