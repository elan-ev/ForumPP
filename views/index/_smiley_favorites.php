<?php
require_once('app/models/smiley.php');
$sm = new SmileyFavorites($GLOBALS['user']->id);
?>
<div style="text-align: center; padding-right: 5px;">
    <? $smileys = Smiley::getByIds($sm->get()) ?>
    <? if (!empty($smileys)) : ?>
        <? foreach ($smileys as $smiley) : ?>
            <img src="<?= $smiley->getUrl() ?>"
                style="cursor: pointer;" onClick="jQuery('#inhalt').val(jQuery('#inhalt').val() + ' :<?= $smiley->name ?>:')">&nbsp;
        <? endforeach ?>
    <? endif ?>
    <br/>
    <a href="<?= URLHelper::getLink('dispatch.php/smileys') ?>" target="new"><?= _("Smileys") ?></a> |
    <a href="<?= format_help_url("Basis.VerschiedenesFormat") ?>" target="new"><?= _("Formatierungshilfen") ?></a>
    <br>
</div>