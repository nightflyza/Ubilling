<b><?=__('Nickname')?>: [<?=$system->user['nickname']?>]</b>
<?=($tpldata) ? rcms_show_bbcode_panel('form1.comtext') : ''?>
<form method="post" action="" name="form1">
<textarea name="comtext" cols="20" rows="7" style="width: 90%"></textarea><br />
<p align="center"><input type="submit" value="<?=__('Submit')?>" /></p>
</form>