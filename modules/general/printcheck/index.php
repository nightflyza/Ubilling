<?php
if (cfr('PRINTCHECK')) {
if (isset($_GET['paymentid'])) {
   $paymentid=$_GET['paymentid'];
   
    
   print(zb_PrintCheck($paymentid));
   die();
}


} else {
      show_error(__('You cant control this module'));
}

?>
