<?php

/**
 * Creates card in database with some serial and price
 * 
 * @param int   $serial
 * @param float $cash
 * @param int   $part
 * @param int   $selling
 *
 * @return void
 */
function zb_CardCreate($serial, $cash, $part, $selling) {
    $admin = whoami();
    $date = curdatetime();
    $insert = 'INSERT INTO `cardbank` (`id`, `serial`, `part`, `cash`, `admin`, `date`, `receipt_date`, `selling_id`, `active`, `used`, `usedate`, `usedlogin`, `usedip`)';
    $query = sprintf("%s VALUES (NULL , '%s', '%s', '%s', '%s', '%s', '%s', '%s', '1', '0', NULL , '', NULL);", $insert, $serial, $part, $cash, $admin, $date, $date, $selling);
    nr_query($query);
}

/**
 * Generates cards in database with some price, and returns it serials
 * 
 * @param array $cardCreate
 *
 * @return string
 */
function zb_CardGenerate(array($cardCreate)) {
    $count = vf($cardCreate['count'], 3);
    $price = vf($cardCreate['price']);
    $part = $cardCreate['part'];
    $selling = vf($cardCreate['selling'], 3);

    if ((empty($count)) or (empty($price))) {
        die('No count or price');
    }

    $reported = '';
    for ($cardCount = 0; $cardCount < $count; $cardCount++) {
        $serial = mt_rand(1111, 9999) . mt_rand(1111, 9999) . mt_rand(1111, 9999) . mt_rand(1111, 9999);
        $reported .= $serial . "\n";
        zb_CardCreate($serial, $price, $part, $selling);
    }
    log_register(sprintf('CARDS CREATED `%s` PART `%s` SERIAL `%s` PRICE `%s` SELLING_ID [%s]', $count, $part, $serial, $price, $selling));

    return $reported;
}

/**
 * Returns count of available payment cards
 * 
 * @return int
 */
function zb_CardsGetCount() {
    $query = 'SELECT COUNT(`id`) from `cardbank`';
    $result = simple_query($query);
    $result = $result['COUNT(`id`)'];

    return $result;
}

/**
 * Returns available list with some controls
 * 
 * @return string
 */
function web_CardsShow() {
    $selling = zb_BuilderSelectSellingData();
    $totalcount = zb_CardsGetCount();
    $perpage = 100;

     //pagination 
     if (!isset($_GET['page'])) {
         $current_page = 1;
     } else {
         $current_page = vf($_GET['page'], 3);
     }

    if ($totalcount > $perpage) {
        $paginator = wf_pagination($totalcount, $perpage, $current_page, '?module=cards', 'ubButton');
        $from = $perpage * ($current_page - 1);
        $to = $perpage;
        $query = 'SELECT * from `cardbank` ORDER by `id` DESC LIMIT '.$from.','.$to.';';
        $alluhw = simple_queryall($query);
    } else {
        $paginator = '';
        $query = 'SELECT * from `cardbank` ORDER by `id` DESC;';
        $alluhw = simple_queryall($query);
    }

    $allcards = simple_queryall($query);

    $cells = wf_TableCell(__('ID'));
    $cells .= wf_TableCell(__('Serial part'));
    $cells .= wf_TableCell(__('Serial number'));
    $cells .= wf_TableCell(__('Price'));
    $cells .= wf_TableCell(__('Admin'));
    $cells .= wf_TableCell(__('Date'));
    $cells .= wf_TableCell(__('Active'));
    $cells .= wf_TableCell(__('Used'));
    $cells .= wf_TableCell(__('Usage date'));
    $cells .= wf_TableCell(__('Used login'));
    $cells .= wf_TableCell(__('Used IP'));
    $cells .= wf_TableCell(__('Receipt date'));
    $cells .= wf_TableCell(__('Selling'));
    $cells .= wf_TableCell(wf_CheckInput('check', '', false, false));
    $rows = wf_TableRow($cells, 'row1');

    if (!empty($allcards)) {
        foreach ($allcards as $io => $eachcard) {
            $nameSelling = array_key_exists($eachcard['selling_id'], $selling) ? $selling[$eachcard['selling_id']] : '';

            $cells = wf_TableCell($eachcard['id']);
            $cells .= wf_TableCell($eachcard['part']);
            $cells .= wf_TableCell($eachcard['serial']);
            $cells .= wf_TableCell($eachcard['cash']);
            $cells .= wf_TableCell($eachcard['admin']);
            $cells .= wf_TableCell($eachcard['date']);
            $cells .= wf_TableCell(web_bool_led($eachcard['active']));
            $cells .= wf_TableCell(web_bool_led($eachcard['used']));
            $cells .= wf_TableCell($eachcard['usedate']);
            $cells .= wf_TableCell($eachcard['usedlogin']);
            $cells .= wf_TableCell($eachcard['usedip']);
            $cells .= wf_TableCell($eachcard['receipt_date']);
            $cells .= wf_TableCell($nameSelling);
            $cells .= wf_TableCell(wf_CheckInput('_cards['.$eachcard['id'].']', '', false, false));
            $rows .= wf_TableRow($cells, 'row3');
        }
    }

    $result = wf_TableBody($rows, '100%', 0, '');
    $result .= $paginator.wf_delimiter();
    $result = web_CardActions($result);

    return $result;
}

