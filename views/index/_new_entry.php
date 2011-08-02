<? if ($has_rights) : ?>
<a name="#create"></a>
<form action="<?= PluginEngine::getLink('forumpp/index/add_entry/'. ($child_topic ? $child_topic : $topic_id)) ?>" method="post">
    <div class="posting bg2">
        <span class="corners-top"><span></span></span>

        <div class="postbody">
            <span class="title"><? switch ($depth):
                case 0:
                    echo _('Neuen Bereich erstellen');
                    break;
                
                case 1:
                    echo _('Neues Thema erstellen');
                    break;

                case 2:
                default:
                    echo _('Neuen Beitrag erstellen');
                    break;
            endswitch; ?></span><br/>

            <p class="content" style="margin-bottom: 0pt">
                <strong><?= _('Titel:') ?></strong><br/>
                <input type="text" name="name" style="width: 100%" value="<?= $name_value ?>"><br/>
                <br/>
            </p>
        </div>

        <div class="postbody">
            <textarea class="add_toolbar" id="inhalt" name="content" style="width: 100%" rows="10"><?= $content_value ?></textarea><br/>
        </div>

        <dl class="postprofile">
            <dt>
                <?= $this->render_partial('index/_smiley_favorites') ?>
            </dt>
        </dl>

        <div class="buttons">
            <input type="image" <?= makebutton('erstellen', 'src') ?> style="margin-right: 20px">
            <a href="<?= PluginEngine::getLink('forumpp/index/index/'. $topic_id) ?>">
                <?= makebutton('abbrechen') ?>
            </a>
        </div>

        <span class="corners-bottom"><span></span></span>
    </div>

    <input type="hidden" name="parent" value="<?= $topic_id ?>">
</form>
<br>
<? endif ?>