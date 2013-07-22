<?php
// check for right of current admin on this module
if (cfr('CARDREPORT')) {
    

   $altcfg=  rcms_parse_ini_file(CONFIG_PATH."alter.ini");
   if ($altcfg['PAYMENTCARDS_ENABLED']) {
    
   function web_CardShowDateForm() {
       $curmonth=date("m");
       $inputs=wf_YearSelector('yearsel');
       $inputs.=wf_MonthSelector('monthsel', 'Month', $curmonth, false);
       $inputs.=wf_Submit('Show');
       $form=wf_Form("", 'POST', $inputs, 'glamour');
       show_window(__('Date'),$form);
   }
    
   function web_CardShowUsageByMonth($year,$month) {
       $month=mysql_real_escape_string($month);
       $year=mysql_real_escape_string($year);
       $query="SELECT * from `cardbank` WHERE `usedate` LIKE '%".$year."-".$month."-%'";
       $allusedcards=simple_queryall($query);
       $allrealnames=zb_UserGetAllRealnames();
       $alladdress=zb_AddressGetFulladdresslist();
       $totalsumm=0;
       $totalcount=0;
       
       $tablecells=wf_TableCell(__('ID'));
       $tablecells.=wf_TableCell(__('Serial number'));
       $tablecells.=wf_TableCell(__('Cash'));
       $tablecells.=wf_TableCell(__('Usage date'));
       $tablecells.=wf_TableCell(__('Used login'));
       $tablecells.=wf_TableCell(__('Full address'));
       $tablecells.=wf_TableCell(__('Real name'));
       $tablerows=wf_TableRow($tablecells, 'row1');
       
       
       if (!empty ($allusedcards)) {
           foreach ($allusedcards as $io=>$eachcard) {
                      $tablecells=wf_TableCell($eachcard['id']);
                      $tablecells.=wf_TableCell($eachcard['serial']);
                      $tablecells.=wf_TableCell($eachcard['cash']);
                      $tablecells.=wf_TableCell($eachcard['usedate']);
                      $profilelink=wf_Link("?module=userprofile&username=".$eachcard['usedlogin'], web_profile_icon().' '.$eachcard['usedlogin'], false);
                      $tablecells.=wf_TableCell($profilelink);
                      $tablecells.=wf_TableCell(@$alladdress[$eachcard['usedlogin']]);
                      $tablecells.=wf_TableCell(@$allrealnames[$eachcard['usedlogin']]);
                      $tablerows.=wf_TableRow($tablecells, 'row3');
                      $totalcount++;
                      $totalsumm=$totalsumm+$eachcard['cash'];
           }
       }
       
       $result=wf_TableBody($tablerows, '100%', '0', 'sortable');
       $result.=__('Total').': '.$totalcount.' '.__('payments').', '.__('with total amount').': '.$totalsumm;
       show_window(__('Payment cards usage report'),$result);
   }
   
   web_CardShowDateForm();
   
   //selecting month and date
   if (wf_CheckPost(array('yearsel','monthsel'))) {
       $needyear=$_POST['yearsel'];
       $needmonth=$_POST['monthsel'];
   } else {
       $needyear=curyear();
       $needmonth=date("m");
       
   }
   
   web_CardShowUsageByMonth($needyear,$needmonth);
   
   } else {
       show_window(__('Error'), __('This module is disabled'));
   }
   
} else {
      show_error(__('You cant control this module'));
}

?>