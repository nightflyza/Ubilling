<?php
// Send main headers
header('Last-Modified: ' . gmdate('r')); 
header('Content-Type: text/html; charset=utf-8');
header("Cache-Control: no-store, no-cache, must-revalidate"); // HTTP/1.1 
header("Pragma: no-cache");
include("libs/api.mysql.php");
include("libs/api.uhw.php");
$uconf=  uhw_LoadConfig();

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="content-type" content="text/html; charset=utf-8" />
<title><?=$uconf['TITLE'];?></title>
<link href="style.css" rel="stylesheet" type="text/css" media="screen" />
<link type="text/css" href="jui/css/smoothness/jquery-ui-1.8.23.custom.css" rel="stylesheet" />
	<script type="text/javascript" src="jui/js/jquery-1.8.0.min.js"></script>
	<script type="text/javascript" src="jui/js/jquery-ui-1.8.23.custom.min.js"></script>
</head>
<body>
<div id="wrapper">
	<div id="header" class="container">
		<div id="logo">
			<h1><a href="<?=$uconf['ISP_URL'];?>"><img src="<?=$uconf['ISP_LOGO'];?>" width="80" border="0"></a> <?=$uconf['ISP_NAME'];?></h1>
			
		</div>
		<div id="menu">
			
		</div>
	</div>
	<div id="page" class="container">
		<div id="content">
			<div class="post">
				<h3 class="title"> <font color="#000000"><?=$uconf['SUB_TITLE'];?></font></h3>
				<div style="clear: both;">&nbsp;</div>
				<div class="entry">
				<h3><?=$uconf['CALL_US'];?> <?=$uconf['SUP_PHONES'];?> <?=$uconf['SUP_ACTIVATE'];?> 
	                            <?=$uconf['SUP_REQUIRE'];?>
     
         
       <?php
        
        // debug
        //$remote_ip='172.32.0.101';
        $remote_ip=$_SERVER['REMOTE_ADDR'];
     
        if (ispos($remote_ip, $uconf['UNKNOWN_MASK'])) {
            $useroption=  uhw_FindOpt82($remote_ip);
            if ($useroption) {
                //show user option 
                uhw_Opt82Display($useroption);
     
                if ($uconf['SELFACT_ENABLED']) {
                    //$db=new MySQLDB;
                    
                }
                
                
            } else {
                print($uconf['SUP_NOOPT']);
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
			<p>&copy; 2012 <a href="<?=$uconf['ISP_URL'];?>"><?=$uconf['ISP_NAME'];?></a></p>
		</div>
		<div id="column2">
				<?=$uconf['SUP_DESC'];?><br>
			     <i><?=$uconf['SUP_DAYS'];?><br>
				<?=$uconf['SUP_TIME'];?></i>
		</div>
		<div id="column3"> 
                        Powered by <a href="http://ubilling.net.ua">Ubilling</a>
                        <br>
                        QC:<?=$query_counter; ?>
		</div>
	</div>
</div>
<div id="footer">
</div>
</body>
</html>