/**
 * Returns new cards generation form
 * 
 * @return string
 */
function web_CardsGenerateForm() {
    $inputs = wf_Selector('card_create[selling]', zb_BuilderSelectSellingData(), __('Selling'), '', false);
    $inputs .= wf_TextInput('card_create[part]', 'Serial part', '', false, '5');
    $inputs .=  wf_TextInput('card_create[count]', 'Count', '', false, '5');
    $inputs .= wf_TextInput('card_create[price]', 'Price', '', false, '5');
    $inputs .= wf_Submit('Create');
    $form = wf_Form('', 'POST', $inputs, 'glamour');

    return $form;
}

/**
 * Returns cards search form
 * 
 * @return string
 */
function web_CardsSearchForm() {
    $inputs = wf_Selector('card_search[selling]', zb_BuilderSelectSellingData(), __('Selling'), '', false);
    $inputs .= __('ID');
    $inputs .= wf_TextInput('card_search[idfrom]', __('From'), '', false, '5');
    $inputs .= wf_TextInput('card_search[idto]', __('To'), '', true, '5');
    $inputs .= __('Date');
    $inputs .= wf_DatePickerPreset('card_search[datefrom]', '') . ' ' . __('From');
    $inputs .= wf_DatePickerPreset('card_search[dateto]', '') . ' ' . __('To');
    $inputs .= wf_CheckInput('card_search[used]', __('Not used'), true);
    $inputs .= wf_TextInput('card_search[part]', __('Serial part'), '', false, '5');
    $inputs .= wf_TextInput('card_search[serial]', __('Serial number'), '', true, '17');
    $inputs .= wf_Submit('Search');
    $result = wf_Form('', 'POST', $inputs, 'glamour');

    return $result;
}

/**
 * Returns cards search form
 *
 * @param array $ids
 *
 * @return string
 */
function web_CardsChangeForm(array $ids) {
    $inputs = wf_Selector('card_edit[selling]', zb_BuilderSelectSellingData(), __('Selling'), '', false);
    $inputs .= wf_TextInput('card_edit[part]', __('Serial part'), '', false, '17');
    foreach ($ids as $key => $id) {
        $inputs .= wf_HiddenInput(sprintf('card_edit[id][%s]', $key), $id);
    }
    $inputs .= wf_Submit('Update');
    $result = wf_Form('', 'POST', $inputs, 'glamour');

    return $result;
}

/**
 * Returns card by serial search results
 * 
 * @param array $search
 *
 * @return string
 */
