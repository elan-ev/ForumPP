<? if ($answer_link) : ?>
<center>
  <a href="<?= $answer_link ?>#create_posting"><?= makebutton('antworten') ?></a>
</center>
<? endif; ?>
<form action="<?= PluginEngine::getLink('forumpp/index/index') ?>" method="post">
<input type="hidden" name="page" value="<?= $GLOBALS['_REQUEST']['page'] ?>">
<table cellspacing="0" cellpadding="1" border="0" width="100%">
  <?
    $posting_num = 1;
    $last = sizeof($postings);
    if ($_REQUEST['page']) $page = $_REQUEST['page']; else $page = 1;
    foreach ($postings as $post) :

        $last_posting = ($posting_num == $last);

        echo $this->render_partial('index/_post', compact('post', 'last_posting'));

        //if ((ceil($posting_num / $plugin->POSTINGS_PER_PAGE)) == $page) :
        //endif;

        $posting_num++;
    endforeach ?>
</table>

</form>
<br />
<? if ($answer_link) : ?>
<center>
  <a href="<?= $answer_link ?>#create_posting"><?= makebutton('antworten') ?></a>
</center>
<? endif; ?>