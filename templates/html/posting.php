<? $GLOBALS['_SWITCHER'] = 1 - $GLOBALS['_SWITCHER'] ?>
<!-- Posting -->
<tr>
  <td>
    <!-- Anker, um zu diesem Posting springen zu können -->
    <a name="<?= $entry['id'] ?>"></a>
    <div class="posting <?=($GLOBALS['_SWITCHER'] == 0) ? 'bg1' : 'bg2'?>">
      <span class="corners-top"><span></span></span>

      <div class="postbody">
        <span class="title">
          <?= ($entry['titel']) ? $entry['titel'] : ''?>
          <p class="author">
            von <strong><a href="<?= URLHelper::getLink('about.php?username='. $entry['real_username']) ?>">
                <?= htmlReady($entry['username']) ?>
            </a></strong>
            am <?= strftime($plugin->time_format_string, (int)$entry['datum']) ?>
          </p>
        </span>
        <!-- Aktionsicons -->
        <span class="icons">
          <? if (is_array($entry['icons'])) foreach ($entry['icons'] as $an_icon) : ?>
            <a href="<?= $an_icon['link'] ?>" title="<?= $an_icon['title'] ?>" alt="<?= $an_icon['title'] ?>">
              <img src="<?= $an_icon['image'] ?>" title="<?= $an_icon['title'] ?>" alt="<?= $an_icon['title'] ?>">
            </a>&nbsp;
          <? endforeach; ?>
        </span>
        <!-- Postinginhalt -->
        <p class="content">
          <?= $entry['inhalt'] ?>
        </p>
      </div>

      <!-- Infobox rechts neben jedem Posting -->
      <dl class="postprofile">
        <dt>
          <a href="<?= URLHelper::getLink('about.php?username='. $entry['real_username']) ?>">
            <? if ($entry['userpicture']) : ?>
            <?= $entry['userpicture'] ?><br/>
            <? endif; ?>
            <strong><?= htmlReady($entry['username']) ?></strong>
          </a>
        </dt>

        <? if ($entry['userrights']) : ?>
        <dd><?= $entry['userrights']?></dd>
        <? endif; ?>

        <dd>&nbsp;</dd>
        <dd>
          Beiträge:
          <?= $entry['userpostings'] ?>
      </dl>
      <? if ($entry['buttons']) : ?>
        <span class="buttons">
          <?= $entry['buttons'] ?>
        </span>
      <? endif; ?>
      <span class="corners-bottom"><span></span></span>
    </div>
  </td>
</tr>

<!-- Trennzeile -->
<tr class="trenner">
  <td class="blank" style="height: 3px"></td>
</tr>
