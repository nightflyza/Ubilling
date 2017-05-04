<?php
if(!empty($system->config['perpage'])) {
    $pages = ceil(sizeof($tpldata) / ($system->config['perpage'] * 3));
    if(!empty($_GET['page']) && ((int) $_GET['page']) > 0) $page = ((int) $_GET['page'])-1; else $page = 0;
    $start = $page * $system->config['perpage'] * 3;
    $total = $system->config['perpage'] * 3;
} else {
    $pages = 1;
    $page = 0;
    $start = 0;
    $total = sizeof($tpldata);
}
?>
<table width="100%" style="border: 1px solid black" cellpadding="0" cellspacing="0">
<tr>
    <td align="left" class="row2">
        &nbsp;&nbsp;&gt; <a href="?module=forum"><?=__('Topics list')?></a>
    </td>
    <td align="right" class="row2">
        [ <a href="?module=forum&amp;action=new_topic"><?=__('New topic')?></a> ]
    </td>
</tr>
</table>
<br />
<div align="right"><?=rcms_pagination(sizeof($tpldata), $system->config['perpage'] * 3, $page + 1, '?module=forum')?></div>
<table width=100% cellpadding=2 cellspacing=1 class=forumline>
<tr>
    <th width="100%"><?=__('Topic')?></th>
    <th nowrap="nowrap" align="center"><?=__('Date')?></th>
    <th nowrap="nowrap" align="center"><?=__('Replies')?></th>
    <th nowrap="nowrap" align="center"><?=__('Author')?></th>
    <th nowrap="nowrap" align="center"><?=__('Last reply')?></th>
</tr>
<?php
$order = array();
$sorder = array();
foreach ($tpldata as $topic_id => $topic) {
    if(!empty($topic)){
        if(empty($topic['sticky'])) $order[$topic['last_reply']] = $topic_id;
        else $sorder[$topic['last_reply']] = $topic_id;
    }
}
krsort($order);
krsort($sorder);
$order = array_merge($sorder, $order);
$c = $start;
while ($total > 0 && $c <= sizeof($order)){
    if(isset($order[$c]) && !empty($tpldata[$order[$c]])){
        $topic_id = &$order[$c];
        $topic = &$tpldata[$topic_id];
        $msg = '';
        $style = 'row2';
        if(!empty($topic['closed'])) $msg .= '[' . __('Closed') . '] ';
        if(!empty($topic['sticky'])) {
            $msg .= __('Sticky') . ': ';
            $style = 'row3';
        }
?>
<tr>
    <td class="<?=$style?>" width="100%">
        <b><?=$msg?></b>
        <a href="?module=forum&amp;action=topic&amp;id=<?=$topic_id?>"><?=$topic['title']?></a>
        <?=rcms_pagination($topic['replies'], $system->config['perpage'], -1, '?module=forum&amp;action=topic&amp;id=' . $topic_id)?>
    </td>
    <td class="<?=$style?>" nowrap="nowrap" align="center">
        <?php if(rcms_format_time('d', $topic['date']) == rcms_format_time('d', rcms_get_time())) { ?> <?=__('Today at') . ' ' . rcms_format_time('H:i:s', $topic['date'])?> <?php } else {?>
            <?=rcms_format_time('H:i:s d.m.Y', $topic['date'])?>
        <?php }?>
    </td>
    <td class="<?=$style?>" nowrap="nowrap" align="center">
        <?=(int)@$topic['replies']?>
    </td>
    <td class="<?=$style?>" nowrap="nowrap" align="center">
        <?=user_create_link($topic['author_name'], $topic['author_nick'])?>
    </td>
    <td class="<?=$style?>" nowrap="nowrap" align="center">        
        <?php if($topic['last_reply'] != 0 && @$topic['last_reply_id'] != 0){?>
            <?php if(rcms_format_time('d', $topic['last_reply']) == rcms_format_time('d', rcms_get_time())) { ?>
                <?=__('Today at') . ' ' . rcms_format_time('H:i:s', $topic['last_reply'])?>
            <?php } else {?>
                <?=rcms_format_time('H:i:s d.m.Y', $topic['last_reply'])?>
            <?php }?>
            <?php if(!empty($topic['last_reply_author'])) {?>
            	<br /><?=__('Author')?>: <?=$topic['last_reply_author']?>
            <?php }?>
            <a href="?module=forum&amp;action=topic&amp;id=<?=$topic_id?>&amp;pid=<?=$topic['last_reply_id'] + 2?>#<?=$topic['last_reply_id'] + 2?>">&gt;&gt;</a>
        <?php } else {?>
            <?=__('No replies')?>
        <?php }?>
    </td>
</tr>
<?php
        $total--;
    }
    $c++;
}
?>
</table>
<div align="left"><?=rcms_pagination(sizeof($tpldata), $system->config['perpage'] * 3, $page + 1, '?module=forum')?></div>