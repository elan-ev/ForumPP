<div class="posting bg2">
	<span class="corners-top"><span></span></span>

	<div class="postbody">
		<span class="Title" style="margin-bottom: 5px">Konfiguration</span>
		<br/>
		<p>
			<?

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
				// All ares which are not assigned to a predefined category are collected now
				$unconnected['areas'] = $areas;
				$unconnected['name'] = 'Keiner Kategorie zugeordnet';
				$categories[] = $unconnected;
				*/
				
				if (sizeof($areas) > 0) {
					echo 'Keiner Kategorie zugeordnete Bereiche:<br/>';
					foreach ($areas as $area) {
						echo '&nbsp;&nbsp;'. $area['name'] .'<br/>';
					}
				}
				echo '<hr>';

				echo '<form action="" method="post">';
				echo 'Bereiche:<br/>';
				foreach($categories as $cat_id => $cat) {
					echo '&nbsp;&nbsp;'. $cat['name'];
					echo '&nbsp&nbsp;<a href="'. PluginEngine::getLink($plugin, array('plugin_subnavi_params' => 'config', 'action' => 'delete_category', 'category_id' => $cat_id)).'">X</a>';
					echo '<br/>';

					foreach($cat['areas'] as $area_id => $area) {
						echo '&nbsp;&nbsp;&nbsp;&nbsp;'. $area['name'];
						echo '&nbsp&nbsp;<a href="'. PluginEngine::getLink($plugin, array('plugin_subnavi_params' => 'config', 'action' => 'delete_area', 'area_id' => $area_id, 'category_id' => $cat_id)).'">X</a>';
						echo '<br/>';
					}
					if (sizeof($areas) > 0) {
						echo '&nbsp;&nbsp;&nbsp;&nbsp;';
						echo '<select name="cat_'. $cat_id .'">';
						foreach ($areas as $area_id => $area) {
							echo '<option value="'. $area_id .'">'. $area['name'] .'</option>';
						}
						echo '</select>';
						echo '&nbsp;&nbsp;<button name="add_area" value="'. $cat_id .'">hinzuf&uuml;gen</button>';
						echo '<br/>';
					}
				}

				echo '<input type="text" name="category">';
				echo '&nbsp;&nbsp;<button name="create_category">Bereich erstellen</button>';
				echo '<input type="hidden" name="plugin_subnavi_params" value="config">';
				echo '<input type="hidden" name="action" value="administrate">';
				echo '</form>';
			?>
		</p>
	</div>

	<span class="corners-bottom"><span></span></span>
</div>

