<div style="width: 54%;text-align: right; float: left">
    <a href="<?= PluginEngine::getLink('forumpp/index/new_entry/'. ($child_topic ? $child_topic : $topic_id)) ?>"><?= makebutton('antworten') ?></a>
</div>
<br style="clear: both"><br>

<table cellspacing="0" cellpadding="1" border="0" width="100%">
    <?
    $posting_num = 1;
    $last = sizeof($postings);

    foreach ($postings as $post) :
        $last_posting = ($posting_num == $last);
        echo $this->render_partial('index/_post', compact('post', 'last_posting'));

        $posting_num++;
    endforeach
    ?>
</table>
<br>

<div style="width: 54%;text-align: right; float: left">
    <a href="<?= PluginEngine::getLink('forumpp/index/new_entry/'. ($child_topic ? $child_topic : $topic_id)) ?>"><?= makebutton('antworten') ?></a>
</div>
<div style="float: right; padding-right: 10px;">
    <?= $GLOBALS['template_factory']->render('shared/pagechooser', array(
        'page'         => ForumPPHelpers::getPage() + 1,
        'num_postings' => $number_of_entries,
        'perPage'      => ForumPPEntry::POSTINGS_PER_PAGE,
        'pagelink'     => PluginEngine::getLink('forumpp/index/goto_page/'. $topic_id .'/%s')
    )); ?>
</div>