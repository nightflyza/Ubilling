<?php

/**
 * backport of user tags API from kvtstg
 */

/**
 * Returns array of user assigned tags as login=>array of tags with their names
 *
 * @param string $login
 *
 * @return array
 */
function zb_UserGetAllTags($login = '') {
    $result = array();
    $tagTypes = stg_get_alltagnames();
    $queryWhere = (empty($login)) ? '' : " WHERE `login` = '" . $login . "'";
    if (!empty($tagTypes)) {
        $query = "SELECT * from `tags`" . $queryWhere;
        $all = simple_queryall($query);
        if (!empty($all)) {
            foreach ($all as $io => $each) {
                $result[$each['login']][$each['tagid']] = @$tagTypes[$each['tagid']];
            }
        }
    }
    return ($result);
}

/**
 * Returns array of user assigned tags as login => array($dbID => $tagID)
 *
 * This function ensures the uniqueness of selected tags assigned to user
 * if user has one and the same tag assigned multiple times for some reason
 *
 * @param string $login
 * @param string $whereTagID
 *
 * @return array
 */
function zb_UserGetAllTagsUnique($login = '', $whereTagID = '') {
    $result = array();
    $queryWhere = (empty($login)) ? '' : " WHERE `login` = '" . $login . "'";

    if (!empty($whereTagID)) {
        if (empty($queryWhere)) {
            $queryWhere = " WHERE `tagid` IN(" . $whereTagID .")";
        } else {
            $queryWhere.= " AND `tagid` IN(" . $whereTagID .")";
        }
    }

    $query = "SELECT * from `tags`" . $queryWhere;
    $allTags = simple_queryall($query);

    if (!empty($allTags)) {
        foreach ($allTags as $io => $each) {
            $result[$each['login']][$each['id']] = $each['tagid'];
        }
    }

    return ($result);
}

/**
 * Returns tag creation priority selector
 * 
 * @param int $max
 * @return string
 */
function web_priority_selector($max = 6) {
    $params = array_combine(range($max, 1), range($max, 1));
    $result = wf_Selector('newpriority', $params, __('Priority'), '', false);
    return ($result);
}

/**
 * Render available tag types list with all needed controls
 * 
 * @return string
 */
function stg_show_tagtypes() {
    $messages = new UbillingMessageHelper();
    $query = "SELECT * from `tagtypes` ORDER BY `id` ASC";
    $alltypes = simple_queryall($query);

    $cells = wf_TableCell(__('ID'));
    $cells .= wf_TableCell(__('Color'));
    $cells .= wf_TableCell(__('Priority'));
    $cells .= wf_TableCell(__('Text'));
    $cells .= wf_TableCell(__('Actions'));
    $rows = wf_TableRow($cells, 'row1');

    if (!empty($alltypes)) {
        foreach ($alltypes as $io => $eachtype) {
            $eachtagcolor = $eachtype['tagcolor'];
            $actions = wf_JSAlert('?module=usertags&delete=' . $eachtype['id'], web_delete_icon(), $messages->getDeleteAlert());
            $actions .= wf_JSAlert('?module=usertags&edit=' . $eachtype['id'], web_edit_icon(), $messages->getEditAlert());

            $cells = wf_TableCell($eachtype['id']);
            $cells .= wf_TableCell(wf_tag('font', false, '', 'color="' . $eachtagcolor . '"') . $eachtagcolor . wf_tag('font', true));
            $cells .= wf_TableCell($eachtype['tagsize']);
            $cells .= wf_TableCell(wf_Link('?module=tagcloud&tagid=' . $eachtype['id'], $eachtype['tagname']));
            $cells .= wf_TableCell($actions);
            $rows .= wf_TableRow($cells, 'row5');
        }
    }

    $result = wf_TableBody($rows, '100%', 0, 'sortable');

    //construct adding form
    $inputs = wf_ColPicker('newcolor', __('Color'), '#' . rand(11, 99) . rand(11, 99) . rand(11, 99), false, '10');
    $inputs .= wf_TextInput('newtext', __('Text'), '', false, '15');
    $inputs .= web_priority_selector() . ' ';
    $inputs .= wf_HiddenInput('addnewtag', 'true');
    $inputs .= wf_Submit(__('Create'));
    $form = wf_Form("", 'POST', $inputs, 'glamour');
    $result .= $form;


    return ($result);
}

