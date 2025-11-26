<?php
set_time_limit(0);
if(cfr('MASSRESET')) {
    $altcfg=  rcms_parse_ini_file(CONFIG_PATH."alter.ini");
    if ($altcfg['MASSRESET_ENABLED']) {
        
        //form with optional confirmation
        function mrst_FormShow() {
            global $altcfg;
            
            $inputs=__('After clicking the button below for all users will perform the standard procedure reset. By default, this will reinitialize shapers and  MAC bindings.');
            if (!isset($altcfg['MASSRESET_NOCONFIRM'])) {
              $confirmKey=rand(1111,9999);
              $inputs.=' '.__('If you are completely sure of what you wish, enter the following numbers into the next field.').'<br>';  
              $inputs.=wf_HiddenInput('confirmkey', $confirmKey);
              $inputs.=$confirmKey.' ⇨ '.wf_TextInput('confirmcheck','', '', true, 5);
            }
            $inputs.=wf_HiddenInput('runmassreset', 'true').'<br>';
            $inputs.=wf_Submit(__('I`m ready'));
            
            $form=  wf_Form("", 'POST', $inputs, 'glamour');
            
            show_window(__('Mass user reset'),$form);
        }
        
        //resetting all users
        function mrst_MassReset() {
            global $altcfg,$billing;
            $query="SELECT `login` from `users`";
            $allusers=  zb_UserGetAllStargazerData();
            if (!empty($allusers)) {
                foreach ($allusers as $io=>$eachuser) {
                    //very shitty hack
                    sleep(2);
                    $billing->resetuser($eachuser['login']);
                    if (!isset($altcfg['MASSRESET_NOLOG'])) {
                    log_register("MASSRESET USER (".$eachuser['login'].")");
                    }
                }
                //preventing F5
                rcms_redirect("?module=massreset");
            } else {
                show_error(__('Any users found'));
            }
        }

/*
 *  Main codepart
 */        
        mrst_FormShow();
        
    
        //mass resetting sub
        if (wf_CheckPost(array('runmassreset'))) {
            if (!isset($altcfg['MASSRESET_NOCONFIRM'])) {
                if (wf_CheckPost(array('confirmcheck','confirmkey'))) {
                    if ($_POST['confirmcheck']==$_POST['confirmkey']) {
                        //if confirmation received
                        mrst_MassReset();
                        
                    } else {
                        show_error(__('You are not mentally prepared for this'));
                    }
                } else {
                    show_error(__('You are not mentally prepared for this'));
                }
                
            } else {
                //just reset all
                mrst_MassReset();
            }
        }
        
        
    } else {
        show_error(__('This module is disabled'));
    }
    
} else {
    show_error(__('Access denied'));
}

?>