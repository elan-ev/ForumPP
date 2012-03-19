<div class="posting bg2" style="margin-bottom: 20px; display: none; position: relative; text-align: left;">
    <span class="corners-top"><span></span></span>

    <span class="title" style="padding-left: 5px; font-weight: bold">
        <?= _('Vorschau ihres Beitrags:') ?> (<?= _('Vergessen Sie nicht, ihren Beitrag zu speichern!')?>)
        <br><br>
    </span>
    
    <?= Assets::img('icons/16/red/decline.png', array(
        'style'   => 'position: absolute; top: 5px; right: 5px; cursor: pointer;',
        'onClick' => 'jQuery(this).parent().hide();',
        'title'   => _('Vorschaufenster schließen'))) ?>

    <div class="postbody" id="<?= $preview_id ?>"></div>

    <span class="corners-bottom"><span></span></span>
</div>