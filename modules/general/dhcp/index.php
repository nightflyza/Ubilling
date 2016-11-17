<?php

if (cfr('DHCP')) {

    //creating object
    $dhcp = new UbillingDHCP();

    //main controls panel
    show_window('', $dhcp->renderPanel());

    //new network creation
    if (isset($_POST['adddhcp'])) {
        $netid = $_POST['networkselect'];
        $dhcpconfname = $_POST['dhcpconfname'];
        if (!empty($dhcpconfname)) {
            if ($dhcp->isConfigNameFree($dhcpconfname)) {
                $dhcp->createNetwork($netid, $dhcpconfname);
                $dhcp->restartDhcpServer();
                rcms_redirect('?module=dhcp');
            } else {
                show_error(__('Config name is already used'));
            }
        } else {
            show_error(__('Config name is required'));
        }
    }


    //editing existing dhcp network
    if (isset($_GET['edit'])) {
        //if someone changes network
        if (isset($_POST['editdhcpconfname'])) {
            @$editdhcpconfig = $_POST['editdhcpconfig'];
            $dhcpconfname = $_POST['editdhcpconfname'];
            if (!empty($dhcpconfname)) {
                $dhcpid = $_GET['edit'];
                $dhcp->updateNetwork($dhcpid, $dhcpconfname, $editdhcpconfig);
                $dhcp->restartDhcpServer();
                rcms_redirect("?module=dhcp");
            } else {
                show_error(__('Config name is required'));
            }
        }
        // show editing form
        show_window(__('Edit custom subnet template'), $dhcp->editForm($_GET['edit']));
    }

    //deleting network
    if (isset($_GET['delete'])) {
        $dhcp->deleteNetwork($_GET['delete']);
        $dhcp->restartDhcpServer();
        rcms_redirect("?module=dhcp");
    }

    //downloading config
    if (wf_CheckGet(array('downloadconfig'))) {
        $dhcp->downloadConfig($_GET['downloadconfig']);
    }

    //downloading template
    if (wf_CheckGet(array('downloadtemplate'))) {
        $dhcp->downloadTemplate($_GET['downloadtemplate']);
    }

    //manual server restart
    if (wf_CheckGet(array('restartserver'))) {
        $dhcp->restartDhcpServer();
        rcms_redirect("?module=dhcp");
    }

    //rendering some interface
    show_window(__('Available DHCP networks'), $dhcp->renderNetsList());

    show_window(__('Generated configs preview'), $dhcp->renderConfigPreviews());
    show_window(__('Global templates'), $dhcp->renderConfigTemplates());
} else {
    show_error(__('Access denied'));
}
?>