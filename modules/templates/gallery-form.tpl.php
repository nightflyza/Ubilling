<span style="font-weight: bold;"><?=__('Nickname')?>: [<?=$system->user['nickname']?>]</span
<?=rcms_show_bbcode_panel('form1.comtext'); ?>
<form method="post" action="" name="form1">
<input type="hidden" name="id" value="<?=(int)@$_GET['id']?>" />
<textarea name="comtext" cols="20" rows="7" style="width: 90%"></textarea><br />
<p align="center"><input type="submit" value="<?=__('Submit')?>" /></p>
</form>