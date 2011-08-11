<?php

$infobox_content[] = array(
    'kategorie' => _('Informationen'),
    'eintrag'   => array(
        array(
            'icon' => 'icons/16/black/exclaim.png',
            'text' => _('Hier können Sie die Bereiche des Forums in Kategorien zusammenfassen')
        ),
        array(
            'icon' => 'icons/16/black/exclaim.png',
            'text' => _("Sie können mittels Drag'n'Drop Elemente zu Kategorien hinzufügen und innerhalb von Kategorien sortieren.")
        )
    )
);

$infobox_content[] = array(
    'kategorie' => _('Aktionen'),
    'eintrag'   => array(
        array(
            'icon' => 'icons/16/black/exclaim.png',
            'text' => $message
        )
    )
);

$infobox = array('picture' => 'infobox/schedules.jpg', 'content' => $infobox_content);
?>
<? /*
<script>
    $(document).ready(function() {
        alert('1234');
    });
</script> */ ?>
<div class="posting bg2">
	<span class="corners-top"><span></span></span>

	<div class="postbody" style="width: 58%">
		<span class="title" style="margin-bottom: 5px"><?= _('Vorhandene Kategorien') ?></span><br>
        <br>

        <? if (sizeof($categories) == 0) : ?>
        <b><?= _("Sie haben noch keine Kategorien definiert. Legen Sie eine Neue an!") ?></b><br>
        <br>
        <? else : foreach($categories as $name => $entries) : ?>
            <? $cat_id = $entries['cat']['category_id'] ?>
            <div id="cat_<?= $cat_id ?>" class="cat">
                <div class="bgtext"><?= _("Zum Hinzufügen ziehen sie eine &Uuml;berschrift in diesen Bereich!") ?></div>
                <span class="category_title"><?= $name ?></span>
                &nbsp;
                <?= Assets::img('icons/16/blue/edit.png') ?><br>
                <br>
                
                <? if (!empty($entries['areas'])) foreach($entries['areas'] as $area) : ?>
                    <div id="area_<?= $area['topic_id'] ?>" class="areas"><?= $area['name'] ?></div>
                <? endforeach ?>

            </div>
            <br/>
        <? endforeach; ?>
		<? endif; ?>

        <b><?= _('Neue Kategorie anlegen') ?></b><br>
        <form action="<?= PluginEngine::getLink('forumpp/index/add_category') ?>" method="post">
			<input type="text" name="category">
            <input type="image" <?= makeButton('hinzufuegen', 'src') ?>>
		</form>
	</div>

	<dl class="postprofile" style="width: 38%">
	<? if (sizeof($areas) > 0) : ?>
		<dt><b><?= _("Keiner Kategorie zugeordnet") ?>:</b><br/><br/></dt>
		<dd>
		<? foreach ($areas as $area) : ?>
			<div id="area_<?= $area['topic_id'] ?>" class="areas pointer"><?= $area['name'] ?></div>
		<? endforeach; ?>
		</dd>
	<? endif; ?>
	</dl>
	<span class="corners-bottom"><span></span></span>
</div>

