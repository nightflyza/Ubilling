<?php
if(cfr('VSERVICES')) {
    if (ubRouting::checkPost('newfee', false)) {
        $tagid = ubRouting::post('newtagid');
        $price = ubRouting::post('newfee');
        $cashtype = ubRouting::post('newcashtype');
        $priority = ubRouting::post('newpriority');
        $feechargealways = (ubRouting::post('feechargealways')) ? 1 : 0;
        $feechargeperiod = (ubRouting::post('newperiod')) ? ubRouting::post('newperiod') : 0;

        if (!empty($price)) {
            zb_VserviceCreate($tagid, $price, $cashtype, $priority, $feechargealways, $feechargeperiod);
            rcms_redirect("?module=vservices");
        } else {
            show_error(__('No all of required fields is filled'));
        }
    }

    if (ubRouting::checkGet('delete')) {
        $vservid = ubRouting::get('delete');
        zb_VsericeDelete($vservid);
        rcms_redirect("?module=vservices");
    }
    
    if (ubRouting::checkGet('edit')) {
        $editId = vf(ubRouting::get('edit'), 3);

        if (ubRouting::checkPost(array('edittagid', 'editcashtype', 'editpriority', 'editfee'))) {
            $feechargealways = (ubRouting::post('editfeechargealways')) ? 1 : 0;
            $feechargeperiod = (ubRouting::post('editperiod')) ? ubRouting::post('editperiod') : 0;

            zb_VserviceEdit($editId, ubRouting::post('edittagid'), ubRouting::post('editfee'),
                            ubRouting::post('editcashtype'), ubRouting::post('editpriority'),
                            $feechargealways, $feechargeperiod);

            rcms_redirect("?module=vservices");
        }

        show_window(__('Edit'), web_VserviceEditForm($_GET['edit']));
    } else {
        //show available services list
        web_VservicesShow();
    }
}
else {
	show_error(__('Access denied'));
}
?>
