<?php

if (cfr('DHCP')) {

    //creating object
    $dhcp = new UbillingDHCP();

    //main controls panel
    show_window('', $dhcp->renderPanel());

    //new dhcp network creation
    if (ubRouting::checkPost('adddhcp')) {
        $netid = ubRouting::post('networkselect');
        $dhcpconfname = ubRouting::post('dhcpconfname');
        if (!empty($dhcpconfname)) {
            if ($dhcp->isConfigNameFree($dhcpconfname)) {
                $dhcp->createNetwork($netid, $dhcpconfname);
                $dhcp->restartDhcpServer();
                ubRouting::nav($dhcp::URL_ME);
            } else {
                show_error(__('Config name is already used'));
            }
        } else {
            show_error(__('Config name is required'));
        }
    }


    //editing existing dhcp network
    if (ubRouting::checkGet('edit', false)) {
        //if someone changes network
        if (ubRouting::checkPost('editdhcpconfname')) {
            @$editdhcpconfig = ubRouting::post('editdhcpconfig');
            $dhcpconfname = ubRouting::post('editdhcpconfname');
            if (!empty($dhcpconfname)) {
                $dhcpid = ubRouting::get('edit');
                $dhcp->updateNetwork($dhcpid, $dhcpconfname, $editdhcpconfig);
                $dhcp->restartDhcpServer();
                ubRouting::nav($dhcp::URL_ME);
            } else {
                show_error(__('Config name is required'));
            }
        }
        // show editing form
        show_window(__('Edit custom subnet template'), $dhcp->editForm(ubRouting::get('edit')));
    }

    //deleting network
    if (ubRouting::checkGet('delete', false)) {
        $dhcp->deleteNetwork(ubRouting::get('delete'));
        $dhcp->restartDhcpServer();
        ubRouting::nav($dhcp::URL_ME);
    }

    //downloading config
    if (ubRouting::checkGet('downloadconfig')) {
        $dhcp->downloadConfig(ubRouting::get('downloadconfig'));
    }

    //downloading template
    if (ubRouting::checkGet('downloadtemplate')) {
        $dhcp->downloadTemplate(ubRouting::get('downloadtemplate'));
    }

    //manual server restart
    if (ubRouting::checkGet('restartserver')) {
        $dhcp->restartDhcpServer();
        ubRouting::nav($dhcp::URL_ME);
    }

    //rendering some interface
    show_window(__('Available DHCP networks'), $dhcp->renderNetsList());

    show_window(__('Generated configs preview'), $dhcp->renderConfigPreviews());
    show_window(__('Global templates'), $dhcp->renderConfigTemplates());
} else {
    show_error(__('Access denied'));
}
