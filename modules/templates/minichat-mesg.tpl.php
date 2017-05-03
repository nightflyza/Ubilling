<div class="border">
    <div class="row3"><?=user_create_link($tpldata['username'], $tpldata['nickname'])?></div>
    <div class="row2" style=""><?=$tpldata['text']?></div>
    <div class="row3" style="height: auto;">
        <?=rcms_format_time('d F Y H:i:s', $tpldata['time'], $system->user['tz'])?>
    <?php if($system->checkForRight('MINICHAT')){?>
        <form method="post" action="">
            <input type="hidden" name="mcdelete" value="<?=$tpldata['id']?>" />
            <input type="submit" name="" value="X" />
        </form>
    <?php }?>
    </div>
</div>
<br />