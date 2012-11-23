<?php

error_reporting(E_ALL & ~E_NOTICE);
// check for right of current admin on this module
if (cfr('TARIFFS')) {

    $dirs = getAllDirs();
    if (empty($dirs)) {
        $alert='
            <script type="text/javascript">
                alert("'.__('Error').': '.__('No traffic classes available, now you will be redirected to the module with which you can add traffic classes').'");
            </script>
            ';
        print($alert);
        rcms_redirect("?module=rules");
        die();
        
    }
    
    function tariff_name_filter($tariffname) {
       $tariffname=trim($tariffname);
       return preg_replace("#[^a-z0-9A-Z\-_\.]#Uis",'',$tariffname);
    }

    function web_TariffCreateForm() {
        global $dirs;
        $form = '<form id="tariff_add" method="POST" action="">
    <table>
        <tr>
            <td>' . __('Tariff name') . '</td><td><input  type="text" name="options[TARIFF]" value=""></td>
        </tr>
        <tr>
            <td>' . __('Fee') . '</td><td><input size="2" type="text" name="options[Fee]" value=""></td>
        </tr>
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
                <td><input id="no0" OnClick="hide(0,\'no\')" name="options[NoDiscount][' . $dir['rulenumber'] . ']" value="1" type="checkbox" > ' . __('Without threshold') . '</td>
            </tr>
        </table>
        <input id="single' . $dir['rulenumber'] . '" OnClick="hide(0,\'si\')" name="options[SinglePrice][' . $dir['rulenumber'] . ']" type="checkbox" value="1" > ' . __('Price does not depend on time') . '
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
        $form = '<a href="?module=tariffs&action=new">' . __('Create new tariff') . '</a>';
        $form .= '<table width="100%" border="0" class="sortable">';
        $form.='
        <tr class="row1">
            <td>' . __('Tariff name') . '</td>
                <td>' . __('Tariff Fee') . '</td>
            <td>' . __('Actions') . '</td>
        </tr>
        ';
        if (!empty($alltariffs)) {
            foreach ($alltariffs as $io => $eachtariff) {
                $form.='
        <tr class="row3">
            <td>' . $eachtariff['name'] . '</td>
            <td>' . $eachtariff['Fee'] . '</td>
            <td>
            <a  onclick="if(!confirm(\'' . __('Are you serious') . '\')) { return false;}" href="?module=tariffs&action=delete&tariffname=' . $eachtariff['name'] . '">' . web_delete_icon() . '</a>
            <a href="?module=tariffs&action=edit&tariffname=' . $eachtariff['name'] . '">' . web_edit_icon() . '</a>
            </td>
        </tr>
        ';
            }
        }
        $form.='</table>';
        return($form);
    }

    function web_TariffEditForm($tariffname) {

        global $dirs;

        $tariffdata = billing_gettariff($tariffname);

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


        $form = '<form method="POST" action="">
    <table>
        <tr>
            <td>' . __('Tariff name') . '</td><td><input  type="text" name="options[TARIFF]" DISABLED value="' . $tariffdata['name'] . '"></td>
        </tr>
        <tr>
            <td>' . __('Fee') . '</td><td><input size="2" type="text" name="options[Fee]" value="' . $tariffdata['Fee'] . '"></td>
        </tr>
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
        $newtariffname=tariff_name_filter($newtariffname);
        $tariffoptions=$_POST['options'];
        $tariffoptions['Fee']=trim($tariffoptions['Fee']);
        if (!empty ($newtariffname)) {
        $billing->createtariff($newtariffname);
        $billing->edittariff($newtariffname, $tariffoptions);
        log_register("TARIFF CREATE ".$newtariffname);
        }
    }

    if (isset($_GET['action'])) {
        if (isset($_GET['tariffname'])) {
            $tariffname = $_GET['tariffname'];
            if ($_GET['action'] == 'delete') {
                $billing->deletetariff($tariffname);
                log_register("TARIFF DELETE ".$tariffname);
                rcms_redirect('?module=tariffs');
            }

            if ($_GET['action'] == 'edit') {
                if (isset($_POST['options']['Fee'])) {
                    $tariffoptions=$_POST['options'];
                    $tariffoptions['Fee']=trim($tariffoptions['Fee']);
                    $billing->edittariff($tariffname, $tariffoptions);
                    log_register("TARIFF CHANGE ".$tariffname);
                    rcms_redirect('?module=tariffs');
                }
                show_window(__('Edit Tariff'), web_TariffEditForm($tariffname));
            }
        }

        if ($_GET['action'] == 'new') {

            show_window(__('Create new tariff'), web_TariffCreateForm());
            if (isset($_POST['options']['TARIFF'])) {
                $tariffnameredirect=tariff_name_filter($_POST['options']['TARIFF']);
                if (!empty ($tariffnameredirect)) {
                rcms_redirect('?module=tariffs&action=edit&tariffname=' . tariff_name_filter($_POST['options']['TARIFF']));
                }
            }
        }
    }
    

    show_window(__('Available tariffs'), web_TariffLister());
} else {
    show_error(__('You cant control this module'));
}
?>
