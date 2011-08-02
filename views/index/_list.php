<? foreach ($list as $area_name => $entries) : ?>
<table cellspacing="0" cellpadding="2" border="0" width="100%" class="forum">
    <tr>
        <td class="forum_header" colspan="3" align="left">
            <span class="corners-top"></span>
            <span class="heading">
                <? if (!$area_name) : ?>
                <?= strtoupper(_('Themen')) ?>
                <? else: ?>
                <?= strtoupper($area_name) ?>&nbsp;
                <? endif ?>
            </span>
        </td>

        <? if ($show_area_edit) : ?>
        <td class="forum_header" width="1%">
            <span class="no-corner"></span>
            <span class="heading">&nbsp;</span>
        </td>
        <? endif; ?>

        <td class="forum_header" width="1%">
            <span class="no-corner"></span>
            <span class="heading"><?= _("BEITR&Auml;GE") ?></span>
        </td>

        <td class="forum_header" width="30%" colspan="2">
            <span class="corners-top-right"></span>
            <span class="heading"><?= _("LETZTE ANTWORT") ?></span>
        </td>
    </tr>


    <? foreach ($entries as $area) : ?>
    <tr>
        <td class="areaborder">&nbsp;</td>
        <td class="areaentry icon" width="1%" valign="top" align="center">
            <?= Assets::img('icons/16/black/forum.png') ?>
        </td>
        <td class="areaentry" valign="top">

        <? if ($edit_area == $area['topic_id']) : ?>
            <form action="<?= PluginEngine::getLink($plugin) ?>" method="post">
                <input type="text" name="posting_title" style="width: 99%" value="<?= $area['name_raw'] ?>"><br/>
                <textarea name="posting_data" class="add_toolbar" style="width: 99%" rows="7"><?= $area['content_raw'] ?></textarea><br/>
                <div class="buttons">
                    <input type="image" <?= makebutton('speichern', 'src') ?>>&nbsp;&nbsp;&nbsp;
                    <a href="<?= PluginEngine::getLink('ForumPPPlugin') ?>">
                        <?= makebutton('abbrechen') ?>
                    </a>
                </div>
                <input type="hidden" name="subcmd" value="do_edit_posting">
                <input type="hidden" name="posting_id" value="<?= $area['topic_id'] ?>">
            </form>
            <? else : ?>

            <a href="<?= PluginEngine::getLink('forumpp/index/index/'. $area['topic_id']) ?>">
                <span class="areaname"><?= $area['name'] ?></span>
            </a><br/>

            <? if ($this->constraint['depth'] == 1) : ?>
            <?= $area['content_short'] ?>...
            <? else: ?>
            <?= $area['content'] ?>
            <? endif ?>
        <? endif; ?>

        </td>

        <? if ($show_area_edit) : ?>
        <td width="20" align="center" valign="top" class="areaentry2" style="padding-top : 8px">
            <a href="<?= PluginEngine::getLink($plugin, array('subcmd' => 'edit_area', 'area_id' => $area['topic_id']))?>#create_area">
                <?= Assets::img('icons/16/blue/edit.png') ?>
            </a>
        </td>
        <? endif; ?>

        <td width="40" align="center" valign="top" class="areaentry2" style="padding-top : 8px">
            <?= ($area['num_postings'] > 0) ? ($area['num_postings'] - 1) : 0 ?>
        </td>

        <td width="30%" align="left" valign="top" class="areaentry2">
            <? if (!empty($area['last_posting'])) : ?>
            <?= _("von") ?>
            <a href="<?= UrlHelper::getLink('about.php?username='. $area['last_posting']['username']) ?>">
                    <?= htmlReady($area['last_posting']['user_fullname']) ?>
            </a><br>
            <?= _("am") ?> <?= strftime($time_format_string_short, (int)$area['last_posting']['date']) ?>
            <a href="<?= PluginEngine::getLink('/forumpp/index/index/'. $area['last_posting']['topic_id']) ?>#<?= $area['last_posting']['topic_id'] ?>" alt="<?= $infotext ?>" title="<?= $infotext ?>">
                <?= Assets::img('icons/16/blue/link-intern.png', array('title' => $infotext = _("Direkt zum Beitrag..."))) ?>
            </a>
            <? else: ?>
            <?= _("von") ?>
            <a href="<?= UrlHelper::getLink('about.php?username='. get_username($area['owner_id'])) ?>">
                    <?= htmlReady($area['author']) ?>
            </a>
            <? endif; ?>
        </td>
        <td class="areaborder">&nbsp;</td>
    </tr>
        <? endforeach; ?>



	<!-- bottom border -->
	<tr>
        <td class="areaborder" colspan="7">
            <span class="corners-bottom"><span></span></span>
        </td>
	</tr>
	<tr>
        <td colspan="6">&nbsp;</td>
	</tr>
</table>
<? endforeach; ?>