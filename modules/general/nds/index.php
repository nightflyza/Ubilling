<?php
if (cfr('NDS')) {
    
    
    if (!isset($_POST['yearsel'])) {
        $show_year=curyear();
        } else {
        $show_year=$_POST['yearsel'];
        }
        
      $dateform='
<form action="?module=nds" method="POST">
'.  web_CalendarControl('showdatepayments').'
<input type="submit" value="'.__('Show').'">
</form>
<br>
';
      
      $yearform='
        <form action="?module=nds" method="POST">
         '.web_year_selector().'
        <input type="submit" value="'.__('Show').'">
        </form>
          ';
      
show_window(__('Year'),$yearform);
show_window(__('Payments by date'),$dateform);
web_NdsPaymentsShowYear($show_year);
    


    

if (!isset($_GET['month'])) {

// payments by somedate
if (isset($_POST['showdatepayments'])) {
    $paydate=mysql_real_escape_string($_POST['showdatepayments']);
    //deb($paydate);
    show_window(__('Payments by date').' '.$paydate,  web_NdsPaymentsShow("SELECT * from `payments` WHERE `date` LIKE '".$paydate."%'"));
} else {

// today payments
$today=curdate();
show_window(__('Today payments'),  web_NdsPaymentsShow("SELECT * from `payments` WHERE `date` LIKE '".$today."%'"));
}

} else {
    // show monthly payments
    $paymonth=mysql_real_escape_string($_GET['month']);
    
    show_window(__('Month payments'),  web_NdsPaymentsShow("SELECT * from `payments` WHERE `date` LIKE '".$paymonth."%'"));
}


} else {
      show_error(__('You cant control this module'));
}

?>