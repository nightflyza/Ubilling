<table width="100%" style="border: 1px solid black" cellpadding="0" cellspacing="0">
<tr>
    <td align="left" class="row2">
        &nbsp;&nbsp;&gt; <a href="?module=forum"><?=__('Topics list')?></a> &gt; <?=__('Edit post')?>
    </td>
</tr>
</table>
<br />
<form method="post" action="" name="edit" style="text-align: center">
    <input type="hidden" name="edit_submit" value="1" />
    <input type="hidden" name="topic_id" value="<?=$tpldata[2]?>" />
    <input type="hidden" name="post_id" value="<?=(int)@$tpldata[3]?>" />
    <?php if(!empty($tpldata[0])) { ?><input type="text" name="title" value="<?=$tpldata[0]?>" size="70"/><br /><?php }?>
    <?=rcms_show_bbcode_panel('edit.text')?>
    <textarea name="text" cols="70" rows="7" style="width: 95%;"><?=$tpldata[1]?></textarea><br />
    <input type="submit" value="<?=__('Submit')?>" />
</form>