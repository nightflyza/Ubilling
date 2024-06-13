<!DOCTYPE html>
<html>

<head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1" />
    <title><? rcms_show_element('title') ?></title>
    <meta content='width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0, shrink-to-fit=no' name='viewport' />
    <? rcms_show_element('meta') ?>
    <link rel="stylesheet" href="<?= CUR_SKIN_PATH ?>assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i">
    <link rel="stylesheet" href="<?= CUR_SKIN_PATH ?>assets/css/ready.css">
    <link rel="stylesheet" href="<?= CUR_SKIN_PATH ?>assets/css/ubilling.css">
    <link href="<?= CUR_SKIN_PATH ?>assets/css/stickynotes.css" rel="stylesheet" type="text/css">
</head>

<body>
    <div class="wrapper">
        <? if (LOGGED_IN) {  ?>
            <div class="main-header">
                <div class="logo-header">
                    <a href="?module=taskbar" class="logo">
                        <img src="<?= CUR_SKIN_PATH ?>assets/img/logo.png" height="32" border="0">
                    </a>
                    <span class="ubproductname"><a href="https://ubilling.net.ua/" target="blank" class="logo">Ubilling</a></span>
                    <small><sup class="ubverinfo"><?= web_ReleaseInfo(); ?></sup></small>
                    <button class="navbar-toggler sidenav-toggler ml-auto" type="button" data-toggle="collapse" data-target="collapse" aria-controls="sidebar" aria-expanded="false" aria-label="Toggle navigation">
                        <span class="navbar-toggler-icon"></span>
                    </button>
                    <button class="topbar-toggler more"><i class="la la-ellipsis-v"></i></button>
                </div>
                <nav class="navbar navbar-header navbar-expand-lg">


                    <div class="container-fluid">
                        <div class="globalsearchinput">
                            <form class="navbar-left navbar-form nav-search mr-md-3" method="POST" action="?module=usersearch">
                                <div class="input-group">
                                    <?php
                                    if (cfr('USERSEARCH')) {
                                        $globalSearch = new GlobalSearch(CUR_SKIN_PATH . 'assets/css/');
                                        print($globalSearch->renderSearchInput('form-control globalsearchinput'));
                                    }
                                    ?>
                                    <div class="input-group-append">
                                        <span class="input-group-text">
                                            <i class="la la-search search-icon"></i>
                                        </span>
                                    </div>
                            </form>
                        </div>
                    </div>


                    <ul class="navbar-nav topbar-nav ml-md-auto align-items-center">
                        <div class="darkvoid">
                            <?php
                            if (LOGGED_IN) {
                                //display notification area
                                $notifyArea = new DarkVoid();
                                print($notifyArea->render());
                            }
                            ?>
                            <?= web_HelpIconShow(); ?> <?= web_SqlDebugIconShow(); ?> <? if (XHPROF) {
                                                                                            print($xhprof_link);
                                                                                        } ?> <?= zb_IdleAutologoutRun(); ?>
                        </div>



                        <li class="nav-item dropdown">
                            <a class="dropdown-toggle profile-pic" data-toggle="dropdown" href="#" aria-expanded="false">
                                <?php
                                $adminMail = gravatar_GetUserEmail(whoami());
                                $admAva36 = gravatar_GetAvatar($adminMail, 36, 'img-circle');
                                print($admAva36);
                                ?>


                                <span>
                                    <?php
                                    if (@$_COOKIE['ghost_user']) {
                                        print(' <img src="skins/ghost.png" width="10" title="' . __('in ghost mode') . '">');
                                    }
                                    ?>
                                    <?= whoami(); ?>
                                </span></span> </a>
                            <ul class="dropdown-menu dropdown-user">
                                <li>
                                    <div class="user-box">
                                        <div class="u-img">
                                            <?php
                                            $adminMail = gravatar_GetUserEmail(whoami());
                                            $admAva80 = gravatar_GetAvatar($adminMail, 80);
                                            print($admAva80);
                                            ?>
                                        </div>
                                        <div class="u-text">
                                            <h4>
                                                <?php
                                                if (@$_COOKIE['ghost_user']) {
                                                    print(' <img src="skins/ghost.png" width="10" title="' . __('in ghost mode') . '">');
                                                }
                                                ?>
                                                <?= whoami(); ?>
                                            </h4>
                                            <a href="?forceLogout=true" class="btn btn-rounded btn-danger btn-sm"><?= __('Log out'); ?></a>
                                        </div>
                                    </div>
                                    <?php
                                    $globalMenu = new GlobalMenu();
                                    ?>
                                    <div class="fastaccessmenutop">
                                        <hr>
                                        <?php
                                        print($globalMenu->renderFastAccessMenu());
                                        ?>
                                    </div>
                                </li>

                            </ul>
                            <!-- /.dropdown-user -->
                        </li>
                    </ul>
            </div>
            </nav>
    </div>
    <div class="sidebar">
        <div class="scrollbar-inner sidebar-wrapper">

            <ul class="nav">
                <li class="nav-item">
                    <?php
                    //display global menu widget
                    print($globalMenu->render());
                    ?>
                </li>
            </ul>
            <ul class="toggle">
                <?php if (cfr('GLMENUCONF')) { ?> <li><a href="?module=glmenuconf"><?= __('Personalize menu'); ?></a></li> <?php } ?>
                <li>
                    <form name="lang_select" method="post" action=""><?= user_lang_select('lang_form', $system->language, 'font-size: 90%; width: 100px;', 'onchange="document.forms[\'lang_select\'].submit()" title="' . __('Lang') . '"') ?></form>
                    <form name="skin_select" method="post" action=""><?= user_skin_select(SKIN_PATH, 'user_selected_skin', $system->skin, 'font-size: 90%; width: 100px;', 'onchange="document.forms[\'skin_select\'].submit()" title="' . __('Skin') . '"') ?></form>
                </li>
                </br>
                </br>

            </ul>
        </div>
    </div>
    <div class="main-panel">
        <div class="content">
            <div class="container-fluid">
                <div class="row">
                    <? rcms_show_element('menu_point', 'up_center@window') ?>
                    <? rcms_show_element('main_point', $module . '@window') ?>

                </div>
            </div>
        </div>
        <footer class="footer">
            <div class="fastaccmenu">
                <?php
                //rebuild fast access menu cache on language switch
                if (wf_CheckPost(array('lang_form'))) {
                    $globalMenu->rebuildFastAccessMenuData();
                }
                print($globalMenu->renderFastAccessMenu());
                ?>
            </div>
            <div class="container-fluid">

                <div class="copyright ml-auto">
                    <?php
                    // Page gentime end
                    $mtime = explode(' ', microtime());
                    $totaltime = $mtime[0] + $mtime[1] - $starttime;
                    print('GT:' . round($totaltime, 3));
                    print(' QC: ' . $query_counter);
                    ?>

                </div>
            </div>
        </footer>
    </div>
<? } else {
            $ubLoginForm = new LoginForm();
            print($ubLoginForm->render());
        }
