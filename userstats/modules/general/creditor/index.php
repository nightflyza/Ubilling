<?php

$user_ip=zbs_UserDetectIp('debug');
$user_login=zbs_UserGetLoginByIp($user_ip);
$us_config=zbs_LoadConfig();

function zbs_ShowCreditForm() {
    $form='
        <center>
        <form action="" method="POST">
        <input type="hidden" name="setcredit" value="true">
        <input type="checkbox" name="agree"> '.__('I am sure that I am an adult and have read everything that is written above').'<br><br>
        <input type="submit" value="'.__('Take me credit please').'"> <br>
        
        </form>
        </center>
        <p>
        ';
    return($form);
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
$tariff=zbs_UserGetTariff($user_login);
$tariffprice=zbs_UserGetTariffPrice($tariff);
$cday=date("d");

//welcome message
$wmess=__('If you wait too long to pay for the service, here you can get credit for').' '.$sc_term.' '.__('days. The price of this service is').': '.$sc_price.' '.$us_currency;
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
             // freaky like-a tomorrow =)
            $scend= date("Y-m-d",mktime(0, 0, 0, date("m"), date("d")+$sc_term, date("Y")));
            if (abs($current_cash)<=$tariffprice) { 
            billing_setcredit($user_login,$tariffprice+$sc_price);
            billing_setcreditexpire($user_login, $scend);
            zbs_PaymentLog($user_login, '-'.$sc_price, $sc_cashtypeid, "SCFEE");
            billing_addcash($user_login, '-'.$sc_price);
            show_window('',__('Now you have a credit'));
            rcms_redirect("index.php");
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
