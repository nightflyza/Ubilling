<?php
// check for right of current admin on this module
if (cfr('BINDER')) {
    if (ubRouting::checkGet('username')) {
        global $ubillingConfig;
         //needed login
         $login = ubRouting::get('username', 'callback','vf');

         //if change
         if (ubRouting::checkPost('changeapt', false)) {
             $changeaptdata = zb_AddressGetAptData($login);
             $changeaptid = $changeaptdata['id'];
             $changeaptbuildid = $changeaptdata['buildid'];
             $changeapt = ubRouting::post('changeapt');
             if (empty($changeapt)) {
                 $changeapt = 0;
             }

             @$changefloor = ubRouting::post('changefloor');
             @$changeentrance = ubRouting::post('changeentrance');
             zb_AddressChangeApartment($changeaptid, $changeaptbuildid, $changeentrance, $changefloor, $changeapt);
             rcms_redirect("?module=binder&username=" . $login);
         }

         //if extended address info change
         if (ubRouting::checkPost('change_extended_address')) {
             zb_AddAddressExtenSave($login, true,
                                    ubRouting::post('changepostcode'),
                                    ubRouting::post('changetowndistr'),
                                    ubRouting::post('changeaddrexten')
                                   );
             rcms_redirect("?module=binder&username=" . $login);
         }

         //if delete
         if (ubRouting::checkGet('orphan')) {
             $deletedata = zb_AddressGetAptData($login);
             $deleteatpid = $deletedata['aptid'];
             zb_AddressOrphanUser($login);
             zb_AddressDeleteApartment($deleteatpid);

             if ($ubillingConfig->getAlterParam('ADDRESS_EXTENDED_ENABLED')) {
                 zb_AddAddressExtenDelete($login);
             }

             rcms_redirect("?module=binder&username=" . $login);
         }

         //if create new home to user
         if (ubRouting::checkPost('apt', false)) {
             $apt = ubRouting::post('apt');
             if (empty($apt)) {
                 $apt = 0;
             }

             @$entrance = ubRouting::post('entrance');
             @$floor = ubRouting::post('floor');
             $buildid = ubRouting::post('buildsel');
             zb_AddressCreateApartment($buildid, $entrance, $floor, $apt);
             $newaptid = zb_AddressGetLastid();
             zb_AddressCreateAddress($login, $newaptid);
             rcms_redirect("?module=binder&username=" . $login);
         }

         $addrdata = zb_AddressGetAptData($login);
         if (!empty($addrdata)) {
             //if just wan to modify entrance/floor/apt
             show_window(__('Change user apartment'), web_AddressAptForm($login));
         } else {
             // if user is orphan and need new home
             show_window(__('User occupancy'), web_AddressOccupancyForm());
         }

         show_window('', web_UserControls($login));
    }
} else {
    show_error(__('Access denied'));
}
?>
