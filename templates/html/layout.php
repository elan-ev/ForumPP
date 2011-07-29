<? $layout = $GLOBALS['template_factory']->open('layouts/base_without_infobox');
   $this->set_layout($layout); ?>
<table cellspacing="0" cellpadding="0" border="0" width="100%">
  <td valign="top" width="100%">
    <?= $menubar ?>
    <?= $content_for_layout ?>
  </td>
  <td>&nbsp;&nbsp;</td>
  <td valign="top" width="255">
    <?= $infobox->render() ?>
  </td>
</table>
