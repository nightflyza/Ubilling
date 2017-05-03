<?php
// check for right of current admin on this module
if (cfr('STREETS')) {

    if (isset($_POST['newstreetname'])) {
        $newstreetname=trim($_POST['newstreetname']);
        $newstreetname=  zb_AddressFilterStreet($newstreetname);
        $newstreetcityid=$_POST['citysel'];
        if (isset($_POST['newstreetalias'])) {
        $newstreetalias=trim($_POST['newstreetalias']);
        } else {
        $newstreetalias='';
        }
        
        if (!empty($newstreetname)) {
            //check for existing same street in city
            $existingStreets_raw=  zb_AddressGetStreetAllDataByCity($newstreetcityid);
            $existingStreets=array();
            if (!empty($existingStreets_raw)) {
                foreach ($existingStreets_raw as $ix=>$eachstreetdata) {
                    $existingStreets[]=  strtolower_utf8($eachstreetdata['streetname']);
                }
            }
            if (!in_array(strtolower_utf8($newstreetname), $existingStreets)) {
            zb_AddressCreateStreet($newstreetcityid, $newstreetname, $newstreetalias);
            } else {
                show_error(__('The same street already exists'));
            }
            
        } else {
            show_error(__('Empty street name'));
        }
         
    }
    if (isset($_GET['action'])) {
        if (isset($_GET['streetid'])) {
        $streetid=$_GET['streetid'];

        if ($_GET['action']=='delete') {
            if (!zb_AddressStreetProtected($streetid)) {
            zb_AddressDeleteStreet($streetid);
            rcms_redirect('?module=streets');
            } else {
                show_error(__('You can not delete the street if it has existing buildings'));
            }
        }
        if ($_GET['action']=='edit') {
            if (isset ($_POST['editstreetname'])) {
                if (!empty($_POST['editstreetname'])) {
                    zb_AddressChangeStreetName($streetid, $_POST['editstreetname']);
                }
                
                zb_AddressChangeStreetAlias($streetid, $_POST['editstreetalias']);
                rcms_redirect('?module=streets');
          }
            show_window(__('Edit Street'),web_StreetEditForm($streetid));
            show_window('',  wf_Link("?module=streets", 'Back', true, 'ubButton'));
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
