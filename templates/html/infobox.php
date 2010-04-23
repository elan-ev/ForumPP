<table align="center" width="250" border="0" cellpadding="0" cellspacing="0">

  <!-- Bild -->
  
  <tr>
    <td class="infobox" width="100%" align="right">
      <img src="<?=Assets::url('images/'.$picture)?>">
    </td>
  </tr>

  <tr>
    <td class="infoboxrahmen" width="100%">
    <table background="<?=Assets::url('images/white.gif')?>" align="center" width="99%" border="0" cellpadding="4" cellspacing="0">

      <!-- Statusmeldungen -->
      <? if ($messages) :
            // render status messages partial  
            echo $standard_infobox->render_partial("infobox/infobox_statusmessages_partial.php"); 
         endif; 
      ?>
            
      <!-- Informationen -->
    
      <tr>
        <td class="infobox" width="100%" colspan="2">
          <font size="-1"><b><?=_("Ansicht")?>:</b></font>
          <br>
        </td>
      </tr>

      <tr>
          <td class="infobox" align="center" valign="center" width="1%">
						<? if ($section == 'areas' || $section == 'threads' || $section == 'postings') : ?>
            <img src="<?=$plugin->getPluginUrl()?>/img/simple_indicator_red.gif">
						<? else : ?>
            <img src="<?=$plugin->getPluginUrl()?>/img/simple_indicator_gray.gif">
						<? endif; ?>
          </td>
          <td class="infobox" width="99%" align="left">
            <font size="-1">
			        <a href="<?= PluginEngine::getLink($plugin, array('source' => 'va')) ?>">Forum</a>
							<? if ($_REQUEST['root_id']) : ?>
			        &bull;<br/><a href="<?= PluginEngine::getLink($plugin, array('root_id' => $_REQUEST['root_id'])) ?>"><?= $area_name ?></a>
							<? endif; if ($_REQUEST['thread_id']) : ?>
			        &bull;<br/><a href="<?= PluginEngine::getLink($plugin, array('root_id' => $_REQUEST['root_id'], 'thread_id' => $_REQUEST['thread_id'])) ?>"><?= $thread_name ?></a>
							<? endif; ?>
						</font>
          </td>
      </tr>                             

      <tr>
          <td class="infobox" align="center" valign="center" width="1%">
						<? if ($section == 'favorites') : ?>
            <img src="<?=$plugin->getPluginUrl()?>/img/simple_indicator_red.gif">
						<? else : ?>
            <img src="<?=$plugin->getPluginUrl()?>/img/simple_indicator_gray.gif">
						<? endif; ?>
          </td>
          <td class="infobox" width="99%" align="left">
            <font size="-1">
							<a href="<?= PluginEngine::getLink($plugin, array('plugin_subnavi_params' => 'favorites')) ?>"><?=_("Favoriten")?></a>
						</font>
          </td>
      </tr>                             

       <tr>
          <td class="infobox" align="center" valign=center" width="1%">
						<? if ($section == 'new_postings') : ?>
            <img src="<?=$plugin->getPluginUrl()?>/img/simple_indicator_red.gif">
						<? else : ?>
            <img src="<?=$plugin->getPluginUrl()?>/img/simple_indicator_gray.gif">
						<? endif; ?>
          </td>
          <td class="infobox" width="99%" align="left">
            <font size="-1">
							 <a href="<?= PluginEngine::getLink($plugin, array('plugin_subnavi_params' => 'new_postings')) ?>"><?=_("neue Beiträge")?></a>
						</font>
          </td>
      </tr>                             

      <tr>
          <td class="infobox" align="center" valign="center" width="1%">
						<? if ($section == 'last_postings') : ?>
            <img src="<?=$plugin->getPluginUrl()?>/img/simple_indicator_red.gif">
						<? else : ?>
            <img src="<?=$plugin->getPluginUrl()?>/img/simple_indicator_gray.gif">
						<? endif; ?>
          </td>
          <td class="infobox" width="99%" align="left">
            <font size="-1">
							<a href="<?= PluginEngine::getLink($plugin, array('plugin_subnavi_params' => 'last_postings')) ?>"><?=_("letzte Beiträge")?></a>
						</font>
          </td>
      </tr>                             
         
      <!-- Aktionen -->
    
			<? if ($aktionen && sizeof($aktionen) > 0) : ?>
      <tr>
        <td class="infobox" width="100%" colspan="2">
          <font size="-1"><b><?=_("Aktionen")?>:</b></font>
          <br>
        </td>
      </tr>
		
			<? foreach ($aktionen as $aktion) : ?>
       <tr>
         <td class="infobox" align="center" valign="top" width="1%">
           <img src="<?=Assets::url('images/link_intern.gif')?>">
         </td>
         <td class="infobox" width="99%" align="left">
           <font size="-1">
					   <a href="<?= $aktion['link'] ?>#<?= $aktion['anchor'] ?>"><?= _($aktion['name']) ?></a>
					 </font>
           <br />
         </td>
      </tr>
			<? endforeach; ?>
			<? endif; ?>

			<!-- Forumssuche -->
      <tr>
        <td class="infobox" width="100%" colspan="2">
          <font size="-1"><b><?=_("Suche")?>:</b></font>
          <br>
        </td>
      </tr>

			<tr>
				<td class="infobox" align="center" width="1%" valign="top" style="padding-top: 10px">
					<? if ($section == 'search') : ?>
           <img src="<?=$plugin->getPluginUrl()?>/img/simple_indicator_red.gif">
					<? else : ?>
           <img src="<?=$plugin->getPluginUrl()?>/img/simple_indicator_gray.gif">
					<? endif; ?>
				</td>
				<td class="infobox" width="99%" align="left" nowrap>
					<font size="-1">
						<form action="<?= PluginEngine::getLink($plugin, array()) ?>" method="post">
							<input type="text" name="searchfor" value="<?= htmlReady(stripslashes($_REQUEST['searchfor']))?>">
							<input type="image" src="<?= $plugin->getPluginUrl()?>/img/suchen.gif" align="absbottom"><br/>
							<input type="checkbox" name="search_title" value="1" checked="checked"> <?= _("Titel") ?><br/>
							<input type="checkbox" name="search_content" value="1" checked="checked"> <?= _("Inhalt") ?><br/>
							<input type="checkbox" name="search_author" value="1" checked="checked"> <?= _("Autor") ?><br/>
							<input type="hidden" name="plugin_subnavi_params" value="search">
							<!--
							<br/>Backend: <select name="engine">
								<option value="search" <?= ($_REQUEST['engine'] == 'search') ? 'selected="selected"' : '' ?>>Stud.IP</option>
								<option value="search_indexed" <?= ($_REQUEST['engine'] == 'search_indexed') ? 'selected="selected"' : '' ?>>Sphinx</option>
							</select>
							-->
							<input type="hidden" name="backend" value="search">
						</form>
					</font>
				</td>
			</tr>

			<!-- Designauswahl -->
      <? if (sizeof($plugin->getDesigns()) > 1) : ?>
      <tr>
        <td class="infobox" width="100%" colspan="2">
          <font size="-1"><b><?=_("Design")?>:</b></font>
          <br>
        </td>
      </tr>

			<tr>
				<td class="infobox" align="center" valign="top" width="1%">
				</td>
				<td class="infobox" width="99%" align="left">
					<font size="-1">
						<form action="<?= PluginEngine::getLink($plugin, array()) ?>" method="post">
							<select name="template">
								<? foreach ($plugin->getDesigns() as $design) : ?>
								<option value="<?= $design['value'] ?>" <?= ($design['value'] == $plugin->getDesign()) ? 'selected="selected"':''?>><?= $design['name'] ?></option>
								<? endforeach; ?>
							</select>
							<input type="image" src="<?= $plugin->getPluginUrl()?>/img/GruenerHakenButton.png" align="absbottom">
							<input type="hidden" name="subcmd" value="set_design">

							<? if ($_REQUEST['plugin_subnavi_params']) : ?>
							<input type="hidden" name="plugin_subnavi_params" value="<?= $_REQUEST['plugin_subnavi_params'] ?>">
							<? endif; if ($_REQUEST['root_id']) : ?>
							<input type="hidden" name="root_id" value="<?= $_REQUEST['root_id'] ?>">
							<? endif; if ($_REQUEST['thread_id']) : ?>
							<input type="hidden" name="thread_id" value="<?= $_REQUEST['thread_id'] ?>">
							<? endif; if ($_REQUEST['page']) : ?>
							<input type="hidden" name="page" value="<?= $_REQUEST['page'] ?>">
							<? endif; if ($_REQUEST['searchfor']) :?>
							<input type="hidden" name="searchfor" value="<?= $_REQUEST['searchfor'] ?>">
							<? endif; ?>
						</form>
					</font>
					<br />
				</td>
			</tr>
        <? endif; ?>
    </table>
    </td>
  </tr>
</table>

