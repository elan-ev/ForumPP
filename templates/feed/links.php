<? foreach ($formats as $format => $content_type) : ?>
  <link rel="alternate" type="<?= $content_type ?>" title="Newsfeed (<?= $format ?>)" href="<?= PluginEngine::getLink($plugin, compact('format', 'token'), 'rss') ?>">
<? endforeach ?>
