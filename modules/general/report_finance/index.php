<?php
if (cfr('REPORTFINANCE')) {

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
      $controlrows=  wf_TableRow($controlcells);
      
      $controlcells=  wf_TableCell($yearform);
      $controlcells.=  wf_TableCell($dateform);
      $controlcells.=  wf_TableCell(wf_Link("?module=payfind", 'Find', false, 'ubButton'));
      $controlrows.=  wf_TableRow($controlcells);
      
      $controlgrid=  wf_TableBody($controlrows, '100%', 0, '');
      show_window('',$controlgrid);
      

web_PaymentsShowGraph($show_year);


if (!isset($_GET['month'])) {

// payments by somedate
if (isset($_POST['showdatepayments'])) {
    $paydate=mysql_real_escape_string($_POST['showdatepayments']);
    //deb($paydate);
    show_window(__('Payments by date').' '.$paydate,  web_PaymentsShow("SELECT * from `payments` WHERE `date` LIKE '".$paydate."%'"));
} else {

// today payments
$today=curdate();
show_window(__('Today payments'),  web_PaymentsShow("SELECT * from `payments` WHERE `date` LIKE '".$today."%'"));
}

} else {
    // show monthly payments
    $paymonth=mysql_real_escape_string($_GET['month']);
    
    show_window(__('Month payments'),  web_PaymentsShow("SELECT * from `payments` WHERE `date` LIKE '".$paymonth."%'"));
}
    

 zb_BillingStats(true);

} else {
      show_error(__('You cant control this module'));
}

?>