/**
 * Creates new tag type in database 
 * 
 * @return void
 */
function stg_add_tagtype() {
    $color = mysql_real_escape_string($_POST['newcolor']);
    $size = vf($_POST['newpriority'], 3);
    $text = mysql_real_escape_string($_POST['newtext']);
    $query = "INSERT INTO `tagtypes` (`id` ,`tagcolor` ,`tagsize` ,`tagname`) VALUES (NULL , '" . $color . "', '" . $size . "', '" . $text . "');";
    nr_query($query);
    $newId = simple_get_lastid('tagtypes');
    log_register('TAGTYPE ADD `' . $text . '` [' . $newId . ']');
}

/**
 * Deletes tag type from database
 * 
 * @param int $tagid
 */
function stg_delete_tagtype($tagid) {
    $tagid = vf($tagid, 3);
    $query = "DELETE from `tagtypes` WHERE `id`='" . $tagid . "'";
    nr_query($query);
    log_register('TAGTYPE DELETE [' . $tagid . ']');
    $query = "UPDATE `employee` SET `tagid` = NULL WHERE `employee`.`tagid` = '" . $tagid . "'";
    nr_query($query);
    $query = "DELETE from `tags` WHERE `tagid`='" . $tagid . "';";
    nr_query($query);
    log_register('TAGTYPE FLUSH [' . $tagid . ']');
}

/**
 * Returns array of available tagtypes as id=>name
 * 
 * @return array
 */
function stg_get_alltagnames() {
    $query = "SELECT * from `tagtypes`";
    $alltagtypes = simple_queryall($query);
    $result = array();
    if (!empty($alltagtypes)) {
        foreach ($alltagtypes as $io => $eachtype) {
            $result[$eachtype['id']] = $eachtype['tagname'];
        }
    }
    return($result);
}

/**
 * Returns array of some tag type data
 * 
 * @param int $tagtypeid
 * @return array
 */
function stg_get_tagtype_data($tagtypeid) {
    $tagtypeid = vf($tagtypeid, 3);
    $query = "SELECT * from `tagtypes` WHERE `id`='" . $tagtypeid . "'";
    $result = simple_query($query);
    return($result);
}

/**
 * Returns user applied tags as browsable html
 * 
 * @param string $login
 * @return string
 */
function stg_show_user_tags($login) {
    $query = "SELECT * from `tags` INNER JOIN (SELECT * from `tagtypes`) AS tt ON (`tags`.`tagid`=`tt`.`id`) LEFT JOIN (SELECT `mobile`,`tagid` AS emtag FROM `employee` WHERE `tagid` != '') as tem ON (`tags`.`tagid`=`tem`.`emtag`) WHERE `login`='" . $login . "';";
    $alltags = simple_queryall($query);
    $result = '';
    if (!empty($alltags)) {

        foreach ($alltags as $io => $eachtag) {
                $emploeeMobile = ($eachtag['mobile']) ? wf_modal(wf_img('skins/icon_mobile.gif', $eachtag['tagname']),  $eachtag['tagname'] . ' - ' . __('Mobile'),  $eachtag['mobile'], '', 400, 200) : '';
                $result .= wf_tag('font', false, '', 'color="' . $eachtag['tagcolor'] . '" size="' . $eachtag['tagsize'] . '"');
                $result .= wf_tag('a', false, '', 'href="?module=tagcloud&tagid=' . $eachtag['tagid'] . '" style="color: ' . $eachtag['tagcolor'] . ';"') . $eachtag['tagname'] . wf_tag('a', true);
                $result .= $emploeeMobile;
                $result .= wf_tag('font', true);
                $result .= '&nbsp;';
        }
    }
    return ($result);
}

/**
 * Shows tag addition dialogue
 * 
 * @return void
 */
function stg_tagadd_selector() {
    $query = "SELECT * from `tagtypes` ORDER by `id` ASC";
    $alltypes = simple_queryall($query);
    $tagArr = array();
    if (!empty($alltypes)) {
        foreach ($alltypes as $io => $eachtype) {
            $tagArr[$eachtype['id']] = $eachtype['tagname'];
        }
    }

    $inputs = wf_Selector('tagselector', $tagArr, '', '', false);
    $inputs .= wf_Submit(__('Save'));
    $result = wf_Form('', 'POST', $inputs, '');

    show_window(__('Add tag'), $result);
}

