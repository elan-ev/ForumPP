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
            'text' => _('Sie können mittels Drag\'n\'Drop die Reihenfolge der '
                . 'Element innerhalb einer Kategorien ändern. Außerdem können '
                . 'Sie auch die Kategorien selbst auf diese Weise sortieren.')
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
<script>
    $(document).ready(function() {
        jQuery('a.toggle_list').bind('click', function() {
            jQuery(this).parent().find('div').toggle();
            jQuery(this).remove();
        })
        
        jQuery('ul.sortable').sortable({
            axis: 'y',
            placeholder: "ui-state-highlight",
            stop: function() {
                var areas = {};
                areas['areas'] = {};
                jQuery(this).find('li').each(function() {
                    var name = jQuery(this).attr('data-id');
                    areas['areas'][name] = name;
                });
                
                $.ajax({
                    type: 'POST',
                    url: '<?= PluginEngine::getLink('forumpp/index/saveareas') ?>',
                    data: areas
                });
            }            
        });
        
        jQuery('#sortable_categories').sortable({
            axis: 'y',
            placeholder: "ui-state-highlight",
            stop: function() {
                var categories = {};
                categories['categories'] = {};
                jQuery('#sortable_categories > li').each(function() {
                    var name = jQuery(this).attr('data-id');
                    categories['categories'][name] = name;
                });
                
                $.ajax({
                    type: 'POST',
                    url: '<?= PluginEngine::getLink('forumpp/index/savecats') ?>',
                    data: categories
                });
            }
        });
    });
</script>
<div class="posting bg2">
    <span class="corners-top"><span></span></span>

    <div class="postbody" style="width: 95%;">
        <span class="title" style="margin-bottom: 5px;"><?= _('Vorhandene Kategorien') ?></span><br>

        <? if (sizeof($categories) == 0) : ?>
        <b><?= _("Sie haben noch keine Kategorien definiert. Legen Sie eine Neue an!") ?></b><br>
        <br>
        <? else : ?>

        <ul id="sortable_categories" class="cat_list">
        <? foreach($categories as $name => $entries) : ?>
            <? $cat_id = $entries['cat']['category_id'] ?>
            <li data-id="<?= $cat_id ?>" class="cat">
                <a href="<?= PluginEngine::getLink('forumpp/index/remove_category/' . $cat_id) ?>">
                    <?= Assets::img('icons/16/blue/trash.png') ?>
                </a>
                <span class="category_title" style="cursor: move;"><?= $name ?></span>
                
                <br><br>
                
                <ul class="cat_list sortable">
                <? if (!empty($entries['areas'])) foreach($entries['areas'] as $area) : ?>
                    <li data-id="<?= $area['topic_id'] ?>" class="areas" style="cursor: move">
                        <a href="<?= PluginEngine::getLink('forumpp/index/remove_area/' . $area['topic_id']) ?>">
                            <?= Assets::img('icons/16/blue/trash.png') ?>
                        </a>
                        <?= $area['name'] ?>
                    </li>
                <? endforeach ?>
                </ul>

                <? if (!empty($areas)) : ?>
                <div class="add_cat">
                    <a href="#" class="toggle_list">Klicken, um Bereiche zu dieser Kategorie hinzufügen</a>

                    <div style="display: none; padding-left: 5px;">
                        <form action="<?= PluginEngine::getLink('forumpp/index/add_areas') ?>" method="post">
                        <select multiple name="areas[]" class="cat_list">
                        <? foreach ($areas as $area) : ?>
                            <option value="<?= $area['topic_id'] ?>"><?= $area['name'] ?></option>
                        <? endforeach; ?>
                        </select><br>                        
                        <input type="hidden" name="cat_id" value="<?= $cat_id ?>">
                        <input type="image" <?= makebutton('hinzufuegen', 'src') ?>>
                        </form>
                        <br>
                        <br>
                    </div>
                </div>
                <? endif ?>
            </li>
        <? endforeach; ?>
        </ul>
        <? endif; ?>

        <br>
        <b><?= _('Neue Kategorie anlegen') ?></b><br>
        <form action="<?= PluginEngine::getLink('forumpp/index/add_category') ?>" method="post">
            <input type="text" name="category">
            <input type="image" <?= makeButton('hinzufuegen', 'src') ?>>
        </form>
        <br>
        <br>
    </div>

    <span class="corners-bottom"><span></span></span>
</div>

