<?php
if (cfr('LOUSYTARIFFS')) {
  
  //deleting lousy mark
  if (wf_CheckGet(array('delete'))) {
      zb_LousyTariffDelete($_GET['delete']);
      rcms_redirect("?module=lousytariffs");
  }  
  
  //adding new lousy mark for tariff
  if (wf_CheckPost(array('newlousytariff'))) {
      zb_LousyTariffAdd($_POST['newlousytariff']);
      rcms_redirect("?module=lousytariffs");
  }
  
  show_window(__('Rarely used tariffs'),web_LousyShowAll());
  show_window('',  web_LousyAddForm());
    
} else {
      show_error(__('You cant control this module'));
}

?>