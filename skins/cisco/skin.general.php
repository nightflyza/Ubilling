<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?=$system->config['language']?>" lang="<?=$system->config['language']?>">
<head>                                                        
    <title><?rcms_show_element('title')?></title>
    <?rcms_show_element('meta')?> 
<link href="<?=CUR_SKIN_PATH?>style.css" rel="stylesheet" type="text/css" media="screen" />
<link href="<?= CUR_SKIN_PATH ?>ubim.css" rel="stylesheet" type="text/css" media="screen" />
</head>
<body> 
	<div id="header">
		<div id="logo">
			<h1><a href="http://ubilling.net.ua"><img src="skins/logo.png" height="32" border="0"></a> Ubilling</h1>
			<p><?=web_ReleaseInfo();?></p>
                        <form name="lang_select" method="post" action=""><?=user_lang_select('lang_form', $system->language, 'font-size: 90%; width: 100px;', 'onchange="document.forms[\'lang_select\'].submit()" title="' . __('Lang') . '"')?>
                        </form>
                        <form name="skin_select" method="post" action=""><?=user_skin_select(SKIN_PATH, 'user_selected_skin', $system->skin, 'font-size: 90%; width: 100px;', 'onchange="document.forms[\'skin_select\'].submit()" title="' . __('Skin') . '"')?>
                                      
                        </form>
                        <?=web_HelpIconShow();?> <?=zb_IdleAutologoutRun(); ?>
                          <div class="notificationArea">
                 <?php 
                    if (LOGGED_IN) {
                        $notifyArea=new DarkVoid();
                        print($notifyArea->render());
                    }
                    ?>
            </div> 
		</div> <? if (LOGGED_IN) {  ?>
           <form action="" method="POST">
	  <input name="logout_form" value="1" type="hidden">
	  <input value="<?=__('Log out').' '.whoami();?>" type="submit">
      	  </form>
     
	</div> 
	<div id="menu">
		<ul>
				<?rcms_show_element('navigation', '<li><a href="{link}" target="{target}" id="{id}">{title}</a></li>')?>
		</ul>
	</div>
	<center>
<table style="text-align: left; width: 90%;" border="0" cellpadding="2" cellspacing="2" bgcolor="#FFFFFF">
  <tbody>
    <tr>
      <td class="post">
        <?rcms_show_element('menu_point', 'up_center@window')?>
        <?rcms_show_element('main_point', $module . '@window')?>
      </td>
    </tr>
  </tbody>
</table>
</center>
			
      
		
<div style="clear: both;">&nbsp;</div>
	
	
	<div id="footer">
		<p><?rcms_show_element('copyright')?> 
		<?php    	
  // Page gentime end
  $mtime = explode(' ', microtime());
  $totaltime = $mtime[0] + $mtime[1] - $starttime;
  print(__('GT:').round($totaltime,2));
  print(' QC: '.$query_counter);
 ?> </p>
            
            <? } else { 
			$ubLoginForm=new LoginForm();
                        print($ubLoginForm->render());
		  }?>	
	</div>
	</body>
</html>
