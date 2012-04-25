<?php
if (cfr('CATV')) {
    $alter_conf=rcms_parse_ini_file(CONFIG_PATH."alter.ini");
    if ($alter_conf['CATV_ENABLED']) {
        
    
    
  //show controls
  catv_GlobalControlsShow();
  
  
  /////////// Tariff actions
  
  //if someone adds new tariff
            $needtocreate=array('newtariffname','newtariffprice');
            if (wf_CheckPost($needtocreate)) {
                catv_TariffAdd($_POST['newtariffname'], $_POST['newtariffprice'], @$_POST['newtariffchans']);
                rcms_redirect("?module=catv&action=tariffs");
            }
          
         //if someone deletes tariff
            $needtodelete=array('tariffdelete');
            if (wf_CheckGet($needtodelete)) {
                if (!catv_TariffProtected($_GET['tariffdelete'])) {
                   catv_TariffDelete($_GET['tariffdelete']);
                   rcms_redirect("?module=catv&action=tariffs");
                } else {
                    show_error(__('Tariff is used by some users'));
                }
                
                
            }
         //if someone want to edit tariff
           $needtoeditform=array('tariffedit'); 
           if (wf_CheckGet($needtoeditform)) {
               catv_TariffEditForm($_GET['tariffedit']);
           }
         // if received tariff edit event
           $needtoedittariff=array('edittariffid','edittariffname','edittariffprice');
           if (wf_CheckPost($needtoedittariff)) {
               catv_TariffModify($_POST['edittariffid'], $_POST['edittariffname'], $_POST['edittariffprice'], @$_POST['edittariffchans']);
               rcms_redirect("?module=catv&action=tariffs");
           }
            
  
  if (isset($_GET['action'])) {
      
       //show available tariffs and add form
      if ($_GET['action']=='tariffs') {
         
          catv_TariffShowAll();
          catv_TariffAddForm();
      }
       //show user registration form
       if ($_GET['action']=='userreg') {
           //if someone register user
           $needtoregisteruser=array('realyregister','newusercontract');
           if (wf_CheckPost($needtoregisteruser)) {
              catv_UserRegister($_POST['newusercontract'], $_POST['newuserrealname'], $_POST['newuserstreet'], $_POST['newuserbuild'], $_POST['newuserapt'], $_POST['newuserphone'], $_POST['newusertariff'], $_POST['newusercash'], $_POST['newuserdecoder']);
              $newuserid=simple_get_lastid('catv_users');
              rcms_redirect("?module=catv_profile&userid=".$newuserid);
           }
           catv_UserRegisterForm();     
       }
       
       //show user list
        if ($_GET['action']=='showusers') {
            catv_UsersShowList();
        }
        
        
        //fee charge form
        if ($_GET['action']=='fees') {
            
            // charge fee subroutine
            if (wf_CheckPost(array('newcharge'))) {
                if (catv_FeeChargeCheck($_POST['chargemonthfee'], $_POST['chargeyear'])) {
                catv_FeeChargeAllUsers($_POST['chargemonthfee'], $_POST['chargeyear']);
                rcms_redirect("?module=catv&action=fees");
                } else {
                    show_window(__('Error'), __('This month has already been assessed fee'));
                }
            }
            
            // tariff change subroutine
            if (wf_CheckPost(array('newtarchange'))) {
                catv_TariffChangeAllPlanned();
                rcms_redirect("?module=catv&action=fees");
            }
            
            //construct tariff change form
              $tarchangeinputs=wf_HiddenInput('newtarchange', 'true');
              $tarchangeinputs.=wf_Submit('Change tariff for all users');
              $tarform=wf_Form('', 'POST', $tarchangeinputs, 'glamour', '');
              show_window(__('Check out the planned tariff changes'), $tarform);
            
            
            //construct charge form
              $feeinputs=wf_HiddenInput('newcharge', 'true');
              $feeinputs.=wf_MonthSelector('chargemonthfee', 'Month', date("m"), false);
              $feeinputs.=wf_YearSelector('chargeyear', 'Year', false).'&nbsp;&nbsp;';
              $feeinputs.=wf_Submit(__('Manual fee charge'));
              $feeform=wf_Form('', 'POST', $feeinputs, 'glamour', '');
              show_window(__('Сharge monthly fee'), $feeform);
        }
        
        //reports list
        if ($_GET['action']=='reports') {
           // show available reports list
            
           
            if (!isset ($_GET['showreport'])) {
                catv_ReportsShowList();
            } else {
                // current debtors
                if ($_GET['showreport']=='current_debtors') {
                     catv_ReportDebtors();
                }
            }
            
        }
        
        
  }
    } else {
        show_error('CaTV support is disabled');
    }
    
} else {
      show_error(__('You cant control this module'));
}

?>