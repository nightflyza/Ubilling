<table border="0" cellpadding="1" cellspacing="1" style="width: 100%;" class="grayborder">
<?php if(!empty($tpldata['showtitle'])) {?>
<tr>
<?php if(!empty($tpldata['linktext']) && !empty($tpldata['linkurl'])) {?>
    <th align="center" colspan="3">
        <a href="<?=$tpldata['linkurl']?>"><?=$tpldata['title']?></a>
   	</th>
<?php } else {?>
    <th align="center" colspan="3">
        <?=$tpldata['title']?>
    </th>
<?php }?>
</tr>
<?php }?>
<tr>
    <td valign="top" colspan="3" class="row2">
        <?php if(!empty($tpldata['cat_data']['icon'])) {?>
            <?php if(!empty($tpldata['iconurl'])) {?>
                <a href="<?=$tpldata['iconurl']?>">
            <?php }?>
            <img src="<?=$tpldata['cat_data']['iconfull']?>" alt="" align="left" />
            <?php if(!empty($tpldata['iconurl'])) {?>
                </a>
            <?php }?>
        <?php }?>
        <?=(empty($tpldata['text'])) ? $tpldata['desc'] : $tpldata['text']?>
    </td>
</tr>
<tr>
    <td class="row3" align="left" style="white-space: nowrap;">
        <?=rcms_format_time('d F Y H:i:s', $tpldata['time'])?>
    </td>
    <td align="center" class="row2" style="width: 100%;">
        <?=__('Posted by')?> <?=user_create_link($tpldata['author_name'], str_replace(' ', '&nbsp;', $tpldata['author_nick']))?>,
        <?=__('Author/source')?>: <?=rcms_parse_text($tpldata['src'], false, false, false, false, false)?>
    </td>
<?php if(!empty($tpldata['linktext']) && !empty($tpldata['linkurl'])) {?>
   	<td class="row3" align="left" style="white-space: nowrap;">
   	    <a href="<?=$tpldata['linkurl']?>"><?=$tpldata['linktext']?></a>
   	</td>
<?php }?>
</tr></table>
<br />