<?php

/*
 * Copayco user frontend
 */

///подключаем все требуемые либы
require_once './incl/copayco.php';
require_once '../../libs/api.openpayz.php';

header('Content-Type: text/html; charset=utf-8');

if (isset($_GET['customer_id'])) {
    $customer_id=vf($_GET['customer_id'],3);
} else {
    die('customer_id fail');
}

$copConf=  parse_ini_file("config/copayco.ini");

$sTaId        = 'opcop_'.round(microtime(true) * 100);
$nAmount      =  @$_POST['pushamount'];
$sCurrency    = $copConf['CURRENCY'];
$sDescription = $copConf['DESC'];
$sLang        = $copConf['LANG'];
$sCustom      = $customer_id;




try {
    $oCop = copayco_api::instance();
    // Установка основных параметров
    $oCop->set_main_data($sTaId, $nAmount, $sCurrency);
    // Установка дополнительных параметров (если необходимо)
    $oCop->set_description($sDescription);
    $oCop->set_custom_field($sCustom);
    $oCop->set_language($sLang);
    $oCop->set_payment_mode(array('paycard', 'account','copayco','ecurrency','terminal','sms'));
    // Данные (в виде строки HTML-кода) для отображения формы способом 1
    $sFormFields_1 = $oCop->get_form_fields(
        array(
            'ta_id' => array(
                'type'     => 'hidden',
            ),
            
            'currency' => array(
                'id' => 'currency1',
            ),
        ),
        "\n"
    );

  
    // URL (строка) для подстановки в тэг <form ...>
    $sSubmitUrl = $oCop->get_submit_url();

    $sException = NULL;
    
} catch (copayco_exception $e) {
    $sException = print_r($e, true);
}

function copayco_AmountForm($copConf) {
    $result='<form action="" method="post">';
    $i=0;
    if (!empty($copConf['AMOUNTS'])) {
        $amounts=  explode(',', $copConf['AMOUNTS']);
        foreach ($amounts as $each) {
            $i++;
            $checked = ($i==1) ? 'CHECKED' : '';
            $result.='<input name="pushamount" value="'.trim($each).'" type="radio" '.$checked.'> '.$each.' '.$copConf['CURRENCY'].'<br>'."\n";
        }
    } else {
        throw new Exception('EMPTY_AMOUNTS');
    }
    $result.='<br> <input type="submit">';
    $result.='</form>';
    return ($result);
}

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="content-type" content="text/html; charset=utf-8" />
<title>CoPAYCo</title>
<link href="style.css" rel="stylesheet" type="text/css" media="screen" />
</head>
<body>
<div id="wrapper">
	<div id="header" class="container">
		<div id="logo">
			<h1>CoPAYCo</h1>
			<p></p>
		</div>
		<div id="menu">
			
		</div>
	</div>
	<!-- end #header -->
	<div id="page" class="container">
		<div id="content">
			<div class="post">
    
                <div class="entry">
                    <h3><?=$sDescription;?> <?=$customer_id;?> </h3> <br>
                    
    <?php if (isset($_POST['pushamount'])) { ?>
    <h3>в размере <?=number_format($nAmount, 2)?> <?=$sCurrency?>.</h3>
    <form action="<?=$sSubmitUrl?>" method="post" target="_top" name="my_payment1" id="my_payment1">
        <?=$sFormFields_1?>
    <input type="submit"/>
    </form>
    <?php } else { 
       print(copayco_AmountForm($copConf));    
    }
    ?>
    		</div>
			</div>
			
			<div style="clear: both;">&nbsp;</div>
		</div>
		<!-- end #content -->
		<div id="sidebar">
			<ul>
				<li>
                                   <!-- some logo here -->
                                   <img src="http://ubilling.net.ua/logo.png">
				</li>
				
			</ul>
		</div>
		<!-- end #sidebar -->
		<div style="clear: both;">&nbsp;</div>
	</div>
	<!-- end #page -->
</div>
<div id="footer-content" class="container">
	<div id="footer-bg">
		<div id="column1">
          Powered by <a href="http://ubilling.net.ua">OpenPayz</a>
		</div>
		<div id="column2">
		</div>
		<div id="column3">
		</div>
	</div>
</div>
<div id="footer">
</div>
<!-- end #footer -->
</body>
</html>