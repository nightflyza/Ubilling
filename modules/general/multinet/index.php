<?php

if (cfr('MULTINET')) {
    $altcfg = $ubillingConfig->getAlter();
    $urlMe = '?module=multinet'; //current module basic route
//adding new network
    if (ubRouting::checkPost('addnet')) {
        $netadd_req = array('firstip', 'lastip', 'desc');
        if (ubRouting::checkPost($netadd_req)) {
            $desc = ubRouting::post('desc');
            $firstip = ubRouting::post('firstip');
            $lastip = ubRouting::post('lastip');
            $nettype = ubRouting::post('nettypesel');
            multinet_add_network($desc, $firstip, $lastip, $nettype);
            ubRouting::nav($urlMe);
        } else {
            show_error(__('No all of required fields is filled'));
        }
    }

//deleting network
    if (ubRouting::checkGet('deletenet')) {
        $network_id = ubRouting::get('deletenet');
        // check have this network any users inside?
        if (!multinet_network_is_used($network_id)) {
            multinet_delete_network($network_id);
            ubRouting::nav($urlMe);
        } else {
            //if here users - go back
            show_error(__('The network that you are trying to remove - contains live users. We can not afford to do so with them.'));
            show_window('', wf_BackLink($urlMe, 'Back', true));
        }
    }

//service adding
    if (ubRouting::checkPost('serviceadd')) {
        $servadd_req = array('networkselect', 'servicename');
        if (ubRouting::checkPost($servadd_req)) {
            $net = ubRouting::post('networkselect');
            $desc = ubRouting::post('servicename');
            multinet_add_service($net, $desc);
            ubRouting::nav($urlMe);
        } else {
            show_error(__('No all of required fields is filled'));
        }
    }

//service deletion
    if (ubRouting::checkGet('deleteservice')) {
        $service_id = ubRouting::get('deleteservice');
        multinet_delete_service($service_id);
        ubRouting::nav($urlMe);
    }

//available network and services render
    if (!ubRouting::checkGet('editnet') AND ! ubRouting::checkGet('editservice') AND ! ubRouting::checkGet('freeipstats')) {
        multinet_show_available_networks();
        multinet_show_networks_create_form();

        multinet_show_available_services();
        multinet_show_service_add_form();
        multinet_rebuild_all_handlers();
    } else {
        // editing network
        if (ubRouting::checkGet('editnet')) {
            $editnet = ubRouting::get('editnet', 'int');
            if (ubRouting::checkPost('netedit')) {

                $neted_req = array('editstartip', 'editendip', 'editdesc');
                if (ubRouting::checkPost($neted_req)) {
                    simple_update_field('networks', 'startip', $_POST['editstartip'], "WHERE `id`='" . $editnet . "'");
                    simple_update_field('networks', 'endip', $_POST['editendip'], "WHERE `id`='" . $editnet . "'");
                    simple_update_field('networks', 'desc', $_POST['editdesc'], "WHERE `id`='" . $editnet . "'");
                    simple_update_field('networks', 'nettype', $_POST['nettypesel'], "WHERE `id`='" . $editnet . "'");
                    log_register('MODIFY MultiNetNet [' . $editnet . ']');
                    ubRouting::nav($urlMe);
                } else {
                    show_error(__('No all of required fields is filled'));
                }
            }
            multinet_show_neteditform($editnet);
        }

        //editing service
        if (ubRouting::checkGet('editservice')) {
            $editservice = ubRouting::get('editservice', 'int');
            if (ubRouting::checkPost('serviceedit')) {
                if (ubRouting::checkPost('editservicename')) {
                    simple_update_field('services', 'desc', $_POST['editservicename'], "WHERE `id`='" . $editservice . "'");
                    simple_update_field('services', 'netid', $_POST['networkselect'], "WHERE `id`='" . $editservice . "'");
                    log_register('MODIFY MultiNetService [' . $editservice . ']');
                    ubRouting::nav($urlMe);
                } else {
                    show_error(__('No all of required fields is filled'));
                }
            }
            multinet_show_serviceeditform($editservice);
        }

        //network stats
        if (ubRouting::checkGet('freeipstats')) {
            $freeIpStats = web_FreeIpStats();
            $freeIpStats .= wf_BackLink($urlMe);
            $freeIpStats .= wf_Link($urlMe . '&freeipstats=true', wf_img('skins/done_icon.png') . ' ' . __('Services'), false, 'ubButton');
            $freeIpStats .= wf_Link($urlMe . '&freeipstats=true&allnets=true', wf_img('skins/categories_icon.png') . ' ' . __('All networks'), false, 'ubButton');
            show_window(__('IP usage stats'), $freeIpStats);
        }
    }
} else {
    show_error(__('Access denied'));
}
