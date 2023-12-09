<?php

/**
 * Deletes virtual service from database
 * 
 * @param int $vservId
 * 
 * @return void
 */
function zb_VsericeDelete($vservId) {
    $vservId = ubRouting::filters($vservId, 'int');

    $vservDb = new NyanORM('vservices');
    $vservDb->where('id', '=', $vservId);
    $vservDb->delete();

    log_register('VSERVICE DELETE [' . $vservId . ']');
}

/**
 * Gets all available virtual services from database
 * 
 * @return array
 */
function zb_VserviceGetAllData() {
    $vservDb = new NyanORM('vservices');
    $result = $vservDb->getAll();
    return ($result);
}

/**
 * Returns array of virtual services names as vserviceId=>tagName
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
    global $ubillingConfig;
    $serviceFeeTypes = array('stargazer' => __('stargazer user cash'));
    if ($ubillingConfig->getAlterParam('VCASH_ENABLED')) {
        $serviceFeeTypes['virtual'] = __('virtual services cash');
    }
    $inputs = stg_tagid_selector() . wf_tag('br');
    $inputs .= wf_Selector('newcashtype', $serviceFeeTypes, __('Cash type'), '', true);
    $inputs .= web_priority_selector() . wf_tag('br');
    $inputs .= wf_TextInput('newfee', __('Fee'), '', true, '5');
    $inputs .= wf_TextInput('newperiod', __('Charge period in days'), '', true, '5', 'digits');
    $inputs .= wf_CheckInput('feechargealways', __('Always charge fee, even if balance cash < 0'), true, false);
    $inputs .= wf_Submit(__('Create'));
    $result = wf_Form("", 'POST', $inputs, 'glamour');
    return($result);
}

/**
 * Returns virtual service editing form
 * 
 * @param int $vserviceId
 * 
 * @return string
 */
