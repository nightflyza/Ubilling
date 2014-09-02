<?php

if (cfr('CAPAB')) {

    $altercfg = rcms_parse_ini_file(CONFIG_PATH . "alter.ini");

    if ($altercfg['CAPABDIR_ENABLED']) {
        $capabilities = new CapabilitiesDirectory();

//process deletion
        if (wf_CheckGet(array('delete'))) {
            if (cfr('ROOT')) {
                $capabilities->deleteCapability($_GET['delete']);
                rcms_redirect("?module=capabilities");
            } else {
                show_window(__('Error'), __('Permission denied'));
            }
        }

//process creation
        if (wf_CheckPost(array('newaddress', 'newphone'))) {
            $newaddress = $_POST['newaddress'];
            $newphone = $_POST['newphone'];
            @$newnotes = $_POST['newnotes'];
            $capabilities->addCapability($newaddress, $newphone, $newnotes);
            rcms_redirect("?module=capabilities");
        }

//show editing form
        if (wf_CheckGet(array('edit'))) {
            //editing processing 
            if (wf_CheckPost(array('editaddress', 'editphone'))) {
                $capabilities->editCapability($_GET['edit'], $_POST['editaddress'], $_POST['editphone'], $_POST['editstateid'], @$_POST['editnotes'], @$_POST['editprice'], $_POST['editemployeeid']);
                rcms_redirect("?module=capabilities");
            }
            show_window(__('Edit'), $capabilities->editForm($_GET['edit']));
        }

//show current states editor
        if (wf_CheckGet(array('states'))) {
            //creating new state
            if (wf_CheckPost(array('createstate', 'createstatecolor'))) {
                $capabilities->statesCreate($_POST['createstate'], $_POST['createstatecolor']);
                rcms_redirect("?module=capabilities&states=true");
            }
            //deleting existing state
            if (wf_CheckGet(array('deletestate'))) {
                $capabilities->statesDelete($_GET['deletestate']);
                rcms_redirect("?module=capabilities&states=true");
            }


            if (!wf_CheckGet(array('editstate'))) {
                show_window(__('Create new states'), $capabilities->statesAddForm());
                show_window(__('Available states'), $capabilities->statesList());
            } else {
                //editing of existing states
                if (wf_CheckPost(array('editstate', 'editstatecolor'))) {
                    $capabilities->statesChange($_GET['editstate'], $_POST['editstate'], $_POST['editstatecolor']);
                    rcms_redirect("?module=capabilities&states=true");
                }
                show_window(__('Edit'), $capabilities->statesEditForm($_GET['editstate']));
            }
        }


//show available
        if (!wf_CheckGet(array('edit'))) {
            if (!wf_CheckGet(array('states'))) {
                show_window(__('Available connection capabilities'), $capabilities->render());
            }
        }
    } else {
        show_window(__('Error'), __('This module is disabled'));
    }
} else {
    show_error(__('You cant control this module'));
}
?>
