<?php end($tpldata['files']); $lastfile = current($tpldata['files']); reset($tpldata['files']); ?>
<table border="0" cellpadding="1" cellspacing="1" width="100%" class="grayborder">
<tr>
    <th align="center" colspan="2"><?=__($tpldata['name'])?></th>
</tr>
<tr>
    <td valign="top" class="row2" width="100%" colspan="2">
        <?=rcms_parse_text_by_mode($tpldata['desc'], 'text')?>
    </td>
</tr>
<tr>
    <td align="center" valign="middle" class="row2">
   		<?=__('Files in category') . ': ' . sizeof($tpldata['files'])?>
   		<?php if(sizeof($tpldata['files'])){?>
   		<br /><?=__('Last file added at')?> <?=rcms_format_time('d F Y H:i:s', $lastfile['date'])?>
   		<?php }?>
   	</td>
   	<td align="center" valign="middle" class="row3" width="20%">
   	    <a href="<?=$tpldata['link']?>"><?=__('Browse')?></a>
   	</td>
</tr>
</table>