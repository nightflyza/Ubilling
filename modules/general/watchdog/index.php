<?php

if (cfr('WATCHDOG')) {
    $altercfg = $ubillingConfig->getAlter();
    if ($altercfg['WATCHDOG_ENABLED']) {
        $interface = new WatchDogInterface();
        $interface->loadAllTasks();
        $interface->loadSettings();

        //manual run of existing tasks
        if (wf_CheckGet(array('manual'))) {
            $watchdog = new WatchDog();
            $watchdog->processTask();
            ubRouting::nav('?module=watchdog');
        }
        //deleting existing task
        if (wf_CheckGet(array('delete'))) {
            $interface->deleteTask($_GET['delete']);
            ubRouting::nav('?module=watchdog');
        }

        //adding new task
        if (wf_CheckPost(array('newname', 'newchecktype', 'newparam', 'newoperator'))) {
            if (isset($_POST['newactive'])) {
                $newActivity = 1;
            } else {
                $newActivity = 0;
            }
            $interface->createTask($_POST['newname'], $_POST['newchecktype'], $_POST['newparam'], $_POST['newoperator'], $_POST['newcondition'], $_POST['newaction'], $newActivity);
            ubRouting::nav('?module=watchdog');
        }

        //changing task
        if (wf_CheckPost(array('editname'))) {
            $interface->changeTask();
            ubRouting::nav('?module=watchdog');
        }

        //changing watchdog settings
        if (wf_CheckPost(array('changealert'))) {
            $interface->saveSettings();
            ubRouting::nav('?module=watchdog');
        }

        //enabling/disabling maintenance mode
        if (ubRouting::checkGet('maintenance')) {
            $interface->setMaintenance(ubRouting::get('maintenance'));
            ubRouting::nav('?module=watchdog');
        }


        //show watchdog main control panel
        show_window('', $interface->panel());

        if (!wf_CheckGet(array('edit'))) {
            //show previous detections
            if (wf_CheckGet(array('previousalerts'))) {
                $interface->loadAllPreviousAlerts();

                if (wf_CheckPost(array('previousalertsearch'))) {
                    //do the search
                    show_window(__('Search results'), $interface->alertSearchResults($_POST['previousalertsearch']));
                } else {
                    //calendar
                    show_window(__('Previous alerts'), $interface->renderAlertsCalendar());
                }
            } else {
                //or list of existing tasks
                show_window(__('Available Watchdog tasks'), $interface->listAllTasks());
            }
        } else {
            //show task edit form
            show_window(__('Edit task'), $interface->editTaskForm($_GET['edit']));
        }
    } else {
        show_error(__('This module is disabled'));
    }
} else {
    show_error(__('Access denied'));
}
?>