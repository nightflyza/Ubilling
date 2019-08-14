<?php

class PrintReceipt {
    /**
     * Contains array of 3 user statuses: 'debtor', 'antidebtor', 'all'
     *
     * @var array
     */
    protected $receiptAllUserStatuses = array();

    /**
     * Contains array of all streets(selected distinctly) represented as streetname => streetname
     *
     * @var array
     */
    protected $receiptAllStreets = array('' => '-');

    /**
     * Contains array of all builds represented as streetname + buildnum => buildnum
     *
     * @var array
     */
    protected $receiptAllBuilds = array();


    const TEMPLATE_PATH = 'content/documents/receipt_template/';


    public function __construct() {
        $this->receiptAllUserStatuses = array(
                                              'debt' => __('Debtors'),
                                              'debtasbalance' => __('Debtors (as of current balance)'),
                                              'undebt' => __('AntiDebtors'),
                                              'all' => __('All')
                                             );
        $this->reloadStreetsAndBuilds();
    }

    /**
     * Fills $receiptAllStreets placeholder
     *
     * @return void
     */
    protected function getAllStreetsDistinct() {
        $query = "SELECT DISTINCT `streetname` FROM `street` ORDER BY `streetname` ASC;";
        $allStreets = simple_queryall($query);

        if (!empty($allStreets)) {
            foreach ($allStreets as $io => $each) {
                $this->receiptAllStreets[trim($each['streetname'])] = trim($each['streetname']);
            }
        }
    }

    /**
     * Fills $receiptAllBuilds placeholder
     *
     * @return void
     */
    protected function getAllBuilds() {
        $query = "SELECT `street`.`streetname`, `build`.`buildnum` FROM `street` RIGHT JOIN `build` ON `build`.`streetid` = `street`.`id` ORDER BY `buildnum`;";
        $allBuilds = simple_queryall($query);

        if (!empty($allBuilds)) {
            foreach ($allBuilds as $io => $each) {
                $this->receiptAllBuilds[trim($each['streetname']) . trim($each['buildnum'])] = trim($each['buildnum']);
            }
        }
    }

    /**
     * Fires updating of $receiptAllStreets and $receiptAllBuilds placeholder
     *
     * @return void
     */
    public function reloadStreetsAndBuilds() {
        $this->getAllStreetsDistinct();
        $this->getAllBuilds();
    }

