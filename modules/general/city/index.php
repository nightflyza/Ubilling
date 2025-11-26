<?php
// check for right of current admin on this module
if (cfr('CITY')) {
    $messages = new UbillingMessageHelper();
    $errorStyling = 'style="margin: auto 0; padding: 10px 3px; width: 100%;"';

    if (ubRouting::checkPost('newcityname')) {
        $newcityname = ubRouting::post('newcityname', 'safe');
        $newcityalias = (ubRouting::checkPost('newcityalias'))  ? ubRouting::post('newcityalias', 'gigasafe') : '';

        if (!empty($newcityname)) {
            $FoundCityID = checkCityExists($newcityname);
            if (empty($FoundCityID)) {
                $cityCreationResult = zb_AddressCreateCity($newcityname, $newcityalias);
                if ($cityCreationResult) {
                    $errormes = $messages->getStyledMessage($cityCreationResult, 'error', $errorStyling);
                    die(wf_modalAutoForm(__('Error'), $errormes, ubRouting::post('errfrmid'), '', true));
                } else {
                    die();
                }
            } else {
                $errormes = $messages->getStyledMessage(__('City with such name already exists with ID: ') . $FoundCityID, 'error', $errorStyling);
                log_register('CITY CREATE FAILED NAME `' . $newcityname . '` EXISTS');
                die(wf_modalAutoForm(__('Error'), $errormes, ubRouting::post('errfrmid'), '', true));
            }
        }
    }

    if (ubRouting::checkGet('action')) {
        if (ubRouting::checkGet('cityid', false)) {
            $cityid = ubRouting::get('cityid', 'int');

            if (ubRouting::get('action') == 'delete') {
                if (!zb_AddressCityProtected($cityid)) {
                    zb_AddressDeleteCity($cityid);
                    die();
                } else {
                    $errormes = $messages->getStyledMessage(__('You can not just remove a city where there are streets and possibly survivors'), 'error', $errorStyling);
                    log_register('CITY DELETE FAILED PROTECTED [' . $cityid . ']');
                    die(wf_modalAutoForm(__('Error'), $errormes, ubRouting::get('errfrmid'), '', true));
                }
            }

            if (ubRouting::get('action') == 'edit') {
                if (ubRouting::checkPost('editcityname')) {
                    if (ubRouting::post('editcityname', 'safe')) {
                        $FoundCityID = checkCityExists(ubRouting::post('editcityname', 'safe'), $cityid);

                        if (empty($FoundCityID)) {
                            $cityRenameResult = zb_AddressChangeCityName($cityid, ubRouting::post('editcityname', 'safe'));
                            if (!empty($cityRenameResult)) {
                                $errormes = $messages->getStyledMessage($cityRenameResult, 'error', $errorStyling);
                                die(wf_modalAutoForm(__('Error'), $errormes, ubRouting::post('errfrmid'), '', true));
                            }
                        } else {
                            $errormes = $messages->getStyledMessage(__('City with such name already exists with ID: ') . $FoundCityID, 'error', $errorStyling);
                            log_register('CITY CHANGE NAME FAILED [' . $cityid . '] NAME `' . ubRouting::post('editcityname', 'safe') . '` EXISTS');
                            die(wf_modalAutoForm(__('Error'), $errormes, ubRouting::post('errfrmid'), '', true));
                        }
                    }

                    zb_AddressChangeCityAlias($cityid, ubRouting::post('editcityalias', 'gigasafe'));
                    die();
                } else {
                    die(wf_modalAutoForm(__('Edit City'), web_CityEditForm($cityid, ubRouting::get('ModalWID')), ubRouting::get('ModalWID'), ubRouting::get('ModalWBID'), true));
                }
            }
        }
    }

    if (ubRouting::checkGet('ajax')) {
        renderCityJSON();
    }

    show_window(__('Available cities'), web_CityLister());
} else {
    show_error(__('You cant control this module'));
}
