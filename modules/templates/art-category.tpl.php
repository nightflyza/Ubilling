<?php global $module ?>
<table border="0" cellpadding="1" cellspacing="1" style="width: 100%;" class="grayborder">
<tr>
    <th align="center" colspan="2">
        <a href="<?=$tpldata['link']?>"><?=$tpldata["title"]?></a>
    </th>
</tr>
<tr>
    <td valign="top" class="row2" width="70%">
        <?php if(!empty($tpldata['icon'])) {?><img src="<?=$tpldata['iconfull']?>" alt="" align="left" /><?php }?>
        <?=@$tpldata['description']?>
    </td>
    <td class="row3" width="30%">
        <table border="0" cellpadding="1" cellspacing="1" style="width: 100%;">
   	    <tr>
   	        <td class="row2">
   	            <?=__('Articles count')?>: <?=$tpldata['articles_clv']?>
   	        </td>
   	    </tr>
   	    <tr>
   	        <?php if($tpldata['articles_clv'] != '0' && !empty($tpldata['last_article'])){?>
   	        <td class="row2">
   	            <?=__('Last article')?>:<br />
   	            <a href="<?=$tpldata['link'] . '&amp;a=' . $tpldata['last_article']['id']?>"><?=$tpldata['last_article']['title']?></a><br/>
   	            <?=rcms_format_time('d F Y H:i:s', $tpldata['last_article']['time'])?>
   	        </td>
   	        <?php }?>
   	    </tr>
   	    </table>
    </td>
</tr>
</table>