function web_CardsSearch(array $search) {
    $selling = zb_BuilderSelectSellingData();
    $serial = '%' . mysql_real_escape_string($search['serial']) . '%';

    $querySelling = '';
    if ($search['selling']) {
        $sellingId = mysql_real_escape_string($search['selling']);
        $querySelling = sprintf("AND `selling_id` = %s", $sellingId);

    }
    $queryUsed = '';
    if (key_exists('used', $search)) {
        $queryUsed = sprintf("AND `used` = 0");
    }
    $queryPart = '';
    if ($search['part']) {
        $part = mysql_real_escape_string($search['part']);
        $queryPart = sprintf("AND `part` = %s", $part);
    }
    $queryId = '';
    if ($search['idfrom'] || $search['idto']) {
        if (empty($search['idfrom'])) {
            $search['idfrom'] = $search['idto'];
        }
        if (empty($search['idto'])) {
            $search['idto'] = $search['idfrom'];
        }
        $idFrom = mysql_real_escape_string($search['idfrom']);
        $idTo = mysql_real_escape_string($search['idto']);
        $queryId = sprintf("AND `id` BETWEEN %s AND %s", $idFrom, $idTo);
    }
    $queryDate = '';
    if ($search['datefrom'] || $search['dateto']) {
        if (empty($search['datefrom'])) {
            $search['datefrom'] = $search['dateto'];
        }
        if (empty($search['dateto'])) {
            $search['dateto'] = $search['datefrom'];
        }
        $dateFrom = mysql_real_escape_string($search['datefrom']);
        $dateTo = mysql_real_escape_string($search['dateto']);
        $queryDate = sprintf("AND DATE(`receipt_date`) BETWEEN STR_TO_DATE('%s', '%s') AND STR_TO_DATE('%s', '%s')", $dateFrom, '%Y-%m-%d %H:%i:%s', $dateTo, '%Y-%m-%d %H:%i:%s');
    }

    $query = sprintf("SELECT * from `cardbank` WHERE `serial` LIKE '%s' %s %s %s %s %s", $serial, $queryUsed, $querySelling, $queryPart, $queryId, $queryDate);

    $allcards = simple_queryall($query);
    $result = __('Nothing found');

    if (!empty($allcards)) {
        $cells = wf_TableCell(__('ID'));
        $cells .= wf_TableCell(__('Serial part'));
        $cells .= wf_TableCell(__('Serial number'));
        $cells .= wf_TableCell(__('Price'));
        $cells .= wf_TableCell(__('Admin'));
        $cells .= wf_TableCell(__('Date'));
        $cells .= wf_TableCell(__('Active'));
        $cells .= wf_TableCell(__('Used'));
        $cells .= wf_TableCell(__('Usage date'));
        $cells .= wf_TableCell(__('Used login'));
        $cells .= wf_TableCell(__('Used IP'));
        $cells .= wf_TableCell(__('Receipt date'));
        $cells .= wf_TableCell(__('Selling'));
        $cells .= wf_TableCell(wf_CheckInput('check', '', false, false));
        $rows = wf_TableRow($cells, 'row1');

        foreach ($allcards as $io => $eachcard) {
            $nameSelling = array_key_exists($eachcard['selling_id'], $selling) ? $selling[$eachcard['selling_id']] : '';
            $cells = wf_TableCell($eachcard['id']);
            $cells .= wf_TableCell($eachcard['part']);
            $cells .= wf_TableCell($eachcard['serial']);
            $cells .= wf_TableCell($eachcard['cash']);
            $cells .= wf_TableCell($eachcard['admin']);
            $cells .= wf_TableCell($eachcard['date']);
            $cells .= wf_TableCell(web_bool_led($eachcard['active']));
            $cells .= wf_TableCell(web_bool_led($eachcard['used']));
            $cells .= wf_TableCell($eachcard['usedate']);
            $cells .= wf_TableCell($eachcard['usedlogin']);
            $cells .= wf_TableCell($eachcard['usedip']);
            $cells .= wf_TableCell($eachcard['receipt_date']);
            $cells .= wf_TableCell($nameSelling);
            $cells .= wf_TableCell(wf_CheckInput('_cards['.$eachcard['id'].']', '', false, false));
            $rows .=  wf_TableRow($cells, 'row3');
        }

        $result = wf_TableBody($rows, '100%', 0);
    }

    $result = web_CardActions($result);

    return $result;
}

/**
 * @param $result
 *
 * @return string
 */
