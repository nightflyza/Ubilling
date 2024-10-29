<?php

if (cfr('AGENTS')) {
    //listing stricts agent assign
    if (ubRouting::checkGet('ajaxagenassign')) {
        web_AgentAssignStrictShow();
    }

    //mb some custom options?
    $alter_conf = $ubillingConfig->getAlter();

    //if deleting agent
    if (ubRouting::checkGet('delete', false) and ! ubRouting::checkGet('extinfo')) {
        zb_ContrAhentDelete(ubRouting::get('delete'));
        ubRouting::nav("?module=contrahens");
    }

    //if deleting strict assign for user
    if (ubRouting::checkGet(array('deleteassignstrict', 'username'))) {
        zb_AgentAssignStrictDelete(ubRouting::get('username'));
        ubRouting::nav("?module=contrahens");
    }

    //if adding new agent
    if (wf_CheckPost(array('newcontrname'))) {
        $bankacc = ubRouting::post('newbankacc');
        $bankname = ubRouting::post('newbankname');
        $bankcode = ubRouting::post('newbankcode');
        $edrpo = ubRouting::post('newedrpo');
        $ipn = ubRouting::post('newipn');
        $licensenum = ubRouting::post('newlicensenum');
        $juraddr = ubRouting::post('newjuraddr');
        $phisaddr = ubRouting::post('newphisaddr');
        $phone = ubRouting::post('newphone');
        $contrname = ubRouting::post('newcontrname');
        $agnameabbr = ubRouting::post('newagnameabbr');
        $agsignatory = ubRouting::post('newagsignatory');
        $agsignatory2 = ubRouting::post('newagsignatory2');
        $agbasis = ubRouting::post('newagbasis');
        $agmail = ubRouting::post('newagmail');
        $siteurl = ubRouting::post('newsiteurl');

        zb_ContrAhentAdd($bankacc, $bankname, $bankcode, $edrpo, $ipn, $licensenum, $juraddr, $phisaddr, $phone, $contrname, $agnameabbr, $agsignatory, $agsignatory2, $agbasis, $agmail, $siteurl);
        ubRouting::nav("?module=contrahens");
    }

    if (ubRouting::checkGet('edit', false) and ! ubRouting::checkGet('extinfo')) {

        //if someone changing agent
        if (ubRouting::post('changecontrname')) {
            $ahentid = ubRouting::get('edit');
            $bankacc = ubRouting::post('changebankacc');
            $bankname = ubRouting::post('changebankname');
            $bankcode = ubRouting::post('changebankcode');
            $edrpo = ubRouting::post('changeedrpo');
            $ipn = ubRouting::post('changeipn');
            $licensenum = ubRouting::post('changelicensenum');
            $juraddr = ubRouting::post('changejuraddr');
            $phisaddr = ubRouting::post('changephisaddr');
            $phone = ubRouting::post('changephone');
            $contrname = ubRouting::post('changecontrname');
            $agnameabbr = ubRouting::post('changeagnameabbr');
            $agsignatory = ubRouting::post('changeagsignatory');
            $agsignatory2 = ubRouting::post('changeagsignatory2');
            $agbasis = ubRouting::post('changeagbasis');
            $agmail = ubRouting::post('changeagmail');
            $siteurl = ubRouting::post('changesiteurl');
            zb_ContrAhentChange($ahentid, $bankacc, $bankname, $bankcode, $edrpo, $ipn, $licensenum, $juraddr, $phisaddr, $phone, $contrname, $agnameabbr, $agsignatory, $agsignatory2, $agbasis, $agmail, $siteurl);
            ubRouting::nav("?module=contrahens");
        }
        // show edit form  
        show_window(__('Edit'), zb_ContrAhentEditForm(ubRouting::get('edit')));
        show_window('', wf_BackLink('?module=contrahens'));
    }

    // show or manipulate extended agent info
    if (ubRouting::checkGet('extinfo')) {
        if ($ubillingConfig->getAlterParam('AGENTS_EXTINFO_ON')) {
        // edit extended agent info
        if (ubRouting::checkPost('extinfeditmode') and ubRouting::checkPost('extinfrecid') and ubRouting::checkPost('extinfagentid')) {
            zb_EditAgentExtInfoRec(ubRouting::post('extinfrecid'), ubRouting::post('extinfagentid'),
                                   ubRouting::post('extinfsrvtype'), ubRouting::post('extinfintpaysysname'),
                                   ubRouting::post('extinfintpaysysid'), ubRouting::post('extinfintpaysyssrvid'),
                                   ubRouting::post('extinfintpaysystoken'), ubRouting::post('extinfintpaysyskey'),
                                   ubRouting::post('extinfintpaysyspasswd'), ubRouting::post('extinfintpayfeeinfo'),
                                   ubRouting::post('extinfintpaysyscallbackurl'));
        //  add extended agent info
        } elseif (ubRouting::checkPost('extinfeditmode', false) and ubRouting::checkPost('extinfagentid')) {
            zb_CreateAgentExtInfoRec(ubRouting::post('extinfagentid'), ubRouting::post('extinfsrvtype'),
                                     ubRouting::post('extinfintpaysysname'), ubRouting::post('extinfintpaysysid'),
                                     ubRouting::post('extinfintpaysyssrvid'), ubRouting::post('extinfintpaysystoken'),
                                     ubRouting::post('extinfintpaysyskey'), ubRouting::post('extinfintpaysyspasswd'),
                                     ubRouting::post('extinfintpayfeeinfo'), ubRouting::post('extinfintpaysyscallbackurl'));
        } elseif (ubRouting::checkGet('delete')) {
            zb_DeleteAgentExtInfoRec(ubRouting::get('delete'));
        }

        show_window(__('Extended info'), zb_RenderAgentExtInfoTable(ubRouting::get('extinfo')) .
                wf_delimiter() .
                (ubRouting::checkGet('edit') ? zb_AgentEditExtInfoForm(ubRouting::get('edit')) : zb_AgentEditExtInfoForm()) .
                wf_delimiter() .
                (ubRouting::checkGet('edit') ? wf_BackLink('?module=contrahens&extinfo=' . ubRouting::get('extinfo')) : wf_BackLink('?module=contrahens'))
                );
            } else {
                show_error(__('This module is disabled'));
            }
    }

    //list ahents if not editing
    if (!ubRouting::checkGet(array('edit'), false) and ( !ubRouting::checkGet('agentstats')) and ! ubRouting::checkGet('extinfo')) {
        $statsControl = wf_Link('?module=contrahens&agentstats=true', web_icon_charts());
        show_window(__('Available contrahens') . ' ' . $statsControl, zb_ContrAhentShow());
        show_window('', wf_modalAuto(web_icon_create() . ' ' . __('Create new contrahent'), __('Create new contrahent'), zb_ContrAhentAddForm(), 'ubButton'));
    }

    //check agents region assign
    if ($alter_conf['AGENTS_ASSIGN']) {

        //if delete assign
        if (ubRouting::checkGet('deleteassign', false)) {
            zb_AgentAssignDelete(ubRouting::get('deleteassign'));
            ubRouting::nav("?module=contrahens");
        }
        //if adding assign 
        if (wf_CheckPost(array('newassign'), false)) {
            zb_AgentAssignAdd(ubRouting::post('ahentsel'), ubRouting::post('newassign'));
            ubRouting::nav("?module=contrahens");
        }


        //list assigns if not editing
        if ((!ubRouting::checkGet(array('edit'), false)) and ( !ubRouting::checkGet('agentstats')) and ! ubRouting::checkGet('extinfo')) {
            show_window(__('Contrahent assign'), web_AgentAssignForm());
            show_window(__('Available assigns'), web_AgentAssignShow());
            show_window(__('Assign overrides'), web_AgentAssignStrictRender());
        }

        //agent assigned users stats
        if (ubRouting::checkGet('agentstats')) {
            $privateTariffsMask = @$alter_conf['PRIVATE_TARIFFS_MASK'];
            show_window(__('Available assigns'), zb_AgentStatsRender($privateTariffsMask));
            show_window('', wf_BackLink('?module=contrahens'));
        }
    }
} else {
    show_error(__('You cant control this module'));
}

