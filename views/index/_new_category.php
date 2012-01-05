<? if ($has_perms) : ?>
<a name="create"></a>
<form action="<?= PluginEngine::getLink('forumpp/index/add_category') ?>" method="post">
    <?= CSRFProtection::tokenTag() ?>
    <div class="forum_header">
        <span class="corners-top"><span></span></span>
        
        <span class="area_title"><?= _('Neue Kategorie erstellen') ?></span>
    </div>
    <div class="areaborder" style="width: 100%;">
        <span class="area_input">
            <input type="text" size="50" placeholder="<?= _('Titel für neue Kategorie') ?>" name="category" required>
            <input type="image" <?= makebutton('hinzufuegen', 'src') ?> title="Kategorie erstellen">
        </span>
    </div>

    <div class="areaborder" style="width: 100%">
        <span class="corners-bottom"><span></span></span>
    </div>
</form>
<br>
<? endif ?>