function web_CardActions($result) {
    $cardActions = array(
        'cachangepart' => __('Change'),
        'caprint' => __('Print'),
        'caexport' => __('Export serials'),
        'caactive' => __('Mark as active'),
        'cainactive' => __('Mark as inactive'),
        'cadelete' => __('Delete'),
    );

    $actionSelect = wf_Selector('cardactions', $cardActions, '', '', false);
    $actionSelect .= wf_Submit(__('With selected'));
    $result .= $actionSelect.wf_delimiter();
    $result = wf_Form('', 'POST', $result, '');

    return $result;
}

/**
 * Gets payment card data by its ID
 * 
 * @param int $id
 *
 * @return array
 */
function zb_CardsGetData($id) {
    $id = vf($id, 3);
    $query = "SELECT * from `cardbank` WHERE `id`='".$id."'";
    $result = simple_query($query);

    return $result;
}

/**
 * Marks payment card as inactive
 * 
 * @param int $id
 */
function zb_CardsMarkInactive($id) {
    $id = vf($id, 3);
    $query = "UPDATE `cardbank` SET `active` = '0' WHERE `id` = '".$id."'";
    nr_query($query);
    log_register('CARDS INACTIVE ['.$id.']');
}

/**
 * Marks payment card as active
 * 
 * @param int $id
 */
function zb_CardsMarkActive($id) {
    $id = vf($id, 3);
    $query = "UPDATE `cardbank` SET `active` = '1' WHERE `id` = '".$id."'";
    nr_query($query);
    log_register('CARDS ACTIVE ['.$id.']');
}

/**
 * Delete card from database by its ID
 * 
 * @param int $id
 */
function zb_CardsDelete($id) {
    $id = vf($id, 3);
    $query = "DELETE FROM `cardbank` WHERE `id`='".$id."'";
    nr_query($query);
    log_register('CARDS DELETE ['.$id.']');
}

/**
 * Exports payment card number 
 * 
 * @param int $id
 *
 * @return string
 */
function zb_CardsExport($id) {
    $id = vf($id, 3);
    $carddata = zb_CardsGetData($id);
    $cardnum = $carddata['serial'];
    // i want to templatize it later
    $result = $cardnum;

    return $result;
}

function zb_CardsMassactions() {
    if (isset($_POST['_cards'])) {
        $cardsArr = $_POST['_cards'];
        if (!empty($cardsArr)) {
            //cards change part
            if ($_POST['cardactions'] == 'cachangepart') {
                show_window(__('Change cards'), web_CardsChangeForm(array_keys($cardsArr)));
            }
            //cards export
            if ($_POST['cardactions'] == 'caexport') {
                $exportdata = '';
                foreach ($cardsArr as $cardid => $on) {
                    $exportdata .= zb_CardsExport($cardid)."\n";
                }

                $exportresult = wf_TextArea($exportdata, '', $exportdata, true, '80x20');
                show_window(__('Export'), $exportresult);
            }
            //cards activate
            if ($_POST['cardactions'] == 'caactive') {
                foreach ($cardsArr as $cardid => $on) {
                    zb_CardsMarkActive($cardid);
                }
            }
            //cards deactivate
            if ($_POST['cardactions'] == 'cainactive') {
                foreach ($cardsArr as $cardid => $on) {
                    zb_CardsMarkInactive($cardid);
                }
            }
            //cards delete
            if ($_POST['cardactions'] == 'cadelete') {
                foreach ($cardsArr as $cardid => $on) {
                    zb_CardsDelete($cardid);
                }
            }
        } else {
            show_error(__('No cards selected'));
        }
    } else {
        show_error(__('No cards selected'));
    }
}

/**
 * Returns payment card brutes attempts list
 * 
 * @return string
 */
