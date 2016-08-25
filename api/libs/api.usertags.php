<?php

/**
 * backport of user tags API from kvtstg
 */

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
    $cells.= wf_TableCell(__('Color'));
    $cells.= wf_TableCell(__('Priority'));
    $cells.= wf_TableCell(__('Text'));
    $cells.= wf_TableCell(__('Actions'));
    $rows = wf_TableRow($cells, 'row1');

    if (!empty($alltypes)) {
        foreach ($alltypes as $io => $eachtype) {
            $eachtagcolor = $eachtype['tagcolor'];
            $actions = wf_JSAlert('?module=usertags&delete=' . $eachtype['id'], web_delete_icon(), $messages->getDeleteAlert());
            $actions.= wf_JSAlert('?module=usertags&edit=' . $eachtype['id'], web_edit_icon(), $messages->getEditAlert());

            $cells = wf_TableCell($eachtype['id']);
            $cells.= wf_TableCell(wf_tag('font', false, '', 'color="' . $eachtagcolor . '"') . $eachtagcolor . wf_tag('font', true));
            $cells.= wf_TableCell($eachtype['tagsize']);
            $cells.= wf_TableCell($eachtype['tagname']);
            $cells.= wf_TableCell($actions);
            $rows.= wf_TableRow($cells, 'row3');
        }
    }

    $result = wf_TableBody($rows, '100%', 0, 'sortable');

    //construct adding form
    $inputs = wf_ColPicker('newcolor', __('Color'), '#' . rand(11, 99) . rand(11, 99) . rand(11, 99), false, '10');
    $inputs.= wf_TextInput('newtext', __('Text'), '', false, '15');
    $inputs.= web_priority_selector() . ' ';
    $inputs.= wf_HiddenInput('addnewtag', 'true');
    $inputs.= wf_Submit(__('Create'));
    $form = wf_Form("", 'POST', $inputs, 'glamour');
    $result.= $form;


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
    $query = "SELECT * from `tags` WHERE `login`='" . $login . "';";
    $alltags = simple_queryall($query);
    $result = '';
    if (!empty($alltags)) {
        foreach ($alltags as $io => $eachtag) {
            $result.=stg_get_tag_body($eachtag['tagid']);
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
    $inputs.= wf_Submit(__('Save'));
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
            $result.=stg_get_tag_body_deleter($eachtag['tagid'], $login, $eachtag['id']);
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
    $login = mysql_real_escape_string($login);
    $tagid = vf($tagid, 3);
    $query = "INSERT INTO `tags` (`id` ,`login` ,`tagid`) VALUES (NULL , '" . $login . "', '" . $tagid . "'); ";
    nr_query($query);
    stg_putlogevent('TAGADD (' . $login . ') TAGID [' . $tagid . ']');
}

/**
 * Deletes user tag by its ID
 * 
 * @param int $tagid
 * 
 * @return void
 */
function stg_del_user_tag($tagid) {
    $tagid = vf($tagid, 3);
    $query = "DELETE from `tags` WHERE `id`='" . $tagid . "'";
    nr_query($query);
    stg_putlogevent('TAGDEL TAGID [' . $tagid . ']');
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
    $query = "DELETE from `tags` WHERE `login`='" . $login . "' AND`tagid`='" . $tagid . "'";
    nr_query($query);
    stg_putlogevent('TAGDEL LOGIN (' . $login . ') TAGID [' . $tagid . ']');
}

/**
 * Returns tag html preprocessed body
 * 
 * @param int $id
 * @return string
 */
function stg_get_tag_body($id) {
    $id = vf($id, 3);
    $query = "SELECT * from `tagtypes` where `id`='" . $id . "'";
    $tagbody = simple_query($query);

    $result = wf_tag('font', false, '', 'color="' . $tagbody['tagcolor'] . '" size="' . $tagbody['tagsize'] . '"');
    $result.= wf_tag('a', false, '', 'href="?module=tagcloud&tagid=' . $id . '" style="color: ' . $tagbody['tagcolor'] . ';"') . $tagbody['tagname'] . wf_tag('a', true);
    $result.= wf_tag('font', true);
    $result.='&nbsp;';
    return($result);
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

    $result.= wf_tag('font', false, '', 'color="' . $tagbody['tagcolor'] . '" size="' . $tagbody['tagsize'] . '"');
    $result.= $tagbody['tagname'];
    $result.= wf_tag('sup');
    $result.= wf_tag('a', false, '', 'href="?module=usertags&username=' . $login . '&deletetag=' . $tagid . '"') . web_delete_icon() . wf_tag('a', true);
    $result.= wf_tag('sup', true);
    $result.= wf_tag('font', true);
    $result.='&nbsp;';

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
 * @param int  $priority
 */
function zb_VserviceCreate($tagid, $price, $cashtype, $priority) {
    $tagid = vf($tagid, 3);
    $price = vf($price);
    $cashtype = vf($cashtype);
    $priority = vf($priority, 3);
    $query = "INSERT INTO `vservices` (`id` , `tagid` , `price` , `cashtype` , `priority`)
              VALUES (NULL , '" . $tagid . "', '" . $price . "', '" . $cashtype . "', '" . $priority . "');";
    nr_query($query);
    log_register("CREATE VSERVICE [" . $tagid . '] `' . $price . '` [' . $cashtype . '] `' . $priority . '`');
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
    $serviceFeeTypes = array('stargazer' => __('stargazer user cash'), 'virtual' => __('virtual services cash'));
    $inputs = stg_tagid_selector() . wf_tag('br');
    $inputs.= wf_Selector('newcashtype', $serviceFeeTypes, __('Cash type'), '', true);
    $inputs.= web_priority_selector() . wf_tag('br');
    $inputs.= wf_TextInput('newfee', __('Fee'), '', true, '5');
    $inputs.= wf_Submit(__('Create'));
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


        $inputs = wf_Selector('edittagid', $allTags, __('Tag'), $serviceData['tagid'], true);
        $inputs.= wf_Selector('editcashtype', $serviceFeeTypes, __('Cash type'), $serviceData['cashtype'], true);
        $inputs.= wf_Selector('editpriority', $priorities, __('Priority'), $serviceData['priority'], true);
        $inputs.= wf_TextInput('editfee', __('Fee'), $serviceData['price'], true, '5');
        $inputs.= wf_Submit(__('Save'));

        $form = wf_Form("", 'POST', $inputs, 'glamour');
        $form.=wf_Link('?module=vservices', __('Back'), true, 'ubButton');
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
        'Priority'
    );
    $keys = array('id',
        'tagid',
        'price',
        'cashtype',
        'priority'
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
    $newcash = $balance - $fee;
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
 * @param int $debug
 * @param bool $log_payment
 * @param bool $charge_frozen
 * 
 * @return void
 */
function zb_VservicesProcessAll($debug = 0, $log_payment = true, $charge_frozen = true) {
    $frozenUsers = array();
    $query_services = "SELECT * from `vservices` ORDER by `priority` DESC";
    if ($debug) {
        print (">>" . curdatetime() . "\n");
        print (">>Searching virtual services\n");
        print ($query_services . "\n");
    }
    $allservices = simple_queryall($query_services);
    if (!empty($allservices)) {
        if ($debug) {
            print (">>Virtual services found!\n");
            print_r($allservices);
        }

        if ($charge_frozen) {
            if ($debug) {
                print('>>Charge fee from frozen users too' . "\n");
            }
        } else {
            if ($debug) {
                print('>>Frozen users will be skipped' . "\n");
            }
            $frozen_query = "SELECT `login` from `users` WHERE `Passive`='1';";
            if ($debug) {
                print($frozen_query . "\n");
            }
            $allFrozen = simple_queryall($frozen_query);
            if (!empty($allFrozen)) {
                foreach ($allFrozen as $ioFrozen => $eachFrozen) {
                    $frozenUsers[$eachFrozen['login']] = $eachFrozen['login'];
                }
                if ($debug) {
                    print_r($frozenUsers);
                }
            }
        }
        foreach ($allservices as $io => $eachservice) {
            $users_query = "SELECT `login` from `tags` WHERE `tagid`='" . $eachservice['tagid'] . "'";
            if ($debug) {
                print (">>Searching users with this services\n");
                print($users_query . "\n");
            }
            $allusers = simple_queryall($users_query);


            if (!empty($allusers)) {
                if ($debug) {
                    print (">>Users found!\n");
                    print_r($allusers);
                }

                foreach ($allusers as $io2 => $eachuser) {
                    if ($debug) {
                        print (">>Processing user:" . $eachuser['login'] . "\n");
                    }
                    if ($debug) {
                        print (">>service:" . $eachservice['id'] . "\n");
                        print (">>price:" . $eachservice['price'] . "\n");
                        print (">>processing cashtype:" . $eachservice['cashtype'] . "\n");
                    }
                    if ($eachservice['cashtype'] == 'virtual') {
                        if ($debug) {
                            $current_cash = zb_VserviceCashGet($eachuser['login']);
                            print(">>current cash:" . $current_cash . "\n");
                        }
                        if ($debug != 2) {
                            zb_VserviceCashFee($eachuser['login'], $eachservice['price'], $eachservice['id']);
                        }
                    }
                    if ($eachservice['cashtype'] == 'stargazer') {
                        if ($debug) {
                            $current_cash = zb_UserGetStargazerData($eachuser['login']);
                            $current_cash = $current_cash['Cash'];
                            print(">>current cash:" . $current_cash . "\n");
                        }
                        if ($debug != 2) {
                            $fee = "-" . $eachservice['price'];
                            if ($log_payment) {
                                $method = 'add';
                            } else {
                                $method = 'correct';
                            }
                            if ($charge_frozen) {
                                zb_CashAdd($eachuser['login'], $fee, $method, '1', 'Service:' . $eachservice['id']);
                            } else {
                                if (isset($frozenUsers[$eachuser['login']])) {
                                    if ($debug) {
                                        print('>>user frozen - skipping him' . "\n");
                                    }
                                } else {
                                    zb_CashAdd($eachuser['login'], $fee, $method, '1', 'Service:' . $eachservice['id']);
                                }
                            }
                        }
                    }
                }
            }
        }
    }
}

?>