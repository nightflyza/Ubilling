<?php

/**
 * Receipts/Bills printing
 */
class PrintReceipt {

    /**
     * Contains array user statuses. Possible values: "debt" / "debtasbalance" / "undebt" / "all"
     *
     * @var array
     */
    protected $receiptAllUserStatuses = array();

    /**
     * Contains array user freeze statuses. Possible values: "frozen" / "unfrozen" / "all"
     *
     * @var array
     */
    protected $receiptAllFrozenStatuses = array();

    /**
     * Contains array of all cities represented as cityname => cityname
     *
     * @var array
     */
    protected $receiptAllCities = array('-' => '-');

    /**
     * Contains array of all streets represented as cityname + streetname => streetname
     *
     * @var array
     */
    protected $receiptAllStreets = array();

    /**
     * Contains array of all builds represented as cityname + streetname + buildnum => buildnum
     *
     * @var array
     */
    protected $receiptAllBuilds = array();

    /**
     * Placeholder for PRINT_RECEIPTS_HISTORY_ENABLED alter.ini option
     *
     * @var bool
     */
    public $receiptsHistoryOn = false;

    /**
     * Placeholder for ADDRESS_EXTENDED_ENABLED alter.ini option
     *
     * @var bool
     */
    public $extenAddressOn = false;

    /**
     * Placeholder for UbillingConfig object
     *
     * @var null
     */
    protected $ubConfig = null;

    /**
     * Contains receipt folders to use different templates
     *
     * @var array
     */
    protected $receiptTemplateFolders = array();

    const TEMPLATE_PATH = 'content/documents/receipt_template/';
    const URL_ME = '?module=printreceipts';

    public function __construct() {
        global $ubillingConfig;
        $this->ubConfig = $ubillingConfig;
        $this->receiptsHistoryOn = $this->ubConfig->getAlterParam('PRINT_RECEIPTS_HISTORY_ENABLED');
        $this->extenAddressOn = $this->ubConfig->getAlterParam('ADDRESS_EXTENDED_ENABLED');

        $this->receiptAllUserStatuses = array(
            'debt' => __('Debtors'),
            'debtasbalance' => __('Debtors (as of current balance)'),
            'undebt' => __('AntiDebtors'),
            'all' => __('All')
        );

        $this->receiptAllFrozenStatuses = array(
            'all' => __('All'),
            'unfrozen' => __('Not frozen'),
            'frozen' => __('Frozen')
        );

        $this->reloadCitiesStreetsBuilds();

        $tmpFolders = rcms_scandir(self::TEMPLATE_PATH, '', 'dir');
        if (!empty($tmpFolders)) {
            foreach ($tmpFolders as $tmpFolder) {
                $this->receiptTemplateFolders[$tmpFolder] = $tmpFolder;
            }
        }
        $this->receiptTemplateFolders = array('-' => __('Default')) + $this->receiptTemplateFolders;
    }

    /**
     * Fills $receiptAllStreets placeholder
     *
     * @return void
     */
    protected function getAllCities() {
        $query = "SELECT DISTINCT `cityname` FROM `city` ORDER BY `cityname` ASC;";
        $allStreets = simple_queryall($query);

        if (!empty($allStreets)) {
            foreach ($allStreets as $io => $each) {
                $this->receiptAllCities[trim($each['cityname'])] = trim($each['cityname']);
            }
        }
    }

    /**
     * Fills $receiptAllStreets placeholder
     *
     * @return void
     */
    protected function getAllStreets() {
        $query = "SELECT `city`.`cityname`, `street`.`streetname` 
                    FROM `city` 
                        RIGHT JOIN `street` ON `street`.`cityid` = `city`.`id` 
                    ORDER BY `streetname`;";
        $allStreets = simple_queryall($query);

        if (!empty($allStreets)) {
            foreach ($allStreets as $io => $each) {
                $this->receiptAllStreets[trim($each['cityname']) . trim($each['streetname'])] = trim($each['streetname']);
            }
        }
    }

    /**
     * Fills $receiptAllBuilds placeholder
     *
     * @return void
     */
    protected function getAllBuilds() {
        $query = "SELECT `city`.`cityname`, `street`.`streetname`, `build`.`buildnum` 
                    FROM `city`
                        RIGHT JOIN `street` ON `street`.`cityid` = `city`.`id`
                        RIGHT JOIN `build` ON `build`.`streetid` = `street`.`id` 
                    ORDER BY `buildnum`;";
        $allBuilds = simple_queryall($query);

        if (!empty($allBuilds)) {
            foreach ($allBuilds as $io => $each) {
                $this->receiptAllBuilds[trim($each['cityname']) . trim($each['streetname']) . trim($each['buildnum'])] = trim($each['buildnum']);
            }
        }
    }

    /**
     * Saves receipt to DB
     *
     * @param $login
     * @param $rcptNum
     * @param $rcptDate
     * @param $rcptSum
     * @param $rcptBody
     */
    protected function saveToDB($login, $rcptNum, $rcptDate, $rcptSum, $rcptBody) {
        $tabInvoices = new nya_invoices();
        $tabInvoices->dataArr(array(
            'login' => $login,
            'invoice_num' => $rcptNum,
            'invoice_date' => $rcptDate,
            'invoice_sum' => $rcptSum,
            'invoice_body' => $rcptBody,
                )
        );
        $tabInvoices->create();
    }