function web_VserviceEditForm($vserviceId) {
    $vserviceId = ubRouting::filters($vserviceId, 'int');
    $allservicesRaw = zb_VserviceGetAllData();
    $serviceData = array();
    $result = '';
    if (!empty($allservicesRaw)) {
        foreach ($allservicesRaw as $io => $each) {
            if ($each['id'] == $vserviceId) {
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

        $result .= wf_Form("", 'POST', $inputs, 'glamour');
        $result .= wf_delimiter();
        $result .= wf_BackLink('?module=vservices');
    } else {
        $messages = new UbillingMessageHelper();
        $result .= $messages->getStyledMessage(__('Virtual service') . ' [' . $vserviceId . '] ' . __('Not exists'), 'error');
        $result .= wf_delimiter();
        $result .= wf_BackLink('?module=vservices');
    }
    return($result);
}

/**
 * Shows available virtual services list with some controls
 * 
 * @return void
 */
function web_VservicesShow() {
    $allvservices = zb_VserviceGetAllData();
    $allTagTypes = stg_get_alltagnames();

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

    show_window(__('Virtual services'), web_GridEditorVservices($titles, $keys, $allvservices, 'vservices'));
    if (!empty($allTagTypes)) {
        show_window(__('Add virtual service'), web_VserviceAddForm());
    } else {
        $messages = new UbillingMessageHelper();
        show_window('', $messages->getStyledMessage(__('Any user tags not exists'), 'warning'));
    }
}

/**
 * Returns virtual services editor grid
 * 
 * @param array $titles
 * @param array $keys
 * @param array $alldata
 * @param string $module
 * 
 * @return string
 */
function web_GridEditorVservices($titles, $keys, $alldata, $module) {
    $result = '';
    $messages = new UbillingMessageHelper();
    if (!empty($alldata)) {
        $alltagnames = stg_get_alltagnames();
        $cells = '';
        foreach ($titles as $eachtitle) {
            $cells .= wf_TableCell(__($eachtitle));
        }

        $cells .= wf_TableCell(__('Actions'));
        $rows = wf_TableRow($cells, 'row1');

        foreach ($alldata as $io => $eachdata) {
            $cells = '';
            foreach ($keys as $eachkey) {
                if (array_key_exists($eachkey, $eachdata)) {
                    if ($eachkey == 'tagid') {
                        @$tagname = $alltagnames[$eachdata['tagid']];
                        $cells .= wf_TableCell($tagname);
                    } else {
                        if ($eachkey == 'fee_charge_always') {
                            $cells .= wf_TableCell(web_bool_led($eachdata[$eachkey]));
                        } else {
                            $cells .= wf_TableCell($eachdata[$eachkey]);
                        }
                    }
                }
            }

            $delUrl = '?module=' . $module . '&delete=' . $eachdata['id'];
            $cancelUrl = '?module=' . $module;
            $delTitle = __('Delete') . '?';
            $delAlert = __('Delete') . ' ' . __('Virtual service') . ' `' . $tagname . '`? ';
            $delAlert .= $messages->getDeleteAlert();

            $deletecontrol = wf_ConfirmDialog($delUrl, web_delete_icon(), $delAlert, '', $cancelUrl, $delTitle);
            $editcontrol = wf_JSAlert('?module=' . $module . '&edit=' . $eachdata['id'], web_edit_icon(), $messages->getEditAlert());
            $cells .= wf_TableCell($deletecontrol . ' ' . $editcontrol);
            $rows .= wf_TableRow($cells, 'row5');
        }

        $result .= wf_TableBody($rows, '100%', 0, 'sortable');
    } else {
        $result = $messages->getStyledMessage(__('Nothing to show'), 'info');
    }



    return($result);
}

/**
 * Flushes virtual cash account for some user
 * 
 * @param string $login
 * 
 * @return void
 */
function zb_VserviceCashClear($login) {
    $login = ubRouting::filters($login, 'mres');
    $vcashDb = new NyanORM('vcash');
    $vcashDb->where('login', '=', $login);
    $vcashDb->delete();
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
    $loginF = ubRouting::filters($login, 'mres');
    $cashF = ubRouting::filters($cash, 'mres');

    $vcashDb = new NyanORM('vcash');
    $vcashDb->data('login', $loginF);
    $vcashDb->data('cash', $cashF);
    $vcashDb->create();

    log_register('VCASH CREATE (' . $login . ') `' . $cash . '`');
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
    $loginF = ubRouting::filters($login, 'mres');
    $cashF = ubRouting::filters($cash, 'mres');
    $vcashDb = new NyanORM('vcash');
    $vcashDb->data('cash', $cashF);
    $vcashDb->where('login', '=', $loginF);
    $vcashDb->save();
    log_register('VCASH CHANGE (' . $login . ') `' . $cash . '`');
}

/**
 * Returns virtual account cash amount for some login
 * 
 * @param string $login
 * 
 * @return float
 */
function zb_VserviceCashGet($login) {
    $result = 0;
    $loginF = ubRouting::filters($login, 'mres');
    $vcashDb = new NyanORM('vcash');
    $vcashDb->where('login', '=', $loginF);
    $rawCash = $vcashDb->getAll('login');

    if (empty($rawCash)) {
        zb_VserviceCashCreate($login, 0);
    } else {
        $result = $rawCash[$login]['cash'];
    }

    return($result);
}

/**
 * Pushes an record into vcash log
 * 
 * @param string $login
 * @param float $balance
 * @param float $cash
 * @param string $cashtype
 * @param string $note
 * 
 * @return void
 */
function zb_VserviceCashLog($login, $balance, $cash, $cashtype, $note = '') {
    $login = ubRouting::filters($login, 'mres');
    $cash = ubRouting::filters($cash, 'mres');
    $cashtype = ubRouting::filters($cashtype, 'mres');
    $note = ubRouting::filters($note, 'mres');
    $balance = zb_VserviceCashGet($login);

    $vcashLogDb = new NyanORM('vcashlog');
    $vcashLogDb->data('login', $login);
    $vcashLogDb->data('date', curdatetime());
    $vcashLogDb->data('balance', $balance);
    $vcashLogDb->data('summ', $cash);
    $vcashLogDb->data('cashtypeid', $cashtype);
    $vcashLogDb->data('note', $note);
    $vcashLogDb->create();
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
    $login = ubRouting::filters($login, 'mres');
    $fee = ubRouting::filters($fee, 'mres');
    $vserviceid = ubRouting::filters($vserviceid, 'int');
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
    $login = ubRouting::filters($login, 'mres');
    $cash = ubRouting::filters($cash, 'mres');
    $vserviceid = ubRouting::filters($vserviceid, 'int');
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
    $considerCreditFlag = $ubillingConfig->getAlterParam('VSERVICES_CONSIDER_CREDIT', 0);
    $frozenUsers = array();
    $paymentTypeId = 1;
    $allUserData = zb_UserGetAllStargazerDataAssoc();
    $vservDb = new NyanORM('vservices');
    if ($whereString) {
        $vservDb->whereRaw($whereString);
    }
    $vservDb->orderBy('priority', 'DESC');
    $allServices = $vservDb->getAll();

    //custom payment type ID optional option
    if (isset($alterconf['VSERVICES_CASHTYPEID'])) {
        if (!empty($alterconf['VSERVICES_CASHTYPEID'])) {
            $paymentTypeId = $alterconf['VSERVICES_CASHTYPEID'];
        }
    }


    if (!empty($allServices)) {
        if (!$charge_frozen) {
            $frozen_query = "SELECT `login` from `users` WHERE `Passive`='1';";
            $allFrozen = simple_queryall($frozen_query);
            if (!empty($allFrozen)) {
                foreach ($allFrozen as $ioFrozen => $eachFrozen) {
                    $frozenUsers[$eachFrozen['login']] = $eachFrozen['login'];
                }
            }
        }

        foreach ($allServices as $io => $eachService) {
            $users_query = "SELECT `login` from `tags` WHERE `tagid`='" . $eachService['tagid'] . "'";
            $allUsers = simple_queryall($users_query);

            if (!empty($allUsers)) {
                foreach ($allUsers as $io2 => $eachUser) {
                    //virtual cash charging (DEPRECATED)
                    if ($eachService['cashtype'] == 'virtual') {
                        $current_cash = zb_VserviceCashGet($eachUser['login']);
                        $current_credit = $allUserData[$eachUser['login']]['Credit'];
                        //charge fee is allowed?
                        if ($eachService['fee_charge_always']) {
                            $feeChargeAllowed = true;
                        } else {
                            if ($considerCreditFlag) {
                                $feeChargeAllowed = ($current_cash >= '-' . $current_credit) ? true : false;
                            } else {
                                $feeChargeAllowed = ($current_cash > 0) ? true : false;
                            }
                        }

                        if ($feeChargeAllowed) {
                            zb_VserviceCashFee($eachUser['login'], $eachService['price'], $eachService['id']);
                        }
                    }

                    //stargazer balance charging
                    if ($eachService['cashtype'] == 'stargazer') {
                        $current_cash = $allUserData[$eachUser['login']]['Cash'];
                        $current_credit = $allUserData[$eachUser['login']]['Credit'];
                        //charge fee is allowed?
                        if ($eachService['fee_charge_always']) {
                            $feeChargeAllowed = true;
                        } else {
                            if ($considerCreditFlag) {
                                $feeChargeAllowed = ($current_cash >= '-' . $current_credit) ? true : false;
                            } else {
                                $feeChargeAllowed = ($current_cash > 0) ? true : false;
                            }
                        }

                        if ($feeChargeAllowed) {
                            $fee = $eachService['price'];
                            if ($fee >= 0) {
                                //charge cash from user balance
                                $fee = "-" . $eachService['price'];
                            } else {
                                //add some cash to balance
                                $fee = abs($eachService['price']);
                            }
                            if ($log_payment) {
                                $method = 'add';
                            } else {
                                $method = 'correct';
                            }
                            if ($charge_frozen) {
                                zb_CashAdd($eachUser['login'], $fee, $method, $paymentTypeId, 'Service:' . $eachService['id']);
                                $allUserData[$eachUser['login']]['Cash'] += $fee; //updating preloaded cash values
                            } else {
                                if (!isset($frozenUsers[$eachUser['login']])) {
                                    zb_CashAdd($eachUser['login'], $fee, $method, $paymentTypeId, 'Service:' . $eachService['id']);
                                    $allUserData[$eachUser['login']]['Cash'] += $fee; //updating preloaded cash values
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
    $vservDb = new NyanORM('vservices');
    $all = $vservDb->getAll();
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
    $vservDb = new NyanORM('vservices');
    $all = $vservDb->getAll();

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
    $allUserTags = zb_UserGetAllTagsUnique($login);
    //user have some tags assigned?
    if (!empty($allUserTags[$login])) {
        $vservicePrices = zb_VservicesGetAllPrices();
        foreach ($allUserTags[$login] as $tagRecordId => $tagId) {
            if (isset($vservicePrices[$tagId])) {
                $result += $vservicePrices[$tagId];
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
            $curVsrvPrice = $eachSrvData['price'];
            $curVsrvDaysPeriod = $eachSrvData['daysperiod'];
            $dailyVsrvPrice = 0;

            // getting daily vservice price
            if (!empty($curVsrvDaysPeriod)) {
                $dailyVsrvPrice = ($curVsrvDaysPeriod > 1) ? $curVsrvPrice / $curVsrvDaysPeriod : $curVsrvPrice;
            }

            // if vservice has no charge period set and $dailyVsrvPrice == 0
            // then virtual service price is considered as for global $defaultPeriod period
            if ($defaultPeriod == 'month') {
                $totalVsrvPrice += (empty($dailyVsrvPrice)) ? $curVsrvPrice : $dailyVsrvPrice * $curMonthDays;
            } else {
                $totalVsrvPrice += (empty($dailyVsrvPrice)) ? $curVsrvPrice : $dailyVsrvPrice;
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

/**
 * Creates new virtual service
 * 
 * @param int $tagid
 * @param float $price
 * @param string $cashtype
 * @param int $priority
 * @param int $feechargealways
 * @param int $feechargeperiod
 * 
 * @return void
 */
function zb_VserviceCreate($tagid, $price, $cashtype, $priority, $feechargealways = 0, $feechargeperiod = 0) {
    $tagid = ubRouting::filters($tagid, 'int');
    $price = ubRouting::filters($price, 'mres');
    $cashtype = ubRouting::filters($cashtype, 'mres');
    $priority = ubRouting::filters($priority, 'int');
    $feechargealways = ubRouting::filters($feechargealways, 'int');
    $feechargeperiod = ubRouting::filters($feechargeperiod, 'int');

    $vservDb = new NyanORM('vservices');
    $vservDb->data('tagid', $tagid);
    $vservDb->data('price', $price);
    $vservDb->data('cashtype', $cashtype);
    $vservDb->data('priority', $priority);
    $vservDb->data('fee_charge_always', $feechargealways);
    $vservDb->data('charge_period_days', $feechargeperiod);
    $vservDb->create();
    $newId = $vservDb->getLastId();
    log_register('VSERVICE CREATE TAG [' . $tagid . '] PRICE `' . $price . '` [' . $cashtype . '] `' . $priority . '` [' . $feechargealways . '] `' . '` [' . $feechargeperiod . '] ` AS [' . $newId . ']');
}

/**
 * Edits virtual service
 *
 * @param int $vserviceId
 * @param int $tagid
 * @param float $price
 * @param string $cashtype
 * @param int  $priority
 * @param int $feechargealways
 * @param int $feechargeperiod
 * 
 * @return void
 */
function zb_VserviceEdit($vserviceId, $tagid, $price, $cashtype, $priority, $feechargealways = 0, $feechargeperiod = 0) {
    $vserviceId = ubRouting::filters($vserviceId, 'int');
    $tagid = ubRouting::filters($tagid, 'int');
    $price = ubRouting::filters($price, 'mres');
    $cashtype = ubRouting::filters($cashtype, 'mres');
    $priority = ubRouting::filters($priority, 'int');
    $feechargealways = ubRouting::filters($feechargealways, 'int');
    $feechargeperiod = ubRouting::filters($feechargeperiod, 'int');

    $vservDb = new NyanORM('vservices');
    $vservDb->data('tagid', $tagid);
    $vservDb->data('price', $price);
    $vservDb->data('cashtype', $cashtype);
    $vservDb->data('priority', $priority);
    $vservDb->data('fee_charge_always', $feechargealways);
    $vservDb->data('charge_period_days', $feechargeperiod);
    $vservDb->where('id', '=', $vserviceId);
    $vservDb->save();

    log_register('VSERVICE CHANGE [' . $vserviceId . '] PRICE `' . $price . '`');
}
