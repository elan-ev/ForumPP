<?
$delete_link = '&nbsp&nbsp;<a href="'. 
	PluginEngine::getLink($plugin, array(
		'plugin_subnavi_params' => 'config',
		'action' => 'delete_area',
		'area_id' => '%s',
		'category_id' => '%s')
	).'"><img src="'. $picturepath .'/icons/delete.png"></a>';
?>
<script>
	var delete_link = '<?= str_replace('%25', '%', $delete_link) ?>';

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
		new Ajax.Request(url = '<?=PluginEngine::getLink($plugin) ?>savecats?topic_id='+ container + params, {
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
					<span class="bgtext"><?= _("Ziehen sie eine &Uuml;berschrift in diesen Bereich um die &Uuml;berschrift dieser Kategorie hinzuzuf&uuml;gen!") ?></span>
					<?
					echo '&nbsp;&nbsp;<b>'. $cat['name'] .'</b>';
					//echo '&nbsp&nbsp;<a href="'. PluginEngine::getLink($plugin, array('plugin_subnavi_params' => 'config', 'action' => 'delete_category', 'category_id' => $cat_id)).'">X</a>';
					// echo '<br/>';

					echo '<ul id="cat_list_'. $cat_id .'">';
					foreach($cat['areas'] as $area_id => $area) {
						echo '<li id="area_'. $area['entry_id'] .'" class="areas" dropped="dropped">'. $area['name'];
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
		<form method="post">
			<input type="text" name="category">
			&nbsp;&nbsp;<button name="create_category">Kategorie erstellen</button>
			<input type="hidden" name="plugin_subnavi_params" value="config">
			<input type="hidden" name="action" value="administrate">
		</form>
		</p>
	</div>

	<script>areas = Array();</script>
	<dl class="postprofile" style="width: 38%">
	<? if (sizeof($areas) > 0) : ?>
		<dt><b><?= _("Keiner Kategorie zugeordnet") ?>:</b><br/><br/></dt>
		<dd><ul>
		<? foreach ($areas as $area) : ?>
			<li id="area_<?= $area['entry_id'] ?>" class="areas pointer"><?= $area['name'] ?></li>
			<script>
				areas['area_<?= $area['entry_id'] ?>'] = new Draggable("area_<?= $area['entry_id'] ?>", {revert: true});
			</script>
		<? endforeach; ?>
		</ul></dd>
	<? endif; ?>
	</dl>
	<span class="corners-bottom"><span></span></span>
</div>

