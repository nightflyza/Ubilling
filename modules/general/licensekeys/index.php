<?php
if (cfr('ROOT')) {
    

      //key deletion
      if (wf_CheckGet(array('licensedelete'))) {
          $avarice=new Avarice();
          $avarice->deleteKey($_GET['licensedelete']);
          rcms_redirect('?module=licensekeys');
      }
      
      //key installation
      if (wf_CheckPost(array('createlicense'))) {
          $avarice=new Avarice();
          if ($avarice->createKey($_POST['createlicense'])) {
              rcms_redirect('?module=licensekeys');
          } else {
              show_window(__('Error'), __('Unacceptable license key'));
          }
      }
      //key editing
      if (wf_CheckPost(array('editlicense','editdbkey'))) {
          $avarice=new Avarice();
          if ($avarice->updateKey($_POST['editdbkey'], $_POST['editlicense'])) {
              rcms_redirect('?module=licensekeys');
          } else {
              show_window(__('Error'), __('Unacceptable license key'));
          }
      }
      
      zb_BillingStats(true);

   //show available license keys
  zb_LicenseLister();
} else {
    show_window(__('Error'), __('Access denied'));
}
  
 ?>


