<? if (!empty($flash['messages'])) foreach ($flash['messages'] as $type => $message): ?>
    <? if (Request::isAjax()) : ?>
    <?= utf8_encode(MessageBox::$type($message)) ?>
    <? else : ?>
    <?= MessageBox::$type($message) ?>
    <? endif ?>
<? endforeach ?>