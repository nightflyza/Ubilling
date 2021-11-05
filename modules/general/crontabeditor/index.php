<?php

if (cfr('ROOT')) {
    $crontab = new CrontabEditor();

    show_window('', wf_BackLink($crontab::URL_BACK));
    $hostSystem = $crontab->getSystemName(); //politically incorrect
    if ($hostSystem == 'FreeBSD' OR $hostSystem == 'Debian11') { //yes it's discrimination. Fuck you.
        if (ubRouting::checkPost(array('editfilepath'))) {
            //something changed?
            $crontab->saveTempCrontab();
            $installResult = $crontab->installNewCrontab();
            if (!empty($installResult)) {
                show_error($installResult);
            } else {
                //its ok
                ubRouting::nav($crontab::URL_ME);
            }
        }
        show_window(__('Crontab'), $crontab->renderEditForm());
    } else {
        show_error(__('Sorry your system is currently unsupported'));
    }
} else {
    show_error(__('Access denied'));
}