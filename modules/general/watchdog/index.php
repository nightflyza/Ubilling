<?php

if (cfr('WATCHDOG')) {
    $altercfg = $ubillingConfig->getAlter();
    if ($altercfg['WATCHDOG_ENABLED']) {
        $interface = new WatchDogInterface();
        $interface->loadAllTasks();
        $interface->loadSettings();

        //manual run of existing tasks
        if (ubRouting::checkGet('manual')) {
            $watchdog = new WatchDog();
            $watchdog->processTask();
            ubRouting::nav('?module=watchdog');
        }

        //deleting existing task
        if (ubRouting::checkGet('delete')) {
            $interface->deleteTask(ubRouting::get('delete'));
            ubRouting::nav('?module=watchdog');
        }

        //new task creation
        if (ubRouting::checkPost(array('newname', 'newchecktype', 'newparam', 'newoperator'))) {
            $interface->createTask(
                ubRouting::post('newname'),
                ubRouting::post('newchecktype'),
                ubRouting::post('newparam'),
                ubRouting::post('newoperator'),
                ubRouting::post('newcondition'),
                ubRouting::post('newaction'),
                ubRouting::post('newactive')
            );
            ubRouting::nav('?module=watchdog');
        }

        //changing task
        if (ubRouting::checkPost('editname')) {
            $interface->changeTask();
            ubRouting::nav('?module=watchdog');
        }

        //changing watchdog settings
        if (ubRouting::checkPost('changealert')) {
            $interface->saveSettings();
            ubRouting::nav('?module=watchdog');
        }

        //enabling/disabling maintenance mode
        if (ubRouting::checkGet('maintenance')) {
            $interface->setMaintenance(ubRouting::get('maintenance'));
            ubRouting::nav('?module=watchdog');
        }

        //enabling/disabling sms silence mode
        if (ubRouting::checkGet('smssilence')) {
            $interface->setSmsSilence(ubRouting::get('smssilence'));
            ubRouting::nav('?module=watchdog');
        }


        //show watchdog main control panel
        show_window('', $interface->panel());

        if (!ubRouting::checkGet(array('edit'))) {
            //show previous detections
            if (ubRouting::checkGet(array('previousalerts'))) {
                $interface->loadAllPreviousAlerts();

                if (ubRouting::checkPost(array('previousalertsearch'))) {
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
