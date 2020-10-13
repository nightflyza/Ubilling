<?php

if (cfr('AGENTS')) {
    //mb some custom options?
    $alter_conf = $ubillingConfig->getAlter();

    //if deleting agent
    if (ubRouting::checkGet('delete', false)) {
        zb_ContrAhentDelete(ubRouting::get('delete'));
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

        zb_ContrAhentAdd($bankacc, $bankname, $bankcode, $edrpo, $ipn, $licensenum, $juraddr, $phisaddr, $phone, $contrname);
        ubRouting::nav("?module=contrahens");
    }

    if (ubRouting::checkGet('edit', false)) {

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
            zb_ContrAhentChange($ahentid, $bankacc, $bankname, $bankcode, $edrpo, $ipn, $licensenum, $juraddr, $phisaddr, $phone, $contrname);
            ubRouting::nav("?module=contrahens");
        }
        // show edit form  
        show_window(__('Edit'), zb_ContrAhentEditForm(ubRouting::get('edit')));
        show_window('', wf_BackLink('?module=contrahens'));
    }

    //list ahents if not editing
    if (!ubRouting::checkGet(array('edit'), false) AND ( !ubRouting::checkGet('agentstats'))) {
        $statsControl = wf_Link('?module=contrahens&agentstats=true', web_icon_charts());
        show_window(__('Available contrahens') . ' ' . $statsControl, zb_ContrAhentShow());
        show_window(__('Add new'), zb_ContrAhentAddForm());
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
        if ((!ubRouting::checkGet(array('edit'), false)) AND ( !ubRouting::checkGet('agentstats'))) {
            show_window(__('Contrahent assign'), web_AgentAssignForm());
            show_window(__('Available assigns'), web_AgentAssignShow());
            show_window(__('Assign overrides'), web_AgentAssignStrictShow());
        }

        //agent assigned users stats
        if (ubRouting::checkGet('agentstats')) {
            $privateTariffsMask=@$alter_conf['PRIVATE_TARIFFS_MASK'];
            show_window(__('Available assigns'), zb_AgentStatsRender($privateTariffsMask));
            show_window('', wf_BackLink('?module=contrahens'));
        }
    }
} else {
    show_error(__('You cant control this module'));
}
?>
