<?php

// check for right of current admin on this module
if (cfr('STREETS')) {
    $altCfg = $ubillingConfig->getAlter();

    if (isset($_POST['newstreetname'])) {
        $newstreetname = trim($_POST['newstreetname']);
        $newstreetname = zb_AddressFilterStreet($newstreetname);
        $newstreetcityid = $_POST['citysel'];

        if (isset($_POST['newstreetalias'])) {
            $newstreetalias = trim($_POST['newstreetalias']);
        } else {
            $newstreetalias = '';
        }

        if (!empty($newstreetname)) {
            $StreetID = checkStreetInCityExists($newstreetname, $newstreetcityid);

            if ( empty($StreetID) ) {
                //alias autogeneration
                if (empty($newstreetalias)) {
                    if (isset($altCfg['STREETS_ALIAS_AUTOGEN'])) {
                        if ($altCfg['STREETS_ALIAS_AUTOGEN']) {
                            $aliasProposal = zb_TranslitString($newstreetname);
                            $aliasProposal = str_replace(' ', '', $aliasProposal);
                            $aliasProposal = str_replace('-', '', $aliasProposal);
                            if (strlen($aliasProposal) > 5) {
                                $newstreetalias = substr($aliasProposal, 0, 5);
                            } else {
                                $newstreetalias = $aliasProposal;
                            }
                        }
                    }
                }

                zb_AddressCreateStreet($newstreetcityid, $newstreetname, $newstreetalias);
                die();
            } else {
                $messages = new UbillingMessageHelper();
                $errormes = $messages->getStyledMessage(__('Street with such name already exists in this city with ID: ') . $StreetID, 'error', 'style="margin: auto 0; padding: 10px 3px; width: 100%;"');
                die(wf_modalAutoForm(__('Error'), $errormes, $_POST['errfrmid'], '', true));
            }
        }
    }

    if (isset($_GET['action'])) {
        if (isset($_GET['streetid'])) {
            $streetid = $_GET['streetid'];

            if ($_GET['action'] == 'delete') {
                if (!zb_AddressStreetProtected($streetid)) {
                    zb_AddressDeleteStreet($streetid);
                    die();
                } else {
                    $messages = new UbillingMessageHelper();
                    $errormes = $messages->getStyledMessage(__('You can not delete the street if it has existing buildings'), 'error', 'style="margin: auto 0; padding: 10px 3px; width: 100%;"');
                    die(wf_modalAutoForm(__('Error'), $errormes, $_GET['errfrmid'], '', true));
                }
            }

            if ($_GET['action'] == 'edit') {
                if (isset($_POST['editstreetname'])) {
                    if (!empty($_POST['editstreetname'])) {
                        $StreetID = checkStreetInCityExists($_POST['editstreetname'], $_GET['cityid']);

                        if ( empty($StreetID) ) {
                            zb_AddressChangeStreetName($streetid, $_POST['editstreetname']);
                        } else {
                            $messages = new UbillingMessageHelper();
                            $errormes = $messages->getStyledMessage(__('Street with such name already exists in this city with ID: ') . $StreetID, 'error', 'style="margin: auto 0; padding: 10px 3px; width: 100%;"');
                            die(wf_modalAutoForm(__('Error'), $errormes, $_POST['errfrmid'], '', true));
                        }
                    }

                    zb_AddressChangeStreetAlias($streetid, $_POST['editstreetalias']);
                    die();
                }

                die(wf_modalAutoForm(__('Edit Street'), web_StreetEditForm($streetid, $_GET['ModalWID']), $_GET['ModalWID'], $_GET['ModalWBID'], true));
            }
        }
    }

    if ( wf_CheckGet(array('ajax')) ) {
        renderStreetJSON($_GET['filterbycityid']);
    }

    show_window(__('Available streets'), web_StreetLister($_GET['filterbycityid']));
} else {
    show_error(__('You cant control this module'));
}
?>