/**
 * Returns tag id selector 
 * 
 * @return string
 */
function stg_tagid_selector() {
    $query = "SELECT * from `tagtypes`";
    $alltypes = simple_queryall($query);
    $tmpArr = array();
    if (!empty($alltypes)) {
        foreach ($alltypes as $io => $eachtype) {
            $tmpArr[$eachtype['id']] = $eachtype['tagname'];
        }
    }

    $result = wf_Selector('newtagid', $tmpArr, __('Tag'), '', false);
    return ($result);
}

/**
 * shows tag deletion controls
 * 
 * @param string $login
 * 
 * @return void
 */
function stg_tagdel_selector($login) {
    $login = vf($login);
    $query = "SELECT * from `tags` where `login`='" . $login . "'";
    $usertags = simple_queryall($query);
    $result = '';
    if (!empty($usertags)) {
        foreach ($usertags as $io => $eachtag) {
            $result .= stg_get_tag_body_deleter($eachtag['tagid'], $login, $eachtag['id']);
        }
    }
    show_window(__('Delete tag'), $result);
}

/**
 * Assosicates some tag with existing user login
 * 
 * @param string $login
 * @param int $tagid
 * 
 * @return void
 */
function stg_add_user_tag($login, $tagid) {
    $login = ubRouting::filters($login, 'mres');
    $tagid = ubRouting::filters($tagid, 'int');
    $tagsDb = new nya_tags();

    $tagsDb->data('login', $login);
    $tagsDb->data('tagid', $tagid);
    $tagsDb->create();

    log_register('TAGADD (' . $login . ') TAGID [' . $tagid . ']');
}

/**
 * Deletes user tag by its ID
 * 
 * @param int $tagid
 * 
 * @return void
 */
function stg_del_user_tag($tagid) {
    $tagid = ubRouting::filters($tagid, 'int');
    $tagsDb = new nya_tags();
    $tagsDb->where('id', '=', $tagid);
    $tagData = $tagsDb->getAll();
    if (!empty($tagData)) {
        $tagLogin = $tagData[0]['login'];
        $tagType = $tagData[0]['tagid'];
        $tagsDb->where('id', '=', $tagid);
        $tagsDb->delete();
        log_register('TAGDEL (' . $tagLogin . ') TAGID [' . $tagType . ']');
    } else {
        log_register('TAGDEL TAGID [' . $tagid . '] FAIL_NOT_EXISTS');
    }
}

/**
 * Deletes user tag by tagid
 *  
 * @param string $login
 * @param int $tagid
 * 
 * @return void
 */
function stg_del_user_tagid($login, $tagid) {
    $login = mysql_real_escape_string($login);
    $tagid = vf($tagid, 3);
    $query = "DELETE from `tags` WHERE `login`='" . $login . "' AND `tagid`='" . $tagid . "'";
    nr_query($query);
    stg_putlogevent('TAGDEL LOGIN (' . $login . ') TAGID [' . $tagid . ']');
}

/**
 * Returns tag data by its ID
 * 
 * @param int $tagid
 * 
 * @return array
 */
function stg_get_tag_data($tagid) {
    $tagid = vf($tagid, 3);
    $query = "SELECT * from `tags` where `id`='" . $tagid . "';";
    $result = simple_query($query);
    return ($result);
}

/**
 * Returns user tag deletion HTML control 
 * 
 * @param int $id
 * @param string $login
 * @param int $tagid
 * @return string
 */
function stg_get_tag_body_deleter($id, $login, $tagid) {
    $query = "SELECT * from `tagtypes` where `id`='" . $id . "'";
    $tagbody = simple_query($query);
    $result = '';

    $result .= wf_tag('font', false, '', 'color="' . $tagbody['tagcolor'] . '" size="' . $tagbody['tagsize'] . '"');
    $result .= $tagbody['tagname'];
    $result .= wf_tag('sup');
    $result .= wf_tag('a', false, '', 'href="?module=usertags&username=' . $login . '&deletetag=' . $tagid . '"') . web_delete_icon() . wf_tag('a', true);
    $result .= wf_tag('sup', true);
    $result .= wf_tag('font', true);
    $result .= '&nbsp;';

    return($result);
}

