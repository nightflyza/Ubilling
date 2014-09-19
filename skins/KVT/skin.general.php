<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?=$system->config['language']?>" lang="<?=$system->config['language']?>">
  <head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title><?php rcms_show_element('title')?></title>
    <?php rcms_show_element('meta')?>
    <?php if ( defined('BOOTSTRAP') && constant('BOOTSTRAP') ): ?>
    <!-- Bootstrap -->
    <link href="/skins/bootstrap/css/bootstrap.min.css" rel="stylesheet" />
    <link href="/skins/bootstrap/css/bootstrap-theme.min.css" rel="stylesheet" />
    <!-- HTML5 Shim and Respond.js IE8 support of HTML5 elements and media queries -->
    <!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
    <!--[if lt IE 9]>
      <script src="https://oss.maxcdn.com/html5shiv/3.7.2/html5shiv.min.js"></script>
      <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
    <![endif]-->
    <script src="/skins/bootstrap/js/bootstrap.min.js"></script>
    <?php endif; ?>
    <!-- Default Styles -->
    <link href="<? echo CUR_SKIN_PATH; ?>style.css" rel="stylesheet" type="text/css" media="screen" />
  </head>
  <body> 
    <div id="header">
      <div id="logo">
        <h1>
          <a href="http://ubilling.net.ua"><img src="skins/logo.png" height="32" border="0"></a> Ubilling
        </h1>
        <p><?php echo file_get_contents('RELEASE'); ?></p>
        <form name="lang_select" method="post" action="">
          <?php echo user_lang_select('lang_form', $system->language, 'font-size: 90%; width: 100px;', 'onchange="document.forms[\'lang_select\'].submit()" title="' . __('Lang') . '"')?>
        </form>
        <form name="skin_select" method="post" action="">
          <?php echo user_skin_select(SKIN_PATH, 'user_selected_skin', $system->skin, 'font-size: 90%; width: 100px;', 'onchange="document.forms[\'skin_select\'].submit()" title="' . __('Skin') . '"')?>
        </form>
        <?php echo web_HelpIconShow();?>
        <?php if ( XHPROF ): ?>
        <?php echo $xhprof_link; ?>
        <?php endif; ?>
        <?php echo zb_IdleAutologoutRun(); ?>
      </div>
      <? if ( LOGGED_IN ):  ?>  
      <form action="" method="POST">
        <input name="logout_form" value="1" type="hidden" />
        <input value="<?=__('Log out').' '.whoami();?>" type="submit" />
      </form> 
    </div> 
	<div id="menu">
      <ul>
        <?rcms_show_element('navigation', '<li><a href="{link}" target="{target}" id="{id}">{title}</a></li>')?>
      </ul>
	</div>
      <?php if ( defined('BOOTSTRAP') && constant('BOOTSTRAP') ): ?>
    <div class="container" style="background: #fff">
      <?php rcms_show_element('menu_point', 'up_center@window');  ?>
      <?php rcms_show_element('main_point', $module . '@window'); ?>
    </div>
      <?php else: ?>
    <center>
      <table style="text-align: left; width: 90%;" border="0" cellpadding="2" cellspacing="2" bgcolor="#FFFFFF">
        <tbody>
          <tr>
            <td class="post">
              <?php rcms_show_element('menu_point', 'up_center@window')?>
              <?php rcms_show_element('main_point', $module . '@window')?>
            </td>
          </tr>
        </tbody>
      </table>
    </center>
      <?php endif; ?>
	<?php else: ?> 
      <?php if ( file_exists('DEMO_MODE') ): ?>
    <form action="" method="post">
      <input type="hidden" name="login_form" value="1" />
      &nbsp; '.__('Login').' <input name="username" type="text" value="admin" size="12">
      &nbsp; '.__('Password').' <input name="password" type="password"  value="demo" size="12">
      <input value="'.__('Log in').'" type="submit">
    </form>
      <? else: ?>
    <form action="" method="post">
      <input type="hidden" name="login_form" value="1">
      &nbsp; '.__('Login').' <input name="username" type="text" size="12">
      &nbsp; '.__('Password').' <input name="password" type="password" size="12">
      <input value="'.__('Log in').'" type="submit">
    </form>
      <?php endif; ?>
    <?php endif; ?>	
    <div style="clear: both;">&nbsp;</div>
    <div id="footer">
      <p>
        <?php rcms_show_element('copyright'); ?> 
        <?php // Page gentime end
          $mtime = explode(' ', microtime());
          $totaltime = $mtime[0] + $mtime[1] - $starttime;
        ?>
        <?php echo __('GT:') . round($totaltime,2); ?>
        <?php echo __('QC:') . $query_counter; ?>
      </p>
	</div>
  </body>
</html>