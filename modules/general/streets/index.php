<?php
// check for right of current admin on this module
if (cfr('STREETS')) {

    if (isset($_POST['newstreetname'])) {
        $newstreetname=$_POST['newstreetname'];
        $newstreetcityid=$_POST['citysel'];
        if (isset($_POST['newstreetalias'])) {
        $newstreetalias=$_POST['newstreetalias'];
        } else {
        $newstreetalias='';
        }
         zb_AddressCreateStreet($newstreetcityid, $newstreetname, $newstreetalias);
    }
    if (isset($_GET['action'])) {
        if (isset($_GET['streetid'])) {
        $streetid=$_GET['streetid'];

        if ($_GET['action']=='delete') {
            if (!zb_AddressStreetProtected($streetid)) {
            zb_AddressDeleteStreet($streetid);
            rcms_redirect('?module=streets');
            } else {
                show_window(__('Error'),__('You can not delete the street if it has existing buildings'));
            }
        }
        if ($_GET['action']=='edit') {
            if (isset ($_POST['editstreetname'])) {
                //$newstreetcityid=$_POST['citysel'];
                zb_AddressChangeStreetName($streetid, $_POST['editstreetname']);
                zb_AddressChangeStreetAlias($streetid, $_POST['editstreetalias']);
                rcms_redirect('?module=streets');
          }
            show_window(__('Edit Street'),web_StreetEditForm($streetid));
        }
        }
    }
    ///// forms
    show_window(__('Create new street'),  web_StreetCreateForm());
    show_window(__('Available streets'),web_StreetLister());
    
} else {
      show_error(__('You cant control this module'));
}

?>
