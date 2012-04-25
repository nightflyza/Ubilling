<table border="0" cellpadding="2" cellspacing="1" width="100%" class="grayborder">
<tr>
    <th align="center" colspan="2"><?=__($tpldata['name'])?></th>
</tr>
<?php if(!empty($tpldata['desc'])) { ?>
<tr>
    <td align="left" valign="top" class="row3" nowrap="nowrap"><?=__('Description')?>: </td>
    <td align="left" valign="top" class="row3" width="100%"><?=rcms_parse_text_by_mode($tpldata['desc'], 'text')?></td>
</tr>
<?php } ?>
<tr>
    <td align="left" valign="top" class="row3" nowrap="nowrap"><?=__('Downloads count')?></td>
    <td align="left" valign="top" class="row3" width="100%"><?=(int)@$tpldata['count']?></td>
</tr>
<tr>
    <td align="left" valign="top" class="row3" nowrap="nowrap"><?=__('Size of file')?></td>
    <td align="left" valign="top" class="row3" width="100%"><?=$tpldata['size']?></td>
</tr>
<tr>
    <td align="left" valign="top" class="row3" nowrap="nowrap"><?=__('Author')?></td>
    <td align="left" valign="top" class="row3" width="100%"><?=$tpldata['author']?></td>
</tr>
<tr>
    <td align="left" valign="top" class="row3" nowrap="nowrap"><?=__('Date')?></td>
    <td align="left" valign="top" class="row3" width="100%"><?=rcms_format_time('d F Y H:i:s', $tpldata['date'])?></td>
</tr>
<tr>
   	<th align="center" colspan="2">
   	    <a href="<?=$tpldata['down_url']?>" target="_blank">
   	        <?=__('Download!')?>
   	    </a>
   	</th>
</tr>
</table>