    /**
     * Returns users print data considering filters values
     *
     * @param string $receiptServiceType
     * @param string $receiptUserStatus
     * @param string $receiptDebtCash
     * @param string $receiptStreet
     * @param string $receiptBuild
     *
     * @return array
     */
    public function getUsersPrintData($receiptServiceType, $receiptUserStatus, $receiptUserLogin = '', $receiptDebtCash = '', $receiptStreet = '', $receiptBuild = '') {
        $whereClause = '';
        $debtAsBalance = 0;

        switch ($receiptUserStatus) {
            case 'debt':
                $receiptDebtCash = (empty($receiptDebtCash)) ? 0 : ('-' . vf($receiptDebtCash, 3));
                $whereClause = ' WHERE `cash` < ' . $receiptDebtCash . ' ';
                break;

            case 'undebt':
                $receiptDebtCash = (empty($receiptDebtCash)) ? 0 : (vf($receiptDebtCash, 3));
                $whereClause = ' WHERE `cash` > ' . $receiptDebtCash . ' ';
                break;

            case 'debtasbalance':
                $whereClause = ' WHERE `cash` < \'0\' ';
                $debtAsBalance = 1;
                break;
        }

        if (!empty($receiptStreet)) {
            if (empty($whereClause)) {
                $whereClause = ' WHERE ';
            } else {
                $whereClause.= ' AND ';
            }

            $whereClause.= " `street` = '" . $receiptStreet . "' ";

            if (!empty($receiptBuild)) {
                $whereClause.= " AND `build` = '" . str_ireplace($receiptStreet, '', $receiptBuild) . "' ";
            }
        }

        if (!empty($receiptUserLogin)) {
            if (empty($whereClause)) {
                $whereClause = ' WHERE ';
            } else {
                $whereClause.= ' AND ';
            }

            $whereClause.= ($receiptServiceType == 'inetsrv') ? " `login` = '" . $receiptUserLogin . "' " : " `login` = " . $receiptUserLogin . " ";
        }

        if ($receiptServiceType == 'inetsrv') {
            $query = "SELECT * FROM
                          (SELECT `users`.`login`, `users`.`cash`, `realname`.`realname`, `tariffs`.`name` AS `tariffname`, `tariffs`.`fee` AS `tariffprice`, 
                                  `contracts`.`contract`, `phones`.`phone`, `phones`.`mobile`,  
                                  `tmp_addr`.`cityname` AS `city`, `tmp_addr`.`streetname` AS `street`, `tmp_addr`.`buildnum` AS `build`, `tmp_addr`.`apt`,
                                  " . $debtAsBalance . " AS `debtasbalance` 
                              FROM `users` 
                                  LEFT JOIN `tariffs` ON `users`.`tariff` = `tariffs`.`name`
                                  LEFT JOIN `contracts` USING(`login`)
                                  LEFT JOIN `realname` USING(`login`) 
                                  LEFT JOIN `phones` USING(`login`) 
                                  LEFT JOIN (SELECT `address`.`login`,`city`.`id`,`city`.`cityname`,`street`.`streetname`,`build`.`buildnum`,`apt`.`apt` 
                                                FROM `address` 
                                                    INNER JOIN `apt` ON `address`.`aptid`= `apt`.`id` 
                                                    INNER JOIN `build` ON `apt`.`buildid`=`build`.`id` 
                                                    INNER JOIN `street` ON `build`.`streetid`=`street`.`id` 
                                                    INNER JOIN `city` ON `street`.`cityid`=`city`.`id`
                                            ) AS `tmp_addr` USING(`login`) ) AS tmpQ " .
                          $whereClause . " ORDER BY `street` ASC, `build` ASC";

        } else {
            $query = "SELECT * FROM 
                          ( SELECT `ukv_users`.`id` AS login, `ukv_users`.*, `ukv_tariffs`.`tariffname`, `ukv_tariffs`.`price` AS `tariffprice`,
                                   " . $debtAsBalance . " AS `debtasbalance`  
                                    FROM `ukv_users` 
                                        LEFT JOIN `ukv_tariffs` ON `ukv_users`.`tariffid` = `ukv_tariffs`.`id`) AS tmpQ " .
                          $whereClause . " ORDER BY `street` ASC, `build` ASC";
        }

        $usersDataToPrint = simple_queryall($query);

        return($usersDataToPrint);
    }

