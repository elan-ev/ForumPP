<? foreach ($formats as $format => $content_type) : ?>
  <link rel="alternate" type="<?= $content_type ?>" title="Newsfeed (<?= $format ?>)" href="<?= PluginEngine::getUrl($plugin, array_merge($link_params, compact('format', 'token')), 'feed') ?>">
<? endforeach ?>
