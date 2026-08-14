<?php
if (cfr('DOWN')) {

if (ubRouting::checkGet('username')) {
    $login=ubRouting::filters(ubRouting::get('username'),'login');
    $userAddress=zb_UserGetFullAddress($login);

       // change down  if need
       if (ubRouting::checkPost('newdown',false)) {
        $down=ubRouting::filters(ubRouting::post('newdown'),'int');
        $billing->setdown($login,$down);
        log_register('DOWN CHANGE ('.$login.') ON `'.$down.'`');
        ubRouting::nav('?module=downedit&username='.$login);
    }

    $current_down=zb_UserGetStargazerData($login);
    $current_down=$current_down['Down'];
    $useraddress=zb_UserGetFullAddress($login).' ('.$login.')';


// Edit form construct
$fieldname=__('Current Down state');
$fieldkey='newdown';
$form=web_EditorTrigerDataForm($fieldname, $fieldkey, $useraddress, $current_down);
$profileControls=web_UserControls($login);
show_window(__('Edit Down').' '.$userAddress, $form);

if ($ubillingConfig->getAlterParam('CHURN_REASONS_ENABLED')) {
    if (cfr('CHURNREASONS')) {
    $churnReasons = new ChurnReasons($login);
    $churnReasonsInterface=$churnReasons->renderChurnController();
    show_window(__('Churn reason'), $churnReasonsInterface);
    }
}
show_window('', $profileControls);

}

} else {
      show_error(__('You cant control this module'));
}
