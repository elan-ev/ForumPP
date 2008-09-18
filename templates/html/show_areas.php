<table cellspacing="0" cellpadding="2" border="0" width="100%" class="forum">
	<? foreach ($categories as $key => $cat) :
		if (sizeof($cat['areas']) > 0) : ?>
  <tr>
    <td class="forum_header" colspan="3" align="left">
			<span class="corners-top"></span>
			<span class="heading"><?= strtoupper($cat['name']) ?>&nbsp;</span>
		</td>
    <td class="forum_header" width="1%">
			<span class="no-corner"></span>
			<span class="heading"><?= _("BEITR&Auml;GE") ?></span>
		</td>
    <td class="forum_header" width="30%" colspan="2">
			<span class="corners-top-right"></span>
			<span class="heading"><?= _("LETZTE ANTWORT") ?></span>
		</td>
  </tr>
  <? foreach ($cat['areas'] as $area) : ?>
    <tr>
			<td class="areaborder">&nbsp;</td>
      <td class="areaentry icon" width="1%" valign="top" align="center">
        <?= Assets::img('eigene2') ?>
			</td>
      <td class="areaentry" valign="top">
        <font size="-1">
          <a href="<?= PluginEngine::getLink($plugin, array('root_id' => $area['entry_id'])) ?>">
            <span class="areaname"><?= $area['name'] ?></span>
          </a><br/>
          <?= $area['description'] ?>
        </font>
      </td>
      <td width="5%" align="center" valign="top" class="areaentry2" style="padding-top : 8px">
      	<?= $area['num_postings'] - 1 ?>
      </td>
      <td width="30%" align="left" valign="top" class="areaentry2">
				<?= _("von") ?>
				<a href="about.php?username=<?= $area['last_posting']['username'] ?>">
					<?= $area['last_posting']['user_fullname'] ?>
				</a>
				<? $infotext = _("Direkt zum Beitrag...") ?>
				<a href="<?= $area['last_posting']['link'] ?>" alt="<?= $infotext ?>" title="<?= $infotext ?>">
					<img src="<?= $plugin->picturepath ?>/goto_posting.png" alt="<?= $infotext ?>" title="<?= $infotext ?>">
				</a><br/>
				<?= _("am") ?> <?= strftime($plugin->time_format_string_short, (int)$area['last_posting']['date']) ?>
      </td>
			<td class="areaborder">&nbsp;</td>
    </tr>
  <? endforeach; ?>
	<!-- bottom border -->
	<tr>
		<td class="areaborder" colspan="6">
			<span class="corners-bottom"><span></span></span>
		</td>
	</tr>
	<tr>
		<td class="blank">&nbsp;</td>
	</tr>
  <? endif; endforeach; ?>
</table>
<br/>

