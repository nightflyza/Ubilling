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
            $CityID = checkCityExists($newcityname);

            if ( empty($CityID) ) {
                zb_AddressCreateCity($newcityname, $newcityalias);
                die();
            } else {
                $messages = new UbillingMessageHelper();
                $errormes = $messages->getStyledMessage(__('City with such name already exists with ID: ') . $CityID, 'error', 'style="margin: auto 0; padding: 10px 3px; width: 100%;"');
                die(wf_modalAutoForm(__('Error'), $errormes, $_POST['errfrmid'], '', true));
            }
        }
    }

    if (isset($_GET['action'])) {
        if (isset($_GET['cityid'])) {
            $cityid = $_GET['cityid'];

            if ($_GET['action'] == 'delete') {
                if (!zb_AddressCityProtected($cityid)) {
                    zb_AddressDeleteCity($cityid);
                    die();
                } else {
                    $messages = new UbillingMessageHelper();
                    $errormes = $messages->getStyledMessage(__('You can not just remove a city where there are streets and possibly survivors'), 'error', 'style="margin: auto 0; padding: 10px 3px; width: 100%;"');
                    die(wf_modalAutoForm(__('Error'), $errormes, $_GET['errfrmid'], '', true));
                }
            }

            if ($_GET['action'] == 'edit') {
                if (isset ($_POST['editcityname'])) {
                    if (!empty($_POST['editcityname'])) {
                        $CityID = checkCityExists($_POST['editcityname']);

                        if ( empty($CityID) ) {
                            zb_AddressChangeCityName($cityid, $_POST['editcityname']);
                        } else {
                            $messages = new UbillingMessageHelper();
                            $errormes = $messages->getStyledMessage(__('City with such name already exists with ID: ') . $CityID, 'error', 'style="margin: auto 0; padding: 10px 3px; width: 100%;"');
                            die(wf_modalAutoForm(__('Error'), $errormes, $_POST['errfrmid'], '', true));
                        }
                    }

                    zb_AddressChangeCityAlias($cityid, $_POST['editcityalias']);
                    die();
                } else {
                    die(wf_modalAutoForm(__('Edit City'), web_CityEditForm($cityid, $_GET['ModalWID']), $_GET['ModalWID'], $_GET['ModalWBID'], true));
                }
            }
        }
    }

    if ( wf_CheckGet(array('ajax')) ) {
        renderCityJSON();
    }

    show_window(__('Available cities'), web_CityLister());
} else {
      show_error(__('You cant control this module'));
}

?>