?>

<?php
if (!LOGGED_IN) {
?>
    <div class="nologinforms">
        <form name="lang_select" method="post" action=""><?= user_lang_select('lang_form', $system->language, 'font-size: 90%; width: 100px;', 'onchange="document.forms[\'lang_select\'].submit()" title="' . __('Lang') . '"') ?></form>
        <form name="skin_select" method="post" action=""><?= user_skin_select(SKIN_PATH, 'user_selected_skin', $system->skin, 'font-size: 90%; width: 100px;', 'onchange="document.forms[\'skin_select\'].submit()" title="' . __('Skin') . '"') ?></form>
    </div>
<?php
}
?>
</div>
</div>
<?php
if ((LOGGED_IN) and (!file_exists('I_HATE_NEW_YEAR'))) {
    $dateny = time();
    $monthny = date('m');

    $date_startny = null;
    $date_stopny = null;

    switch ($monthny) {
        case '12':
            $date_startny = strtotime(date('Y') . '-12-25');
            $date_stopny = strtotime((date('Y') + 1) . '-1-05');
            break;
        case '1':
            $date_startny = strtotime((date('Y') - 1) . '-12-25');
            $date_stopny = strtotime(date('Y') . '-1-05');
            break;
    }

    if ($dateny >= $date_startny && $dateny < $date_stopny) {
        print(file_get_contents('skins/ubny.txt'));
    }
}
?>
</body>

<script src="<?= CUR_SKIN_PATH ?>/assets/js/core/popper.min.js"></script>
<script src="<?= CUR_SKIN_PATH ?>/assets/js/core/bootstrap.min.js"></script>
<script src="<?= CUR_SKIN_PATH ?>/assets/js/plugin/chartist/chartist.min.js"></script>
<script src="<?= CUR_SKIN_PATH ?>/assets/js/plugin/chartist/plugin/chartist-plugin-tooltip.min.js"></script>
<script src="<?= CUR_SKIN_PATH ?>/assets/js/plugin/bootstrap-notify/bootstrap-notify.min.js"></script>
<script src="<?= CUR_SKIN_PATH ?>/assets/js/plugin/bootstrap-toggle/bootstrap-toggle.min.js"></script>
<script src="<?= CUR_SKIN_PATH ?>/assets/js/plugin/jquery-mapael/jquery.mapael.min.js"></script>
<script src="<?= CUR_SKIN_PATH ?>/assets/js/plugin/jquery-mapael/maps/world_countries.min.js"></script>
<script src="<?= CUR_SKIN_PATH ?>/assets/js/plugin/chart-circle/circles.min.js"></script>
<script src="<?= CUR_SKIN_PATH ?>/assets/js/plugin/jquery-scrollbar/jquery.scrollbar.min.js"></script>
<script src="<?= CUR_SKIN_PATH ?>/assets/js/ready.min.js"></script>
<script src="modules/jsc/jquery.cookie.js" type="text/javascript"></script>
<!-- bootstrap jquery ui hotfix -->
<script>
    var bootstrapButton = $.fn.button.noConflict();
    $.fn.bootstrapBtn = bootstrapButton;
</script>

</html>