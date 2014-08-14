<?php
$altcfg=$ubillingConfig->getAlter();
if ($altcfg['NETWORKS_EXT']) {
    if (cfr('MULTINET')) {
        
         $extNets=new ExtNets();
         
         /*
          * Extnets Pool Controller
          */
         if (!wf_CheckGet(array('showipsbypoolid'))) {
          show_window(__('Network available for allocation pools'), $extNets->renderNetworks()) ;
         } 
         
          //show available pools assigned by this network
          if (wf_CheckGet(array('showpoolbynetid'))) {
              //creating an new pool
              if (wf_CheckPost(array('newpool','newpoolnetid','newpoolnetmask'))) {
                  $extNets->poolCreate($_POST['newpoolnetid'], $_POST['newpool'], $_POST['newpoolnetmask'], $_POST['newpoolvlan']);
                  rcms_redirect("?module=extnets&showpoolbynetid=".$_POST['newpoolnetid']);
              }
              
              //deleting pool
              if (wf_CheckGet(array('deletepoolid','showpoolbynetid'))) {
                   $extNets->poolDelete($_GET['deletepoolid']);
                   rcms_redirect("?module=extnets&showpoolbynetid=".$_GET['showpoolbynetid']);
              }
              
              //editing pool
              if (wf_CheckPost(array('editpoolid','editpoolnetid'))) {
                  $extNets->poolEdit($_POST['editpoolid'], $_POST['editpoolvlan'], $_POST['editpoollogin']);
                  rcms_redirect("?module=extnets&showpoolbynetid=".$_POST['editpoolnetid']);
              }
              
              $poolNetCidr=$extNets->getNetworkCidr($_GET['showpoolbynetid']);
              show_window(__('Extended address pools in').' '.$poolNetCidr, $extNets->renderPools($_GET['showpoolbynetid']));
              //pool creation form
              show_window(__('Create new pool'), $extNets->poolCreateForm($_GET['showpoolbynetid']));
          }
        
        /*
         * Extnets IPS Controller
         */
          if (wf_CheckGet(array('showipsbypoolid'))) {
              //editing ip
              if (wf_CheckPost(array(''))) {
                  
              }
              show_window(__('IP associated with pool'), $extNets->renderIps($_GET['showipsbypoolid']));
          }
          
        
    } else {
        show_window(__('Error'), __('Access denied'));
    }
    
    
} else {
    show_window(__('Error'), __('This module is disabled'));
}

?>