/**
 * Flushes all tags associated with some user login
 * 
 * @param string $login
 * 
 * @return void
 */
function zb_FlushAllUserTags($login) {
    $login = mysql_real_escape_string($login);
    $query = "DELETE from `tags` WHERE `login`='" . $login . "'";
    nr_query($query);
    log_register("TAG FLUSH (" . $login . ")");
}

/**
 * Creates new virtual service
 * 
 * @param int $tagid
 * @param float $price
 * @param string $cashtype
 * @param int $priority
 * @param int $feechargealways
 * @param int $feechargeperiod
 */
function zb_VserviceCreate($tagid, $price, $cashtype, $priority, $feechargealways = 0, $feechargeperiod = 0) {
    $tagid = vf($tagid, 3);
    $price = mysql_real_escape_string($price);
    $cashtype = vf($cashtype);
    $priority = vf($priority, 3);
    $feechargeperiod = vf($feechargeperiod, 3);

    $query = "INSERT INTO `vservices` (`id` , `tagid` , `price` , `cashtype` , `priority`, `fee_charge_always`, charge_period_days)
              VALUES (NULL , '" . $tagid . "', '" . $price . "', '" . $cashtype . "', '" . $priority . "', '" . $feechargealways . "', " . $feechargeperiod . ");";
    nr_query($query);
    log_register("CREATE VSERVICE [" . $tagid . '] `' . $price . '` [' . $cashtype . '] `' . $priority . '` [' . $feechargealways . '] `' . '` [' . $feechargeperiod . '] `');
}

/**
 * Edits virtual service
 *
 * @param int $vserviceID
 * @param int $tagid
 * @param float $price
 * @param string $cashtype
 * @param int  $priority
 * @param int $feechargealways
 * @param int $feechargeperiod
 */
function zb_VserviceEdit($vserviceID, $tagid, $price, $cashtype, $priority, $feechargealways = 0, $feechargeperiod = 0) {
    $tagid = vf($tagid, 3);
    $price = mysql_real_escape_string($price);
    $cashtype = vf($cashtype);
    $priority = vf($priority, 3);
    $feechargeperiod = vf($feechargeperiod, 3);

    $query = "UPDATE `vservices` SET 
                    `tagid` = " . $tagid . ",   
                    `price` = " . $price . ", 
                    `cashtype` = '" . $cashtype . "', 
                    `priority` = " . $priority . ", 
                    `fee_charge_always` = " . $feechargealways . ",
                    `charge_period_days` = " . $feechargeperiod . "
                WHERE `id` = " . $vserviceID;
    nr_query($query);

    log_register("CHANGE VSERVICE [" . $vserviceID . "] PRICE `" . $price . "`");
}

/**
 * Deletes virtual service from database
 * 
 * @param int $vservid
 */
function zb_VsericeDelete($vservid) {
    $vservid = vf($vservid, 3);
    $query = "DELETE from `vservices` where `id`='" . $vservid . "'";
    nr_query($query);
    log_register("DELETE VSERVICE [" . $vservid . "]");
}

/**
 * Gets all available virtual services from database
 * 
 * @return string
 */
function zb_VserviceGetAllData() {
    $query = "SELECT * from `vservices`";
    $result = array();
    $result = simple_queryall($query);
    return ($result);
}

/**
 * Returns array of virtual services as id=>tagname
 * 
 * @return array
 */
function zb_VservicesGetAllNames() {
    $result = array();
    $allservices = zb_VserviceGetAllData();
    $alltagnames = stg_get_alltagnames();
    if (!empty($allservices)) {
        foreach ($allservices as $io => $eachservice) {
            @$result[$eachservice['id']] = $alltagnames[$eachservice['tagid']];
        }
    }
    return ($result);
}

/**
 * Returns array of available virtualservices as Service:id=>tagname
 * 
 * @return array
 */
