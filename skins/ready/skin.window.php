<?php if (!empty($content)) {
    ?>
    <div class="card">
        <?php if (!empty($title)) { ?>
            <div class="card-header">  <h4 class="card-title"><?= $title ?></h4> </div>
        <?php } ?>
        <div class="card-body" style="text-align: <?= $align ?>;">
            <?= $content ?>
        </div>
    </div>
    <?php
} else {
    print($content);
}
?>