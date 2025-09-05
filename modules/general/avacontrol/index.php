<?php
if (cfr('UBIM')) {
    $faceKit = new FaceKit();
    $backUrl=ubRouting::get('back');
    $customLogin=whoami();
    if (cfr('ROOT')) {
        if (ubRouting::checkGet('admlogin')) {
            $customLogin=ubRouting::get('admlogin');
        }
    }
    $controlForm=$faceKit->renderAvatarControlForm($backUrl,$customLogin);
    show_window(__('Avatar control').' ('.$customLogin.')' ,$controlForm);
    zb_BillingStats();
} else {
    show_error(__('Access denied'));
}