function zb_VservicesGetAllNamesLabeled() {
    $result = array();
    $allservices = zb_VserviceGetAllData();
    $alltagnames = stg_get_alltagnames();
    if (!empty($allservices)) {
        foreach ($allservices as $io => $eachservice) {
            @$result['Service:' . $eachservice['id']] = $alltagnames[$eachservice['tagid']];
        }
    }
    return ($result);
}

/**
 * Returns virtual service creation form
 * 
 * @return string
 */
function web_VserviceAddForm() {
    //$FeeIsChargedAlways = false;
    $serviceFeeTypes = array('stargazer' => __('stargazer user cash'), 'virtual' => __('virtual services cash'));
    $inputs = stg_tagid_selector() . wf_tag('br');
    $inputs .= wf_Selector('newcashtype', $serviceFeeTypes, __('Cash type'), '', true);
    $inputs .= web_priority_selector() . wf_tag('br');
    $inputs .= wf_TextInput('newfee', __('Fee'), '', true, '5');
    $inputs .= wf_TextInput('newperiod', __('Charge period in days'), '', true, '5', 'digits');
    $inputs .= wf_CheckInput('feechargealways', __('Always charge fee, even if balance cash < 0'), true, false);
    $inputs .= wf_Submit(__('Create'));
    $form = wf_Form("", 'POST', $inputs, 'glamour');
    return($form);
}

/**
 * Returns virtual service editing form
 * 
 * @param int $vserviceid
 * @return string
 * @throws Exception
 */
function web_VserviceEditForm($vserviceid) {
    $vserviceid = vf($vserviceid, 3);
    $allservicesRaw = zb_VserviceGetAllData();
    $serviceData = array();
    if (!empty($allservicesRaw)) {
        foreach ($allservicesRaw as $io => $each) {
            if ($each['id'] == $vserviceid) {
                $serviceData = $each;
            }
        }
    }
    if (!empty($serviceData)) {
        $serviceFeeTypes = array('stargazer' => __('stargazer user cash'), 'virtual' => __('virtual services cash'));
        $allTags = stg_get_alltagnames();
        $priorities = array();
        for ($i = 6; $i >= 1; $i--) {
            $priorities[$i] = $i;
        }

        $feeIsChargedAlways = ($serviceData['fee_charge_always'] == 1) ? true : false;

        $inputs = wf_Selector('edittagid', $allTags, __('Tag'), $serviceData['tagid'], true);
        $inputs .= wf_Selector('editcashtype', $serviceFeeTypes, __('Cash type'), $serviceData['cashtype'], true);
        $inputs .= wf_Selector('editpriority', $priorities, __('Priority'), $serviceData['priority'], true);
        $inputs .= wf_TextInput('editfee', __('Fee'), $serviceData['price'], true, '5');
        $inputs .= wf_TextInput('editperiod', __('Charge period in days'), $serviceData['charge_period_days'], true, '5', 'digits');
        $inputs .= wf_CheckInput('editfeechargealways', __('Always charge fee, even if balance cash < 0'), true, $feeIsChargedAlways);
        $inputs .= wf_Submit(__('Save'));

        $form = wf_Form("", 'POST', $inputs, 'glamour');
        $form .= wf_BackLink('?module=vservices');
        return($form);
    } else {
        throw new Exception('NOT_EXISTING_VSERVICE_ID');
    }
}

/**
 * Shows available virtual services list with some controls
 * 
 * @return void
 */
function web_VservicesShow() {
    $allvservices = zb_VserviceGetAllData();
    $tagtypesquery = "SELECT * from `tagtypes`";
    $alltagtypes = simple_queryall($tagtypesquery);

    //construct editor
    $titles = array(
        'ID',
        'Tag',
        'Fee',
        'Cash type',
        'Priority',
        'Always charge fee',
        'Charge period in days'
    );
    $keys = array('id',
        'tagid',
        'price',
        'cashtype',
        'priority',
        'fee_charge_always',
        'charge_period_days'
    );
    show_window(__('Virtual services'), web_GridEditorVservices($titles, $keys, $allvservices, 'vservices', true, true));
    if (!empty($alltagtypes)) {
        show_window(__('Add virtual service'), web_VserviceAddForm());
    }
}

/**
 * Flushes virtual cash account for some user
 * 
 * @param string $login
 * 
 * @return void
 */