    /**
     * Returns macro substituted, ready to print template filled with data from $usersDataToPrint
     *
     * @param array $usersDataToPrint
     * @param string $receiptServiceName
     * @param string $receiptPayTillDate
     * @param int $receiptMonthsCnt
     *
     * @return string
     */
    public function printReceipts($usersDataToPrint, $receiptServiceName = '', $receiptPayTillDate = '', $receiptMonthsCnt = 1, $receiptPayForPeriod = '') {
        $rawTemplate = file_get_contents(self::TEMPLATE_PATH . "payment_receipt.tpl");
        $rawTemplateHeader = file_get_contents(self::TEMPLATE_PATH . "payment_receipt_head.tpl");
        $rawTemplateFooter = file_get_contents(self::TEMPLATE_PATH . "payment_receipt_footer.tpl");
        $printableTemplate = '';
        $qrCodeExtInfo = '';
        $formatDates = 'd.m.Y';
        $formatMonthYear = 'm.Y';
        $i = 0;

        preg_match('/{QR_EXT_START}(.*?){QR_EXT_END}/ms', $rawTemplateHeader, $matchResult);
        if (isset($matchResult[1])) {
            $qrCodeExtInfo = trim(str_ireplace('"', "'", $matchResult[1]));
        }

        preg_match('/{DATES_FORMAT_START}(.*?){DATES_FORMAT_END}/ms', $rawTemplateHeader, $matchResult);
        if (isset($matchResult[1])) {
            $tmpStr = trim($matchResult[1]);
            $formatDates = (!empty($tmpStr)) ? $tmpStr : $formatDates;
        }

        preg_match('/{MONTHYEAR_FORMAT_START}(.*?){MONTHYEAR_FORMAT_END}/ms', $rawTemplateHeader, $matchResult);
        if (isset($matchResult[1])) {
            $tmpStr = trim($matchResult[1]);
            $formatMonthYear = (!empty($tmpStr)) ? $tmpStr : $formatMonthYear;
        }

        if (!empty($receiptPayTillDate)) {
            $tmpDate = new DateTime($receiptPayTillDate);
            $receiptPayTillDate = $tmpDate->format($formatDates);
        }

        foreach ($usersDataToPrint as $item => $eachUser) {
            if ($eachUser['debtasbalance']) {
                $receiptPaySum = abs(round($eachUser['cash'], 2));
            } else {
                $receiptPaySum = $eachUser['tariffprice'] * $receiptMonthsCnt;
            }

            // replacing macro values for qr-code info in template
            $tmpQRCode = str_ireplace('{CURDATE}', date($formatDates), $qrCodeExtInfo);
            $tmpQRCode = str_ireplace('{PAYFORPERIODSTR}', $receiptPayForPeriod, $qrCodeExtInfo);
            $tmpQRCode = str_ireplace('{PAYTILLMONTHYEAR}', date($formatMonthYear, strtotime("+1 month")), $qrCodeExtInfo);
            $tmpQRCode = str_ireplace('{PAYTILLDATE}', $receiptPayTillDate, $qrCodeExtInfo);
            $tmpQRCode = str_ireplace('{SERVICENAME}', $receiptServiceName, $qrCodeExtInfo);
            $tmpQRCode = str_ireplace('{CONTRACT}', $eachUser['contract'], $qrCodeExtInfo);
            $tmpQRCode = str_ireplace('{REALNAME}', $eachUser['realname'], $qrCodeExtInfo);
            $tmpQRCode = str_ireplace('{CITY}', $eachUser['city'], $qrCodeExtInfo);
            $tmpQRCode = str_ireplace('{STREET}', $eachUser['street'], $qrCodeExtInfo);
            $tmpQRCode = str_ireplace('{BUILD}', $eachUser['build'], $qrCodeExtInfo);
            $tmpQRCode = str_ireplace('{APT}', (!empty($eachUser['apt'])) ? '/' . $eachUser['apt'] : '', $qrCodeExtInfo);
            $tmpQRCode = str_ireplace('{PHONE}', $eachUser['phone'], $qrCodeExtInfo);
            $tmpQRCode = str_ireplace('{MOBILE}', $eachUser['mobile'], $qrCodeExtInfo);
            $tmpQRCode = str_ireplace('{TARIFF}', $eachUser['tariffname'], $qrCodeExtInfo);
            $tmpQRCode = str_ireplace('{TARIFFPRICE}', $eachUser['tariffprice'], $qrCodeExtInfo);
            $tmpQRCode = str_ireplace('{TARIFFPRICECOINS}', $eachUser['tariffprice'] * 100, $qrCodeExtInfo);
            $tmpQRCode = str_ireplace('{TARIFFPRICEDECIMALS}', number_format((float)$eachUser['tariffprice'], 2, '.', ''), $qrCodeExtInfo);
            $tmpQRCode = str_ireplace('{SUMM}', $receiptPaySum, $qrCodeExtInfo);
            $tmpQRCode = str_ireplace('{SUMMCOINS}', $receiptPaySum * 100, $qrCodeExtInfo);
            $tmpQRCode = str_ireplace('{SUMMDECIMALS}', number_format((float)($receiptPaySum),2, '.', ''), $qrCodeExtInfo);

            // replacing macro values in template
            $rowtemplate = $rawTemplate;
            $rowtemplate = str_ireplace('{QR_INDEX}', ++$i, $rowtemplate);
            $rowtemplate = str_ireplace('{QR_CODE_CONTENT}', $tmpQRCode, $rowtemplate);
            $rowtemplate = str_ireplace('{CURDATE}', date($formatDates), $rowtemplate);
            $rowtemplate = str_ireplace('{PAYFORPERIODSTR}', $receiptPayForPeriod, $rowtemplate);
            $rowtemplate = str_ireplace('{PAYTILLMONTHYEAR}', date($formatMonthYear, strtotime("+1 month")), $rowtemplate);
            $rowtemplate = str_ireplace('{PAYTILLDATE}', $receiptPayTillDate, $rowtemplate);
            $rowtemplate = str_ireplace('{SERVICENAME}', $receiptServiceName, $rowtemplate);
            $rowtemplate = str_ireplace('{CONTRACT}', $eachUser['contract'], $rowtemplate);
            $rowtemplate = str_ireplace('{REALNAME}', $eachUser['realname'], $rowtemplate);
            $rowtemplate = str_ireplace('{CITY}', $eachUser['city'], $rowtemplate);
            $rowtemplate = str_ireplace('{STREET}', $eachUser['street'], $rowtemplate);
            $rowtemplate = str_ireplace('{BUILD}', $eachUser['build'], $rowtemplate);
            $rowtemplate = str_ireplace('{APT}', (!empty($eachUser['apt'])) ? '/' . $eachUser['apt'] : '', $rowtemplate);
            $rowtemplate = str_ireplace('{PHONE}', $eachUser['phone'], $rowtemplate);
            $rowtemplate = str_ireplace('{MOBILE}', $eachUser['mobile'], $rowtemplate);
            $rowtemplate = str_ireplace('{TARIFF}', $eachUser['tariffname'], $rowtemplate);
            $rowtemplate = str_ireplace('{TARIFFPRICE}', $eachUser['tariffprice'], $rowtemplate);
            $rowtemplate = str_ireplace('{TARIFFPRICECOINS}', $eachUser['tariffprice'] * 100, $rowtemplate);
            $rowtemplate = str_ireplace('{TARIFFPRICEDECIMALS}', number_format((float)$eachUser['tariffprice'], 2, '.', ''), $rowtemplate);
            $rowtemplate = str_ireplace('{SUMM}', $receiptPaySum, $rowtemplate);
            $rowtemplate = str_ireplace('{SUMMCOINS}', $receiptPaySum * 100, $rowtemplate);
            $rowtemplate = str_ireplace('{SUMMDECIMALS}', number_format((float)($receiptPaySum),2, '.', ''), $rowtemplate);

            $printableTemplate.= $rowtemplate;
        }

        $rawTemplateHeader = str_ireplace('{QR_CODES_CNT}', $i, $rawTemplateHeader);

        return($rawTemplateHeader . $printableTemplate . $rawTemplateFooter);
    }

