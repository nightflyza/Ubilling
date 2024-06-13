<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?=$system->config['language']?>" lang="<?=$system->config['language']?>">
<head>                                                        
    <title><?rcms_show_element('title')?></title>
    <?rcms_show_element('meta')?> 
<link href="<?=CUR_SKIN_PATH?>style.css" rel="stylesheet" type="text/css" media="screen" />
</head>
<body>
<div id="wrapper">
	<div id="header" class="container">
		<div id="logo">
			<h1><a href="http://ubilling.net.ua"><img src="skins/logo.png" height="64" border="0"></a><a href="?module=taskbar">Ubilling</a> </h1>
			   <p><?=web_ReleaseInfo();?></p>
                        <div class="notificationArea">
                 <?php 
                    if (LOGGED_IN) {
                        $notifyArea=new DarkVoid();
                        print($notifyArea->render());
                    }
                    ?>
            </div> 
  		</div>
		<div id="menu">
                <ul>
                <li>
                <form name="lang_select" method="post" action=""><?=user_lang_select('lang_form', $system->language, 'font-size: 90%; width: 100px;', 'onchange="document.forms[\'lang_select\'].submit()" title="' . __('Lang') . '"')?></form>
                </li>
                <li>    
                <form name="skin_select" method="post" action=""><?=user_skin_select(SKIN_PATH, 'user_selected_skin', $system->skin, 'font-size: 90%; width: 100px;', 'onchange="document.forms[\'skin_select\'].submit()" title="' . __('Skin') . '"')?></form>                    
                <br>
                     <p align="right"><?=web_HelpIconShow();?></p> <?=zb_IdleAutologoutRun(); ?>
             
                </li>
                
                </ul>
		</div>
	</div>
	<!-- end #header -->
	<div id="page" class="container">
		<div id="content">
                      <? if (LOGGED_IN) {  ?>
          <form action="" method="POST">
	  <input name="logout_form" value="1" type="hidden">
	  <input value="<?=__('Log out').' '.whoami();?>" type="submit">
      	  </form> 
			<div class="post">
                            
                            
                          <p align="right"><a href="?module=taskbar" class="ubButton"><?=__('Taskbar')?></a></p>
			 <?rcms_show_element('menu_point', 'up_center@window')?>
                         <?rcms_show_element('main_point', $module . '@window')?>
			</div>
                    
                
			<div style="clear: both;">&nbsp;</div>
		</div>
	
			<div style="clear: both;">&nbsp;</div>
	
	</div>
	<!-- end #page -->
</div>

<div id="footer">
<p><?rcms_show_element('copyright')?> 
		<?php    	
  // Page gentime end
  $mtime = explode(' ', microtime());
  $totaltime = $mtime[0] + $mtime[1] - $starttime;
  print(__('GT:').round($totaltime,2));
  print(' QC: '.$query_counter);
 ?> </p>
</div>
    <? } else { 
			$ubLoginForm=new LoginForm();
		        print($ubLoginForm->render());
		  }?>	
<!-- end #footer -->
</body>
</html>
