<?php if(!empty($title)) {?>
<header><h3><?=$title?></h3></header><div class="toggleWMAN">
<?php }?>
<div class="module_content" style="text-align: <?=$align?>;">
    <?=$content?>
</div>
<?php if(!empty($title)) {?>
    </div>
<?php }?>
<br>
