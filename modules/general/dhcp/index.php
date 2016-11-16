<?php

if (cfr('DHCP')) {

    //creating object
    $dhcp = new UbillingDHCP();

    //if someone adds new dhcp network
    if (isset($_POST['adddhcp'])) {
        $netid = $_POST['networkselect'];
        $dhcpconfname = $_POST['dhcpconfname'];
        if (!empty($dhcpconfname)) {
            $dhcp->createNetwork($netid, $dhcpconfname);
            multinet_rebuild_all_handlers();
            rcms_redirect('?module=dhcp');
        } else {
            
        }
    }


    //editing existing dhcp network
    if (isset($_GET['edit'])) {
        //if someone changes network
        if (isset($_POST['editdhcpconfname'])) {
            @$editdhcpconfig = $_POST['editdhcpconfig'];
            $dhcpconfname = $_POST['editdhcpconfname'];
            $dhcpid = $_GET['edit'];
            dhcp_update_data($dhcpid, $dhcpconfname, $editdhcpconfig);
            multinet_rebuild_all_handlers();
            rcms_redirect("?module=dhcp");
        }
        // show editing form
        dhcp_show_edit_form($_GET['edit']);
    }

    //if someone deleting net
    if (isset($_GET['delete'])) {
        dhcp_delete_net($_GET['delete']);
        multinet_rebuild_all_handlers();
        rcms_redirect("?module=dhcp");
    }



    show_window(__('Available DHCP networks'), $dhcp->renderNetsList());
    show_window(__('Add DHCP network'), $dhcp->addForm());
    show_window(__('Global templates'), $dhcp->renderConfigTemplates());
    show_window(__('Generated configs preview'), $dhcp->renderConfigPreviews());
} else {
    show_error(__('Access denied'));
}
?>