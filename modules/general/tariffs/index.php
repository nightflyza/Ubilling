<?php

error_reporting(E_ALL & ~E_NOTICE);
// check for right of current admin on this module
if (cfr('TARIFFS')) {

    $dirs = getAllDirs();
    if (empty($dirs)) {
        $alert = '
            <script type="text/javascript">
                alert("' . __('Error') . ': ' . __('No traffic classes available, now you will be redirected to the module with which you can add traffic classes') . '");
            </script>
            ';
        print($alert);
        rcms_redirect("?module=rules");
        die();
    }

    function tariff_name_filter($tariffname) {
        $tariffname = trim($tariffname);
        return preg_replace("#[^a-z0-9A-Z\-_\.]#Uis", '', $tariffname);
    }

    function web_TariffCreateForm() {
        global $dirs;

        $dbSchema = zb_CheckDbSchema();

        if ($dbSchema > 0) {
            $availOpts = array('month' => __('Month'), 'day' => __('Day'));

            $periodCells = wf_TableCell(__('Period'));
            $periodCells.= wf_TableCell(wf_Selector("options[Period]", $availOpts, '', $tariffdata['period']));
            $periodRows = wf_TableRow($periodCells);
            $periodControls = $periodRows;
        } else {
            $periodControls = '';
        }

        $form = wf_Link("?module=tariffs", __('Back'), true, 'ubButton');
        $form.= '<form id="tariff_add" method="POST" action="">
    <table>
        <tr>
            <td>' . __('Tariff name') . '</td><td><input  type="text" name="options[TARIFF]" value=""></td>
        </tr>
        <tr>
            <td>' . __('Fee') . '</td><td><input size="2" type="text" name="options[Fee]" value=""></td>
        </tr>
        ' . $periodControls . '
        <tr>
            <td>' . __('Prepaid traffic') . '</td><td><input size="2" type="text" name="options[Free]" value=""></td>
        </tr>
        <tr>
            <td>' . __('Counting traffic') . '</td><td><select name="options[TraffType]">
                    <option >up+down</option><option >up</option><option>down</option><option >max</option></select>
            </td>
        </tr>
        <tr>
            <td>' . __('Cost of freezing') . '</td><td><input size="2" type="text" name="options[PassiveCost]" value=""></td>
        </tr>

    </table>';

        foreach ($dirs as $dir) {

            $form.='<fieldset><legend><b>' . $dir['rulename'] . '</b></legend>

        <table>
            <tr>
                <td>' . __('Hours') . '</td><td>' . __('Minutes') . '</td>
            </tr>
            <tr>
                <td>
                    <select id="dhour' . $dir['rulenumber'] . '"  name="options[dhour][' . $dir['rulenumber'] . ']">
                        <option SELECTED>00</option>';
            $form.=tariff_time(24);

            $form.='</select>
                        </td>
                        <td>
                            <select id="dmin' . $dir['rulenumber'] . '"  name="options[dmin][' . $dir['rulenumber'] . ']">
                                <option SELECTED>00</option>';
            $form.=tariff_time(60);

            $form.='                                    </select>

                                    <br>

                                </td>
                                <td>' . __('Day') . '</td>


                                <td><input size="3" type="text" name="options[PriceDay][' . $dir['rulenumber'] . ']" value=""></td>
                                <td>' . __('Price day') . '</td>

                                <td><input id="thr' . $dir['rulenumber'] . '"  size="3" type="text" name="options[Threshold][' . $dir['rulenumber'] . ']" value=""> ' . __('Threshold') . ' (' . __('Mb') . ')</td>
                            </tr>
                            <tr>
                                <td>
                                    <select id="nhour' . $dir['rulenumber'] . '"  name="options[nhour][' . $dir['rulenumber'] . ']">
                                        <option  SELECTED>00</option>';
            $form.=tariff_time(24);
            $form.='</select>
                                        </td>
                                        <td>
                                            <select id="nmin' . $dir['rulenumber'] . '"  name="options[nmin][' . $dir['rulenumber'] . ']">
                                                <option SELECTED>00</option>';
            $form.=tariff_time(60);

            $form.='                    </select>

                </td>
                <td>' . __('Night') . '</td>
                <td><input   id="pricenight' . $dir['rulenumber'] . '"  size="3" type="text" name="options[PriceNight][' . $dir['rulenumber'] . ']" value=""></td>
                <td>' . __('Price night') . '</td>
                <td><input id="no0" OnClick="hide(0,\'no\')" name="options[NoDiscount][' . $dir['rulenumber'] . ']" value="1" type="checkbox" checked> ' . __('Without threshold') . '</td>
            </tr>
        </table>
        <input id="single' . $dir['rulenumber'] . '" OnClick="hide(0,\'si\')" name="options[SinglePrice][' . $dir['rulenumber'] . ']" type="checkbox" value="1" checked> ' . __('Price does not depend on time') . '
    </fieldset>';
        }

        $form.='<input type="submit" id="save" name="save" value="' . __('Create') . '">
</form>';
        return($form);
    }

    function tariff_time($t, $s = false) {
        for ($i = 1; $i < $t; ++$i) {
            if ($i < 10) {
                $a = '0';
            } else {
                unset($a);
            }
            if ($s == @$a . $i) {

                $b = "SELECTED";
            }
            $form.= '<option ' . @$b . '>' . $a . $i . '</option>';

            unset($b);
        }
        return $form;
    }

    function tariff_price($a, $b) {

        if ($a == $b) {
            return $a;
        } else {
            return "$a/$b";
        }
    }

    function web_TariffLister() {
        $alltariffs = billing_getalltariffs();
        $dbSchema = zb_CheckDbSchema();

        global $ubillingConfig;
        $alter = $ubillingConfig->getAlter();
        $tariffSpeeds = zb_TariffGetAllSpeeds();

        $cells = wf_TableCell(__('Tariff name'));
        $cells.= wf_TableCell(__('Tariff Fee'));
        if ($dbSchema > 0) {
            $cells.= wf_TableCell(__('Period'));
        }
        $cells.= wf_TableCell(__('Speed'));
        $cells.= wf_TableCell(__('Actions'));
        $rows = wf_TableRow($cells, 'row1');

        $result = wf_Link("?module=tariffs&action=new", __('Create new tariff'), true, 'ubButton');

        if (!empty($alltariffs)) {
            foreach ($alltariffs as $io => $eachtariff) {
                $cells = wf_TableCell($eachtariff['name']);
                $cells.= wf_TableCell($eachtariff['Fee']);
                if ($dbSchema > 0) {
                    $cells.= wf_TableCell(__($eachtariff['period']));
                }

                if (isset($tariffSpeeds[$eachtariff['name']])) {
                    $speedData = $tariffSpeeds[$eachtariff['name']]['speeddown'] . ' / ' . $tariffSpeeds[$eachtariff['name']]['speedup'];
                } else {
                    $speedData = wf_tag('font', false, '', 'color="#bc0000"') . __('Speed is not set') . wf_tag('font', true);
                }
                $cells.= wf_TableCell($speedData);

                $actions = wf_JSAlert("?module=tariffs&action=delete&tariffname=" . $eachtariff['name'], web_delete_icon(), __('Delete') . ' ' . $eachtariff['name'] . '? ' . __('Removing this may lead to irreparable results'));
                $actions .= wf_JSAlert("?module=tariffs&action=edit&tariffname=" . $eachtariff['name'], web_edit_icon(), __('Edit') . ' ' . $eachtariff['name'] . '? ' . __('Are you serious'));
                $actions .= wf_Link('?module=tariffspeeds&tariff=' . $eachtariff['name'], wf_img('skins/icon_speed.gif', __('Edit speed')), false, '');
                $actions .= ( isset($alter['SIGNUP_PAYMENTS']) && !empty($alter['SIGNUP_PAYMENTS']) ) ? wf_Link('?module=signupprices&tariff=' . $eachtariff['name'], wf_img('skins/icons/register.png', __('Edit signup price')), false, '') : null;
                $cells.= wf_TableCell($actions);
                $rows.= wf_TableRow($cells, 'row3');
            }
        }

        $result.= wf_TableBody($rows, '100%', 0, 'sortable');

        return($result);
    }

    function web_TariffEditForm($tariffname) {

        global $dirs;

        $tariffdata = billing_gettariff($tariffname);
        $dbSchema = zb_CheckDbSchema();

        if ($tariffdata['TraffType'] == 'up') {
            $s1 = "SELECTED";
        }
        if ($tariffdata['TraffType'] == 'down') {
            $s2 = "SELECTED";
        }
        if ($tariffdata['TraffType'] == 'up+down') {
            $s3 = "SELECTED";
        }
        if ($tariffdata['TraffType'] == 'max') {
            $s4 = "SELECTED";
        }

        if ($dbSchema > 0) {
            $availOpts = array('month' => __('Month'), 'day' => __('Day'));

            $periodCells = wf_TableCell(__('Period'));
            $periodCells.= wf_TableCell(wf_Selector("options[Period]", $availOpts, '', $tariffdata['period']));
            $periodRows = wf_TableRow($periodCells);
            $periodControls = $periodRows;
        } else {
            $periodControls = '';
        }
        $form = wf_Link("?module=tariffs", __('Back'), true, 'ubButton');
        $form.= '<form method="POST" action="">
    <table>
        <tr>
            <td>' . __('Tariff name') . '</td><td><input  type="text" name="options[TARIFF]" DISABLED value="' . $tariffdata['name'] . '"></td>
        </tr>
        <tr>
            <td>' . __('Fee') . '</td><td><input size="2" type="text" name="options[Fee]" value="' . $tariffdata['Fee'] . '"></td>
        </tr>
        ' . $periodControls . '
        <tr>
            <td>' . __('Prepaid traffic') . '</td><td><input size="2" type="text" name="options[Free]" value="' . $tariffdata['Free'] . '"></td>
        </tr>
        <tr>
            <td>' . __('Counting traffic') . '</td><td>
                <select name="options[TraffType]">
                    <option ' . $s1 . '>up</option>
                    <option ' . $s2 . '>down</option>
                    <option ' . $s3 . '>up+down</option>
                    <option ' . $s4 . '>max</option>
                    </select>
            </td>
        </tr>
        <tr>
            <td>' . __('Cost of freezing') . '</td><td><input size="2" type="text" name="options[PassiveCost]" value="' . $tariffdata['PassiveCost'] . '"></td>
        </tr>

    </table>';

        foreach ($dirs as $dir) {

            $arrTime = explode('-', $tariffdata ["Time$dir[rulenumber]"]);
            $day = explode(':', $arrTime [0]);
            $night = explode(':', $arrTime [1]);

            $tariffdata ['Time'] [$dir[rulenumber]] ['Dmin'] = $day [1];
            $tariffdata ['Time'] [$dir[rulenumber]] ['Dhour'] = $day [0];
            $tariffdata ['Time'] [$dir[rulenumber]] ['Nmin'] = $night [1];
            $tariffdata ['Time'] [$dir[rulenumber]] ['Nhour'] = $night [0];

            if ($tariffdata["NoDiscount$dir[rulenumber]"] == 1) {

                $ns[$dir[rulenumber]] = "CHECKED";
            }
            if ($tariffdata["SinglePrice$dir[rulenumber]"] == 1) {

                $sp[$dir[rulenumber]] = "CHECKED";
            }

            $form.='<fieldset><legend><b>' . $dir['rulename'] . '</b></legend>

        <table>
            <tr>
                <td>' . __('Hours') . '</td><td>' . __('Minutes') . '</td>
            </tr>
            <tr>
                <td>
                    <select id="dhour' . $dir['rulenumber'] . '"  name="options[dhour][' . $dir['rulenumber'] . ']">
                        <option>00</option>';
            $form.=tariff_time(24, $tariffdata['Time'][$dir[rulenumber]] ['Dhour']);

            $form.='</select>
                        </td>
                        <td>
                            <select id="dmin' . $dir['rulenumber'] . '"  name="options[dmin][' . $dir['rulenumber'] . ']">
                                <option>00</option>';
            $form.=tariff_time(60, $tariffdata['Time'][$dir[rulenumber]] ['Dmin']);

            $form.='</select>

                                    <br>

                                </td>
                                <td>' . __('Day') . '</td>


                                <td><input size="3" type="text" name="options[PriceDay][' . $dir['rulenumber'] . ']" value="' . tariff_price($tariffdata ["PriceDayA$dir[rulenumber]"], $tariffdata ["PriceDayB$dir[rulenumber]"]) . '"></td>
                                <td>' . __('Price day') . '</td>

                                <td><input id="thr' . $dir['rulenumber'] . '"  size="3" type="text" name="options[Threshold][' . $dir['rulenumber'] . ']" value="' . $tariffdata ["Threshold$dir[rulenumber]"] . '"> ' . __('Threshold') . ' (' . __('Mb') . ')</td>
                            </tr>
                            <tr>
                                <td>
                                    <select id="nhour' . $dir['rulenumber'] . '"  name="options[nhour][' . $dir['rulenumber'] . ']">
                                        <option  SELECTED>00</option>';
            $form.=tariff_time(24, $tariffdata['Time'][$dir[rulenumber]] ['Nhour']);
            $form.='</select>
                                        </td>
                                        <td>
                                            <select id="nmin' . $dir['rulenumber'] . '"  name="options[nmin][' . $dir['rulenumber'] . ']">
                                                <option SELECTED>00</option>';
            $form.=tariff_time(60, $tariffdata['Time'][$dir[rulenumber]] ['Nmin']);

            $form.='                    </select>

                </td>
                <td>' . __('Night') . '</td>
                <td><input  id="pricenight' . $dir['rulenumber'] . '"  size="3" type="text" name="options[PriceNight][' . $dir['rulenumber'] . ']" value="' . tariff_price($tariffdata ["PriceNightA$dir[rulenumber]"], $tariffdata ["PriceNightB$dir[rulenumber]"]) . '"></td>
                <td>' . __('Price night') . '</td>
                <td><input id="no0" OnClick="hide(0,\'no\')" name="options[NoDiscount][' . $dir['rulenumber'] . ']" value="1" ' . $ns[$dir[rulenumber]] . ' type="checkbox" > ' . __('Without threshold') . '</td>
            </tr>
        </table>
        <input id="single' . $dir['rulenumber'] . '" OnClick="hide(0,\'si\')" name="options[SinglePrice][' . $dir['rulenumber'] . ']" type="checkbox" ' . $sp[$dir[rulenumber]] . ' value="1" > ' . __('Price does not depend on time') . '
    </fieldset>';
        }

        $form.='<input type="submit" id="save" name="save" value="' . __('Edit') . '">
</form>';
        return($form);
    }

    if (isset($_POST['options']['TARIFF'])) {
        $newtariffname = $_POST['options']['TARIFF'];
        $newtariffname = tariff_name_filter($newtariffname);
        $tariffoptions = $_POST['options'];
        $tariffoptions['Fee'] = trim($tariffoptions['Fee']);
        if (!empty($newtariffname)) {
            $billing->createtariff($newtariffname);
            $billing->edittariff($newtariffname, $tariffoptions);
            log_register("TARIFF CREATE `" . $newtariffname . "`");
        }
    }

    if (isset($_GET['action'])) {
        if (isset($_GET['tariffname'])) {
            $tariffname = $_GET['tariffname'];

            if ($_GET['action'] == 'delete') {
                if (!zb_TariffProtected($tariffname)) {
                    $billing->deletetariff($tariffname);
                    log_register("TARIFF DELETE `" . $tariffname . "`");
                    zb_LousyTariffDelete($tariffname);
                    zb_TariffDeleteSpeed($tariffname);
                    $dshaper = new DynamicShaper();
                    $dshaper->flushTariff($tariffname);
                    rcms_redirect('?module=tariffs');
                } else {
                    log_register("TARIFF DELETE TRY USED `" . $tariffname . "`");
                    show_error(__('Tariff is used by some users'));
                    show_window('', wf_Link('?module=tariffs', __('Back'), true, 'ubButton'));
                }
            }

            if ($_GET['action'] == 'edit') {
                if (isset($_POST['options']['Fee'])) {
                    $tariffoptions = $_POST['options'];
                    $tariffoptions['Fee'] = trim($tariffoptions['Fee']);
                    $billing->edittariff($tariffname, $tariffoptions);
                    log_register("TARIFF CHANGE `" . $tariffname . "`");
                    rcms_redirect('?module=tariffs');
                }
                show_window(__('Edit Tariff'), web_TariffEditForm($tariffname));
            }
        }

        if ($_GET['action'] == 'new') {

            show_window(__('Create new tariff'), web_TariffCreateForm());
            if (isset($_POST['options']['TARIFF'])) {
                $tariffnameredirect = tariff_name_filter($_POST['options']['TARIFF']);
                if (!empty($tariffnameredirect)) {
                    rcms_redirect('?module=tariffs&action=edit&tariffname=' . tariff_name_filter($_POST['options']['TARIFF']));
                }
            }
        }
    }

    if (!wf_CheckGet(array('action'))) {
        show_window(__('Available tariffs'), web_TariffLister());
    }
} else {
    show_error(__('You cant control this module'));
}
?>
