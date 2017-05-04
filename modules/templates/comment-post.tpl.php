<b><?=__('Nickname')?>: [<?=$system->user['nickname']?>]</b>
<?=rcms_show_bbcode_panel('form1.comtext'); ?>
<?if(empty($tpldata['field'])) $field = 'comtext'; else $field = $tpldata['field'];?>

<form method="post" action="" name="form1">
	<textarea name="<?=$field?>" cols="20" rows="7" style="width: 90%"><?=@$tpldata['text']?></textarea>
    <p align="center">
        <input type="submit" value="<?=__('Submit')?>" />
    </p>
</form>