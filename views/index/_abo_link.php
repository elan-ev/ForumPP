<? $url = "javascript:STUDIP.ForumPP.loadAction('#abolink', '"
    . (ForumPPAbo::has($constraint['topic_id']) ? 'remove_' : '') 
    . 'abo/'. $constraint['topic_id'] ."')" ?>

<? if (!ForumPPAbo::has($constraint['topic_id'])) : ?>
    <?= Studip\LinkButton::create($constraint['area'] ? _('Diesen Bereich abonnieren') : _('Dieses Thema abonnieren'), $url,
        array('title' => _('Wenn sie diesen Bereich abonnieren, erhalten Sie eine '
            . 'Stud.IP-interne Nachricht sobald in diesem Bereich '
            . 'ein neuer Beitrag erstellt wurde.'))) ?>
<? else : ?>
    <?= Studip\LinkButton::create(_('Nicht mehr abonnieren'), $url) ?>
<? endif; ?>