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

if ($default_forum) :
    $message  = sprintf(_("Das Standardforum ist momentan %saktiviert.%s"), '<b>', '</b>');
    $message .= '<br><a href="'. PluginEngine::getLink('forumpp/index/deactivate_forum')  . '">';
    $message .= _("Standardforum deaktivieren") . '</a>';
else :
    $message  = sprintf(_("Das Standardforum ist momentan %sdeaktiviert.%s"), '<b>', '</b>');
    $message .= '<br><a href="'. PluginEngine::getLink('forumpp/index/activate_forum')  . '">';
    $message .= _("Standardforum aktivieren") . '</a>';
endif;

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
	// var delete_link = '<?= str_replace('%25', '%', $delete_link) ?>';
	var edit_cat = '';

	function editCat( cat_id ) {
		if (edit_cat != cat_id) {
			// show edit-fields
			edit_cat = cat_id;
			$('catimg_' + cat_id).src = '<?= $picturepath ?>/icons/accept.png';
			var text = $('catname_' + cat_id).innerHTML;
			$('catname_' + cat_id).innerHTML = '<input type="text" id="cattext_'+ cat_id +'" value="'+ text +'">';
		} 
		
		else {
			// save new name for categorie
			$('catimg_' + cat_id).src = '<?= $picturepath ?>/icons/edit.png';
			$('catname_'+ cat_id).innerHTML = $('cattext_'+ cat_id).value;
			new Ajax.Request(url = '<?=PluginEngine::getURL('forumpp/index/edit_area') ?>&cat_id='+ cat_id +'&new_name='+ $('catname_'+ cat_id).innerHTML, {
					asynchronous:true
				}
			);
			edit_cat = '';
		}
	}

	function sprintf() {
		if( sprintf.arguments.length < 2 ) {
			return;
		}

		var data = sprintf.arguments[ 0 ];

		for( var k=1; k<sprintf.arguments.length; ++k ) {
			data = data.replace( /%s/, sprintf.arguments[ k ] );
		}
		return( data );
	}
																																
	if( !String.sprintf ) {
		String.sprintf = sprintf;
	}

	function save(container) {
		var list = $(container).childElements();

		var params = '';
		for (var i = 0; i < list.length; i++) {
			params += '&l[]=' + list[i].readAttribute('id');
		}

		var url;
		new Ajax.Request(url = '<?= PluginEngine::getURL('forumpp/index/save_areas') ?>' + container + params, {
				asynchronous:true
			}
		);
	}
</script>
<div class="posting bg2">
	<span class="corners-top"><span></span></span>

	<div class="postbody" style="width: 58%">
		<span class="Title" style="margin-bottom: 5px">Konfiguration</span>
		<br/>
		<p>
			<? if (sizeof($categories) == 0) : ?>
			<b><?= _("Sie haben noch keine Kategorien definiert. Legen Sie eine Neue an!") ?></b><br/>
			<br/>
			<? else :
				// connect categories with areas
				foreach($categories as $cat_id => $cat) {
					$new_areas = array();
					foreach ($cat['areas'] as $id) {
						$new_areas[$id] = $areas[$id];
						unset($areas[$id]);
					}
					$categories[$cat_id]['areas'] = $new_areas;
				}

				/*
				$unconnected['areas'] = array();
				$unconnected['name'] = 'Keiner Kategorie zugeordnet';
				$categories[] = $unconnected;
				*/

			echo '<form action="" method="post">';
			foreach($categories as $cat_id => $cat) : ?>
				<div id="cat_<?= $cat_id ?>" class="cat">
					<? /* <a href="<?=  PluginEngine::getLink($plugin, array(
						'plugin_subnavi_params' => 'config',
						'action' => 'delete_category',
						'category_id' => $cat_id)) ?>">
						<img src="<?= $picturepath ?>/icons/delete.png">
					</a> */ ?>
					&nbsp;&nbsp;
					<b><span id="catname_<?= $cat_id ?>"><?= $cat['name'] ?></span></b>
					<img id="catimg_<?= $cat_id ?>" src="<?= $picturepath ?>/icons/edit.png" onClick="editCat('<?= $cat_id ?>')">

					<div class="bgtext"><?= _("Zum Hinzufügen ziehen sie eine &Uuml;berschrift in diesen Bereich!") ?></div>
					&nbsp;
					<?
					//echo '&nbsp&nbsp;<a href="'. PluginEngine::getLink($plugin, array('plugin_subnavi_params' => 'config', 'action' => 'delete_category', 'category_id' => $cat_id)).'">X</a>';
					// echo '<br/>';

					echo '<ul id="cat_list_'. $cat_id .'">';
					foreach($cat['areas'] as $area_id => $area) {
						echo '<li id="area_'. $area['topic_id'] .'" class="areas" dropped="dropped">'. $area['name'];
						printf($delete_link, $area_id, $cat_id);
						echo '</li>';
					}
					echo '</ul>'; ?>

				</div>
				<script>
				Sortable.create('cat_list_<?=$cat_id?>');

				Droppables.add("cat_<?= $cat_id ?>", { 
					accept:'areas',
					hoverclass:'dropcat', 
					onDrop: function(element) {
						if (!element.readAttribute('dropped')) {
							var id = element.readAttribute('id');
							element.insert(String.sprintf(delete_link, id.substring(5, id.length), '<?= $cat_id ?>'));
							$('cat_list_<?=$cat_id?>').insert(element, {position: 'bottom'});
							element.writeAttribute('dropped', 'dropped');
							areas[element.readAttribute('id')].destroy();
							Sortable.create('cat_list_<?=$cat_id?>');
						}

						save('cat_list_<?=$cat_id?>');
					},
				});
				</script>
				<br/>
			<? endforeach; ?>
		<? endif; ?>
		<form action="<?= PluginEngine::getLink('forumpp/index/add_area') ?>" method="post">
			<input type="text" name="category">
            <input type="image" <?= makeButton('hinzufuegen', 'src') ?>>
		</form>
		</p>
	</div>

	<script>areas = Array();</script>
	<dl class="postprofile" style="width: 38%">
	<? if (sizeof($areas) > 0) : ?>
		<dt><b><?= _("Keiner Kategorie zugeordnet") ?>:</b><br/><br/></dt>
		<dd><ul>
		<? foreach ($areas as $area) : ?>
			<li id="area_<?= $area['topic_id'] ?>" class="areas pointer"><?= $area['name'] ?></li>
			<script>
				areas['area_<?= $area['topic_id'] ?>'] = new Draggable("area_<?= $area['topic_id'] ?>", {revert: true});
			</script>
		<? endforeach; ?>
		</ul></dd>
	<? endif; ?>
	</dl>
	<span class="corners-bottom"><span></span></span>
</div>

