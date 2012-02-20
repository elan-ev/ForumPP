<br style="clear: both">

<?
$posting_num = 1;
$zebra = 0;
if (!$section) $section = 'index';

foreach ($postings as $post) :
    // show the line only once and do not show it before the first posting of a thread    
    $zebra = 1 - $zebra;
    echo $this->render_partial('index/_post', compact('post', 'zebra', 'visitdate', 'section'));

    $posting_num++;
endforeach
?>

<div style="float: right; padding-right: 10px;">
    <?= $GLOBALS['template_factory']->render('shared/pagechooser', array(
        'page'         => ForumPPHelpers::getPage() + 1,
        'num_postings' => $number_of_entries,
        'perPage'      => ForumPPEntry::POSTINGS_PER_PAGE,
        'pagelink'     => PluginEngine::getLink('forumpp/index/goto_page/'. $topic_id .'/'. $section .'/%s')
    )); ?>
</div>