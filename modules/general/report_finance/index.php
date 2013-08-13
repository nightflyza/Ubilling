<?php
if (cfr('REPORTFINANCE')) {

  if (!wf_CheckGet(array('analytics'))) {  
    if (!wf_CheckPost(array('yearsel'))) {
        $show_year=curyear();
        } else {
        $show_year=$_POST['yearsel'];
        }
        

      
      $dateinputs= wf_DatePicker('showdatepayments');
      $dateinputs.=wf_Submit(__('Show'));
      $dateform=  wf_Form("?module=report_finance", 'POST', $dateinputs, 'glamour');
      
      
      $yearinputs=  wf_YearSelector('yearsel');
      $yearinputs.=wf_Submit(__('Show'));
      $yearform=  wf_Form("?module=report_finance", 'POST', $yearinputs, 'glamour');
      
      
      $controlcells=  wf_TableCell(wf_tag('h3',false,'title').__('Year').  wf_tag('h3', true));
      $controlcells.=  wf_TableCell(wf_tag('h3',false,'title').__('Payments by date').  wf_tag('h3', true));
      $controlcells.=  wf_TableCell(wf_tag('h3',false,'title').__('Payment search').  wf_tag('h3', true));
      $controlcells.=  wf_TableCell(wf_tag('h3',false,'title').__('Analytics').  wf_tag('h3', true));
      $controlrows=  wf_TableRow($controlcells);
      
      $controlcells=  wf_TableCell($yearform);
      $controlcells.=  wf_TableCell($dateform);
      $controlcells.=  wf_TableCell(wf_Link("?module=payfind", 'Find', false, 'ubButton'));
      $controlcells.=  wf_TableCell(wf_Link("?module=report_finance&analytics=true", 'Show', false, 'ubButton'));
      $controlrows.=  wf_TableRow($controlcells);
      
      $controlgrid=  wf_TableBody($controlrows, '100%', 0, '');
      show_window('',$controlgrid);
      

web_PaymentsShowGraph($show_year);


if (!isset($_GET['month'])) {

// payments by somedate
if (isset($_POST['showdatepayments'])) {
    $paydate=mysql_real_escape_string($_POST['showdatepayments']);
    //deb($paydate);
    show_window(__('Payments by date').' '.$paydate,  web_PaymentsShow("SELECT * from `payments` WHERE `date` LIKE '".$paydate."%' ORDER by `date` DESC;"));
} else {

// today payments
$today=curdate();
show_window(__('Today payments'),  web_PaymentsShow("SELECT * from `payments` WHERE `date` LIKE '".$today."%' ORDER by `date` DESC;"));
}

} else {
    // show monthly payments
    $paymonth=mysql_real_escape_string($_GET['month']);
    
    show_window(__('Month payments'),  web_PaymentsShow("SELECT * from `payments` WHERE `date` LIKE '".$paymonth."%'  ORDER by `date` DESC;"));
}

  } else {
      //show finance analytics info
    
      

     if (wf_CheckPost(array('anyearsel'))) {
         $currentYear=$_POST['anyearsel'];
     } else {
         $currentYear=date("Y");
     }

     
     $yearinputs=  wf_YearSelector('anyearsel');
     $yearinputs.=wf_Submit(__('Show'));
     $yearform=  wf_Form("", 'POST', $yearinputs, 'glamour');
     show_window(__('Year'), $yearform);
     
     $graphs=  wf_Link("?module=report_finance", __('Back'), true, 'ubButton');
     $graphs.= web_AnalyticsArpuMonthGraph($currentYear);
     $graphs.= web_AnalyticsPaymentsMonthGraph($currentYear);
     $graphs.= web_AnalyticsSignupsMonthGraph($currentYear);
     $graphs.= web_AnalyticsSigReqMonthGraph($currentYear);
     $graphs.= web_AnalyticsTicketingMonthGraph($currentYear);
     $graphs.= web_AnalyticsTaskmanMonthGraph($currentYear);
     show_window('',$graphs);
     

  }
  

 zb_BillingStats(true);

} else {
      show_error(__('You cant control this module'));
}

?>
