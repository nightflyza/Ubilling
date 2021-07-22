<?php
// Send main headers
header('Last-Modified: ' . gmdate('r'));
header('Content-Type: text/html; charset=utf-8');
header("Cache-Control: no-store, no-cache, must-revalidate"); // HTTP/1.1 
header("Pragma: no-cache");
include("libs/api.mysql.php");
include("libs/api.uhw.php");
$uconf = uhw_LoadConfig();
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
    <head>
        <meta http-equiv="content-type" content="text/html; charset=utf-8" />
        <title><?= $uconf['TITLE']; ?></title>
        <link href="style.css" rel="stylesheet" type="text/css" media="screen" />
        <link type="text/css" href="jui/css/smoothness/jquery-ui-1.8.23.custom.css" rel="stylesheet" /> 
        <script type="text/javascript" src="jui/js/jquery-1.8.0.min.js"></script>
        <script type="text/javascript" src="jui/js/jquery-ui-1.8.23.custom.min.js"></script>
    </head>
    <body>
        <div id="wrapper">
            <div id="header" class="container">
                <div id="logo">
                    <h1><a href="<?= $uconf['ISP_URL']; ?>"><img src="<?= $uconf['ISP_LOGO']; ?>" width="80" border="0"></a> <?= $uconf['ISP_NAME']; ?></h1>

                </div>
                <div id="menu">

                </div>
            </div>
            <div id="page" class="container">
                <div id="content">
                    <div class="post">
                        <h3 class="title"> <font color="#000000"><?= $uconf['SUB_TITLE']; ?></font></h3>
                        <div style="clear: both;">&nbsp;</div>
                        <div class="entry">
                            <h3><?= $uconf['CALL_US']; ?> <?= $uconf['SUP_PHONES']; ?> <?= $uconf['SUP_ACTIVATE']; ?>
                                <?= $uconf['SUP_REQUIRE']; ?>

                                <?php
                                // debug
                                //$remote_ip='172.32.0.118';
                                $remote_ip = $_SERVER['REMOTE_ADDR'];

                                if (ispos($remote_ip, $uconf['UNKNOWN_MASK'])) {
                                    $usermac = uhw_FindMac($remote_ip);
                                    if ($usermac) {
                                        //show user mac 
                                        uhw_MacDisplay($usermac);

                                        if ($uconf['SELFACT_ENABLED']) {
                                            //is all passwords unique?
                                            if (uhw_IsAllPasswordsUnique() or $uconf['USE_LOGIN']) {
                                                $brute_attempts = uhw_GetBrute($usermac);
                                                if ($brute_attempts < $uconf['SELFACT_BRUTE']) {
                                                    if (uhw_IsMacUnique($usermac)) {
                                                        //catch actiivation request
                                                        if ((!$uconf['USE_LOGIN'] and isset($_POST['password'])) or ( $uconf['USE_LOGIN'] and isset($_POST['login']) and isset($_POST['password']))) {
                                                            if ((!$uconf['USE_LOGIN'] and ! empty($_POST['password'])) or ( $uconf['USE_LOGIN'] and ! empty($_POST['login']) and ! empty($_POST['password']))) {
                                                                $trylogin = (isset($_POST['login']) and ! empty($_POST['login'])) ? $_POST['login'] : '';
                                                                $trypassword = $_POST['password'];
                                                                $userlogin = uhw_FindUserByPassword($trypassword, $trylogin);
                                                                if ($userlogin) {
                                                                    //password ok, we know user login
                                                                    // lets detect his ip
                                                                    $tryip = uhw_UserGetIp($userlogin);
                                                                    if ($tryip) {
                                                                        //get nethost id
                                                                        $nethost_id = uhw_NethostGetID($tryip);
                                                                        if ($nethost_id) {
                                                                            //almost done, now we need too change mac in nethosts
                                                                            //and call rebuild handlers and user reset API calls
                                                                            $oldmac = uhw_NethostGetMac($nethost_id);
                                                                            uhw_ChangeMac($nethost_id, $usermac, $oldmac);
                                                                            uhw_LogSelfact($trypassword, $userlogin, $tryip, $nethost_id, $oldmac, $usermac);
                                                                            uhw_RemoteApiPush($uconf['UBILLING_REMOTE'], $uconf['UBILLING_SERIAL'], 'reset', $userlogin);
                                                                            uhw_RemoteApiPush($uconf['UBILLING_REMOTE'], $uconf['UBILLING_SERIAL'], 'handlersrebuild');

                                                                            print(uhw_modal_open($uconf['SUP_SELFACT'], $uconf['SUP_SELFACTDONE'], '400', '300'));
                                                                        } else {
                                                                            print(uhw_modal_open($uconf['SUP_ERROR'], $uconf['SUP_STRANGE'] . ' NO_NHID', '400', '300'));
                                                                        }
                                                                    } else {
                                                                        print(uhw_modal_open($uconf['SUP_ERROR'], $uconf['SUP_STRANGE'] . ' NO_IP', '400', '300'));
                                                                    }
                                                                } else {
                                                                    //wrong password action
                                                                    uhw_LogBrute($trypassword, $usermac, $trylogin);
                                                                    print(uhw_modal_open($uconf['SUP_ERROR'], $uconf['SUP_WRONGPASS'], '400', '300'));
                                                                }
                                                            }
                                                        }
                                                        //
                                                        //show selfact form
                                                        //
                            uhw_PasswordForm($uconf);
                                                    } else {
                                                        print($uconf['SUP_MACEXISTS']);
                                                    }
                                                } else {
                                                    //bruteforce prevention
                                                    print('<br><br><br>' . uhw_modal($uconf['SUP_SELFACT'], $uconf['SUP_SELFACT'], $uconf['SUP_BRUTEERROR'], 'ubButton', '400', '300'));
                                                }
                                            } else {
                                                print('DEBUG: EXEPTION_PASS_UNIQ ');
                                            }
                                        }
                                    } else {
                                        print($uconf['SUP_NOMAC']);
                                    }
                                } else {
                                    //not unknown user network
                                    uhw_redirect($uconf['ISP_URL']);
                                }
                                ?>

                            </h3>
                        </div>
                    </div>
                    <div style="clear: both;">&nbsp;</div>
                </div>
                <div id="sidebar">
                    <ul>

                    </ul>
                </div>
                <div style="clear: both;">&nbsp;</div>
            </div>
        </div>
        <div id="footer-content" class="container">
            <div id="footer-bg">
                <div id="column1">
                    <p>&copy; 2012 <a href="<?= $uconf['ISP_URL']; ?>"><?= $uconf['ISP_NAME']; ?></a></p>
                </div>
                <div id="column2">
                    <?= $uconf['SUP_DESC']; ?><br>
                        <i><?= $uconf['SUP_DAYS']; ?><br>
                            <?= $uconf['SUP_TIME']; ?></i>
                </div>
                <div id="column3">
                    Powered by <a href="http://ubilling.net.ua">Ubilling</a>
                    <br>
                        QC:<?= $query_counter; ?>
                </div>
            </div>
        </div>
        <div id="footer">
        </div>
    </body>
</html>
