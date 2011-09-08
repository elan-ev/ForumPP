<form action="<?= PluginEngine::getLink('forumpp/index/search') ?>" method="post">
    <input type="text" name="searchfor" value="<?= htmlReady(stripslashes(Request::get('searchfor')))?>">
    <input type="image" src="<?= Assets::image_path('icons/16/black/search.png') ?>" title="Forum durchsuchen"><br/>
    <input type="checkbox" name="search_title" value="1" checked="checked"> <?= _("Titel") ?><br/>
    <input type="checkbox" name="search_content" value="1" checked="checked"> <?= _("Inhalt") ?><br/>
    <input type="checkbox" name="search_author" value="1" checked="checked"> <?= _("Autor") ?><br/>
    <input type="hidden" name="backend" value="search">
</form>