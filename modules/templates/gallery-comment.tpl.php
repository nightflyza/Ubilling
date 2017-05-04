<table border="0" cellpadding="1" cellspacing="1" style="width: 100%;" class="grayborder">
<tr>
    <th align="left" style="width: 100%;">
        [<?=rcms_format_time('d F Y H:i:s', $tpldata['time'], $system->user['tz'])?>]
        <?=__('Message by')?> <?=user_create_link($tpldata['username'], $tpldata['nickname'])?>
    </th>
    <th align="right">
        <?php if($system->checkForRight('GALLERY')){?>
        <form method="post" action="">
            <input type="hidden" name="gcd" value="<?=$tpldata['id']?>">
            <input type="submit" name="" value="" class="delete_button">
        </form>
        <?php }?>
    </th>
</tr>
<tr>
    <td align="left" class="row3" colspan="2" style=""><?=$tpldata['text']?></td>
</tr>
</table>