<?php
// check for right of current admin on this module
if (cfr('CATVCASH')) {
    
      catv_GlobalControlsShow();
      if (wf_CheckGet(array('userid'))) {
      $userid=$_GET['userid'];
       
      // if payment post received
      
      if (wf_CheckPost(array('createpayment','newpayment','optype'))) {
         
      //just adding cash
      if ($_POST['optype']=='add') {
        catv_CashAdd($userid, $_POST['date'], $_POST['newpayment'], $_POST['from_month'], $_POST['from_year'], $_POST['to_month'], $_POST['to_year'], $_POST['notes']);
      }
      
      //correcting saldo
      if ($_POST['optype']=='corr') {
          catv_CashCorrect($userid, $_POST['date'], $_POST['newpayment'], $_POST['from_month'], $_POST['from_year'], $_POST['to_month'], $_POST['to_year'], $_POST['notes']);
      }
      
      //mock payment
      if ($_POST['optype']=='mock') {
         catv_CashMock($userid, $_POST['date'], $_POST['newpayment'], $_POST['from_month'], $_POST['from_year'], $_POST['to_month'], $_POST['to_year'], $_POST['notes']);
      }
      
      rcms_redirect("?module=catv_addcash&userid=".$userid);
      }
      
        if (wf_CheckPost(array('createpayment','optype'))) {
             //set payment may be zero
            if ($_POST['optype']=='set') {
            if (isset($_POST['newpayment'])) {
                catv_CashSet($userid, $_POST['date'], $_POST['newpayment'], $_POST['from_month'], $_POST['from_year'], $_POST['to_month'], $_POST['to_year'], $_POST['notes']);
            }
            
            }
            rcms_redirect("?module=catv_addcash&userid=".$userid);
        }
      
      //if someone delete payment
      if (wf_CheckGet(array('deletepayment'))) {
          catv_CashPaymentDelete($_GET['deletepayment']);
          rcms_redirect("?module=catv_addcash&userid=".$userid);
      }
      
      //if someone delete payment
      if (wf_CheckGet(array('editpayment'))) {
          $paymentid=vf($_GET['editpayment'],3);
          
          if (isset($_POST['editpayment'])) {
              catv_CashEdit($paymentid, $_POST['editdate'], $_POST['editpayment'], $_POST['editfrom_month'], $_POST['editfrom_year'], $_POST['editto_month'], $_POST['editto_year'], $_POST['editnotes']);
              rcms_redirect("?module=catv_addcash&userid=".$userid);
          }
          
          show_window(__('Edit').' '.__('Payment'),catv_CashEditForm($paymentid));
      }
      
        if (!wf_CheckGet(array('editpayment'))) {
        show_window(__('Manual receipt of payments'), catv_CashAddForm($userid));
        }
            
        catv_UserStatsByYear($userid,curyear());
        catv_ProfileBack($userid);
      
      }
    
} else {
      show_error(__('You cant control this module'));
}

?>