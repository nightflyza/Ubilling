<?php
$user_ip=zbs_UserDetectIp('debug');
$user_login=zbs_UserGetLoginByIp($user_ip);
$us_config=zbs_LoadConfig();



//tariff changing options
$us_currency=$us_config['currency'];
$tc_enabled=$us_config['TC_ENABLED'];
$tc_priceup=$us_config['TC_PRICEUP'];
$tc_pricedown=$us_config['TC_PRICEDOWN'];
$tc_pricesimilar=$us_config['TC_PRICESIMILAR'];
$tc_credit=$us_config['TC_CREDIT'];
$tc_cashtypeid=$us_config['TC_CASHTYPEID'];
$tc_tariffsallowed=explode(',',$us_config['TC_TARIFFSALLOWED']);
$tc_tariffenabledfrom=explode(',',$us_config['TC_TARIFFENABLEDFROM']);
$user_data=zbs_UserGetStargazerData($user_login);
$user_cash=$user_data['Cash'];
$user_credit=$user_data['Credit'];
$user_tariff=zbs_UserGetTariff($user_login);
$user_tariffnm=$user_data['TariffChange'];

function zbs_TariffSelector($tc_tariffsallowed,$user_tariff) {
    $result='<select name="newtariff">';
    if (!empty ($tc_tariffsallowed)) {
        foreach ($tc_tariffsallowed as $io=>$eachtariff) {
            if ($eachtariff!=$user_tariff) {
            $result.='<option value="'.trim($eachtariff).'">'.$eachtariff.'</option>';
            }
        }
    }
    $result.='</select>';
    return ($result);
}

function zbs_TariffGetAllPrices() {
    $query="SELECT `name`,`Fee` from `tariffs`";
    $alltariffs=simple_queryall($query);
    $result=array();
    if (!empty ($alltariffs)) {
        foreach ($alltariffs as $io=>$eachtariff) {
            $result[$eachtariff['name']]=$eachtariff['Fee'];
        }
    }
    return ($result);
}

function zbs_TariffGetChangePrice($tc_tariffsallowed,$user_tariff,$tc_priceup,$tc_pricedown,$tc_pricesimilar) {
    $allprices=zbs_TariffGetAllPrices();
    $current_fee=$allprices[$user_tariff];
    $result=array();
    if (!empty ($tc_tariffsallowed)) {
        foreach ($tc_tariffsallowed as $eachtariff) {
            //if higer then current fee
            if ($allprices[$eachtariff]>$current_fee) {
                $result[$eachtariff]=$tc_priceup;
            }
            //if lower then current
            if ($allprices[$eachtariff]<$current_fee) {
                $result[$eachtariff]=$tc_pricedown;
            }
            // if eq
            if ($allprices[$eachtariff]==$current_fee) {
                $result[$eachtariff]=$tc_pricesimilar;
            }
        }
    }
    return ($result);
    
}

function zbs_TariffGetShowPrices($tc_tariffsallowed,$us_currency,$user_tariff, $tc_priceup, $tc_pricedown, $tc_pricesimilar) {
    $allprices=zbs_TariffGetAllPrices();
    $allcosts=zbs_TariffGetChangePrice($tc_tariffsallowed, $user_tariff, $tc_priceup, $tc_pricedown, $tc_pricesimilar);
    $result='<table width="50%" border="0">';
     $result.='
                <tr class="row1">
                <td>'.__('Tariff').'</td>
                <td>'.__('Monthly fee').'</td>
                <td>'.__('Cost of change').'</td>
                </tr>
                ';
    if (!empty ($tc_tariffsallowed)) {
        foreach ($tc_tariffsallowed as $eachtariff) {
            $result.='
                <tr class="row2">
                <td><b>'.$eachtariff.'</b></td>
                <td>'.@$allprices[$eachtariff].' '.$us_currency.'</td>
                <td>'.@$allcosts[$eachtariff].' '.$us_currency.'</td>
                </tr>
                ';
        }
    }
    $result.='</table>';
    return ($result);
}

function zbs_TariffChangeForm($login, $tc_tariffsallowed,$tc_priceup,$tc_pricedown,$tc_pricesimilar, $us_currency) {
    $user_tariff=zbs_UserGetTariff($login);
    $alltariffs=zbs_TariffGetAllPrices();
    $form='
        '.__('You current tariff is').': '.$user_tariff.' '.__('with monthly fee').' '.$alltariffs[$user_tariff].' '.$us_currency.'<br>
        '.__('The cost of switching to a lower rate monthly fee').': '.$tc_pricedown.' '.$us_currency.'<br>
        '.__('The cost of switching to a higher monthly fee tariff').': '.$tc_priceup.' '.$us_currency.'<br>
        '.__('The cost of the transition rate for the same monthly fee').': '.$tc_pricesimilar.' '.$us_currency.'<br>
        <br>
        '.zbs_TariffGetShowPrices($tc_tariffsallowed,$us_currency, $user_tariff, $tc_priceup, $tc_pricedown, $tc_pricesimilar).'
        <br>
        ';
    $form.='
        <form action="" method="POST">
      '.__('New tariff').'  '.  zbs_TariffSelector($tc_tariffsallowed,$user_tariff).' <br><br>
        <input type="checkbox" name="agree">  '.__('I am sure that I am an adult and have read everything that is written above').'<br><br>
        <input type="submit" value="'.__('I want this tariff next month').'">
        </form>
        ';
    return ($form);
}


//check is tariff changing is enabled? 
if ($tc_enabled) {
    
    //check is TC allowed for current user tariff plan
    if (in_array($user_tariff, $tc_tariffenabledfrom)) {
    //tariff change subroutines
    if (isset ($_POST['newtariff'])) {
     $change_prices=zbs_TariffGetChangePrice($tc_tariffsallowed, $user_tariff, $tc_priceup, $tc_pricedown, $tc_pricesimilar);
     if (in_array($_POST['newtariff'], $tc_tariffsallowed)) {        
         // agreement check
         if (isset($_POST['agree'])) {
            
              // and not enought money, set credit
              if ($user_cash<$change_prices[$_POST['newtariff']]) {
                  //if TC_CREDIT option enabled
                  if ($tc_credit) {
                      $newcredit=$change_prices[$_POST['newtariff']]+$user_credit;
                      billing_setcredit($user_login, $newcredit);
                  }
              } 
              
              //TC change fee anyway
              zbs_PaymentLog($user_login, '-'.$change_prices[$_POST['newtariff']], $tc_cashtypeid, "TCHANGE:".$_POST['newtariff']);
              billing_addcash($user_login, '-'.$change_prices[$_POST['newtariff']]);
            
        //nm set tariff routine
        billing_settariffnm($user_login, mysql_real_escape_string($_POST['newtariff']));
        rcms_redirect("index.php");
        
        
        } else {
                // agreement check fail
                show_window(__('Sorry'),__('You must accept our policy'));
        }
      //die if tariff not allowed  
      } else {
          die("oO");
      }
    } // end of tariff change subroutine (POST processing)
    
    if (empty ($user_tariffnm)) {
    show_window(__('Tariff change'), zbs_TariffChangeForm($user_login,$tc_tariffsallowed,$tc_priceup,$tc_pricedown,$tc_pricesimilar,$us_currency));
    } else {
        show_window(__('Sorry'),__('You already have planned tariff change'));
    }
 } else {
        show_window(__('Sorry'),__('Your current tariff does not provide an independent tariff change'));
 }
 //end of TC enabled check
 
// or not enabled at all?
} else {
    show_window(__('Sorry'),__('Unfortunately self tariff change is disabled'));
}


?>
