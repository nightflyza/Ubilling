<?php

// check for right of current admin on this module
if (cfr('STREETS')) {
    $altCfg = $ubillingConfig->getAlter();
    $messages = new UbillingMessageHelper();
    $errorStyling = 'style="margin: auto 0; padding: 10px 3px; width: 100%;"';

    if (ubRouting::checkPost('newstreetname')) {
        $newstreetname = ubRouting::post('newstreetname', 'safe');
        $newstreetcityid = ubRouting::post('citysel', 'int');
        $newstreetalias = (ubRouting::checkPost('newstreetalias'))  ? ubRouting::post('newstreetalias', 'gigasafe') : '';

        if (!empty($newstreetname)) {
            $FoundStreetID = checkStreetInCityExists($newstreetname, $newstreetcityid);

            if (empty($FoundStreetID)) {
                //alias autogeneration
                if (empty($newstreetalias)) {
                    if (isset($altCfg['STREETS_ALIAS_AUTOGEN'])) {
                        if ($altCfg['STREETS_ALIAS_AUTOGEN']) {
                            $aliasProposal = zb_TranslitString($newstreetname);
                            $aliasProposal = str_replace(' ', '', $aliasProposal);
                            $aliasProposal = str_replace('-', '', $aliasProposal);
                            $aliasProposal = ubRouting::filters($aliasProposal, 'gigasafe');
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
                $errormes = $messages->getStyledMessage(__('Street with such name already exists in this city with ID: ') . $FoundStreetID, 'error', $errorStyling);
                log_register('STREET CREATE FAILED CITYID [' . $newstreetcityid . '] NAME `' . $newstreetname . '` EXISTS');
                die(wf_modalAutoForm(__('Error'), $errormes, ubRouting::post('errfrmid'), '', true));
            }
        }
    }

    if (ubRouting::checkGet('action')) {
        if (ubRouting::checkGet('streetid')) {
            $streetid = ubRouting::get('streetid', 'int');

            if (ubRouting::get('action') == 'delete') {
                if (!zb_AddressStreetProtected($streetid)) {
                    zb_AddressDeleteStreet($streetid);
                    die();
                } else {
                    $errormes = $messages->getStyledMessage(__('You can not delete the street if it has existing buildings'), 'error', $errorStyling);
                    log_register('STREET DELETE FAILED PROTECTED [' . $streetid . ']');
                    die(wf_modalAutoForm(__('Error'), $errormes, $_GET['errfrmid'], '', true));
                }
            }

            if (ubRouting::get('action') == 'edit') {
                if (ubRouting::post('editstreetname', 'safe')) {
                    if (ubRouting::post('editstreetname')) {
                        $editstreetname = ubRouting::post('editstreetname', 'safe');

                        $FoundStreetID = checkStreetInCityExists($editstreetname, ubRouting::get('cityid'), $streetid);

                        if (empty($FoundStreetID)) {
                            zb_AddressChangeStreetName($streetid, $editstreetname);
                        } else {
                            $errormes = $messages->getStyledMessage(__('Street with such name already exists in this city with ID: ') . $FoundStreetID, 'error', $errorStyling);
                            log_register('STREET CHANGE NAME FAILED [' . $streetid . '] NAME `' . $editstreetname . '` EXISTS');
                            die(wf_modalAutoForm(__('Error'), $errormes, ubRouting::post('errfrmid'), '', true));
                        }
                    }

                    zb_AddressChangeStreetAlias($streetid, ubRouting::post('editstreetalias'));
                    die();
                }

                die(wf_modalAutoForm(__('Edit Street'), web_StreetEditForm($streetid, ubRouting::get('ModalWID')), ubRouting::get('ModalWID'), ubRouting::get('ModalWBID'), true));
            }
        }
    }

    $FilterByCityID = (ubRouting::checkGet('filterbycityid')) ? ubRouting::get('filterbycityid','int') : '';

    if (ubRouting::get('ajax')) {
        renderStreetJSON($FilterByCityID);
    }

    show_window(__('Available streets'), web_StreetLister($FilterByCityID));
} else {
    show_error(__('You cant control this module'));
}
