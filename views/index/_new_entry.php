<? if ($has_rights) : ?>
<a name="create"></a>
<form action="<?= PluginEngine::getLink('forumpp/index/add_entry/'. ($child_topic ? $child_topic : $topic_id)) ?>" method="post">
    <div class="posting bg2">
        <span class="corners-top"><span></span></span>

        <div class="postbody" <?= $constraint['depth'] == 0 ? 'style="width: 97%"' : '' ?>>
            <span class="title"><? switch ($constraint['depth']):
                case 1:
                    echo _('Neues Thema erstellen');
                    break;

                case 2:
                default:
                    echo _('Neuen Beitrag erstellen');
                    break;
            endswitch; ?></span>

            <p class="content" style="margin-bottom: 0pt">
                <input type="text" name="name" style="width: 99%" value="<?= $this->flash['new_entry_title'] ?>" <?= $constraint['depth'] == 1 ? 'required' : '' ?> placeholder="<?= _('Titel') ?>" tabindex="1"><br/>
                <br/>
            </p>
        </div>
        
        <? if ($constraint['depth'] > 0): ?>

        <div class="postbody">
            <textarea class="add_toolbar" id="inhalt" name="content" required tabindex="2"
                placeholder="<?= _("Schreiben Sie hier ihren Beitrag. Hilfe zu Formatierungen finden Sie rechts neben diesem Textfeld.") ?>"><?= $this->flash['new_entry_content'] ?></textarea>
        </div>

        <dl class="postprofile">
            <dt>
                <?= $this->render_partial('index/_smiley_favorites') ?>
            </dt>
        </dl>

        <? endif ?>

        <div class="buttons button-group">
            <?= Studip\Button::createAccept('Beitrag erstellen', array('tabindex' => '3')) ?>
            
            <?= Studip\Button::createCancel('abbrechen', array(
                'onClick' => "jQuery('#new_entry_button').show();jQuery('#new_entry_box').hide();return false;",
                'tabindex' => '4')) ?>
            
            <?= Studip\LinkButton::create('Vorschau', "javascript:STUDIP.ForumPP.preview('inhalt', 'preview');", array('tabindex' => '5')) ?>
        </div>

        <span class="corners-bottom"><span></span></span>
    </div>

    <?= $this->render_partial('index/_preview', array('preview_id' => 'preview')) ?>

    <input type="hidden" name="parent" value="<?= $topic_id ?>">
</form>
<br>
<? endif ?>