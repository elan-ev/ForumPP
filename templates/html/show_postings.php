<? if ($answer_link) : ?>
<center>
	<a href="<?= $answer_link ?>#create_posting"><?= makebutton('antworten') ?></a>
</center>
<? endif; ?>
<form action="<?= PluginEngine::getLink($plugin, array('root_id' => $_REQUEST['root_id'], 'thread_id' => $_REQUEST['thread_id'])) ?>" method="post">
<input type="hidden" name="page" value="<?= $GLOBALS['_REQUEST']['page'] ?>">
<table cellspacing="0" cellpadding="1" border="0" width="100%">
  <tr>
    <!--<td class="printhead" colspan="8" style="padding-left: 5px; height: 25px;">-->
		<td>
			<span class="pagechooser">
				<br/>
				<?= $plugin->get_page_chooser($_REQUEST['root_id'], $_REQUEST['thread_id']) ?>
			</span>
		</td>
	</tr>
	<?
		$posting_num = 1;
		$main_topic = '';
		$last = sizeof($postings);
		if ($_REQUEST['page']) $page = $_REQUEST['page']; else $page = 1;
		foreach ($postings as $post) :
			if ($posting_num == 1) : 
				//$post['name'] = '';
				//$post['raw_title'] = '';	// comment this out, to allow editing of thread-titles
				$main_topic = $post['thread_id'];
			endif;

			//if ((ceil($posting_num / $plugin->POSTINGS_PER_PAGE)) == $page) :
			$plugin->show_entry($post['author'], $post['chdate'], $post['name'], $post['description'], $post['topic_id'], $main_topic, $post['owner_id'], $post['raw_title'], $post['raw_description'], $post['fav'], ($posting_num == $last));
			//endif;

			$posting_num++;
    endforeach;
	?>
</table>

</form>
<?= $plugin->get_page_chooser($_REQUEST['root_id'], $_REQUEST['thread_id']) ?>
<br />
<? if ($answer_link) : ?>
<center>
	<a href="<?= $answer_link ?>#create_posting"><?= makebutton('antworten') ?></a>
</center>
<? endif; ?>
