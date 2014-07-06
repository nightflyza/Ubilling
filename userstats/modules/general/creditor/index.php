<?php

$user_ip=zbs_UserDetectIp('debug');
$user_login=zbs_UserGetLoginByIp($user_ip);
$us_config=zbs_LoadConfig();

/*
 * returns main self-credit module form
 * 
 * @return string
 */

function zbs_ShowCreditForm() {
    $inputs = la_tag('center');
    $inputs.= la_HiddenInput('setcredit', 'true');
    $inputs.= la_CheckInput('agree', __('I am sure that I am an adult and have read everything that is written above'), false, false);
    $inputs.= la_delimiter();
    $inputs.= la_Submit(__('Take me credit please'));
    $inputs.= la_tag('center', true);
    $form = la_Form("", 'POST', $inputs, '');

    return($form);
}

/*
 * logs succeful self credit fact into database
 * 
 * @param  string $login existing users login
 * 
 * @return void
 */

function zbs_CreditLogPush($login) {
    $login = mysql_real_escape_string($login);
    $date = curdatetime();
    $query = "INSERT INTO `zbssclog` (`id` , `date` , `login` ) VALUES ( NULL , '" . $date . "', '" . $login . "');";
    nr_query($query);
}

/*
 * checks is user current month use SC module and returns false if used or true if feature available
 * 
 * @param  string $login existing users login
 * 
 * @return bool
 */

function zbs_CreditLogCheckMonth($login) {
    $login = mysql_real_escape_string($login);
    $pattern = date("Y-m");
    $query = "SELECT `id` from `zbssclog` WHERE `login` LIKE '" . $login . "' AND `date` LIKE '" . $pattern . "%';";
    $data = simple_query($query);
    if (empty($data)) {
        return (true);
    } else {
        return (false);
    }
}

// if SC enabled
if ($us_config['SC_ENABLED']) {
    
// let needed params
$current_credit=zbs_CashGetUserCredit($user_login);
$current_cash=zbs_CashGetUserBalance($user_login);
$current_credit_expire=zbs_CashGetUserCreditExpire($user_login);
$us_currency=$us_config['currency'];
$sc_minday=$us_config['SC_MINDAY'];
$sc_maxday=$us_config['SC_MAXDAY'];
$sc_term=$us_config['SC_TERM'];
$sc_price=$us_config['SC_PRICE'];
$sc_cashtypeid=$us_config['SC_CASHTYPEID'];
$sc_monthcontrol=$us_config['SC_MONTHCONTROL'];
$tariff=zbs_UserGetTariff($user_login);
$tariffprice=zbs_UserGetTariffPrice($tariff);
$cday=date("d");

//welcome message
$wmess=__('If you wait too long to pay for the service, here you can get credit for').' '.$sc_term.' '.__('days. The price of this service is').': '.$sc_price.' '.$us_currency.'. ';
$wmess.= __('Also promising us you pay for the current month, in accordance with your service plan. Additional services are not subject to credit.');
show_window(__('Credits'),$wmess);
 
//if day is something like that needed
if (($cday<=$sc_maxday) AND ($cday>=$sc_minday)) {
    if (($current_credit<=0) AND ($current_credit_expire==0)) {
        //ok, no credit now
       // allow user to set it
        if (!isset ($_POST['setcredit'])) {
            show_window('',zbs_ShowCreditForm());
        } else {
            // set credit routine
            if (isset($_POST['agree'])) {
            //calculate credit expire date
            $nowTimestamp=time();
            $creditSeconds=($sc_term*86400); //days*secs
            $creditOffset=$nowTimestamp+$creditSeconds;
            $scend=date("Y-m-d",$creditOffset);
            
            // freaky like-a tomorrow =)
            //$scend= date("Y-m-d",mktime(0, 0, 0, date("m"), date("d")+$sc_term, date("Y")));
            if (abs($current_cash)<=$tariffprice) {
            if ($current_cash<0) {
                //additional month contol enabled
            if ($sc_monthcontrol) { 
               if (zbs_CreditLogCheckMonth($user_login)) {
                 
                    zbs_CreditLogPush($user_login);
                    billing_setcredit($user_login,$tariffprice+$sc_price);
                    billing_setcreditexpire($user_login, $scend);
                    zbs_PaymentLog($user_login, '-'.$sc_price, $sc_cashtypeid, "SCFEE");
                    billing_addcash($user_login, '-'.$sc_price);
                    show_window('',__('Now you have a credit'));
                    rcms_redirect("index.php");
                 
               } else {
                   show_window(__('Sorry'), __('You already used credit feature in current month. Only one usage per month is allowed.'));
               }
            } else {
                zbs_CreditLogPush($user_login);
                billing_setcredit($user_login,$tariffprice+$sc_price);
                billing_setcreditexpire($user_login, $scend);
                zbs_PaymentLog($user_login, '-'.$sc_price, $sc_cashtypeid, "SCFEE");
                billing_addcash($user_login, '-'.$sc_price);
                show_window('',__('Now you have a credit'));
                rcms_redirect("index.php");
            }
            
            } else {
                //to many money
                show_window(__('Sorry'),__('Sorry sum of money in the account is enought for use service without credit'));
            }
            } else {
                //no use self credit
                show_window(__('Sorry'),__('Sorry sum of money in the account does not allow to continue working in the credit'));
            }
          
            } else {
                // agreement check
                show_window(__('Sorry'),__('You must accept our policy'));
            }
        }
        
    } else {
       //you alredy have it 
        show_window(__('Sorry'),__('You already have a credit'));
    }
  
$scend= date("d-m-Y",mktime(0, 0, 0, date("m"), date("d")+$sc_term, date("Y")));
  
    
} else {
    show_window(__('Sorry'),__('You can take a credit only between').' '.$sc_minday.__(' and ').$sc_maxday.' '.__('days of month'));
}
    
    
    
//and if disabled :(
} else {
    show_window(__('Sorry'),__('Unfortunately self credits is disabled'));
}


?>
