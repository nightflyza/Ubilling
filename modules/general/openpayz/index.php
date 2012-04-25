<?php
if (cfr('OPENPAYZ')) {
$alter_conf=rcms_parse_ini_file(CONFIG_PATH."alter.ini");
//check is openpayz enabled?
if ($alter_conf['OPENPAYZ_SUPPORT']) {
    
    function zb_OPGetAllCustomers() {
        $query="SELECT * from `op_customers`";
        $allcustomers=simple_queryall($query);
        $result=array();
        if (!empty ($allcustomers)) {
            foreach ($allcustomers as $io=>$eachcustomer) {
                $result[$eachcustomer['virtualid']]=$eachcustomer['realid'];
            }
        }
        return ($result);
    }

    
    function web_OPShowTransactions() {
        global $alter_conf;
        $manual_mode=$alter_conf['OPENPAYZ_MANUAL'];
        $query="SELECT * from `op_transactions` ORDER by `id` DESC";
        $allcustomers=zb_OPGetAllCustomers();
        $alltransactions=simple_queryall($query);
        $allrealnames=zb_UserGetAllRealnames();
        $alladdress=zb_AddressGetFulladdresslist();
        $result='<table width="100%" class="sortable" border="0">';
        $result.='
                <tr class="row1">
                <td>'.__('ID').'</td>
                <td>'.__('Date').'</td>
                <td>'.__('Cash').'</td>
                <td>'.__('Payment ID').'</td>
                <td>'.__('Real Name').'</td>
                <td>'.__('Full address').'</td>
                <td>'.__('IP').'</td>
                <td>'.__('Payment system').'</td>
                <td>'.__('Processed').'</td>
                <td>'.__('Actions').'</td>
                </tr>
                ';

        if (!empty ($alltransactions)) {
            foreach ($alltransactions as $io=>$eachtransaction)  {
                if ($manual_mode) {
                    if ($eachtransaction['processed']==0) {
                    $control='<a href="?module=openpayz&process='.$eachtransaction['id'].'">'. web_add_icon('Payment').'</a>';
                    } else {
                        $control='';
                    }
                } else {
                    $control='';
                }
                @$user_login=$allcustomers[$eachtransaction['customerid']];
                @$user_realname=$allrealnames[$user_login];
                @$user_address=$alladdress[$user_login];
                $result.='
                <tr class="row3">
                <td>'.$eachtransaction['id'].'</td>
                <td>'.$eachtransaction['date'].'</td>
                <td>'.$eachtransaction['summ'].'</td>
                <td>'.$eachtransaction['customerid'].'</td>
                <td>'.$user_realname.'</td>
                <td>'.$user_address.'</td>
                <td>'.int2ip($eachtransaction['customerid']).'</td>
                <td>'.$eachtransaction['paysys'].'</td>
                <td>'.web_bool_led($eachtransaction['processed']).'</td>
                <td>
                <a href="?module=userprofile&username='.$user_login.'">'.  web_profile_icon().'</a> 
                '.$control.'</td>
                </tr>
                ';
            }
            
        }
        $result.='</table>';
        show_window(__('OpenPayz transactions'),$result);
    }
    
    function zb_OPTransactionSetProcessed($transactionid) {
    $transactionid=vf($transactionid);
    $query="UPDATE `op_transactions` SET `processed` = '1' WHERE `id`='".$transactionid."'";
    nr_query($query);
    log_register('OPENPAYZ PROCESSED '.$transactionid);
    }
    
    function zb_OPCashAdd($login,$cash,$paysys) {
        global $alter_conf;
        $note='OP:'.$paysys;
        zb_CashAdd($login, $cash, 'add', $alter_conf['OPENPAYZ_CASHTYPEID'], $note);
    }
    
    function zb_OPTransactionGetData($transactionid) {
        $transactionid=vf($transactionid);
        $query="SELECT * from `op_transactions` WHERE `id`='".$transactionid."'";
        $result=simple_query($query);
        return ($result);
    }
    
    //if manual processing transaction
    if ($alter_conf['OPENPAYZ_MANUAL']) {
        if (isset($_GET['process'])) {
        $transaction_data=zb_OPTransactionGetData($_GET['process']);
        $customerid=$transaction_data['customerid'];
        $transaction_summ=$transaction_data['summ'];
        $transaction_paysys=$transaction_data['paysys'];
        $allcustomers=zb_OPGetAllCustomers();
        if (isset($allcustomers[$customerid])) {
            if ($transaction_data['processed']!=1) {
            zb_OPCashAdd($allcustomers[$customerid], $transaction_summ, $transaction_paysys);
            zb_OPTransactionSetProcessed($transaction_data['id']);
            rcms_redirect("?module=openpayz");
        } else {
            show_error(__('Already processed'));
        }
            
         } else {
             show_error(__('Customer unknown'));
         }
        }
    }
    
    web_OPShowTransactions();
    
    
} else {
    show_error(__('OpenPayz support not enabled'));
}

} else {
      show_error(__('You cant control this module'));
}

?>
