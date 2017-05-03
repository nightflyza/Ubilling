<?php
// check for right of current admin on this module
if (cfr('CITY')) {

    if (isset($_POST['newcityname'])) {
        $newcityname=$_POST['newcityname'];
        if (isset($_POST['newcityalias'])) {
        $newcityalias=$_POST['newcityalias'];
        } else {
        $newcityalias='';
        }
        
        if (!empty($newcityname)) {
         zb_AddressCreateCity($newcityname, $newcityalias);
         rcms_redirect('?module=city');
        } else {
            show_error(__('Empty city name'));
        }
    }
    
    if (isset($_GET['action'])) {
        if (isset($_GET['cityid'])) {
        $cityid=$_GET['cityid'];

        if ($_GET['action']=='delete') {
            if (!zb_AddressCityProtected($cityid)) {
            zb_AddressDeleteCity($cityid);
            rcms_redirect('?module=city');
            } else {
                show_error(__('You can not just remove a city where there are streets and possibly survivors'));
                show_window('', wf_BackLink('?module=city', __('Back'), true, 'ubButton'));
            }
        }
        if ($_GET['action']=='edit') {
            if (isset ($_POST['editcityname'])) {
                if (!empty($_POST['editcityname'])) {
                    zb_AddressChangeCityName($cityid, $_POST['editcityname']);
                }
                
                zb_AddressChangeCityAlias($cityid, $_POST['editcityalias']);
                rcms_redirect('?module=city');
          }
            show_window(__('Edit City'),web_CityEditForm($cityid));
        }
        }
    }
    // create form
    if (!wf_CheckGet(array('action'))) {
    show_window(__('Create new city'),web_CityCreateForm());
    }
    //list
    show_window(__('Available cities'),web_CityLister());
    
} else {
      show_error(__('You cant control this module'));
}

?>
