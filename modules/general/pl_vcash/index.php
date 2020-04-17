<?php

/**
 * This module is deprecated and disabled by default.
 */
if (cfr('PLVCASH')) {
    $altCfg = $ubillingConfig->getAlter();
    if (@$altCfg['VCASH_ENABLED']) {

        function web_VserviceCashForm($login) {
            $currentvcash = zb_VserviceCashGet($login);
            $alladdr = zb_AddressGetFulladdresslist();
            $allrealnames = zb_UserGetAllRealnames();
            $form = '
            <form action="" method="POST">
            <table width="50%" border="0">
            <tr>
            <td  class="row2">' . __('Login') . '</td>
            <td  class="row3">' . $login . '</td>
            </tr>
            <tr>
            <td class="row2">' . __('Address') . '</td>
            <td class="row3">' . @$alladdr[$login] . '</td>
            </tr>
            <tr>
            <td class="row2">' . __('Real Name') . '</td>
            <td class="row3">' . @$allrealnames[$login] . '</td>
            </tr>
            <tr>
            <td class="row2">' . __('Current Cash state') . '</td>
            <td class="row3">' . $currentvcash . '</td>
            </tr>
            <tr>
            <td class="row2">' . __('New cash') . '</td>
            <td class="row3"><input name="newcash" size="5" type="text"></td>
            </tr>
            
            <tr>
            <td class="row2">' . __('Actions') . '</td>
            <td class="row3">
            <input name="operation" value="add" checked="checked" type="radio"> ' . __('Add cash') . '
            <input name="operation" value="set" type="radio"> ' . __('Set cash') . '
            </td>
            </tr>
            <tr>
            <td class="row2">' . __('Payment type') . '</td>
            <td class="row3">' . web_CashTypeSelector() . '</td>
            </tr>
             <tr>
            <td class="row2">' . __('Virtual services') . '</td>
            <td class="row3">' . web_VservicesSelector() . '</td>
            </tr>
            </table> 
            <input type="submit" value="' . __('Change') . '">
           </form>';
            return($form);
        }

        if (isset($_GET['username'])) {
            $login = $_GET['username'];
            //if we adds cash to someone
            if (isset($_POST['newcash'])) {
                //collect needed data
                $cash = mysql_real_escape_string($_POST['newcash']);
                $balance = zb_VserviceCashGet($login);
                $date = curdatetime();
                $cashtype = $_POST['cashtype'];
                $vserviceid = $_POST['vserviceid'];
                $note = 'Service:' . $vserviceid;
                $admin = whoami();

                if ($cash) {
                    //if it only adding
                    if ($_POST['operation'] == 'add') {

                        zb_VserviceCashAdd($login, $cash, $vserviceid);
                        $paymentlog = "INSERT INTO `payments` (
                `id` ,
                `login` ,
                `date` ,
                `admin` ,
                `balance` ,
                `summ` ,
                `cashtypeid` ,
                `note`
                )
                VALUES (
                NULL , '" . $login . "', '" . $date . "','" . $admin . "' ,'" . $balance . "', '" . $cash . "', '" . $cashtype . "', '" . $note . "'
                );";
                        nr_query($paymentlog);
                        rcms_redirect("?module=pl_vcash&username=" . $login);
                    }
                    //or set
                    if ($_POST['operation'] == 'set') {
                        zb_VserviceCashSet($login, $_POST['newcash']);
                        zb_VserviceCashLog($login, $balance, $cash, $vserviceid);
                        rcms_redirect("?module=pl_vcash&username=" . $login);
                    }
                }
            }


            show_window(__('Virtual cash account'), web_VserviceCashForm($login));
            show_window('', web_UserControls($login));
        }
    } else {
        show_error(__('This module is disabled'));
    }
} else {
    show_error(__('You cant control this module'));
}
?>
