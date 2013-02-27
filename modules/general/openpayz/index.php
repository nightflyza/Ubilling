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
    
    function zb_OPGetCount() {
        $query="SELECT COUNT(`id`) from `op_transactions`";
        $result=  simple_query($query);
        return ($result['COUNT(`id`)']);
    }

    
    function web_OPShowTransactions() {
        global $alter_conf;
        $perpage=100;
        $manual_mode=$alter_conf['OPENPAYZ_MANUAL'];
        $allcustomers=zb_OPGetAllCustomers();
        $allrealnames=zb_UserGetAllRealnames();
        $alladdress=zb_AddressGetFulladdresslist();
        $totalcount=  zb_OPGetCount();
        
        //pagination
         //pagination 
         if (!isset ($_GET['page'])) {
          $current_page=1;
          } else {
          $current_page=vf($_GET['page'],3);
          }
          
         if ($totalcount>$perpage) {
          $paginator=wf_pagination($totalcount, $perpage, $current_page, "?module=openpayz",'ubButton');
          $from=$perpage*($current_page-1);
          $to=$perpage;
          $query="SELECT * from `op_transactions` ORDER by `id` DESC LIMIT ".$from.",".$to.";";
          $alluhw=  simple_queryall($query);
         
          } else {
          $paginator='';
          $query="SELECT * from `op_transactions` ORDER by `id` DESC;";
          $alluhw=  simple_queryall($query);
        }
        
        $alltransactions=simple_queryall($query);

        $cells=  wf_TableCell(__('ID'));
        $cells.=  wf_TableCell(__('Date'));
        $cells.=  wf_TableCell(__('Cash'));
        $cells.=  wf_TableCell(__('Payment ID'));
        $cells.=  wf_TableCell(__('Real Name'));
        $cells.=  wf_TableCell(__('Full address'));
        $cells.=  wf_TableCell(__('Payment system'));
        $cells.=  wf_TableCell(__('Processed'));
        $cells.=  wf_TableCell(__('Actions'));
        $rows=  wf_TableRow($cells, 'row1');
        

        if (!empty ($alltransactions)) {
            foreach ($alltransactions as $io=>$eachtransaction)  {
                if ($manual_mode) {
                    if ($eachtransaction['processed']==0) {
                    $control=  wf_Link('?module=openpayz&process='.$eachtransaction['id'], web_add_icon('Payment'));
                    } else {
                        $control='';
                    }
                } else {
                    $control='';
                }
                @$user_login=$allcustomers[$eachtransaction['customerid']];
                @$user_realname=$allrealnames[$user_login];
                @$user_address=$alladdress[$user_login];
         
                $cells=  wf_TableCell($eachtransaction['id']);
                $cells.=  wf_TableCell($eachtransaction['date']);
                $cells.=  wf_TableCell($eachtransaction['summ']);
                $cells.=  wf_TableCell($eachtransaction['customerid']);
                $cells.=  wf_TableCell($user_realname);
                $cells.=  wf_TableCell($user_address);
                $cells.=  wf_TableCell($eachtransaction['paysys']);
                $cells.=  wf_TableCell(web_bool_led($eachtransaction['processed']));
                $cells.=  wf_TableCell(wf_Link('?module=userprofile&username='.$user_login, web_profile_icon()).$control);
                $rows.=  wf_TableRow($cells, 'row3');
            }
            
        }
        $result=  wf_TableBody($rows, '100%', '0', 'sortable');
        $result.=$paginator;
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
