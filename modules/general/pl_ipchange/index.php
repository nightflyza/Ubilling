<?php

if (cfr('PLIPCHANGE')) {


    if (isset($_GET['username'])) {
        $login = mysql_real_escape_string($_GET['username']);
        $current_ip = zb_UserGetIP($login); // getting IP by login
        $current_mac = zb_MultinetGetMAC($current_ip); //extracting current user MAC
        $billingConf = $ubillingConfig->getBilling(); //getting billing.ini config

        /*
         * Returns new user service select form
         * 
         * @return string
         */

        function web_IPChangeFormService() {
            global $current_ip;
            $inputs = multinet_service_selector() . ' ' . __('New IP service');
            $inputs.= wf_delimiter();
            $inputs.= wf_Submit(__('Save'));
            $result = wf_Form("", 'POST', $inputs, 'floatpanels');
            return($result);
        }

        /*
         * Flushes all old user`s networking data and applies new one
         * 
         * @param   string   $current_ip        current users`s IP
         * @param   string   $current_mac       current users`s MAC address
         * @param   int      $new_multinet_id   new network ID extracted from service
         * @param   string   $new_free_ip       new IP address which be applied for user
         * @param   string   $login             existing stargazer user login
         * 
         * @return void
         */

        function zb_IPChange($current_ip, $current_mac, $new_multinet_id, $new_free_ip, $login) {
            global $billing;
            global $billingConf;

            //force user disconnect
            if ($billingConf['RESET_AO']) {
                $billing->setao($login, 0);
            } else {
                $billing->setdown($login, 1);
            }

            $billing->setip($login, $new_free_ip);
            multinet_delete_host($current_ip);
            multinet_add_host($new_multinet_id, $new_free_ip, $current_mac);
            multinet_rebuild_all_handlers();
            multinet_RestartDhcp();

            //back teh user online
            if ($billingConf['RESET_AO']) {
                $billing->setao($login, 1);
            } else {
                $billing->setdown($login, 0);
            }
        }

        // primary module part    
        if (isset($_POST['serviceselect'])) {
            $new_multinet_id = multinet_get_service_networkid($_POST['serviceselect']);
            @$new_free_ip = multinet_get_next_freeip('nethosts', 'ip', $new_multinet_id);
            if (empty($new_free_ip)) {
                $alert = wf_tag('script', false, '', 'type="text/javascript"') . 'alert("' . __('Error') . ': ' . __('No free IP available in selected pool') . '");' . wf_tag('script', true);
                print($alert);
                rcms_redirect("?module=multinet");
                die();
            }

            zb_IPChange($current_ip, $current_mac, $new_multinet_id, $new_free_ip, $login);
            log_register("CHANGE MultiNetIP (" . $login . ") FROM " . $current_ip . " ON " . $new_free_ip."");
            rcms_redirect("?module=pl_ipchange&username=" . $login);
        } else {
            show_window(__('Current user IP'), wf_tag('h2', false, 'floatpanels', '') . ' ' . $current_ip . wf_tag('h2', true) . '<br clear="both" />');
            show_window(__('Change user IP'), web_IPChangeFormService());
        }

        show_window('', web_UserControls($login));
    }
} else {
    show_error(__('You cant control this module'));
}
?>
