<?= $plugin->get_page_chooser_NP($num_postings) ?>
<br/>
<br/>
<table cellspacing="0" cellpadding="1" border="0" width="100%">
	<? if ($info_message) : ?>
	<center>
		<span style="font-weight: bold; font-size: 2.0em">
			<?=$info_message?>
		</span>
	</center>
	<? else: foreach ($postings as $post) : ?>
  <tr>
    <td class="listheader" colspan="8">
			<span class="corners-top"><span></span></span>
    	<strong>
				<a href="<?= PluginEngine::getLink($plugin, array('root_id' => $post['root_id'])) ?>"><?= $post['area_name'] ?></a> &bull;
				<a href="<?= PluginEngine::getLink($plugin, array('root_id' => $post['root_id'], 'thread_id' => $post['thread_id'], 'jump_to' => $post['topic_id'])) ?>#<?= $post['topic_id'] ?>"><?= $post['thread_name'] ?></a>
			</strong>
			<span class="corners-bottom"><span></span></span>
    </td>
  </tr>
	<?
	    $plugin->show_entry($post['author'], $post['chdate'], $post['name'], $post['description'], $post['topic_id'], $main_topic, $post['owner_id'], $post['raw_title'], $post['raw_description'], $post['fav'], true, $highlight);
  ?>
	<tr>
		<td height="5"></td>
	<? endforeach; endif;?>
</table>
<br/>
<?= $plugin->get_page_chooser_NP($num_postings) ?>
<br />
