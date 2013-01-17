<form method="post" action="">
    <input type="hidden" name="add_minichat_message" value="1" />
    <b><?=__('Nickname')?>: [<?=$system->user['nickname']?>]</b>
    <textarea rows="3" cols="10" name="mctext" style="width: 90%;"></textarea><br />
    <input type="submit" value="<?=__('Submit')?>" name="submit" />
</form>