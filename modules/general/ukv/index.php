<?php
if (cfr('UKV')) {
    
  
    
    //creating base system object
    $ukv=new UkvSystem();
    
    /*
     * controller section
     */
    
    //fast ajax render
    if (wf_CheckGet(array('ajax'))) {
        $ukv->ajaxUsers();
    }


    /*
     * some views here
     */
    
    //show global management panel
    show_window('', $ukv->panel());
    
    //renders tariffs list with controls
    if (wf_CheckGet(array('tariffs'))) {
        
    //tariffs editing
    if (wf_CheckPost(array('edittariff'))) {
        $ukv->tariffSave($_POST['edittariff'], $_POST['edittariffname'], $_POST['edittariffprice']);
        rcms_redirect(UkvSystem::URL_TARIFFS_MGMT);
    }
    
    //tariffs creation
    if (wf_CheckPost(array('createtariff'))) {
        $ukv->tariffCreate($_POST['createtariffname'], $_POST['createtariffprice']);
        rcms_redirect(UkvSystem::URL_TARIFFS_MGMT);
    }
    
    //tariffs deletion
    if (wf_CheckGet(array('tariffdelete'))) {
        $ukv->tariffDelete($_GET['tariffdelete']);
        rcms_redirect(UkvSystem::URL_TARIFFS_MGMT);
    }
    
        //show tariffs lister
        show_window(__('Available tariffs'),$ukv->renderTariffs());
    }
    
    //full users listing
    if (wf_CheckGet(array('users','userslist'))) {
        show_window(__('Available users'), $ukv->renderUsers());
    }
    
    //users registration
    if (wf_CheckGet(array('users','register'))) {
        if (wf_CheckPost(array('userregisterprocessing'))) {
         if (wf_CheckPost(array('uregcity','uregstreet','uregbuild'))) {
             //all needed fields is filled - processin registration
             $createdUserId=$ukv->userCreate();
             rcms_redirect(UkvSystem::URL_USERS_PROFILE.$createdUserId);
         } else {
              show_window(__('Error'), __('All fields marked with an asterisk are mandatory'));
         }
        }
        //show new user registration form
        show_window(__('User registration'),$ukv->userRegisterForm());
    }
    
    //user profile show
    if (wf_CheckGet(array('users','showuser'))) {
        
        //user editing processing
        if (wf_CheckPost(array('usereditprocessing'))) {
            $ukv->userSave();
            rcms_redirect(UkvSystem::URL_USERS_PROFILE.$_POST['usereditprocessing']);
        }
        
        //user deletion processing
        if (wf_CheckPost(array('userdeleteprocessing','deleteconfirmation'))) {
            if ($_POST['deleteconfirmation']=='confirm') {
                $ukv->userDelete($_POST['userdeleteprocessing']);
                rcms_redirect(UkvSystem::URL_USERS_LIST);
            } else {
                log_register('UKV USER DELETE TRY (('.$_POST['userdeleteprocessing'].'))');
            }
        }
        
        //manual payments processing
        if (wf_CheckPost(array('manualpaymentprocessing','paymentsumm','paymenttype'))) {
            $paymentNotes='';
            //normal payment
            if ($_POST['paymenttype']=='add') {
                $paymentVisibility=1;
            } 
            //balance correcting
            if ($_POST['paymenttype']=='correct') {
                $paymentVisibility=0;
            }
            //mock payment
            if ($_POST['paymenttype']=='mock') {
                $paymentVisibility=1;
                $paymentNotes.='MOCK:';
            }
            //set payment notes
            if (wf_CheckPost(array('paymentnotes'))) {
               $paymentNotes.=$_POST['paymentnotes']; 
            }
            
            if ($_POST['paymenttype']!='mock') {
              $ukv->userAddCash($_POST['manualpaymentprocessing'], $_POST['paymentsumm'], $paymentVisibility, $_POST['paymentcashtype'], $paymentNotes);
            } else {
              $ukv->logPayment($_POST['manualpaymentprocessing'], $_POST['paymentsumm'], $paymentVisibility, $_POST['paymentcashtype'], $paymentNotes);  
            }
            rcms_redirect(UkvSystem::URL_USERS_PROFILE.$_POST['manualpaymentprocessing']);
        }
        
        show_window(__('User profile'), $ukv->userProfile($_GET['showuser']));
    }
    
    // bank statements processing
    if (wf_CheckGet(array('banksta'))) {
        set_time_limit(0);
        //banksta upload 
        if (wf_CheckPost(array('uploadukvbanksta'))) {
            $bankstaUploaded=$ukv->bankstaDoUpload();
            if (!empty($bankstaUploaded)) {
                $processedBanksta=$ukv->bankstaPreprocessing($bankstaUploaded);
                    rcms_redirect(UkvSystem::URL_BANKSTA_PROCESSING.$processedBanksta);
            }
        } else {
            
            if (wf_CheckGet(array('showhash'))) {
                show_window(__('Bank statement processing'), $ukv->bankstaProcessingForm($_GET['showhash']));
            } else {
                if (wf_CheckGet(array('showdetailed'))) {
                    //show banksta row detailed info
                    show_window(__('Bank statement'), $ukv->bankstaGetDetailedRowInfo($_GET['showdetailed']));
                } else {
                 //show upload form
                 show_window(__('Import bank statement'),$ukv->bankstaLoadForm());
                }
            }
        }
        
        
        
        
    }
    
} else {
    show_window(__('Error'), __('Access denied'));
}

?>