<form action="" method="post">
<input type="hidden" name="vote" value="<?=$tpldata['id']?>" />
<table cellspacing="1" cellpadding="1" border="0" style="width: 100%;">
<tr>
    <th colspan="3"><?=$tpldata['q']?></th>
</tr>
<? if(!$tpldata['voted']) { ?>
<? foreach ($tpldata['v'] as $v_id => $v_title){ ?>
<tr class="row1">
    <td><input type="radio" name="poll_vote" value="<?=$v_id?>" /></td>
    <td align="left" style="width: 100%;"><?=$v_title?></td>
</tr>
<? } ?>
<tr>
    <td colspan="3" class="row1" align="center"><input type="submit" name="" value="<?=__('Submit')?>" /></td>
</tr>
<? } else {?>
<? foreach ($tpldata['X'] as $v_id => $v_cnt){ ?>
<tr class="row1">
    <td align="left" style="width: 100%;"><?=$tpldata['v'][$v_id]?></td>
    <td align="right"><?=$v_cnt?></td>
</tr>
<tr>
    <td colspan="3">
       <table cellspacing="0" cellpadding="0" border="0" style="width: 100%;">
        <tr>
            <td class="row3" width="<?=$tpldata['p'][$v_id]+1?>%" style="white-space: nowrap;">&nbsp;</td>
            <td class="row2">&nbsp;</td>
        </tr>
        </table>
    </td>
</tr>
<? } ?>
<? } ?>
<tr>
    <th colspan="3" align="center">[<?=__('Total votes') . ': ' . $tpldata['t']?>]</th>
</tr>
</table>
</form>
<hr />