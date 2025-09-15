<?php

if (cfr('CONTRACT')) {
    $alter_conf = $ubillingConfig->getAlter();

    if (isset($_GET['username'])) {
        $login = vf($_GET['username']);
        // change contract if need
        if (isset($_POST['newcontract'])) {
            $contract = $_POST['newcontract'];
            //strict unique check
            if ($alter_conf['STRICT_CONTRACTS_UNIQUE']) {
                $allcontracts = zb_UserGetAllContracts();
                if (isset($allcontracts[$contract])) {
                    show_error(__('This contract is already used'));
                } else {
                    zb_UserChangeContract($login, $contract);
                    rcms_redirect("?module=contractedit&username=" . $login);
                }
            } else {
                zb_UserChangeContract($login, $contract);
                rcms_redirect("?module=contractedit&username=" . $login);
            }
        }

        $current_contract = zb_UserGetContract($login);
        $useraddress = zb_UserGetFullAddress($login) . ' (' . $login . ')';


        // Edit form construct
        $fieldnames = array('fieldname1' => __('Current contract'), 'fieldname2' => __('New contract'));
        $fieldkey = 'newcontract';
        $form = web_EditorStringDataFormContract($fieldnames, $fieldkey, $useraddress, $current_contract);
        show_window(__('Edit contract'), $form);

        //filestorage support here
        if (@$alter_conf['FILESTORAGE_ENABLED']) {
            $fileStorage = new FileStorage('USERCONTRACT', $login);
            show_window(__('Uploaded files'), $fileStorage->renderFilesPreview(true, ' ' . __('Upload files')));
        }

        //contract date editing
        $contractDates = new ContractDates();
        //someone creates new contractdate or changes old
        if (ubRouting::checkPost($contractDates::PROUTE_DATE)) {
            if (!empty($current_contract)) {
                $edContract = ubRouting::post($contractDates::PROUTE_RUNEDIT);
                $edContractDate = ubRouting::post($contractDates::PROUTE_DATE);
                $edContractFrom = ubRouting::post($contractDates::PROUTE_FROM);
                $edContractTill = ubRouting::post($contractDates::PROUTE_TILL);
                $contractDates->set($edContract, $edContractDate, $edContractFrom, $edContractTill);
                //back to fresh form
                ubRouting::nav('?module=contractedit&username=' . $login);
            } else {
                show_error(__('With this the user has not yet signed a contract'));
            }
        }

        //editing form
        show_window(__('User contract date'), $contractDates->renderChangeForm($current_contract));

        //agent strict assigning form
        if ($alter_conf['AGENTS_ASSIGN']) {
            if (cfr('AGENTSOVR')) {
                if (wf_CheckPost(array('ahentsel', 'assignstrictlogin'))) {
                    if (isset($_POST['deleteassignstrict'])) {
                        // deletion of manual assign
                        zb_AgentAssignStrictDelete($_POST['assignstrictlogin']);
                    } else {
                        //create new assign
                        zb_AgentAssignStrictCreate($_POST['assignstrictlogin'], $_POST['ahentsel']);
                    }
                    rcms_redirect('?module=contractedit&username=' . $_POST['assignstrictlogin']);
                }

                $allAssignsStrict = zb_AgentAssignStrictGetAllData();
                show_window(__('Manual agent assign'), web_AgentAssignStrictForm($login, @$allAssignsStrict[$login]));
            }
        }

        show_window('', web_UserControls($login));
    }
} else {
    show_error(__('You cant control this module'));
}