function web_CardShowBrutes() {
    $query = 'SELECT * from `cardbrute`';
    $allbrutes = simple_queryall($query);

    $cells = wf_TableCell(__('ID'));
    $cells .= wf_TableCell(__('Serial part'));
    $cells .= wf_TableCell(__('Serial number'));
    $cells .= wf_TableCell(__('Date'));
    $cells .= wf_TableCell(__('Login'));
    $cells .= wf_TableCell(__('IP'));
    $cells .= wf_TableCell(__('Full address'));
    $cells .= wf_TableCell(__('Real Name'));
    $rows = wf_TableRow($cells, 'row1');

    if (!empty($allbrutes)) {
        $allrealnames = zb_UserGetAllRealnames();
        $alladdress = zb_AddressGetFulladdresslist();
        foreach ($allbrutes as $io => $eachbrute) {
            $cleaniplink = wf_JSAlert('?module=cards&cleanip='.$eachbrute['ip'], web_delete_icon(__('Clean this IP')), __('Removing this may lead to irreparable results'));

            $cells = wf_TableCell($eachbrute['id']);
            $cells .= wf_TableCell(array_key_exists('part', $eachbrute) ? $eachbrute['part'] :'');
            $cells .= wf_TableCell($eachbrute['serial']);
            $cells .= wf_TableCell($eachbrute['date']);
            $cells .= wf_TableCell(wf_Link('?module=userprofile&username='.$eachbrute['login'], web_profile_icon().' '.$eachbrute['login']));
            $cells .= wf_TableCell($eachbrute['ip'].' '.$cleaniplink);
            $cells .= wf_TableCell(@$alladdress[$eachbrute['login']]);
            $cells .= wf_TableCell(@$allrealnames[$eachbrute['login']]);
            $rows .=  wf_TableRow($cells, 'row3');
        }
    }

    $result = wf_TableBody($rows, '100%', 0, 'sortable');
    $cleanAllLink = wf_JSAlert('?module=cards&cleanallbrutes=true', wf_img('skins/icon_cleanup.png', __('Cleanup')), 'Are you serious');
    show_window(__('Bruteforce attempts').' '.$cleanAllLink, $result);

    return $result;
}

/**
 * Deletes some brute attempt by target IP
 * 
 * @param string $ip
 *
 * @return void
 */
function zb_CardBruteCleanIP($ip) {
    $query = "DELETE from `cardbrute` where `ip`='".$ip."'";
    nr_query($query);
    log_register('CARDBRUTE DELETE `'.$ip.'`');
}

/**
 * Deletes all brute attempts
 * 
 * @return void
 */
function zb_CardBruteCleanupAll() {
    $query = 'TRUNCATE TABLE `cardbrute`;';
    nr_query($query);
    log_register('CARDBRUTE CLEANUP');
}

/**
 * Update card part
 *
 * @param int   $part
 * @param int   $selling
 * @param array $ids
 */
function zb_CardChange($part, $selling, $ids) {
    if ($part) {
        zb_CardChangePart($part, $ids);
    }
    if ($selling) {
        zb_CardChangeSelling($selling, $ids);
    }
}

/**
 * Update card part
 *
 * @param int   $part
 * @param array $ids
 */
function zb_CardChangePart($part, $ids) {
    foreach ($ids as $key => $id) {
        $ids[$key] = vf($id, 3);
    }

    $ids = implode(',', $ids);
    $query = sprintf("UPDATE `cardbank` SET `part` = '%s' WHERE `id` in (%s)", $part, $ids);
    nr_query($query);
    log_register(sprintf('CARDS UPDATE [%s] part %s', $ids, $part));
}

/**
 * Update card selling
 *
 * @param int   $selling
 * @param array $ids
 */
function zb_CardChangeSelling($selling, array $ids) {
    foreach ($ids as $key => $id) {
        $ids[$key] = vf($id, 3);
    }

    $ids = implode(',', $ids);
    $query = sprintf("UPDATE `cardbank` SET `selling_id` = '%s', `receipt_date` = '%s' WHERE `id` in (%s)", $selling, curdatetime(), $ids);
    nr_query($query);
    log_register(sprintf('CARDS UPDATE [%s] selling %s', $ids, $selling));
}

/**
 * Select card selling
 *
 * @param array $ids
 *
 * @return array|string
 */
function zb_GetCardByIds(array $ids) {
    foreach ($ids as $key => $id) {
        $ids[$key] = vf($id, 3);
    }

    $ids = implode(',', $ids);
    $query = sprintf("SELECT * from `cardbank` WHERE `id` in (%s)", $ids);
    $selectCards = simple_queryall($query);

    return $selectCards;
}

