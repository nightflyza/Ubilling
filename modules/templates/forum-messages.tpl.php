<?php
if(!empty($system->config['perpage'])) {
    if(!empty($_GET['pid'])) {
        $pid = (int) $_GET['pid'] - 2;
        if($pid <= sizeof($tpldata['posts'])){
            $_GET['page'] = ceil($pid / $system->config['perpage']);
        }
    }
    
    $pages = ceil(sizeof($tpldata['posts']) / $system->config['perpage']);
    if(!empty($_GET['page']) && ((int) $_GET['page']) > 0) $page = ((int) $_GET['page']) - 1; else $page = 0;
    $start = $page * $system->config['perpage'];
    $total = $system->config['perpage'];
} else {
    $pages = 1;
    $page = 0;
    $start = 0;
    $total = sizeof($tpldata['posts']);
}
$keys = array_keys($tpldata['posts']);
?>
<table width="100%" style="border: 1px solid black" cellpadding="0" cellspacing="0">
<tr>
    <td align="left" class="row2">
        &nbsp;&nbsp;&gt; <a href="?module=forum"><?=__('Topics list')?></a>
        &gt; <?=$tpldata['topic']['title']?>
    </td>
    <td align="right" class="row2">
        <?php if($system->checkForRight('FORUM')){ ?>
        <?php if($tpldata['topic']['closed']){ ?>
        [ <a href="?module=forum&amp;action=oc_topic&amp;id=<?=$tpldata['topic']['id']?>"><?=__('Open topic')?></a> ]
        <?php } else { ?>
        [ <a href="?module=forum&amp;action=oc_topic&amp;id=<?=$tpldata['topic']['id']?>"><?=__('Close topic')?></a> ]
        <?php } ?>
        <?php if($tpldata['topic']['sticky']){ ?>
        [ <a href="?module=forum&amp;action=st_topic&amp;id=<?=$tpldata['topic']['id']?>"><?=__('Unstick topic')?></a> ]
        <?php } else { ?>
        [ <a href="?module=forum&amp;action=st_topic&amp;id=<?=$tpldata['topic']['id']?>"><?=__('Stick topic')?></a> ]
        <?php } ?>
        <?php } ?>
        [ <a href="?module=forum&amp;action=new_topic"><?=__('New topic')?></a> ]
    </td>
</tr>
</table>
<br />
<div align="right">
    <?=rcms_pagination(sizeof($tpldata['posts']), $system->config['perpage'], $page + 1, '?module=forum&amp;action=topic&amp;id=' . $tpldata['topic']['id'])?>
</div>
<table width="100%" cellpadding="2" cellspacing="1" style="border: 1px solid black;">
<tr height="10">
    <td class="row2" rowspan="2" valign="top" align=center>
        <a name="1" />
        <?php if(!$tpldata['topic']['closed']) {?><a href="javascript:insert_text(document.forms['new_post'].elements['new_post_text'], ' [b]<?=str_replace('\'', '\\\'', $tpldata['topic']['author_nick'])?>[/b] ')"><?php }?><b><?=$tpldata['topic']['author_nick']?></b><?php if(!$tpldata['topic']['closed']) {?></a><?php }?><br />
        <?=show_avatar($tpldata['topic']['author_name'])?><br>
        <?=rcms_get_user_status($tpldata['topic']['author_name'])?><br />
        <?php if (!pm_disabled()) print '<a href="?module=pm&for='.$tpldata['topic']['author_name'].'">'.__('Send PM').'</a><br />'; ?>
    </td>
    <td class="row2" align="left" width="100%">
        <small><?=$tpldata['topic']['title']?> - <?=rcms_format_time('H:i:s d\&\n\b\s\p\;F\&\n\b\s\p\;Y', $tpldata['topic']['date'])?><?php if ((isset($tpldata['topic']['author_ip'])) AND ($system->checkForRight('FORUM'))) { ?> <?=$tpldata['topic']['author_ip']?> <?php }?>  </small>
    </td>
    <td class="row2" align="right" nowrap="nowrap">
        <small>
            <?php if($tpldata['topic']['author_name'] != 'guest'){ ?>
            [ <?=user_create_link($tpldata['topic']['author_name'], __('Profile'))?> ]  
            <?php } ?>
            <?php if($system->checkForRight('FORUM') || ($system->user['username'] != 'guest' && $system->user['username'] == $tpldata['topic']['author_name'])){ ?>
            [ <a href="?module=forum&amp;action=ed_topic&amp;t=<?=$tpldata['topic']['id']?>"><?=__('Edit')?></a> ]
            <?php } ?>
            <?php if($system->checkForRight('FORUM') || ($system->user['username'] != 'guest' && $system->user['username'] == $tpldata['topic']['author_name'] && $tpldata['topic']['replies'] == 0)){ ?>
            [ <a href="?module=forum&amp;action=del_topic&amp;t=<?=$tpldata['topic']['id']?>"><?=__('Delete')?></a> ]
            <?php } ?>
        </small>
    </td>
