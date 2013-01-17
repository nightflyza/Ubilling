<?php
if (cfr('LIFESTORY')) {

if (isset ($_GET['username'])) {
    $login=vf($_GET['username']);

//weblogs user parsing    
$form=web_GrepLogByUser($login);


//raw database fields display
if (cfr('ROOT')) {
$userdata_q="SELECT * from `users` WHERE `login`='".$login."'";
$userdataraw=  simple_query($userdata_q);
if (!empty($userdataraw)) {
    $userdump=print_r($userdataraw,true);
    $userdump=nl2br($userdump);
    $form.=wf_modal(__('User inside'), __('User inside'), $userdump, 'ubButton', '800', '600').' &nbsp;';
    //nethosts data
    $userip=$userdataraw['IP'];
    $nethost_q="SELECT * from `nethosts` WHERE `ip`='".$userip."'";
    $nethostraw=  simple_query($nethost_q);
    if (!empty($nethostraw)) {
        $nethostdump=print_r($nethostraw,true);
        $nethostdump=nl2br($nethostdump);
        $form.=wf_modal(__('User Networking'), __('User Networking'), $nethostdump, 'ubButton', '400', '400').'<br>';
    }
 }
}
$form.=wf_delimiter().web_UserControls($login);

show_window(__('User lifestory'), $form);
}

} else {
      show_error(__('You cant control this module'));
}

?>
