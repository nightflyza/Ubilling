<?php
if (cfr('PHONE')) {

if (isset ($_GET['username'])) {
    $login=vf($_GET['username']);
       // change phone routine
       if (isset ($_POST['newphone'])) {
        $phone=$_POST['newphone'];
        zb_UserChangePhone($login, $phone);
        rcms_redirect("?module=phoneedit&username=".$login);
    }

    $current_phone=zb_UserGetPhone($login);
    $user_address=zb_UserGetFullAddress($login);

 

$form='
    <form method="POST" action="">
    <table width="100%" border="0">
     <tr>
    <td class="row2">
    '.__('User').'
    </td>
    <td class="row3">
   '.$user_address.' ('.$login.')
    </td>
    </tr>
    <tr>
    <td class="row2">
    '.__('Current phone').'
    </td>
    <td class="row3">
   '.$current_phone.'
    </td>
    </tr>
    <tr>
    <td class="row2">
    '.__('New Phone').'
    </td>
    <td class="row3">
    <input type="text" name="newphone">
    </td>
    </tr>
    </table>
    <input type="Submit" value="'.__('Change').'">
    </form>
    ';
$form.=web_UserControls($login);
show_window(__('Edit phone'), $form);
}

} else {
      show_error(__('You cant control this module'));
}

?>
