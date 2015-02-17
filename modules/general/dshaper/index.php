<?php

if (cfr('DSHAPER')) {

   
    $alterconf = $ubillingConfig->getAlter();
    if (isset($alterconf['DSHAPER_ENABLED'])) {
        if ($alterconf['DSHAPER_ENABLED']) {
$dshaper= new DynamicShaper();

//if someone deleting time rule
            if (isset($_GET['delete'])) {
                $dshaper->delete($_GET['delete']);
                rcms_redirect("?module=dshaper");
            }

//if someone adding time rule
            if (isset($_POST['newdshapetariff'])) {
                $dshaper->create($_POST['newdshapetariff'], $_POST['newthreshold1'], $_POST['newthreshold2'], $_POST['newspeed']);
                rcms_redirect("?module=dshaper");
            }

//timerule editing subroutine
            if (isset($_GET['edit'])) {
                if (isset($_POST['editdshapetariff'])) {
                    $dshaper->edit($_GET['edit'], $_POST['editthreshold1'], $_POST['editthreshold2'], $_POST['editspeed']);
                    rcms_redirect("?module=dshaper");
                }
                //show edit form
                show_window(__('Edit time shaper rule'), $dshaper->renderEditForm($_GET['edit']));
            }



            show_window(__('Available dynamic shaper time rules'), $dshaper->renderList());
            show_window(__('Add new time shaper rule'), $dshaper->renderAddForm());
        } else {
            show_error(__('This module is disabled'));
        }
    } else {
        show_error(__('This module is disabled'));
    }
//end of option enabled check
} else {
    show_error(__('You cant control this module'));
}
?>
