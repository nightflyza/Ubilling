<?php
if ( cfr('SIGNUPPRICES') ) {
    $alter = parse_ini_file(CONFIG_PATH . 'alter.ini', true);
    if ( ($alter['SIGNUP_PRICES']) ) {
        if ( isset($_GET['tariff']) ) {
            $tariff = mysql_real_escape_string($_GET['tariff']);
            $prices = zb_TariffGetAllSignupPrices();
            if ( !isset($prices[$tariff]) ) {
                zb_TariffCreateSignupPrice($tariff, 0);
                $prices = zb_TariffGetAllSignupPrices();
            }
            if ( isset($_POST['new_price']) ) {
                zb_TariffDeleteSignupPrice($tariff);
                zb_TariffCreateSignupPrice($tariff, $_POST['new_price']);
                rcms_redirect("?module=signupprices");
            }
            $form = '<form action="" method="POST">
                <table width="100%" border="0">
                    <tr>
                        <td class="row2">' . __('Signup price') . '</td>
                        <td class="row3">
                            <input type="text" name="new_price" value="' . $prices[$tariff] . '">
                        </td>
                    </tr>
                </table>
                <input type="submit" value="' . __('Change') . '">
            </form><br><br>';
            show_window(__('Edit signup price for tariff') . ' "' . $tariff . '"', $form);
            show_window('', wf_Link("?module=signupprices", 'Back', true, 'ubButton'));
        } elseif ( isset($_GET['username']) ) {
            $login = mysql_real_escape_string($_GET['username']);
            $price = zb_UserGetSignupPrice($login);
            if ( isset($_POST['new_price']) ) {
                $fee = $price - $_POST['new_price'];
                zb_UserChangeSignupPrice($login, $_POST['new_price']);
                $billing->addcash($login, $fee);
                log_register("CHARGE SignupPriceFee (" . $login . ") " . $fee);
                rcms_redirect("?module=useredit&username=" . $login);
            }
            $form = '<form action="" method="POST">
                <table width="100%" border="0">
                    <tr>
                        <td class="row2">' . __('Signup price') . '</td>
                        <td class="row3">
                            <input type="text" name="new_price" value="' . $price . '">
                        </td>
                    </tr>
                </table>
                <input type="submit" value="' . __('Change') . '">
            </form><br><br>';
            show_window(__('Edit signup price for user') . ' "' . $login . '"', $form);
            show_window('', wf_Link("?module=useredit&username=" . $login, 'Back', true, 'ubButton'));
        } else {
            $query = "SELECT `name` FROM `tariffs`";
            $tariffs = simple_queryall($query);
            $prices  = zb_TariffGetAllSignupPrices();

            $form = '<table width="100%" class="sortable" border="0">';
            $form.='<tr class="row1"><td>'.__('Tariff').'</td><td>'.__('Signup price').'</td><td>'.__('Actions').'</td></tr>';
            if ( !empty($tariffs) ) {
                foreach ( $tariffs as $tariff ) {
                    $form .= '
                        <tr class="row3">
                        <td>' . $tariff['name'] . '</td>
                        <td>' . ( isset($prices[$tariff['name']]) ? $prices[$tariff['name']] : '0' ) . '</td>
                        <td>
                            <a href="?module=signupprices&tariff=' . $tariff['name'] . '">' . wf_img('skins/icons/register.png', __('Edit signup price')) . '</a>
                        </td>
                        </tr>
                    ';
                }
            }
            $form .= '</table>';
            show_window(__('Signup price'), $form);
        }
    }
}