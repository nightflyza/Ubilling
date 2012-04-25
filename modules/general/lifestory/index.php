<?php
if (cfr('LIFESTORY')) {

if (isset ($_GET['username'])) {
    $login=vf($_GET['username']);

$form=web_GrepLogByUser($login);
$form.=web_UserControls($login);

show_window(__('User lifestory'), $form);
}

} else {
      show_error(__('You cant control this module'));
}

?>
