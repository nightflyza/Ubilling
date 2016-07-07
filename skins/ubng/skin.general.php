<!doctype html>
<html lang="ru">
<head>
	<meta charset="utf-8"/>
	<title><?rcms_show_element('title')?></title>
	<?rcms_show_element('meta')?> 
	<link rel="stylesheet" href="<?=CUR_SKIN_PATH?>css/layout.css" type="text/css" media="screen" />
        <link rel="stylesheet" href="<?=CUR_SKIN_PATH?>css/ubilling.css" type="text/css" media="screen" />
	<!--[if lt IE 9]>
	<link rel="stylesheet" href="css/ie.css" type="text/css" media="screen" />
	<script src="http://html5shim.googlecode.com/svn/trunk/html5.js"></script>
	<![endif]-->
        <script src="modules/jsc/jquery.cookie.js" type="text/javascript"></script>
        <script src="modules/jsc/glmenuCollapser.js" type='text/javascript'></script> 
	<script src="modules/jsc/hideshow.js" type="text/javascript"></script>
        <script src="modules/jsc/winman.js" type="text/javascript"></script>
</head>


<body>

	<header id="header">
		<hgroup>
                    <h1 class="site_title">
                    <a href="http://ubilling.net.ua">
                    <img src="<?=CUR_SKIN_PATH?>/images/logo.png" height="32" border="0">
                    </a> 
                    <span class="ubproductname">Ubilling</span>
                    <sup class="ubverinfo"><?=file_get_contents('RELEASE')?></sup>
                    </h1>
                        
                    <div class="notificationArea">
                    <?php 
                    if (LOGGED_IN) {
                        //display notification area
                        $notifyArea=new DarkVoid();
                        print($notifyArea->render());
                    } else { ?>
                        <div>
                            <form style="float:left;" name="lang_select" method="post" action=""><img src="skins/menuicons/icn_settings.png"><?=user_lang_select('lang_form', $system->language, 'font-size: 90%; width: 100px;', 'onchange="document.forms[\'lang_select\'].submit()" title="' . __('Lang') . '"')?></form>
                            <form style="float:left;" name="skin_select" method="post" action=""><?=user_skin_select(SKIN_PATH, 'user_selected_skin', $system->skin, 'font-size: 90%; width: 100px;', 'onchange="document.forms[\'skin_select\'].submit()" title="' . __('Skin') . '"')?></form>
                        </div>
                    <?php    
                    }
                    ?>
                    </div>
                    
                    <div class="btn_view_help"><?=web_HelpIconShow();?>  <? if (XHPROF) { print($xhprof_link); } ?> <?=zb_IdleAutologoutRun(); ?></div>
		</hgroup>
	</header> <!-- end of header bar -->
	<? if (LOGGED_IN) {  ?> 
	<section id="secondary_bar">
		<div class="user">
                    <p>
                    <a href="?idleTimerAutoLogout=true" title="<?=__('Log out');?>" class="logout_user"><img src="skins/ubng/images/poweroff.png"></a>
                    <?=  whoami();?>
                    </p>
                    <a class="menu_toggle" href="javascript:showhideGlobalMenu();" title="<?=__('Toggle menu');?>"><?=__('Toggle menu');?></a> 
                </div>
		<div class="breadcrumbs_container">
			<article class="breadcrumbs">
                            <?php
                            $globalMenu=new GlobalMenu();
                            //rebuild fast access menu cache on language switch
                            if (wf_CheckPost(array('lang_form'))) {
                                $globalMenu->rebuildFastAccessMenuData();
                            }
                            print($globalMenu->renderFastAccessMenu());
                            ?>
                           
                        </article>
		</div>
	</section><!-- end of secondary bar -->
	
	<aside id="sidebar" class="column">
		<form class="quick_search" method="POST" action="?module=usersearch">
                   <?php
                   if (cfr('USERSEARCH')) {
                    $globalSearch=new GlobalSearch();
                    print($globalSearch->renderSearchInput());
                   }
                   ?>
		</form>
		<hr/>
		
                <?php
                //display global menu widget
                print($globalMenu->render());
                ?>
	
		<h3><?=__('Administrator');?></h3>
		<ul class="toggle">
                    <li>
                         <form action="" method="POST">
                         <input name="logout_form" value="1" type="hidden">
                         <img src="skins/menuicons/icn_jump_back.png"><input value="<?=__('Log out').' '.whoami();?>" type="submit">
                        </form>
                        </li>
		    <li>
                            <form name="lang_select" method="post" action=""><img src="skins/menuicons/icn_settings.png"><?=user_lang_select('lang_form', $system->language, 'font-size: 90%; width: 100px;', 'onchange="document.forms[\'lang_select\'].submit()" title="' . __('Lang') . '"')?></form>
                            <form name="skin_select" method="post" action=""><img src="skins/menuicons/icn_settings.png"><?=user_skin_select(SKIN_PATH, 'user_selected_skin', $system->skin, 'font-size: 90%; width: 100px;', 'onchange="document.forms[\'skin_select\'].submit()" title="' . __('Skin') . '"')?></form>
                           
                    </li>
                   <?php if (cfr('GLMENUCONF')) { ?> <li><img src="skins/menuicons/icn_settings.png"><a href="?module=glmenuconf"><?=__('Personalize menu');?></a></li> <?php } ?>
                    </ul>
                
		<footer>
       <?php
        if ((LOGGED_IN) AND (!file_exists('I_HATE_NEW_YEAR'))) {
        $dateny = time();
        $monthny = date('m');

        $date_startny = null;
        $date_stopny  = null;

        switch ($monthny) {
                case '12':
                        $date_startny = strtotime (date('Y') . '-12-25');
                        $date_stopny  = strtotime ((date('Y') + 1) . '-1-05');
                        break;
                case '1':
                        $date_startny = strtotime ((date('Y') - 1) . '-12-25');
                        $date_stopny  = strtotime (date('Y') . '-1-05');
                        break;
        }

             if ( $dateny >= $date_startny && $dateny < $date_stopny ) {
                    print(file_get_contents('skins/ubny.txt')); 
                    }
                    
                }
                
        ?>
                    	<hr />
			<p><strong><?rcms_show_element('copyright')?> </strong></p>
			<p>
		<?php    	
  // Page gentime end
  $mtime = explode(' ', microtime());
  $totaltime = $mtime[0] + $mtime[1] - $starttime;
  print(__('GT:').round($totaltime,2));
  print(' QC: '.$query_counter);
 ?></p>
		</footer>
	</aside><!-- end of sidebar -->
	
	<section id="main" class="column">
	 <article class="module width_full">
          	<?rcms_show_element('menu_point', 'up_center@window')?>
                <?rcms_show_element('main_point', $module . '@window')?>
         </article>
		<div class="spacer"></div>
	</section>
<? } else { 
                $ubLoginForm=new LoginForm();
		print($ubLoginForm->render());
		  }
                  ?>	
               

</body>

</html>
