<?php

if (cfr('CORPORATE')) {
    // here we show parent user and his controls
    if (isset($_GET['userlink'])) {
        $userlink=$_GET['userlink'];
        $parent_login=cu_GetParentUserLogin($userlink);
        $childusers=cu_GetAllChildUsers($userlink);
        
        $group_controls=wf_Link('?module=corporate&userlink='.$userlink.'&control=cash', 'Cash', false, 'ubButton');
        $group_controls.=wf_Link('?module=corporate&userlink='.$userlink.'&control=tariff', 'Tariff', false, 'ubButton');
        $group_controls.=wf_Link('?module=corporate&userlink='.$userlink.'&control=credit', 'Credit', false, 'ubButton');
        
        show_window(__('Group operations'),$group_controls);
        show_window(__('Linked users'),  web_UserArrayShower($childusers));
        //show parent user profile by default
        if (!isset($_GET['control'])) {
        $profileObj=new UserProfile($parent_login);
        $default_profile=$profileObj->render();
        show_window(__('User profile'),$default_profile);
        } else {
            //show controls
            if ($_GET['control']=='cash') {
                //group cash operations
                $allchildusers=cu_GetAllChildUsers($userlink);
                //cash add form construct
                $cashtypes=zb_CashGetAllCashTypes();
                $cashinputs=wf_TextInput('newcash', 'New cash', '', true, 5);
                $cashinputs.=web_CashTypeSelector().' '.__('Cash type');
                $cashinputs.='<br>';
                $cashinputs.=wf_RadioInput('operation', 'Add cash', 'add', false, true);
                $cashinputs.=wf_RadioInput('operation', 'Correct saldo', 'correct', false, false);
                $cashinputs.=wf_RadioInput('operation', 'Mock payment', 'mock', false, false);
                $cashinputs.=wf_RadioInput('operation', 'Set cash', 'set', true, false);
                $cashinputs.=wf_TextInput('newpaymentnote', 'Payment note', '', true, 35);
                $cashinputs.='<br>';
                $cashinputs.=wf_Submit('Add cash');
                $cashform=wf_Form('', 'POST', $cashinputs, 'glamour');
                show_window(__('Add cash'),$cashform);
                show_window('',  web_UserControls($parent_login));
                //if someone adds cash
                if (wf_CheckPost(array('newcash'))) {
                   $operation=vf($_POST['operation']);
                   $cashtype=vf($_POST['cashtype']);
                   $cash=$_POST['newcash'];
                   if (isset($_POST['newpaymentnote'])) {
                    $note=mysql_real_escape_string($_POST['newpaymentnote']);
                    }
                  //add cash to parent user
                  zb_CashAdd($parent_login, $cash, $operation, $cashtype, $note);
                  
                  // add cash to all child users
                  if (!empty ($allchildusers)) {
                      foreach ($allchildusers as $eachchild) {
                              //adding cash 
                    if ($operation=='add') {
                    $billing->addcash($eachchild,$cash); 
                    }
                    //correcting balance
                    if ($operation=='correct') {
                    $billing->addcash($eachchild,$cash); 
                    }
                    //setting cash
                    if ($operation=='set') {
                    $billing->setcash($eachchild,$cash);     
                    }
                    log_register("GROUPBALANCE ".$eachchild." ".$operation." ON ".$cash);
                    rcms_redirect("?module=corporate&userlink=".$userlink."&control=cash");
                   }
                  }
                }
            }
            //cash control end
            
            if ($_GET['control']=='tariff') {
                 //group tariff operations
                 $allchildusers=cu_GetAllChildUsers($userlink);
                 
                 //construct form
                 $current_tariff=zb_UserGetStargazerData($parent_login);
                 $current_tariff=$current_tariff['Tariff'];
                 $tariffinputs='<h3>'.__('Current tariff').': '.$current_tariff.'</h3> <br>';
                 $tariffinputs.=web_tariffselector('newtariff');
                 $tariffinputs.='<br>';
                 $tariffinputs.=wf_CheckInput('nextmonth', 'Next month', true, false);
                 $tariffinputs.='<br>';
                 $tariffinputs.=wf_Submit('Save');
                 $tariffform=wf_Form('', 'POST', $tariffinputs, 'glamour');
                 show_window(__('Edit tariff'),$tariffform);
                       //if group tariff change 
                  if (wf_CheckPost(array('newtariff'))) {
                       $alter_conf=rcms_parse_ini_file(CONFIG_PATH."alter.ini");
                       $tariff=$_POST['newtariff'];
                    if (!isset($_POST['nextmonth'])) {
                    $billing->settariff($parent_login,$tariff);
                    log_register('CHANGE Tariff '.$parent_login.' ON '.$tariff);
                    //optional user reset
                    if ($alter_conf['TARIFFCHGRESET']) {
                      $billing->resetuser($parent_login);
                      log_register('USER RESET (' . $parent_login . ')');
                     } 
                    } else {
                    // next month parent user tariff change
                    $billing->settariffnm($parent_login,$tariff);
                    log_register('CHANGE TariffNM '.$parent_login.' ON '.$tariff);
                    }
                    
                    //the same for all childs users
                      if (!empty ($allchildusers)) {
                      foreach ($allchildusers as $eachchild) {
                    if (!isset($_POST['nextmonth'])) {
                    $billing->settariff($eachchild,$tariff);
                    log_register('CHANGE Tariff '.$eachchild.' ON '.$tariff);
                    //optional user reset
                    if ($alter_conf['TARIFFCHGRESET']) {
                      $billing->resetuser($eachchild);
                      log_register('USER RESET (' . $eachchild . ')');
                     } 
                    } else {
                    // next month child user tariff change
                    $billing->settariffnm($eachchild,$tariff);
                    log_register('CHANGE TariffNM '.$eachchild.' ON '.$tariff);
                    }
                       }
                      }
                      
                     rcms_redirect("?module=corporate&userlink=".$userlink."&control=tariff");
                   }
                   // end of newtariff checks
                 
           
            }
            //tariffs end
            
               if ($_GET['control']=='credit') {
                 //group credit operations 
                   $allchildusers=cu_GetAllChildUsers($userlink); 
                   $current_credit=zb_UserGetStargazerData($parent_login);
                   $current_credit=$current_credit['Credit'];
                   
                   //construct form
                   $creditinputs=wf_TextInput('newcredit', 'New credit', $current_credit, true, '10');
                   $creditinputs.=wf_Submit('Save');
                   $creditform=wf_Form('', 'POST', $creditinputs, 'glamour');
                   show_window(__('Edit credit'),$creditform);
                   
                   
                      //if group credit change 
                  if (isset($_POST['newcredit'])) {
                       $credit=vf($_POST['newcredit']);
                      //change credit for parent user
                       $billing->setcredit($parent_login,$credit);
                       log_register('CHANGE Credit '.$parent_login.' ON '.$credit);
                       
                     // set credit for all child users
                  if (!empty ($allchildusers)) {
                      foreach ($allchildusers as $eachchild) {
                       $billing->setcredit($eachchild,$credit);
                       log_register('CHANGE Credit '.$eachchild.' ON '.$credit);
                       }
                      }
                  rcms_redirect("?module=corporate&userlink=".$userlink."&control=credit");    
                  }
                   
               }
               //credits end
        }
        //controls if end
        
    }
    //userlink if-end
    
    
    
} else {
show_error(__('You cant control this module'));    
}

?>