function zb_VserviceCashClear($login) {
    $login = vf($login);
    $query = "DELETE from `vcash` where `login`='" . $login . "'";
    nr_query($query);
}

/**
 * Creates new vcash account for user
 * 
 * @param string $login
 * @param float $cash
 * 
 * @return void
 */
function zb_VserviceCashCreate($login, $cash) {
    $login = vf($login);
    $cash = mysql_real_escape_string($cash);
    $query_set = "INSERT INTO `vcash` (`id` , `login` , `cash`) VALUES (NULL , '" . $login . "', '" . $cash . "');";
    nr_query($query_set);
    log_register("ADD VCASH (" . $login . ") `" . $cash . "`");
}

/**
 * Sets virtual account cash for some login
 * 
 * @param string $login
 * @param float $cash
 * 
 * @return void
 */
function zb_VserviceCashSet($login, $cash) {
    $login = vf($login);
    $cash = mysql_real_escape_string($cash);
    $query_set = "UPDATE `vcash` SET `cash` = '" . $cash . "' WHERE `login` ='" . $login . "' LIMIT 1 ;";
    nr_query($query_set);
    log_register("CHANGE VCASH (" . $login . ") `" . $cash . "`");
}

/**
 * Returns virtual account cash amount for some login
 * 
 * @param string $login
 * @return float
 * 
 * @return void
 */
function zb_VserviceCashGet($login) {
    $login = vf($login);
    $query = "SELECT `cash` from `vcash` WHERE `login`='" . $login . "'";
    $result = simple_query($query);
    if (empty($result)) {
        $result = 0;
        zb_VserviceCashCreate($login, 0);
    } else {
        $result = $result['cash'];
    }
    return($result);
}

/**
 * Pushes an record into vcash log
 * 
 * @param string $login
 * @param float $balance
 * @param float $cash
 * @param type $cashtype
 * @param type $note
 * 
 * @return void
 */
function zb_VserviceCashLog($login, $balance, $cash, $cashtype, $note = '') {
    $login = vf($login);
    $cash = mysql_real_escape_string($cash);
    $cashtype = vf($cashtype);
    $note = mysql_real_escape_string($note);
    $date = curdatetime();
    $balance = zb_VserviceCashGet($login);
    $query = "INSERT INTO `vcashlog` ( `id` ,  `login` , `date` , `balance` , `summ` , `cashtypeid` , `note`)
              VALUES (NULL , '" . $login . "', '" . $date . "', '" . $balance . "', '" . $cash . "', '" . $cashtype . "', '" . $note . "');";
    nr_query($query);
}

/**
 * Performs an vcash fee
 * 
 * @param string $login
 * @param float $fee
 * @param int $vserviceid
 * 
 * @return void
 */
function zb_VserviceCashFee($login, $fee, $vserviceid) {
    $login = vf($login);
    $fee = vf($fee);
    $balance = zb_VserviceCashGet($login);
    if ($fee >= 0) {
        $newcash = $balance - $fee;
    } else {
        $newcash = $balance + abs($fee);
    }
    zb_VserviceCashSet($login, $newcash);
    zb_VserviceCashLog($login, $balance, $newcash, $vserviceid);
}

/**
 * Adds cash to virtual cash balance
 * 
 * @param string $login
 * @param float $cash
 * @param int $vserviceid
 * 
 * @return void
 */
function zb_VserviceCashAdd($login, $cash, $vserviceid) {
    $login = vf($login);
    $cash = mysql_real_escape_string($cash);
    $balance = zb_VserviceCashGet($login);
    $newcash = $balance + $cash;
    zb_VserviceCashSet($login, $newcash);
    zb_VserviceCashLog($login, $balance, $newcash, $vserviceid);
}

/**
 * Returns virtual service selector
 * 
 * @return string
 */
function web_VservicesSelector() {
    $allservices = zb_VserviceGetAllData();
    $alltags = stg_get_alltagnames();
    $tmpArr = array();
    if (!empty($allservices)) {
        foreach ($allservices as $io => $eachservice) {
            $tmpArr[$eachservice['id']] = @$alltags[$eachservice['tagid']];
        }
    }

    $result = wf_Selector('vserviceid', $tmpArr, '', '', false);
    return ($result);
}

