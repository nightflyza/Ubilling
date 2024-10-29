<?php

if ($ubillingConfig->getAlterParam('DEALWITHIT_ENABLED')) {
    if (cfr('DEALWITHIT')) {
        $dealWithIt = new DealWithIt();

        if (ubRouting::checkGet('username')) {
            $login = ubRouting::get('username');
            //creating new task
            if (ubRouting::checkPost('newschedlogin')) {
                $createResult = $dealWithIt->catchCreateRequest();
                if ($createResult) {
                    show_error($createResult);
                } else {
                    rcms_redirect(DealWithIt::URL_ME . '&username=' . $login);
                }
            }
            //deleting existing task
            if (ubRouting::checkGet('deletetaskid')) {
                $dealWithIt->deleteTask(ubRouting::get('deletetaskid', 'int'));
                rcms_redirect(DealWithIt::URL_ME . '&username=' . $login);
            }
            //displaying interface parts
            show_window(__('Create new task'), $dealWithIt->renderCreateForm());
            //json reply
            if (ubRouting::checkGet('ajax')) {
                $dealWithIt->AjaxDataTasksList();
            }
            show_window(__('Held jobs for this user'), $dealWithIt->renderTasksListAjax());

            show_window('', web_UserControls($login));
        } elseif (ubRouting::checkPost('newschedloginsarr')) {
            $createMassResult = $dealWithIt->catchCreateMassRequest();
            if ($createMassResult) {
                show_error($createMassResult);
                $dealWithIt->renderDealWithItControl();
            } else {
                rcms_redirect('?module=report_dealwithit');
            }
        } else {
            if (ubRouting::checkGet('ajinput')) {
                $dealWithIt->catchAjRequest();
            } else {
                $dealWithIt->renderDealWithItControl();
            }
        }
    } else {
        show_error(__('Access denied'));
    }
} else {
    show_error(__('This module is disabled'));
}
?>
