<?php

function zb_CashGetUserBalance($login) {
    $login=vf($login);
    $query="SELECT `Cash` from `users` WHERE `login`='".$login."'";
    $cash=simple_query($query);
    return($cash['Cash']); 
    
}

function zb_CashAdd($login,$cash,$operation,$cashtype,$note) {
    global $billing;
    $login=mysql_real_escape_string($login);
    $cash=mysql_real_escape_string($cash);
    $cash=preg_replace("#[^0-9\-\.]#Uis",'',$cash);
    $cash=trim($cash);
    $cashtype=vf($cashtype);
    $note=mysql_real_escape_string($note);
    $date=curdatetime();
    $balance=zb_CashGetUserBalance($login);
    $admin=whoami();
    $noteprefix='';
    //adding cash
    if ($operation=='add') {
    $billing->addcash($login,$cash); 
    log_register("BALANCEADD (".$login.') ON '.$cash);
    }
    //correcting balance
    if ($operation=='correct') {
    $billing->addcash($login,$cash); 
    log_register("BALANCECORRECT (".$login.') ON '.$cash);
    }
    //setting cash
    if ($operation=='set') {
    $billing->setcash($login,$cash);
    log_register("BALANCESET (".$login.') ON '.$cash);
    $noteprefix='BALANCESET:';
    }
    
    //mock payment additional log
    if ($operation=='mock') {
    log_register("BALANCEMOCK (".$login.') ON '.$cash);
    $noteprefix='MOCK:';
    }
    
    if ($operation!='correct') {
        $targettable='payments';
    } else {
        $targettable='paymentscorr';
    }
    
    $query="INSERT INTO `".$targettable."` (
                `id` ,
                `login` ,
                `date` ,
                `admin` ,
                `balance` ,
                `summ` ,
                `cashtypeid` ,
                `note`
                )
                VALUES (
                NULL , '".$login."', '".$date."', '".$admin."', '".$balance."', '".$cash."', '".$cashtype."', '".($noteprefix.$note)."'
                );";
    
    
    nr_query($query);
   
}

function zb_CashGetAlltypes() {
    $query="SELECT * from `cashtype`";
    $alltypes=simple_queryall($query);
    return($alltypes);
}

function zb_CashGetTypeName($typeid) {
    $typeid=vf($typeid,3);
    $query="SELECT `cashtype` from `cashtype` WHERE `id`='".$typeid."'";
    $result=simple_query($query);
    $result=$result['cashtype'];
    return($result);
}

function zb_CashGetUserPayments($login) {
    $login=vf($login);
    $query="SELECT * from `payments` WHERE `login`='".$login."' ORDER BY `id` DESC";
    $allpayments=simple_queryall($query);
    return($allpayments);
   }
   
function zb_CashGetUserLastPayment($login) {
    $login=vf($login);
    $query="SELECT * from `payments` where `login`='".$login."' ORDER BY `date` DESC LIMIT 1";
    $payment=simple_query($query);
    if (!empty ($payment)) {
    $result=__('Last payment').' '.$payment['summ'].' '.$payment['date'];
    } else {
    $result=__('Any payments yet');    
    }
    return($result);
}
   
function zb_CashGetAllCashTypes() {
    $query="SELECT * from `cashtype`";
    $result=array();
    $alltypes=simple_queryall($query);
    if (!empty ($alltypes)) {
        foreach ($alltypes as $io=>$eachtype) {
            $result[$eachtype['id']]=$eachtype['cashtype'];
        }
    }
   
    return($result);
}

function zb_CashCreateCashType($cashtype) {
    $cashtype=mysql_real_escape_string($cashtype);
    $query="INSERT INTO `cashtype` (
                `id` ,
                `cashtype`
                )
                VALUES (
                NULL , '".$cashtype."'); ";
    nr_query($query);
    log_register("CREATE CashType ".$cashtype);
}

function zb_CashDeleteCashtype($cashtypeid) {
    $cashtypeid=vf($cashtypeid);
    $query="DELETE FROM `cashtype` WHERE `id`='".$cashtypeid."'";
    nr_query($query);
    log_register("DELETE CashType ".$cashtypeid);
}

function zb_PaymentsGetYearSumm($year) {
    $year=vf($year);
    $query="SELECT SUM(`summ`) from `payments` WHERE `date` LIKE '".$year."-%' AND `summ` > 0";
    $result=simple_query($query);
    return($result['SUM(`summ`)']);
}

function zb_PaymentsGetMonthSumm($year,$month) {
    $year=vf($year);
    $query="SELECT SUM(`summ`) from `payments` WHERE `date` LIKE '".$year."-".$month."%' AND `summ` > 0";
    $result=simple_query($query);
    return($result['SUM(`summ`)']);
}

function zb_PaymentsGetMonthCount($year,$month) {
    $year=vf($year);
    $query="SELECT COUNT(`id`) from `payments` WHERE `date` LIKE '".$year."-".$month."%' AND `summ` > 0";
    $result=simple_query($query);
    return($result['COUNT(`id`)']);
}

?>
