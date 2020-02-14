<?php

/**
 * management API for lousy tariffs
 */

/**
 * Marks tariff as not polular
 *
 * @param   $tariff tariff name
 * 
 */
function zb_LousyTariffAdd($tariff) {
    $tariff = mysql_real_escape_string($tariff);
    $query = "INSERT INTO `lousytariffs` (`id`,`tariff`) VALUES ('','" . $tariff . "'); ";
    nr_query($query);
    log_register("LOUSYTARIFF ADD `" . $tariff . "`");
}

/**
 * Remove lousy mark
 *
 * @param   $tariff tariff name
 * 
 */
function zb_LousyTariffDelete($tariff) {
    $tariff = mysql_real_escape_string($tariff);
    $query = "DELETE from `lousytariffs` WHERE `tariff`='" . $tariff . "' ";
    nr_query($query);
    log_register("LOUSYTARIFF DELETE `" . $tariff . "`");
}

/**
 *  Returns full list of tariffs marked as lousy
 *  @return  array
 */
function zb_LousyTariffGetAll() {
    $query = "SELECT `id`,`tariff` from `lousytariffs`";
    $result = array();
    $alldata = simple_queryall($query);
    if (!empty($alldata)) {
        foreach ($alldata as $io => $eachtariff) {
            $result[$eachtariff['tariff']] = $eachtariff['id'];
        }
    }
    return ($result);
}

/**
 * Checks is tariff lousy or not?
 *
 * @param   $tariff tariff name
 * @param   $lousyarr all lousy tariffs array
 * @return  bool
 *
 */
function zb_LousyCheckTariff($tariff, $lousyarr) {
    $tariff = mysql_real_escape_string($tariff);
    if (!empty($lousyarr)) {
        //check is tariff marked as lousy?
        if (isset($lousyarr[$tariff])) {
            return (true);
        } else {
            return (false);
        }
    } else {
        // if no lousy marks - all tariffs popular by default
        return (false);
    }
}

/**
 *  Returns list of lousy tariffs
 *  @return  string
 */
function web_LousyShowAll() {
    $allousy = zb_LousyTariffGetAll();
    $allTariffPrices = zb_TariffGetPricesAll();

    $tablecells = wf_TableCell(__('Tariff'));
    $tablecells .= wf_TableCell(__('Fee'));
    $tablecells .= wf_TableCell(__('Actions'));
    $tablerows = wf_TableRow($tablecells, 'row1');

    if (!empty($allousy)) {
        foreach ($allousy as $eachtariff => $id) {
            if (isset($allTariffPrices[$eachtariff])) {
                $rowClass = 'row5';
            } else {
                $rowClass = 'sigdeleteduser';
            }
            $tablecells = wf_TableCell($eachtariff);
            $tariffPrice = (isset($allTariffPrices[$eachtariff])) ? $allTariffPrices[$eachtariff] : __('Deleted');
            $tablecells .= wf_TableCell($tariffPrice);
            $dellink = wf_JSAlert('?module=lousytariffs&delete=' . $eachtariff, web_delete_icon(), 'Removing this may lead to irreparable results');
            $tablecells .= wf_TableCell($dellink);
            $tablerows .= wf_TableRow($tablecells, $rowClass);
        }
    }
    $result = wf_TableBody($tablerows, '100%', '0', 'sortable');
    return ($result);
}

/**
 * Returns available tariffs selector excluding already lousy
 * 
 * @param string $fieldname
 * @return string
 */
function web_LousyTariffSelector($fieldname = 'tariffsel') {
    $alltariffs = zb_TariffsGetAll();
    $options = array();

    if (!empty($alltariffs)) {
        $allLousy = zb_LousyTariffGetAll();
        foreach ($alltariffs as $io => $eachtariff) {
            if (!isset($allLousy[$eachtariff['name']])) {
                $options[$eachtariff['name']] = $eachtariff['name'];
            }
        }
    }

    $selector = wf_Selector($fieldname, $options, '', '', false);
    return($selector);
}

/**
 *  Returns form for adding lousy tariff
 * 
 *  @return  string
 */
function web_LousyAddForm() {
    $addinputs = web_LousyTariffSelector('newlousytariff') . ' ';
    $addinputs .= wf_Submit('Mark this tariff as not popular');
    $addform = wf_Form('', 'POST', $addinputs, 'glamour');
    return ($addform);
}

?>