/**
 * Performs an virtual services payments processing
 * 
 * @param bool $log_payment
 * @param bool $charge_frozen
 * @param string $whereString
 *
 * @return void
 */
function zb_VservicesProcessAll($log_payment = true, $charge_frozen = true, $whereString = '') {
    global $ubillingConfig;
    $alterconf = $ubillingConfig->getAlter();
    $frozenUsers = array();
    $query_services = "SELECT * from `vservices` " . $whereString . " ORDER by `priority` DESC";

    $allUserData = zb_UserGetAllStargazerDataAssoc();
    $paymentTypeId = 1;
    //custom payment type ID optional option
    if (isset($alterconf['VSERVICES_CASHTYPEID'])) {
        if (!empty($alterconf['VSERVICES_CASHTYPEID'])) {
            $paymentTypeId = $alterconf['VSERVICES_CASHTYPEID'];
        }
    }

    $allservices = simple_queryall($query_services);

    if (!empty($allservices)) {
        if (!$charge_frozen) {
            $frozen_query = "SELECT `login` from `users` WHERE `Passive`='1';";
            $allFrozen = simple_queryall($frozen_query);
            if (!empty($allFrozen)) {
                foreach ($allFrozen as $ioFrozen => $eachFrozen) {
                    $frozenUsers[$eachFrozen['login']] = $eachFrozen['login'];
                }
            }
        }

        foreach ($allservices as $io => $eachservice) {
            $users_query = "SELECT `login` from `tags` WHERE `tagid`='" . $eachservice['tagid'] . "'";
            $allusers = simple_queryall($users_query);

            if (!empty($allusers)) {
                foreach ($allusers as $io2 => $eachuser) {
                    //virtual cash charging (DEPRECATED)
                    if ($eachservice['cashtype'] == 'virtual') {
                        $current_cash = zb_VserviceCashGet($eachuser['login']);
                        $FeeChargeAllowed = ($current_cash < 0 AND $eachservice['fee_charge_always'] == 0) ? false : true;

                        if ($FeeChargeAllowed) {
                            zb_VserviceCashFee($eachuser['login'], $eachservice['price'], $eachservice['id']);
                        }
                    }
                    //stargazer balance charging
                    if ($eachservice['cashtype'] == 'stargazer') {
                        $current_cash = $allUserData[$eachuser['login']]['Cash'];
                        $FeeChargeAllowed = ($current_cash < 0 AND $eachservice['fee_charge_always'] == 0) ? false : true;

                        if ($FeeChargeAllowed) {
                            $fee = $eachservice['price'];
                            if ($fee >= 0) {
                                //charge cash from user balance
                                $fee = "-" . $eachservice['price'];
                            } else {
                                //add some cash to balance
                                $fee = abs($eachservice['price']);
                            }
                            if ($log_payment) {
                                $method = 'add';
                            } else {
                                $method = 'correct';
                            }
                            if ($charge_frozen) {
                                zb_CashAdd($eachuser['login'], $fee, $method, $paymentTypeId, 'Service:' . $eachservice['id']);
                                $allUserData[$eachuser['login']]['Cash'] += $fee; //updating preloaded cash values
                            } else {
                                if (!isset($frozenUsers[$eachuser['login']])) {
                                    zb_CashAdd($eachuser['login'], $fee, $method, $paymentTypeId, 'Service:' . $eachservice['id']);
                                    $allUserData[$eachuser['login']]['Cash'] += $fee; //updating preloaded cash values
                                }
                            }
                        }
                    }
                }
            }
        }
    }
}

/**
 * Returns array of all available virtual services as tagid=>price
 * 
 * @return array
 */
function zb_VservicesGetAllPrices() {
    $result = array();
    $query = "SELECT * from `vservices`";
    $all = simple_queryall($query);
    if (!empty($all)) {
        foreach ($all as $io => $each) {
            $result[$each['tagid']] = $each['price'];
        }
    }
    return ($result);
}

/**
 * Returns array of all available virtual services as tagid => array('price' => $price, 'period' => $period);
 *
 * @return array
 */
function zb_VservicesGetAllPricesPeriods() {
    $result = array();
    $query = "SELECT * from `vservices`";
    $all = simple_queryall($query);

    if (!empty($all)) {
        foreach ($all as $io => $each) {
            $result[$each['tagid']] = array('price' => $each['price'], 'daysperiod' => $each['charge_period_days']);
        }
    }

    return ($result);
}

