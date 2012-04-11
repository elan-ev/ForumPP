<a href="javascript:STUDIP.ForumPP.loadAction('#abolink', '<?= ForumPPAbo::has($constraint['topic_id']) ? 'remove_' : '' ?>abo/<?= $constraint['topic_id'] ?>')">
<? if (!ForumPPAbo::has($constraint['topic_id'])) : ?>
    <?= $constraint['area'] ? _('Diesen Bereich abonnieren') : _('Dieses Thema abonnieren') ?>
<? else : ?>
    <?= _('Abonniert. Klicken, um Abonnement aufzuheben.') ?>
<? endif; ?>
</a>