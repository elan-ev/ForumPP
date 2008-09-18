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
          <font size="-1"><b><?=_("Informationen")?>:</b></font>
          <br>
        </td>
      </tr>

      <tr>
          <td class="infobox" align="center" valign="center" width="1%">
            <?= Assets::img('ausruf_small') ?>
          </td>
          <td class="infobox" width="99%" align="left">
            <font size="-1">
							<?= _("Hier können Sie Einstellungen für das ForumPP vornehmen") ?><br/>
						</font>
          </td>
      </tr>

      <tr>
          <td class="infobox" align="center" valign="center" width="1%">
            <?= Assets::img('ausruf_small') ?>
          </td>
          <td class="infobox" width="99%" align="left">
            <font size="-1">
							<?= _("Sie können mittels Drag'n'Drop Elemente zu Kategorien hinzufügen und innerhalb von Kategorien sortieren.") ?><br/>
						</font>
          </td>
      </tr>

      <!-- Aktionen -->

      <tr>
        <td class="infobox" width="100%" colspan="2">
          <font size="-1"><b><?=_("Aktionen")?>:</b></font>
          <br>
        </td>
      </tr>

      <tr>
          <td class="infobox" align="center" valign="center" width="1%">
            <?= Assets::img('ausruf_small') ?>
          </td>
          <td class="infobox" width="99%" align="left">
            <font size="-1">
							<? if ($default_forum) : ?>
							<?= sprintf(_("Das Standardforum ist momentan %saktiviert.%s"), '<b>', '</b>') ?><br/>
							<a href="<?= PluginEngine::getLink($plugin, array('plugin_subnavi_params' => 'config', 'deactivate' => 'deactivate')) ?>">
								<?=  _("Standardforum deaktivieren") ?>
							</a>
							<? else : ?>
							<?= sprintf(_("Das Standardforum ist momentan %sdeaktiviert.%s"), '<b>', '</b>') ?><br/>
							<a href="<?= PluginEngine::getLink($plugin, array('plugin_subnavi_params' => 'config', 'activate' => 'activate')) ?>">
								<?= _("Standardforum aktivieren") ?>
							</a>
							<? endif; ?>
						</font>
          </td>
      </tr>                             

    </table>
    </td>
  </tr>
</table>

