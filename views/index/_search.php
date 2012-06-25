<form action="<?= PluginEngine::getLink('forumpp/index/search') ?>" method="post" id="tutorSearch">
    <input type="text" name="searchfor" value="<?= htmlReady(stripslashes(Request::get('searchfor')))?>" placeholder="<?= _('Beiträge durchsuchen') ?>">
    <input type="image" src="<?= Assets::image_path('icons/16/black/search.png') ?>" title="Forum durchsuchen"><br>
    <input type="checkbox" name="search_title" value="1"   <?= $options['search_title']   || !$searchfor ? 'checked="checked"' : '' ?>> <?= _("Titel") ?>
    <input type="checkbox" name="search_content" value="1" <?= $options['search_content'] || !$searchfor ? 'checked="checked"' : '' ?>> <?= _("Inhalt") ?>
    <input type="checkbox" name="search_author" value="1"  <?= $options['search_author']  || !$searchfor ? 'checked="checked"' : '' ?>> <?= _("Autor") ?>
    <input type="hidden" name="backend" value="search">
</form>