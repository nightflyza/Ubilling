<?php if ( defined('BOOTSTRAP') && constant('BOOTSTRAP') ): ?>
  <?php if ( !empty($title) ): ?>
  <div class="page-header" style="margin: 10px 5px">
    <h2><?php echo $title; ?></h2>
  </div>
  <?php endif; ?>
  <div class="row">
    <div class="col-md-12">
      <?php echo $content; ?>
    </div>
  </div>
<?php else: ?>
  <?php if ( !empty($title) ): ?>
  <div class="title">
    <h2><?php echo $title; ?></h2>
  </div>
  <?php endif; ?>
  <div class="window-main" style="text-align: <?php echo $align; ?>">
    <?php echo $content; ?>
  </div>
  <br>
<?php endif; ?>
