<form method="post" action="" name="new_post" style="text-align: center">
    <input type="hidden" name="new_post_perform" value="1" />
    <input type="hidden" name="new_post_topic" value="<?=$tpldata[0]?>" />
    <?=rcms_show_bbcode_panel('new_post.new_post_text')?>
    <textarea name="new_post_text" cols="70" rows="7" style="width: 95%;"><?=$tpldata[1]?></textarea><br />
    <input type="submit" value="<?=__('Submit')?>" />
</form>