    /**
     * Returns receipts print web form
     *
     * @return string
     */
    public function renderWebForm() {
        $inputs = wf_tag('div', false, '', 'style="line-height: 0.8em"');
        $inputs.= wf_RadioInput('receiptsrv', __('Internet'), 'inetsrv', false, true, 'ReceiptSrvInet');
        $inputs.= wf_RadioInput('receiptsrv', __('UKV'), 'ctvsrv', true, false, 'ReceiptSrvCTV');
        $inputs.= wf_delimiter(0);
        $inputs.= wf_TextInput('receiptsrvtxt', __('Service'), __('Internet'), true, '28', '', '', 'ReceiptSrvName');
        $inputs.= wf_delimiter(0);
        $inputs.= wf_Selector('receiptsubscrstatus', $this->receiptAllUserStatuses, __('Subscriber\'s account status'), '', true, false, 'ReceiptDirSel');
        $inputs.= wf_TextInput('receiptdebtcash', __('The threshold at which the money considered user debtor'), '0', true, '4', '', '', 'ReceiptDebtSumm');
        $inputs.= wf_TextInput('receiptmonthscnt', __('Amount of months to be payed(will be multiplied on tariff cost)'), '1', true, '4', '', '', 'ReceiptMonthsCnt');
        $inputs.= wf_delimiter(0);
        $inputs.= wf_Selector('receiptstreets', $this->receiptAllStreets, __('Street'), '', true, true, 'ReceiptStreets');
        $inputs.= wf_Selector('receiptbuilds', array('' => '-'), __('Build'), '', true, true, 'ReceiptBuilds');
        $inputs.= wf_delimiter(0);
        $inputs.= wf_TextInput('receiptpayperiod', __('Pay for period(months), e.g.: March 2019, April 2019'), '', true, '40', '', '', 'ReceiptPayPeriod');
        $inputs.= wf_delimiter(0);
        $inputs.= wf_tag('span', false);
        $inputs.= wf_DatePickerPreset('receiptpaytill', date("Y-m-d", strtotime("+5 days")), true);
        $inputs.= wf_nbsp(2) . __('Pay till date');
        $inputs.= wf_tag('span', true);
        $inputs.= wf_delimiter(1);
        $inputs.= wf_HiddenInput('printthemall', base64_encode(json_encode($this->receiptAllBuilds)), 'TmpBuildsAll');
        $inputs.= wf_Submit(__('Print'));
        $inputs.= wf_tag('script', false, '', 'type="text/javascript"');
        $inputs.= '$(document).ready(function() {
                        $("[name=receiptsrv]").change(function(evt) {
                            var tmpStr;
                            
                            if ($(this).val() == \'inetsrv\') {
                                tmpStr = \'' . __('Internet') . '\';
                            } else {
                                tmpStr = \'' . __('Cable television') . '\';
                            }
                            
                           $(\'#ReceiptSrvName\').val(tmpStr);
                        });
                        
                        $(\'#ReceiptDirSel\').change(function(evt) {
                            switch ($(this).val()) {
                                case \'all\':
                                    $(\'#ReceiptDebtSumm\').val(\'\');
                                    $(\'#ReceiptDebtSumm\').hide();
                                    $("label[for=\'ReceiptDebtSumm\']").text(\'\');
                                    break;
                                    
                                case \'debtasbalance\':
                                    $(\'#ReceiptDebtSumm\').val(\'\');
                                    $(\'#ReceiptDebtSumm\').hide();
                                    $("label[for=\'ReceiptDebtSumm\']").text(\'\');
                                    
                                    $(\'#ReceiptMonthsCnt\').val(\'\');
                                    $(\'#ReceiptMonthsCnt\').hide();
                                    $("label[for=\'ReceiptMonthsCnt\']").text(\'\');                                    
                                    break;
                                    
                                default:
                                    $(\'#ReceiptDebtSumm\').val(\'0\');
                                    $(\'#ReceiptDebtSumm\').show();
                                    $("label[for=\'ReceiptDebtSumm\']").text(\'' . __('The threshold at which the money considered user debtor') . '\');
                                    
                                    $(\'#ReceiptMonthsCnt\').val(\'0\');
                                    $(\'#ReceiptMonthsCnt\').show();
                                    $("label[for=\'ReceiptMonthsCnt\']").text(\'' . __('Amount of months to be payed(will be multiplied on tariff cost)') . '\');
                            }
                        });
                        
                        $(\'#ReceiptStreets\').change(function(evt) {
                            var keyword = $(this).val();                            
                            var source = JSON.parse(atob($(\'#TmpBuildsAll\').val()));
                            
                            filterBuildsSelect(keyword, source);
                        });
                        
                        function filterBuildsSelect(search_keyword, search_array) {
                            var newselect = $("<select id=\"ReceiptBuilds\" name=\"receiptbuilds\" />");
                            
                            $("<option />", {value: \'\', text: \'-\'}).appendTo(newselect);
                            
                            if (search_keyword.length > 0 && search_keyword.trim() !== "-") {
                                for (var key in search_array) {
                                    if ( key.trim() !== "" && key.toLowerCase() == search_keyword.toLowerCase() + search_array[key].toLowerCase() ) {                                       
                                        $("<option />", {value: key, text: search_array[key]}).appendTo(newselect);
                                    }  
                                }
                            }
                            
                            $(\'#ReceiptBuilds\').replaceWith(newselect);
                        }
                        
                        var keyword = $(\'#ReceiptStreets\').val();
                        var source = JSON.parse(atob($(\'#TmpBuildsAll\').val()));
                            
                        filterBuildsSelect(keyword, source);
                   });
                  ';
        $inputs.= wf_tag('script', true);
        $inputs.= wf_tag('div', true);

        $form = wf_Form('?module=printreceipts', 'POST', $inputs, 'glamour', '', 'ReceiptPrintForm', "_blank");

        return($form);
    }

    /**
     * Returns button with modal form attached for user profile
     *
     * @param mixed $receiptLogin
     * @param $receiptServiceType
     * @param string $receiptServiceName
     * @param mixed $userBalance
     * @param string $receiptStreet
     * @param string $receiptBuild
     *
     * @return string
     */
    public function renderWebFormForProfile($receiptLogin, $receiptServiceType, $receiptServiceName = '', $userBalance = 0, $receiptStreet = '', $receiptBuild = '') {
        $receiptSumSources = array(
                                   'debtasbalance' => __('Get current balance debt sum'),
                                   '' => __('Specify number of months')
                                  );

        $inputs= wf_TextInput('receiptsrvtxt', __('Service'), __($receiptServiceName), true, '28', '', '', 'ReceiptSrvName');
        $inputs.= wf_delimiter(0);

        if ($userBalance < 0) {
            $inputs .= wf_Selector('receiptsumsource', $receiptSumSources, __('Specify receipt sum source'), '', true, false, 'ReceiptSumSource');
            $inputs .= wf_TextInput('receiptbalancesum', __('Current user\'s balance debt sum'), abs(round($userBalance, 2)), true, '4', '', '', 'ReceiptBalanceSum', 'readonly="readonly"');
        }

        $inputs.= wf_TextInput('receiptmonthscnt', __('Amount of months to be payed(will be multiplied on tariff cost)'), '1', true, '4', '', '', 'ReceiptMonthsCnt');
        $inputs.= wf_TextInput('receiptpayperiod', __('Pay for period(months), e.g.: March 2019, April 2019'), '', true, '40', '', '', 'ReceiptPayPeriod');
        $inputs.= wf_delimiter(0);
        $inputs.= wf_tag('span', false);
        $inputs.= wf_DatePickerPreset('receiptpaytill', date("Y-m-d", strtotime("+5 days")), true);
        $inputs.= wf_nbsp(2) . __('Pay till date');
        $inputs.= wf_tag('span', true);
        $inputs.= wf_HiddenInput('receiptsubscrstatus', '', 'ReceiptSubscrStatus');
        $inputs.= wf_HiddenInput('receiptdebtcash', '');
        $inputs.= wf_HiddenInput('receiptsrv', $receiptServiceType);
        $inputs.= wf_HiddenInput('receiptslogin', $receiptLogin);
        $inputs.= wf_HiddenInput('receiptstreets', $receiptStreet);
        $inputs.= wf_HiddenInput('receiptbuilds', $receiptBuild);
        $inputs.= wf_HiddenInput('printthemall', 'true');
        $inputs.= wf_delimiter(1);
        $inputs.= wf_Submit(__('Print'));

        $form = wf_Form('?module=printreceipts',  'POST', $inputs, 'glamour', '', 'ReceiptPrintForm', "_blank");

        if ($userBalance < 0) {
            $form .= wf_tag('script', false, '', 'type="text/javascript"');
            $form .= '
                    $(document).ready(function() {
                        endisControls();
                    });
                    
                    $(\'#ReceiptSumSource\').change(function() {
                        endisControls();
                    });
                    
                    function endisControls() {
                        if ( $(\'#ReceiptSumSource\').val() == \'debtasbalance\' ) {
                            $(\'#ReceiptBalanceSum\').prop("disabled", false);
                            $(\'#ReceiptMonthsCnt\').prop("disabled", true);                                                       
                        } else {
                            $(\'#ReceiptBalanceSum\').prop("disabled", true);
                            $(\'#ReceiptMonthsCnt\').prop("disabled", false);
                        } 
                        
                        $(\'#ReceiptSubscrStatus\').val($(\'#ReceiptSumSource\').val());
                    }
                    ';
            $form .= wf_tag('script', true);
        }

        $form = wf_modalAuto(wf_img_sized('skins/taskbar/receipt_big.png', __('Print receipt'), '', '64'), __('Print receipt'), $form);

        return($form);
    }
}