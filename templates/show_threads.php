<table cellspacing="0" cellpadding="1" border="0" width="100%">
  <tr>
		<td class="forum_header" colspan="3" align="left">
			<span class="corners-top"></span>
			<span class="heading"><?= _("THEMEN") ?></span>
		</td>
    <td class="forum_header" width="1%">
			<span class="no-corner"></span>
			<span class="heading"><?= _("ANTWORTEN") ?></span>
		</td>
    <td class="forum_header" width="30%" colspan="2">
			<span class="corners-top-right"></span>
			<span class="heading"><?= _("LETZTER BEITRAG") ?></span>
		</td>
  </tr>
  <?
		foreach ($threads as $thread) :
	?>
    <tr>
			<td class="areaborder"></td>
      <td class="areaentry icon_thread" valign="center" align="center" width="1%">
        <?= Assets::img('eigene2') ?>
			</td>
      <td class="areaentry" valign="top" align="left">
        <a href="<?= PluginEngine::getLink($plugin, array('root_id' => $_REQUEST['root_id'], 'thread_id' => $thread['entry_id'])) ?>">
          <span class="areaname">
						<? 
						if ($thread['name']) :
							echo $thread['name'];
						else :
							echo '(' . _("Kein Titel angegeben") . ')';
						endif
					?>
					</span>
        </a><br/>
				<span class="threadauthor">
					<?= _("von") ?> <a href="about.php?username=<?= get_username($thread['owner_id']) ?>">
						<?= $thread['author'] ?>
					</a>
					<?= _("am") ?> <?= strftime($plugin->time_format_string_short,  $thread['mkdate']) ?>
				</span>
				<span class="pagechooser_thread"><?= $plugin->get_page_chooser($_REQUEST['root_id'], $thread['entry_id']) ?></span>
      </td>
      <td class="areaentry2" align="center" valign="top" style="padding-top: 8px">
				<?= $thread['num_postings'] ?>
			</td>
      <td class="areaentry2" valign="top">
				<? if (!is_array($thread['last_posting'])) : ?>
				<?= $thread['last_posting'] ?>
				<? else : ?>
				<?= _("von") ?>	<a href="about.php?username=<?= $thread['last_posting']['username'] ?>">
					<?= $thread['last_posting']['user_fullname'] ?>
				</a>
        <? $infotext = _("Direkt zum Beitrag...") ?>
				<a href="<?= $thread['last_posting']['link'] ?>" alt="<?= $infotext ?>" title="<?= $infotext ?>">
					<img src="<?= $plugin->picturepath ?>/goto_posting.png" alt="<?= $infotext ?>" title="<?= $infotext ?>">
				</a><br/>
				<?= _("am") ?> <?= strftime($plugin->time_format_string_short,  $thread['last_posting']['date']) ?>
				<? endif; ?>
      </td>
			<td class="areaborder"></td>
    </tr>
  <? endforeach ?>

  <!-- bottom border -->
	<tr>
		<td class="areaborder" colspan="6">
			<span class="corners-bottom"><span></span></span>
		</td>
	</tr>
</table>
<br />
