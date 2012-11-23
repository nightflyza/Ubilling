<?php
if (cfr('PLFUNDS')) {
  
    if (isset($_GET['username'])) {
       $login=$_GET['username'];
       
       
       
 function funds_GetFees($login) {
       global $billing_config;    
       
       $login=  mysql_real_escape_string($login);
       
       $alter_conf=rcms_parse_ini_file(CONFIG_PATH."alter.ini");
       $sudo=$billing_config['SUDO'];
       $cat=$billing_config['CAT'];
       $grep=$billing_config['GREP'];
       $stglog=$alter_conf['STG_LOG_PATH'];
       
       $result=array();

       $feeadmin='stargazer';
       $feenote='';
       $feecashtype='z';
       // monthly fees output
       $command=$sudo.' '.$cat.' '.$stglog.' | '.$grep.' "fee charge"'.' | '.$grep.' "User \''.$login.'\'" ';
       $rawdata=shell_exec($command);

        $tablecells=wf_TableCell(__('Date'));
        $tablecells.=wf_TableCell(__('Time'));
        $tablecells.=wf_TableCell(__('From'));
        $tablecells.=wf_TableCell(__('To'));
        $tablerows=wf_TableRow($tablecells, 'row1');
            
       if (!empty ($rawdata)) {
        $cleardata=exploderows($rawdata);
        foreach ($cleardata as $eachline) {
            $eachfee=explode(' ',$eachline);
                if (isset($eachfee[1])) {
                $counter=  strtotime($eachfee[0].' '.$eachfee[1]);
                    
                $feefrom=str_replace("'.", '', $eachfee[12]);
                $feeto=str_replace("'.", '', $eachfee[14]);
                $feefrom=str_replace("'", '', $feefrom);
                $feeto=str_replace("'", '', $feeto);
                            
                $result[$counter]['login']=$login;
                $result[$counter]['date']=$eachfee[0].' '.$eachfee[1];
                $result[$counter]['admin']=$feeadmin;
                $result[$counter]['summ']=$feeto-$feefrom;
                $result[$counter]['from']=$feefrom;
                $result[$counter]['to']=$feeto;
                $result[$counter]['operation']='Fee';
                $result[$counter]['note']=$feenote;
                $result[$counter]['cashtype']=$feecashtype;
                
               
                }
         }
        
        }
       return ($result);
       }
       
 function funds_GetPayments($login) {
           $login=  mysql_real_escape_string($login);
           $query="SELECT * from `payments` WHERE `login`='".$login."'";
           $allpayments=  simple_queryall($query);
           
           $result=array();
          
           
           if (!empty($allpayments)) {
               foreach ($allpayments as $io=>$eachpayment) {
                $counter=  strtotime($eachpayment['date']);
                
                if (ispos($eachpayment['note'], 'MOCK:')) {
                    $cashto=$eachpayment['balance'];
                }
                               
                if (ispos($eachpayment['note'], 'BALANCESET:')) {
                    $cashto=$eachpayment['summ'];
                }
                
                if ((!ispos($eachpayment['note'], 'MOCK:')) AND (!ispos($eachpayment['note'], 'BALANCESET:'))) {
                    $cashto=$eachpayment['summ']+$eachpayment['balance'];
                }
                
                
                   
                $result[$counter]['login']=$login;
                $result[$counter]['date']=$eachpayment['date'];
                $result[$counter]['admin']=$eachpayment['admin'];
                $result[$counter]['summ']=$eachpayment['summ'];
                $result[$counter]['from']=$eachpayment['balance'];
                $result[$counter]['to']=$cashto;
                $result[$counter]['operation']='Payment';
                $result[$counter]['note']=$eachpayment['note'];
                $result[$counter]['cashtype']=$eachpayment['cashtypeid'];
                   
                
               }
           }
           
           return ($result);
           
       }
       
       
       function funds_GetPaymentsCorr($login) {
           $login=  mysql_real_escape_string($login);
           $query="SELECT * from `paymentscorr` WHERE `login`='".$login."'";
           $allpayments=  simple_queryall($query);
           
           $result=array();
          
           
           if (!empty($allpayments)) {
               foreach ($allpayments as $io=>$eachpayment) {
                $counter=  strtotime($eachpayment['date']);
                $cashto=$eachpayment['summ']+$eachpayment['balance'];
                $result[$counter]['login']=$login;
                $result[$counter]['date']=$eachpayment['date'];
                $result[$counter]['admin']=$eachpayment['admin'];
                $result[$counter]['summ']=$eachpayment['summ'];
                $result[$counter]['from']=$eachpayment['balance'];
                $result[$counter]['to']=$cashto;
                $result[$counter]['operation']='Correcting';
                $result[$counter]['note']=$eachpayment['note'];
                $result[$counter]['cashtype']=$eachpayment['cashtypeid'];
                   
                
               }
           }
           
           return ($result);
           
       }
  
  function funds_GetCashTypeNames() {
      $query="SELECT * from `cashtype`";
      $alltypes=simple_queryall($query);
      $result=array();
      
      if (!empty($alltypes)) {
          foreach ($alltypes as $io=>$each) {
              $result[$each['id']]=__($each['cashtype']);
          }
      }
      
      return ($result);
     
  }     
       
       
  function funds_ShowArray($fundsflow) {
      $allcashtypes= funds_GetCashTypeNames();
      $allservicenames=  zb_VservicesGetAllNamesLabeled();
      $result='';

      $alter_conf=rcms_parse_ini_file(CONFIG_PATH."alter.ini");

      $tablecells= wf_TableCell(__('Date'));
      $tablecells.=wf_TableCell(__('Cash'));
      $tablecells.=wf_TableCell(__('From'));
      $tablecells.=wf_TableCell(__('To'));
      $tablecells.=wf_TableCell(__('Operation'));
      $tablecells.=wf_TableCell(__('Cash type'));
      $tablecells.=wf_TableCell(__('Notes'));
      $tablecells.=wf_TableCell(__('Admin'));
      $tablerows=  wf_TableRow($tablecells, 'row1');
      
       if (!empty($fundsflow)) {
           foreach ($fundsflow as $io=>$each) {
               //cashtype
               if ($each['cashtype']!='z') {
                   @$cashtype=$allcashtypes[$each['cashtype']];
               } else {
                   $cashtype=__('Fee');
               }
               
               //coloring
               $efc='</font>';
               
               if ($each['operation']=='Fee') {
                   $fc='<font color="#a90000">';
               }
               
               if ($each['operation']=='Payment') {
                   $fc='<font color="#005304">';
               }
               
               if ($each['operation']=='Correcting') {
                   $fc='<font color="#ff6600">';
               }
               
               if (ispos($each['note'],'MOCK:')) {
                   $fc='<font color="#006699">';
               }
               
               if (ispos($each['note'],'BALANCESET:')) {
                   $fc='<font color="#000000">';
               }
               
               
               //notes translation
               if ($alter_conf['TRANSLATE_PAYMENTS_NOTES']) {
               $displaynote=  zb_TranslatePaymentNote($each['note'], $allservicenames);
               } else {
                   $displaynote=$each['note'];
               }
               
                  $tablecells= wf_TableCell($fc.$each['date'].$efc,'150');
                  $tablecells.=wf_TableCell($fc.$each['summ'].$efc);
                  $tablecells.=wf_TableCell($fc.$each['from'].$efc);
                  $tablecells.=wf_TableCell($fc.$each['to'].$efc);
                  $tablecells.=wf_TableCell($fc.__($each['operation']).$efc);
                  $tablecells.=wf_TableCell($cashtype);
                  $tablecells.=wf_TableCell($displaynote);
                  $tablecells.=wf_TableCell($each['admin']);
                  $tablerows.=  wf_TableRow($tablecells, 'row3');
               
           }
           
       $legendcells=  wf_TableCell(__('Legend').':');
       $legendcells.= wf_TableCell('<font color="#005304">'.__('Payment').$efc);
       $legendcells.= wf_TableCell('<font color="#a90000">'.__('Fee').$efc);
       $legendcells.= wf_TableCell('<font color="#ff6600">'.__('Correct saldo').$efc);
       $legendcells.= wf_TableCell('<font color="#006699">'.__('Mock payment').$efc);
       $legendcells.= wf_TableCell('<font color="#000000">'.__('Set cash').$efc);
       $legendrows=  wf_TableRow($legendcells,'row3');
       
       $legend=  wf_TableBody($legendrows, '50%', 0,'glamour');
       $legend.='<div style="clear:both;"></div>';
       $legend.=wf_delimiter();
       
       $result=  wf_TableBody($tablerows, '100%', 0, 'sortable');
       $result.=$legend;
       }
       

    
       
       
       show_window(__('Funds flow'), $result);
  }
      
       
       
    $allfees=funds_GetFees($login);
    $allpayments=funds_GetPayments($login);
    $allcorrectings=funds_GetPaymentsCorr($login);
    
    $fundsflow=$allfees+$allpayments+$allcorrectings;
    ksort($fundsflow);
    $fundsflow=array_reverse($fundsflow);

    funds_ShowArray($fundsflow);
       
    show_window('',web_UserControls($login));
    
    
    }
    
} else {
      show_error(__('You cant control this module'));
}


?>