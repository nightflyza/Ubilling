<table border="0" cellpadding="1" cellspacing="1" width="100%" class="grayborder">
<tr>
    <th align="left" width="100%">
        [<?=rcms_format_time('H:i:s d.m.Y', $tpldata['time'], $system->user['tz'])?>]
        <?=__('Posted by')?> <?=user_create_link($tpldata['author_user'], $tpldata['author_nick'])?>
        <?php if ((isset($tpldata['author_ip'])) AND ($system->checkForRight('ARTICLES-MODERATOR'))) { ?> <?=$tpldata['author_ip']?>   <?php }?>
    </th>
    <th align="right">
        <?php if($system->checkForRight('ARTICLES-MODERATOR')){?>
           <form method="post" action="">
            <input type="hidden" name="cdelete" value="<?=$tpldata['id']?>" />
            <input type="submit" name="" class="delete_button" value="" />
        </form>
        <?php }?>
    </th>
</tr>
</table>
<table border="0" cellpadding="1" cellspacing="1" width="100%">
<tr>
<td align="left" valign="top" class="row3" width="10%"><?=show_avatar($tpldata['author_user'])?></td>
<td align="left" valign="top" class="row3" width="90%"><?=$tpldata['text']?></td>
</tr>
</table>