    /**
     * Returns all tags list as:
     * Inet service: tariffname => tariffname . tarifffee . tariffperiod
     * UKV service: tariffid => tariffname . tarifffee
     *
     * @return array
     */
    public function getAllTariffs($isInetSrv = true) {
        $tmpArr = array('-' => __('-'));

        if ($isInetSrv) {
            $query = "SELECT name, fee, period FROM `tariffs`";
            $alltypes = simple_queryall($query);

            if (!empty($alltypes)) {
                foreach ($alltypes as $io => $eachtype) {
                    $tmpArr[$eachtype['name']] = $eachtype['name'] . ' - ' . $eachtype['fee'] . ' - ' . $eachtype['period'];
                }
            }
        } else {
            $query = "SELECT id, tariffname, price FROM `ukv_tariffs`";
            $alltypes = simple_queryall($query);

            if (!empty($alltypes)) {
                foreach ($alltypes as $io => $eachtype) {
                    $tmpArr[$eachtype['id']] = $eachtype['tariffname'] . ' - ' . $eachtype['price'];
                }
            }
        }

        return ($tmpArr);
    }

    /**
     * Returns all tags list as tagid => tagname
     *
     * @return array
     */
    public function getAllTags() {
        $tmpArr = array('-' => __('-'));
        $query = "SELECT * from `tagtypes`";
        $alltypes = simple_queryall($query);

        if (!empty($alltypes)) {
            foreach ($alltypes as $io => $eachtype) {
                $tmpArr[$eachtype['id']] = $eachtype['tagname'];
            }
        }

        return ($tmpArr);
    }

    /**
     * Returns receipts data
     *
     * @param string $whereString
     *
     * @return mixed
     */
    public function getInvoicesData($whereString = '') {
        $tabInvoices = new nya_invoices();
        $tabInvoices->setDebug(true, true);
        $tabInvoices->whereRaw($whereString);
        $tabInvoices->selectable('*');
        $allInvoices = $tabInvoices->getAll();

        return ($allInvoices);
    }

    /**
     * Fires updating of $receiptAllStreets and $receiptAllBuilds placeholder
     *
     * @return void
     */
    public function reloadCitiesStreetsBuilds() {
        $this->getAllCities();
        $this->getAllStreets();
        $this->getAllBuilds();
    }