/**
 * Returns price summary of all virtual services fees assigned to user
 * 
 * @param string $login
 * 
 * @return float
 */
function zb_VservicesGetUserPrice($login) {
    $result = 0;
    $allUserTags = zb_UserGetAllTags();
    //user have some tags assigned
    if (isset($allUserTags[$login])) {
        if (!empty($allUserTags[$login])) {
            $vservicePrices = zb_VservicesGetAllPrices();
            foreach ($allUserTags[$login] as $tagId => $tagName) {
                if (isset($vservicePrices[$tagId])) {
                    $result += $vservicePrices[$tagId];
                }
            }
        }
    }
    return ($result);
}

/**
 * Returns price total of all virtual services fees assigned to user, considering services fee charge periods
 * $defaultPeriod - specifies the default period to count the prices for: 'month' or 'day', if certain service has no fee charge period set
 *
 * @param string $login
 * @param string $defaultPeriod
 *
 * @return float
 */
function zb_VservicesGetUserPricePeriod($login, $defaultPeriod = 'month') {
    $totalVsrvPrice = 0;
    $allUserVsrvs = zb_VservicesGetUsersAll($login, true);
    $curMonthDays = date('t');

    if (!empty($allUserVsrvs)) {
        $allUserVsrvs = $allUserVsrvs[$login];

        foreach ($allUserVsrvs as $eachTagDBID => $eachSrvData) {
            $curVsrvPrice       = $eachSrvData['price'];
            $curVsrvDaysPeriod  = $eachSrvData['daysperiod'];
            $dailyVsrvPrice     = 0;

            // getting daily vservice price
            if (!empty($curVsrvDaysPeriod)) {
                $dailyVsrvPrice = ($curVsrvDaysPeriod > 1) ? $curVsrvPrice / $curVsrvDaysPeriod : $curVsrvPrice;
            }

            // if vservice has no charge period set and $dailyVsrvPrice == 0
            // then virtual service price is considered as for global $defaultPeriod period
            if ($defaultPeriod == 'month') {
                $totalVsrvPrice+= (empty($dailyVsrvPrice)) ? $curVsrvPrice : $dailyVsrvPrice * $curMonthDays;
            } else {
                $totalVsrvPrice+= (empty($dailyVsrvPrice)) ? $curVsrvPrice : $dailyVsrvPrice;
            }
        }
    }

    return ($totalVsrvPrice);
}

/**
 * Returns all users with assigned virtual services as array:
 *         login => array($tagDBID => vServicePrice1)
 *
 * if $includePeriod is true returned array will look like this:
 *          login => array($tagDBID => array('price' => vServicePrice1, 'daysperiod' => vServicePeriod1))
 *
 * if $includeVSrvName is true 'vsrvname' => tagname is added to the end of the array
 *
 * @param string $login
 * @param bool $includePeriod
 * @param bool $includeVSrvName
 *
 * @return array
 */
function zb_VservicesGetUsersAll($login = '', $includePeriod = false, $includeVSrvName = false) {
    $result = array();
    $allTagNames = array();
    $allUserTags = zb_UserGetAllTagsUnique($login);

    if ($includeVSrvName) {
        $allTagNames = stg_get_alltagnames();
    }

    //user have some tags assigned
    if (!empty($allUserTags)) {
        $vservicePrices = ($includePeriod) ? zb_VservicesGetAllPricesPeriods() : zb_VservicesGetAllPrices();

        foreach ($allUserTags as $eachLogin => $data) {
            $tmpArr = array();

            foreach ($data as $tagDBID => $tagID) {
                if (isset($vservicePrices[$tagID])) {
                    if ($includeVSrvName) {
                        $tmpArr[$tagDBID] = $vservicePrices[$tagID] + array('vsrvname' => $allTagNames[$tagID]);
                    } else {
                        $tmpArr[$tagDBID] = $vservicePrices[$tagID];
                    }
                }
            }

            if (!empty($tmpArr)) {
                $result[$eachLogin] = $tmpArr;
            }
        }
    }

    return ($result);
}

?>