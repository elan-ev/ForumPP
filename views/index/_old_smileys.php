<?php
require_once('lib/classes/smiley.class.php');
$sm = new Smiley(false);
?>
<div style="text-align: center">
    <? if ($sm->read_favorite() && sizeof($sm->my_smiley) > 0) : ?>
        <? foreach ($sm->my_smiley as $smile => $value) : ?>
            <img src="<?= $GLOBALS['DYNAMIC_CONTENT_URL'] ?>/smile/<?= $smile ?>.gif"
                style="cursor: pointer;" onClick="$('#inhalt').val($('#inhalt').val() + ' :<?= $smile ?>:')">&nbsp;
        <? endforeach ?>
    <? endif ?>
    <br/>
    <a href="<?= URLHelper::getLink('show_smiley.php') ?>" target="new"><?= _("Smileys") ?></a> |
    <a href="<?= format_help_url("Basis.VerschiedenesFormat") ?>" target="new"><?= _("Formatierungshilfen") ?></a>
    <br>
</div>