</tr>
<tr>
    <td class="row2" style="height: 60px;" valign="top" colspan="2">
        <?=rcms_parse_text($tpldata['topic']['text'], true, false, true, true, true)?>
    </td>
</tr>
<?php
$c = $start;
while ($total > 0 && $c < sizeof($keys)){
    $post = &$tpldata['posts'][$keys[$c]];
    $post_id = $keys[$c];
    if(!empty($post)) {
?>
<tr>
	<td colspan=4><hr></td>
</tr>
<tr height="10">
    <td class="row2" rowspan="2" valign="top" align=center>
        <a name="<?=$post_id + 2?>" />
        <?php if(!$tpldata['topic']['closed']) {?><a href="javascript:insert_text(document.forms['new_post'].elements['new_post_text'], ' [b]<?=str_replace('\'', '\\\'', $post['author_nick'])?>[/b] ')"><?php }?><b><?=$post['author_nick']?></b><?php if(!$tpldata['topic']['closed']) {?></a><?php }?><br />
        <?=show_avatar($post['author_name'])?><br>
        <?=rcms_get_user_status($post['author_name'])?><br />
        <?php if (!pm_disabled()) print '<a href="?module=pm&for='.$post['author_name'].'">'.__('Send PM').'</a><br />'; ?>
    </td>
    <td class="row2" align="left" width="100%">
        <small><?=rcms_format_time('H:i:s d\&\n\b\s\p\;F\&\n\b\s\p\;Y', $post['date'])?> <?php if ((isset($post['author_ip'])) AND ($system->checkForRight('FORUM'))) { ?> <?=$post['author_ip']?> <?php }?> </small>
    </td>
    <td class="row2" align="right" nowrap="nowrap">
        <small>
            <?php if($post['author_name'] != 'guest'){ ?>
            [ <?=user_create_link($post['author_name'], __('Profile'))?> ]
            <?php } ?>
            <?php if($system->checkForRight('FORUM') || ($system->user['username'] !== 'guest' && $system->user['username'] == $post['author_name'])){ ?>
            [ <a href="?module=forum&amp;action=ed_post&amp;t=<?=$tpldata['topic']['id']?>&amp;p=<?=$post_id + 2?>"><?=__('Edit')?></a> ]
            [ <a href="?module=forum&amp;action=del_post&amp;t=<?=$tpldata['topic']['id']?>&amp;p=<?=$post_id + 2?>"><?=__('Delete')?></a> ]
            <?php } ?>
        </small>
    </td>
</tr>
<tr>
    <td class="row2" style="height: 60px;" valign="top" colspan="2">
        <?=rcms_parse_text($post['text'], true, false, true, true, true)?>
    </td>
</tr>
<?php
        $total--;
    }
    $c++;
}
?>
</table>
<div align="left">
    <?=rcms_pagination(sizeof($tpldata['posts']), $system->config['perpage'], $page + 1, '?module=forum&amp;action=topic&amp;id=' . $tpldata['topic']['id'])?>
</div>