    /**
     * Returns users print data considering filters values
     *
     * @param string $receiptServiceType
     * @param string $receiptUserStatus
     * @param string $receiptUserLogin
     * @param string $receiptDebtCash
     * @param string $receiptStreet
     * @param string $receiptBuild
     * @param string $receiptCity
     * @param string $receiptTagID
     * @param string $receiptTariff
     * @param string $receiptFrozenStatus
     *
     * @return array
     */
    public function getUsersPrintData($receiptServiceType, $receiptUserStatus, $receiptUserLogin = '', $receiptDebtCash = '', $receiptCity = '', $receiptStreet = '', $receiptBuild = '', $receiptTagID = '', $receiptTariff = '', $receiptFrozenStatus = '') {
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

        if ($receiptServiceType == 'inetsrv' and ! empty($receiptFrozenStatus) and $receiptFrozenStatus != 'all') {
            if (empty($whereClause)) {
                $whereClause = ' WHERE ';
            } else {
                $whereClause .= ' AND ';
            }

            switch ($receiptFrozenStatus) {
                case 'frozen':
                    $whereClause .= ' `Passive` = 1 ';
                    break;

                case 'unfrozen':
                    $whereClause .= ' `Passive` = 0 ';
                    break;
            }
        }

        if (!empty($receiptCity)) {
            if (empty($whereClause)) {
                $whereClause = ' WHERE ';
            } else {
                $whereClause .= ' AND ';
            }

            $whereClause .= " `city` = '" . $receiptCity . "' ";

            if (!empty($receiptStreet)) {
                $whereClause .= " AND `street` = '" . str_ireplace($receiptCity, '', $receiptStreet) . "' ";
            }

            if (!empty($receiptBuild)) {
                $whereClause .= " AND `build` = '" . str_ireplace($receiptCity . $receiptStreet, '', $receiptBuild) . "' ";
            }
        }

        if (!empty($receiptTagID)) {
            if ($receiptServiceType == 'inetsrv') {
                $tag_query_str = " `tags`.`tagid`, ";
                $tag_join_str = " INNER JOIN `tags` ON `users`.`login` = `tags`.`login` and `tags`.`tagid` = " . $receiptTagID . " ";
            } else {
                $tag_query_str = " `ukv_tags`.`tagtypeid`, ";
                $tag_join_str = " INNER JOIN `ukv_tags` ON `ukv_users`.`id` = `ukv_tags`.`userid` and `ukv_tags`.`tagtypeid` = " . $receiptTagID . " ";
            }
        } else {
            $tag_query_str = '';
            $tag_join_str = '';
        }

        if (!empty($receiptTariff)) {
            if (empty($whereClause)) {
                $whereClause = ' WHERE ';
            } else {
                $whereClause .= ' AND ';
            }

            if ($receiptServiceType == 'inetsrv') {
                $whereClause .= " `tariffname` = '" . $receiptTariff . "' ";
            } else {
                $whereClause .= " `tariffid` = '" . $receiptTariff . "' ";
            }
        }

        if (!empty($receiptUserLogin)) {
            if (empty($whereClause)) {
                $whereClause = ' WHERE ';
            } else {
                $whereClause .= ' AND ';
            }

            $whereClause .= ($receiptServiceType == 'inetsrv') ? " `login` = '" . $receiptUserLogin . "' " : " `login` = " . $receiptUserLogin . " ";
        }

        if ($this->extenAddressOn) {
            $addrexten_query = " `postal_code`, `town_district`, address_exten, ";
            $addrexten_join = " LEFT JOIN `address_extended` ON `users`.`login` = `address_extended`.`login` ";
        } else {
            $addrexten_query = " '' AS `postal_code`, '' AS `town_district`, '' AS address_exten, ";
            $addrexten_join = '';
        }

        if ($receiptServiceType == 'inetsrv') {
            $query = "SELECT * FROM
                          (SELECT `users`.`login`, `users`.`cash`, `realname`.`realname`, `users`.`Passive`, `tariffs`.`name` AS `tariffname`, `tariffs`.`fee` AS `tariffprice`, 
                                  `contracts`.`contract`, `contractdates`.`date` AS `contractdate`, `phones`.`phone`, `phones`.`mobile`, `emails`.`email`,   
                                  `tmp_addr`.`cityname` AS `city`, `tmp_addr`.`streetname` AS `street`, `tmp_addr`.`buildnum` AS `build`, `tmp_addr`.`apt`, `passportdata`.`pinn` AS `inn`,
                                  " . $tag_query_str . " 
                                  " . $addrexten_query . "
                                  " . $debtAsBalance . " AS `debtasbalance`                                  
                              FROM `users` 
                                  LEFT JOIN `tariffs` ON `users`.`tariff` = `tariffs`.`name`
                                  LEFT JOIN `contracts` USING(`login`)
                                  LEFT JOIN `contractdates` USING(`contract`)
                                  LEFT JOIN `realname` USING(`login`) 
                                  LEFT JOIN `phones` USING(`login`) 
				  LEFT JOIN `emails` USING(`login`)
				  LEFT JOIN `passportdata` USING(`login`)
                                  LEFT JOIN (SELECT `address`.`login`,`city`.`id`,`city`.`cityname`,`street`.`streetname`,`build`.`buildnum`,`apt`.`apt` 
                                                FROM `address` 
                                                    INNER JOIN `apt` ON `address`.`aptid`= `apt`.`id` 
                                                    INNER JOIN `build` ON `apt`.`buildid`=`build`.`id` 
                                                    INNER JOIN `street` ON `build`.`streetid`=`street`.`id` 
                                                    INNER JOIN `city` ON `street`.`cityid`=`city`.`id` 
                                            ) AS `tmp_addr` USING(`login`) "
                    . $addrexten_join
                    . $tag_join_str . " ) AS tmpQ " .
                    $whereClause . " ORDER BY `street` ASC, `build` ASC";
        } else {
            $query = "SELECT * FROM 
                          ( SELECT `ukv_users`.`id` AS login, `ukv_users`.*, `ukv_users`.`regdate` AS `contractdate`, `ukv_tariffs`.`tariffname`, `ukv_tariffs`.`price` AS `tariffprice`,
                                   " . $tag_query_str . "
                                   " . $addrexten_query . "
                                   " . $debtAsBalance . " AS `debtasbalance`                                   
                                    FROM `ukv_users` 
                                        LEFT JOIN `ukv_tariffs` ON `ukv_users`.`tariffid` = `ukv_tariffs`.`id` "
                    . $addrexten_join
                    . $tag_join_str . ") AS tmpQ " .
                    $whereClause . " ORDER BY `street` ASC, `build` ASC";
        }

        $usersDataToPrint = simple_queryall($query);

        return($usersDataToPrint);
    }

    /**
     * Returns macro substituted, ready to print template filled with data from $usersDataToPrint
     *
     * @param array $usersDataToPrint
     * @param string $rcptServiceName
     * @param string $rcptPayTillDate
     * @param int $rcptMonthsCnt
     * @param string $rcptPayForPeriod
     * @param bool $rcptSaveToDB
     * @param string $rcptTemplateFolder
     *
     * @return string
     */
    public function printReceipts($usersDataToPrint, $rcptServiceName = '', $rcptPayTillDate = '', $rcptMonthsCnt = 1, $rcptPayForPeriod = '', $rcptSaveToDB = false, $rcptTemplateFolder = '') {
        $rcptTemplateFolder = (empty($rcptTemplateFolder) or $rcptTemplateFolder == '-') ? '' : $rcptTemplateFolder . '/';
        $rawTemplate = file_get_contents(self::TEMPLATE_PATH . $rcptTemplateFolder . "payment_receipt.tpl");
        $rawTemplateHeader = file_get_contents(self::TEMPLATE_PATH . $rcptTemplateFolder . "payment_receipt_head.tpl");

        $rawTemplateFooter = file_get_contents(self::TEMPLATE_PATH . $rcptTemplateFolder . "payment_receipt_footer.tpl");
        $printableTemplate = '';
        $qrCodeExtInfo = '';
        $formatDates = 'd.m.Y';
        $formatTime = 'H:i:s';
        $formatMonthYear = 'm.Y';
        $i = 0;

        //whether to embed QR-codes ot not
        $qrEmbed = (strpos($rawTemplateHeader, '{QR_EMBED}') !== false);
        if ($qrEmbed) {
            $qrGen = new BarcodeQR();
        }

        //whether to use current date and time as an invoice number
        $invNumCurDateTime = (strpos($rawTemplateHeader, '{INV_NUM_CURDATETIME}') !== false);

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

        $formatTimeNoDelim = str_ireplace(array('.', '/', '-', ':'), '', $formatTime);
        $formatDatesNoDelim = str_ireplace(array('.', '/', '-', ':'), '', $formatDates);

        if (!empty($rcptPayTillDate)) {
            $tmpDate = new DateTime($rcptPayTillDate);
            $rcptPayTillDate = $tmpDate->format($formatDates);
        }

        if ($rcptSaveToDB) {
            $tabInvoices = new nya_invoices();
            $lastID = $tabInvoices->getLastId();
        } else {
            $lastID = 0;
        }

        $curArrIdx = 0;
        log_register('PRINT RECEIPTS: number of users to proceed [' . count($usersDataToPrint) . ']');
        //
        // main template processing
        //
        foreach ($usersDataToPrint as $item => $eachUser) {
            if (empty($eachUser) or empty($eachUser['login'])) {
                continue;
            }

            $curArrIdx++;
            $curUsrContractDate = date($formatDates, strtotime($eachUser['contractdate']));

            if (!$rcptSaveToDB or empty($lastID) or $invNumCurDateTime) {
                if (empty($lastID) or !$rcptSaveToDB) {
                    $receiptNextNum = date($formatDatesNoDelim . $formatTimeNoDelim) . '-' . $curArrIdx;
                } else {
                    $receiptNextNum = date($formatDatesNoDelim . $formatTimeNoDelim);
                }
            } else {
                $receiptNextNum = ++$lastID;
            }

            if ($eachUser['debtasbalance']) {
                $receiptPaySum = abs(round($eachUser['cash'], 2));
            } else {
                $receiptPaySum = $eachUser['tariffprice'] * $rcptMonthsCnt;
            }

            /*            // replacing macro values for qr-code info in template
              $tmpQRCode = str_ireplace('{CURDATE}', date($formatDates), $qrCodeExtInfo);
              $tmpQRCode = str_ireplace('{PAYFORPERIODSTR}', $rcptPayForPeriod, $qrCodeExtInfo);
              $tmpQRCode = str_ireplace('{PAYTILLMONTHYEAR}', date($formatMonthYear, strtotime("+1 month")), $qrCodeExtInfo);
              $tmpQRCode = str_ireplace('{PAYTILLDATE}', $rcptPayTillDate, $qrCodeExtInfo);
              $tmpQRCode = str_ireplace('{SERVICENAME}', $rcptServiceName, $qrCodeExtInfo);
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
              $rowtemplate = str_ireplace('{PAYFORPERIODSTR}', $rcptPayForPeriod, $rowtemplate);
              $rowtemplate = str_ireplace('{PAYTILLMONTHYEAR}', date($formatMonthYear, strtotime("+1 month")), $rowtemplate);
              $rowtemplate = str_ireplace('{PAYTILLDATE}', $rcptPayTillDate, $rowtemplate);
              $rowtemplate = str_ireplace('{SERVICENAME}', $rcptServiceName, $rowtemplate);
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
              } */



            // replacing macro values for qr-code info in template
            $tmpQRCode = $qrCodeExtInfo;
            $tmpQRCode = $this->replaceMainTemplateMacro(
                            $tmpQRCode,
                            date($formatDates),
                            date($formatTime),
                            date($formatDatesNoDelim),
                            date($formatTimeNoDelim),
                            $receiptNextNum,
                            $rcptMonthsCnt,
                            $rcptPayForPeriod,
                            date($formatMonthYear, strtotime("+1 month")),
                            $rcptPayTillDate,
                            $rcptServiceName,
                            $eachUser['contract'],
                            $curUsrContractDate,
                            $eachUser['realname'],
                            $eachUser['city'],
                            $eachUser['street'],
                            $eachUser['build'],
                            (!empty($eachUser['apt'])) ? '/' . $eachUser['apt'] : '',
                            $eachUser['postal_code'],
                            $eachUser['town_district'],
                            $eachUser['address_exten'],
                            $eachUser['phone'],
                            $eachUser['mobile'],
                            @$eachUser['inn'],
                            $eachUser['tariffname'],
                            $eachUser['tariffprice'],
                            $eachUser['tariffprice'] * 100,
                            number_format((float) $eachUser['tariffprice'], 2, '.', ''),
                            $receiptPaySum,
                            $receiptPaySum * 100,
                            number_format((float) ($receiptPaySum), 2, '.', '')
            );

            // replacing macro values in template
            $rowtemplate = $rawTemplate;
            $rowtemplate = str_ireplace('{QR_INDEX}', ++$i, $rowtemplate);

            //embed the qr-code image or just put filled data
            if ($qrEmbed) {
                $rowtemplate = str_ireplace('{QR_CODE_CONTENT}', $tmpQRCode, $rowtemplate);
                $qrGen->text($tmpQRCode);
                $qrImg = base64_encode($qrGen->draw(150, null, true));
                $qrImg = '<img src="data:image/png;base64,' . $qrImg . '" alt="QR-CODE" />';
                $rowtemplate = str_ireplace('{QR_CODE_EMBEDDED}', $qrImg, $rowtemplate);
            } else {
                $rowtemplate = str_ireplace('{QR_CODE_CONTENT}', $tmpQRCode, $rowtemplate);
                $rowtemplate = str_ireplace('{QR_CODE_EMBEDDED}', '', $rowtemplate);
            }


            $rowtemplate = $this->replaceMainTemplateMacro(
                            $rowtemplate,
                            date($formatDates),
                            date($formatTime),
                            date($formatDatesNoDelim),
                            date($formatTimeNoDelim),
                            $receiptNextNum,
                            $rcptMonthsCnt,
                            $rcptPayForPeriod,
                            date($formatMonthYear, strtotime("+1 month")),
                            $rcptPayTillDate, $rcptServiceName, $eachUser['contract'],
                            $curUsrContractDate,
                            $eachUser['realname'],
                            $eachUser['city'],
                            $eachUser['street'],
                            $eachUser['build'],
                            (!empty($eachUser['apt'])) ? '/' . $eachUser['apt'] : '',
                            $eachUser['apt'], $eachUser['postal_code'],
                            $eachUser['town_district'],
                            $eachUser['address_exten'],
                            $eachUser['phone'],
                            $eachUser['mobile'],
                            @$eachUser['inn'],
                            zb_OschadCSgen(@$eachUser['inn']),
                            $eachUser['tariffname'],
                            $eachUser['tariffprice'],
                            $eachUser['tariffprice'] * 100,
                            number_format((float) $eachUser['tariffprice'], 2, '.', ''),
                            $receiptPaySum, $receiptPaySum * 100,
                            number_format((float) ($receiptPaySum), 2, '.', '')
            );

            $printableTemplate .= $rowtemplate;

            if ($rcptSaveToDB) {
                $singleTemplateHeader = $rawTemplateHeader;
                $singleTemplateHeader = str_ireplace('{QR_CODES_CNT}', '1', $singleTemplateHeader);

                // getting one single receipt with header and footer as a separate html document
                $singleReceipt = $singleTemplateHeader . $rowtemplate . $rawTemplateFooter;
                $this->saveToDB($eachUser['login'], $receiptNextNum, curdatetime(), $receiptPaySum, base64_encode($singleReceipt));
            }
        }

        log_register('PRINT RECEIPTS: number of invoices created [' . $curArrIdx . ']');
        $rawTemplateHeader = str_ireplace('{QR_CODES_CNT}', $i, $rawTemplateHeader);

        return($rawTemplateHeader . $printableTemplate . $rawTemplateFooter);
    }

    /**
     * Replaces macro in given main receipt template
     *
     * @param $rcptTemplate
     * @param string $tplCurDate
     * @param string $tplCurTime
     * @param string $tplCurDateNoDelims
     * @param string $tplCurTimeNoDelims
     * @param string $tplCrhgPeriodDStart
     * @param string $tplCrhgPeriodDEnd
     * @param string $tplInvoiceNum
     * @param string $tplMonthCnt
     * @param string $tplPayForPeriodStr
     * @param string $tplPayTillMnthYr
     * @param string $tplPayTillDate
     * @param string $tplSrvName
     * @param string $tplContract
     * @param string $tplContractDate
     * @param string $tplRealName
     * @param string $tplCity
     * @param string $tplStreet
     * @param string $tplBuild
     * @param string $tplApt
     * @param string $tplEAddrPostCode
     * @param string $tplEAddrTwnDstr
     * @param string $tplEAddrExt
     * @param string $tplPhone
     * @param string $tplMobile
     * @param string $tplTotalCoins
     * @param string $tplTotalDecimals
     * @param string $tplTotalVATCoins
     * @param string $tplTotalVATDecimals
     * @param string $tplTotalWithVATCoins
     * @param string $tplTotalWithVATDecimals
     * @param string $tplServicesRows
     *
     * @return mixed
     */
    public function replaceMainTemplateMacro($rcptTemplate, $tplCurDate = '', $tplCurTime = '', $tplCurDateNoDelims = '', $tplCurTimeNoDelims = '', $tplInvoiceNum = '', $tplMonthCnt = '', $tplPayForPeriodStr = '', $tplPayTillMnthYr = '', $tplPayTillDate = '', $tplSrvName = '', $tplContract = '', $tplContractDate = '', $tplRealName = '', $tplCity = '', $tplStreet = '', $tplBuild = '', $tplApt = '', $tplAPT2 = '', $tplEAddrPostCode = '', $tplEAddrTwnDstr = '', $tplEAddrExt = '', $tplPhone = '', $tplMobile = '', $tplInn = '', $tploshadCS = '', $tplTariff = '', $tplTrfPrice = 0, $tplTrfPriceCoins = 0, $tplTrfPriceDecimals = 0, $tplSumm = 0, $tplSummCoins = 0, $tplSummDecimals = 0
    ) {

        $rcptTemplate = str_ireplace('{CURDATE}', $tplCurDate, $rcptTemplate);
        $rcptTemplate = str_ireplace('{CURTIME}', $tplCurTime, $rcptTemplate);
        $rcptTemplate = str_ireplace('{CURDATENODELIMS}', $tplCurDateNoDelims, $rcptTemplate);
        $rcptTemplate = str_ireplace('{CURDATETIMENODELIMS}', $tplCurTimeNoDelims, $rcptTemplate);
        $rcptTemplate = str_ireplace('{INVOICE_NUM}', $tplInvoiceNum, $rcptTemplate);
        $rcptTemplate = str_ireplace('{MONTH_COUNT}', $tplMonthCnt, $rcptTemplate);
        $rcptTemplate = str_ireplace('{PAYFORPERIODSTR}', $tplPayForPeriodStr, $rcptTemplate);
        $rcptTemplate = str_ireplace('{PAYTILLMONTHYEAR}', $tplPayTillMnthYr, $rcptTemplate);
        $rcptTemplate = str_ireplace('{PAYTILLDATE}', $tplPayTillDate, $rcptTemplate);
        $rcptTemplate = str_ireplace('{SERVICENAME}', $tplSrvName, $rcptTemplate);
        $rcptTemplate = str_ireplace('{CONTRACT}', $tplContract, $rcptTemplate);
        $rcptTemplate = str_ireplace('{CONTRACTDATE}', $tplContractDate, $rcptTemplate);
        $rcptTemplate = str_ireplace('{REALNAME}', $tplRealName, $rcptTemplate);
        $rcptTemplate = str_ireplace('{CITY}', $tplCity, $rcptTemplate);
        $rcptTemplate = str_ireplace('{STREET}', $tplStreet, $rcptTemplate);
        $rcptTemplate = str_ireplace('{BUILD}', $tplBuild, $rcptTemplate);
        $rcptTemplate = str_ireplace('{APT}', $tplApt, $rcptTemplate);
        $rcptTemplate = str_ireplace('{APT2}', $tplAPT2, $rcptTemplate);
        $rcptTemplate = str_ireplace('{EXTADDR_POSTALCODE}', $tplEAddrPostCode, $rcptTemplate);
        $rcptTemplate = str_ireplace('{EXTADDR_TOWNDISTR}', $tplEAddrTwnDstr, $rcptTemplate);
        $rcptTemplate = str_ireplace('{EXTADDR_ADDREXT}', $tplEAddrExt, $rcptTemplate);
        $rcptTemplate = str_ireplace('{PHONE}', $tplPhone, $rcptTemplate);
        $rcptTemplate = str_ireplace('{INN}', $tplInn, $rcptTemplate);
        $rcptTemplate = str_ireplace('{oshadCS}', $tploshadCS, $rcptTemplate);
        $rcptTemplate = str_ireplace('{MOBILE}', $tplMobile, $rcptTemplate);
        $rcptTemplate = str_ireplace('{TARIFF}', $tplTariff, $rcptTemplate);
        $rcptTemplate = str_ireplace('{TARIFFPRICE}', $tplTrfPrice, $rcptTemplate);
        $rcptTemplate = str_ireplace('{TARIFFPRICECOINS}', $tplTrfPriceCoins, $rcptTemplate);
        $rcptTemplate = str_ireplace('{TARIFFPRICEDECIMALS}', $tplTrfPriceDecimals, $rcptTemplate);
        $rcptTemplate = str_ireplace('{SUMM}', $tplSumm, $rcptTemplate);
        $rcptTemplate = str_ireplace('{SUMMCOINS}', $tplSummCoins, $rcptTemplate);
        $rcptTemplate = str_ireplace('{SUMMDECIMALS}', $tplSummDecimals, $rcptTemplate);

        return ($rcptTemplate);
    }

    /**
     * Returns receipts print web form
     *
     * @return string
     */
    public function renderWebForm() {
        $inputs = '';
        if ($this->receiptsHistoryOn) {
            $inputs .= wf_Link(self::URL_ME . '&showhistory=true', __('Issued receipts'), true, 'ubButton', 'style="width: 90%; text-align: center;"');
            $inputs .= wf_delimiter(0);
        }
        $inputs .= wf_tag('div', false, '', 'style="line-height: 0.8em"');
        $inputs .= wf_RadioInput('receiptsrv', __('Internet'), 'inetsrv', false, true, 'ReceiptSrvInet');
        $inputs .= wf_RadioInput('receiptsrv', __('UKV'), 'ctvsrv', true, false, 'ReceiptSrvCTV');
        $inputs .= wf_delimiter(0);
        $inputs .= wf_TextInput('receiptsrvtxt', __('Service'), __('Internet'), true, '28', '', '', 'ReceiptSrvName');
        $inputs .= wf_delimiter(0);
        $inputs .= wf_Selector('receipttemplate', $this->receiptTemplateFolders, __('Choose template'), '', true, false, 'ReceiptTemplate');
        $inputs .= wf_delimiter(0);
        $inputs .= wf_Selector('receiptsubscrstatus', $this->receiptAllUserStatuses, __('Subscriber\'s account status'), '', true, false, 'ReceiptDirSel');
        $inputs .= wf_Selector('receiptfrozenstatus', $this->receiptAllFrozenStatuses, __('Subscriber\'s frozen status'), '', true, false, 'ReceiptFrozenSel');
        $inputs .= wf_TextInput('receiptdebtcash', __('The threshold at which the money considered user debtor'), '0', true, '4', '', '', 'ReceiptDebtSumm');
        $inputs .= wf_TextInput('receiptmonthscnt', __('Amount of months to be payed(will be multiplied on tariff cost)'), '1', true, '4', '', '', 'ReceiptMonthsCnt');
        $inputs .= wf_delimiter(0);
        $inputs .= wf_Selector('receiptscities', $this->receiptAllCities, __('City'), '', true, true, 'ReceiptCities');
        $inputs .= wf_Selector('receiptstreets', array('-' => '-'), __('Street'), '', true, true, 'ReceiptStreets');
        $inputs .= wf_Selector('receiptbuilds', array('-' => '-'), __('Build'), '', true, true, 'ReceiptBuilds');
        $inputs .= wf_delimiter(0);
        $inputs .= wf_Selector('receipttariffs', $this->getAllTariffs(), __('User has tariff assigned'), '', true, true, 'ReceiptTariffs');
        $inputs .= wf_delimiter(0);
        $inputs .= wf_Selector('receipttags', $this->getAllTags(), __('User have tag assigned'), '', true, true, 'ReceiptTags');
        $inputs .= wf_delimiter(0);
        $inputs .= wf_TextInput('receiptpayperiod', __('Pay for period(months), e.g.: March 2019, April 2019'), '', true, '40', '', '', 'ReceiptPayPeriod');
        $inputs .= wf_delimiter(0);
        $inputs .= wf_tag('span', false);
        $inputs .= wf_DatePickerPreset('receiptpaytill', date("Y-m-d", strtotime("+5 days")), true);
        $inputs .= wf_nbsp(2) . __('Pay till date');
        $inputs .= wf_tag('span', true);

        if ($this->receiptsHistoryOn) {
            $inputs .= wf_delimiter(1);
            $inputs .= wf_CheckInput('receiptsaveindb', __('Save receipt(s) to DB'), true, false, 'ReceiptSaveInDB');
        }

        $inputs .= wf_delimiter(1);
        $inputs .= wf_HiddenInput('tmpstreetsall', base64_encode(json_encode($this->receiptAllStreets)), 'TmpStreetsAll');
        $inputs .= wf_HiddenInput('tmpbuildsall', base64_encode(json_encode($this->receiptAllBuilds)), 'TmpBuildsAll');
        $inputs .= wf_HiddenInput('tmpinettariffs', base64_encode(json_encode($this->getAllTariffs())), 'TmpInetTariffs');
        $inputs .= wf_HiddenInput('tmpukvtariffs', base64_encode(json_encode($this->getAllTariffs(false))), 'TmpUkvTariffs');
        $inputs .= wf_HiddenInput('printthemall', 'true', 'PrintThemAll');
        $inputs .= wf_Submit(__('Print'), '', 'class="ubButton" style="width: 100%"');

        $inputs .= wf_tag('script', false, '', 'type="text/javascript"');
        $inputs .= '$(document).ready(function() {                        
                        $("[name=receiptsrv]").change(function(evt) {
                            var tmpStr;
                            
                            if ($(this).val() == \'inetsrv\') {
                                tmpStr = \'' . __('Internet') . '\';
                                exchangeSrvsTariffs(true);
                            } else {
                                tmpStr = \'' . __('Cable television') . '\';
                                exchangeSrvsTariffs(false);
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
                        
                        $(\'#ReceiptCities\').change(function(evt) {
                            var keyword = $(this).val();
                            var source = JSON.parse(atob($(\'#TmpStreetsAll\').val()));
                            
                            filterStreetsSelect(keyword, source);
                            $(\'#ReceiptStreets\').change();
                        });
                        
                        $(\'#ReceiptStreets\').change(function(evt) {
                            var keyword = $(this).val();                    
                            var source = JSON.parse(atob($(\'#TmpBuildsAll\').val()));

                            filterBuildsSelect(keyword, source);
                        });
                        
                        function filterStreetsSelect(search_keyword, search_array) {
                            var newselect = \'<option value>-</option>\';
                            
                            if (search_keyword.length > 0 && search_keyword.trim() !== "-") {
                                for (var key in search_array) {
                                    if ( key.trim() !== "" && key.toLowerCase() == search_keyword.toLowerCase() + search_array[key].toLowerCase() ) {                                    
                                        newselect = newselect + \'<option value="\' + key + \'">\' + search_array[key] + \'</option>\';
                                    }  
                                }
                            }
                            
                            $(\'#ReceiptStreets\').html(newselect);                            
                        }
                        
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
                        
                        function exchangeSrvsTariffs(isInetSrv) {
                            if (isInetSrv) {
                                var source = JSON.parse(atob($(\'#TmpInetTariffs\').val()));
                            } else {
                                var source = JSON.parse(atob($(\'#TmpUkvTariffs\').val()));
                            }
                            
                            var newselect = $("<select id=\"ReceiptTariffs\" name=\"receipttariffs\" />");
                            
                            for (var key in source) {
                                $("<option />", {value: key, text: source[key]}).appendTo(newselect);
                            }
                            
                            $(\'#ReceiptTariffs\').replaceWith(newselect);
                        }
                        
                        var keyword = $(\'#ReceiptCities\').val();
                        var source = JSON.parse(atob($(\'#TmpStreetsAll\').val()));
                            
                        filterStreetsSelect(keyword, source);
                        
                        var keyword = $(\'#ReceiptStreets\').val();
                        var source = JSON.parse(atob($(\'#TmpBuildsAll\').val()));
                            
                        filterBuildsSelect(keyword, source);
                   });
                  ';
        $inputs .= wf_tag('script', true);
        $inputs .= wf_tag('div', true);

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

        $inputs = wf_Selector('receipttemplate', $this->receiptTemplateFolders, __('Choose template'), '', true, false, 'ReceiptTemplate');
        $inputs .= wf_delimiter(0);
        $inputs .= wf_TextInput('receiptsrvtxt', __('Service'), __($receiptServiceName), true, '28', '', '', 'ReceiptSrvName');
        $inputs .= wf_delimiter(0);

        if ($userBalance < 0) {
            $inputs .= wf_Selector('receiptsumsource', $receiptSumSources, __('Specify receipt sum source'), '', true, false, 'ReceiptSumSource');
            $inputs .= wf_TextInput('receiptbalancesum', __('Current user\'s balance debt sum'), abs(round($userBalance, 2)), true, '4', '', '', 'ReceiptBalanceSum', 'readonly="readonly"');
        }

        $inputs .= wf_TextInput('receiptmonthscnt', __('Amount of months to be payed(will be multiplied on tariff cost)'), '1', true, '4', '', '', 'ReceiptMonthsCnt');
        $inputs .= wf_TextInput('receiptpayperiod', __('Pay for period(months), e.g.: March 2019, April 2019'), '', true, '40', '', '', 'ReceiptPayPeriod');
        $inputs .= wf_delimiter(0);
        $inputs .= wf_tag('span', false);
        $inputs .= wf_DatePickerPreset('receiptpaytill', date("Y-m-d", strtotime("+5 days")), true);
        $inputs .= wf_nbsp(2) . __('Pay till date');
        $inputs .= wf_tag('span', true);

        if ($this->receiptsHistoryOn) {
            $inputs .= wf_delimiter(1);
            $inputs .= wf_CheckInput('receiptsaveindb', __('Save receipt(s) to DB'), true, false, 'ReceiptSaveInDB');
        }

        $inputs .= wf_HiddenInput('receiptsubscrstatus', '', 'ReceiptSubscrStatus');
        $inputs .= wf_HiddenInput('receiptdebtcash', '');
        $inputs .= wf_HiddenInput('receiptsrv', $receiptServiceType);
        $inputs .= wf_HiddenInput('receiptslogin', $receiptLogin);
        $inputs .= wf_HiddenInput('receiptstreets', $receiptStreet);
        $inputs .= wf_HiddenInput('receiptbuilds', $receiptBuild);
        $inputs .= wf_HiddenInput('printthemall', 'true');
        $inputs .= wf_delimiter(0);
        $inputs .= wf_Submit(__('Print'), '', 'class="ubButton" style="width: 100%"');

        $form = wf_Form('?module=printreceipts', 'POST', $inputs, 'glamour', '', 'ReceiptPrintForm', "_blank");

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

    /**
     * Renders JQDT and returns it
     *
     * @return string
     */
    public function renderJQDT($userLogin = '') {
        $ajaxUrlStr = (empty($userLogin)) ? self::URL_ME . '&ajax=true' : self::URL_ME . '&ajax=true&usrlogin=' . $userLogin;
        $columns = array(__('ID'), __('Login'), __('Number'), __('Date'), __('Sum'), __('Actions'));
        $formID = wf_InputId();

        $jqdtId = 'jqdt_' . md5($ajaxUrlStr);

        // filter controls for dates
        $inputs = wf_DatePicker('invdatefrom');
        $inputs .= __('Creation date from') . wf_nbsp(3);
        $inputs .= wf_DatePicker('invdateto');
        $inputs .= __('Creation date to') . wf_nbsp(4);
        $inputs .= wf_SubmitClassed(true, 'ubButton', '', __('Show'));
        $inputs .= wf_tag('script', false, '', 'type="text/javascript"');
        $inputs .= '
                    $(\'#' . $formID . '\').submit(function(evt) {
                        evt.preventDefault();
                        var FrmData = $(\'#' . $formID . '\').serialize();
                        $(\'#' . $jqdtId . '\').DataTable().ajax.url(\'' . $ajaxUrlStr . '\' + \'&\' + FrmData).load();  
                        //$(\'#' . $jqdtId . '\').DataTable().ajax.url(\'' . $ajaxUrlStr . '\');
                    });
                  ';
        $inputs .= wf_tag('script', true);

        $form = wf_Form('', 'POST', $inputs, 'glamour', '', $formID) . wf_delimiter(0);

        return ( $form . wf_JqDtLoader($columns, $ajaxUrlStr, false, __('results'), 100) );
    }

    /**
     * Renders JSON for JQDT
     */
    public function renderJSON($queryData) {
        $json = new wf_JqDtHelper();

        if (!empty($queryData)) {
            $data = array();

            foreach ($queryData as $eachRec) {
                $data[] = $eachRec['id'];
                $data[] = $eachRec['login'];
                $data[] = $eachRec['invoice_num'];
                $data[] = $eachRec['invoice_date'];
                $data[] = $eachRec['invoice_sum'];
                $data[] = wf_Link(self::URL_ME . '&printid=' . $eachRec['id'], __('Print'), false, 'ubButton', 'target="_blank"');

                $json->addRow($data);
                unset($data);
            }
        }

        $json->getJson();
    }

}
