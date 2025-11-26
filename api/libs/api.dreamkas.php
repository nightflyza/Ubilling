<?php

/**
 * DreamKas service interaction class
 */
class DreamKas {

    /**
     * UbillingConfig object placeholder
     *
     * @var object
     */
    protected $ubConfig = null;

    /**
     * System message helper object placeholder
     *
     * @var object
     */
    protected $messages = null;

    /**
     * UbillingCache instance placeholder
     *
     * @var object
     */
    protected $ubCache = null;

    /**
     * Dreamkas auth token placeholder
     *
     * @var string
     */
    protected $authToken = '';

    /**
     * Placeholder for DREAMKAS_ALWAYS_FISCALIZE_ALL alter.ini option
     *
     * @var bool
     */
    protected $alwaysFiscalizeAll = false;

    /**
     * Placeholder for DREAMKAS_DEFAULT_CASH_MACHINE_ID alter.ini option
     *
     * @var bool
     */
    protected $defaultCashMachineID = '';

    /**
     * Placeholder for DREAMKAS_DEFAULT_TAX_TYPE alter.ini option
     *
     * @var bool
     */
    protected $defaultTaxType = '';

    /**
     * Placeholder for DREAMKAS_NOTIFICATIONS_ENABLED alter.ini option
     *
     * @var bool
     */
    protected $notysEnabled = false;

    /**
     * Caching timeout based on polling interval in seconds.
     *
     * @var int
     */
    protected $notysCachingTimeout = 8;

    /**
     * Plcaeholder for basic and common HTTP headers for Dreamkas connecting and authenticating
     *
     * @var array
     */
    protected $basicHTTPHeaders = array();

    /**
     * Placeholder for all cash machines
     *
     * @var array
     */
    protected $cashMachines = array();

    /**
     * Array containing cash machines IDs => IDs + Names. E.g for selector controls
     *
     * @var array
     */
    protected $cashMachines4Selector = array();

    /**
     * Placeholder for selling positions to services relations
     * to be able to distinguish UKV/Inet services when preforming check fiscalization
     *
     * @var array
     */
    protected $sellPos2SrvTypeMapping = array();

    /**
     * Placeholder for all selling positions
     *
     * @var array
     */
    protected $sellingPositions = array();

    /**
     * Array containing selling positions IDs => Names. E.g for selector controls
     *
     * @var array
     */
    protected $sellingPositionsIDsNames = array();

    /**
     * Array containing fiscal operations initiated and stored locally
     *
     * @var array
     */
    protected $localFiscalOperations = array();

    /**
     * Placeholder for all registered webhooks
     *
     * @var array
     */
    protected $webhooks = array();

    /**
     * Contains last error message string
     *
     * @var string
     */
    protected $lastErrorMEssage = '';

    /**
     * Placeholder for DREAMKAS_CACHE_LIFETIME from alter.ini
     *
     * @var int
     */
    protected $cacheLifeTime = 1800;

    /**
     * $dataCahched array from UbillingCache
     *
     * @var array
     */
    protected $dataCahched = array();

    /**
     * Dreamkas to Banksta2 processed relations data with receipts IDs already
     *
     * @var array
     */
    protected $bs2RelationsProcessed = array();

    /**
     * Dreamkas to Banksta2 unprocessed relations data without receipts IDs yet
     *
     * @var array
     */
    protected $bs2RelationsUnProcessed = array();

    /**
     * Dreamkas to Banksta2 relations data with fiscal operation ID as a key and without receipts IDs yet
     *
     * @var array
     */
    protected $bs2RelationsUnProcFiscOpKey = array();

    /**
     * Placeholder for payment types supported by Dreamkas API
     *
     * @var array
     */
    protected $paymentTypes = array(
        'CASH' => 'Наличные',
        'CASHLESS' => 'Безналичные',
        'PREPAID' => 'Аванс',
        'CREDIT' => 'Кредит',
        'CONSIDERATION' => 'Встречное предоставление'
    );

    /**
     * Placeholder for taxation systems supported by Dreamkas API
     *
     * @var array
     */
    protected $taxTypes = array(
        'DEFAULT' => 'Общая',
        'SIMPLE' => 'Упрощенная доход',
        'SIMPLE_WO' => 'Упрощенная доход минус расход',
        'ENVD' => 'Единый налог на вмененный доход',
        'PATENT' => 'Патентная система налогообложения'
    );

    /**
     * Placeholder for VAT(НДС) types supported by Dreamkas API
     *
     * @var array
     */
    protected $taxTypesVAT = array(
        'NDS_NO_TAX' => 'Без НДС',
        'NDS_0' => 'НДС 0%',
        'NDS_10' => 'НДС 10%',
        'NDS_20' => 'НДС 20%',
        'NDS_10_CALCULATED' => 'НДС 10/110%',
        'NDS_20_CALCULATED' => 'НДС 20/120%'
    );

    const URL_ME = '?module=dreamkas';
    const URL_API = 'https://kabinet.dreamkas.ru/api/';
    const URL_DREAMKAS_RECEIPTS = '?module=dreamkas&getreceipts=true';
    const URL_DREAMKAS_CASHIERS = '?module=dreamkas&getcashiers=true';
    const URL_DREAMKAS_OPERATIONS = '?module=dreamkas&getoperations=true';
    const URL_DREAMKAS_GOODS = '?module=dreamkas&getgoods=true';
    const URL_DREAMKAS_CASH_MACHINES = '?module=dreamkas&getcashmachines=true';
    const URL_DREAMKAS_WEBHOOKS = '?module=dreamkas&getwebhookss=true';
    const URL_DREAMKAS_RECEIPT_DETAILS = '?module=dreamkas&getreceiptdetails=true';
    const URL_DREAMKAS_FORCE_CACHE_UPDATE = '?module=dreamkas&forcecacheupdate=true';
    // fiscalize timeout in minutes
    const RECEIPT_FISCALIZE_TIMEOUT = '7';
    const RECEIPT_PRODUCT_TYPE = 'SERVICE';
    const RECEIPT_OPERATION_TYPE = 'SALE';
    //  Go to:
    //      https://kabinet.dreamkas.ru/api/#cheki
    //  and carefully read about the positions array
    //  and quantity for anything except SCALABLE.
    const RECEIPT_QUANTITY_DIVIDER = '1000';
    const DREAMKAS_CACHE_KEY = 'DREAMKAS_DATA';
    const DREAMKAS_NOTYS_CAHCE_KEY = 'DREAMKAS_NOTIFICATIONS';

    public function __construct() {
        global $ubillingConfig;
        $this->ubConfig = $ubillingConfig;
        $this->ubCache = new UbillingCache();
        $this->initMessages();
        $this->loadOptions();
        $this->basicHTTPHeaders = array(
            'Authorization: Bearer ' . $this->authToken,
            'Content-Type: application/json',
            'Cache-Control: no-cache'
        );

        $thisInstance = $this;
        $this->dataCahched = $this->ubCache->getCallback(self::DREAMKAS_CACHE_KEY, function () use ($thisInstance) {
            return ($thisInstance->getDataForCache());
        }, $this->cacheLifeTime);

        $this->cashMachines = (isset($this->dataCahched['cashmachines'])) ? $this->dataCahched['cashmachines'] : array();
        $this->sellingPositions = (isset($this->dataCahched['sellingpositions'])) ? $this->dataCahched['sellingpositions'] : array();

        $this->getCashMachines4Selector();
        $this->getSellPosIDsNames();
        $this->getSellPos2SrvTypeMapping();
        $this->getBS2RelationsProcessed();
        $this->getBS2RelationsUnProcessed();

        //$curISOTime = date('Y-m-d\TH:i:s.Z\Z', time());
        //$customISOTime = date('Y-m-d\TH:i:s.Z\Z', strtotime('2017-01-25 14:40:46'));
        //date('Y-m-d H:i:s', strtotime('2019-06-05T23:59:59.999Z'))
    }

    /**
     * Inits message helper object for further usage
     *
     * @return void
     */
    protected function initMessages() {
        $this->messages = new UbillingMessageHelper();
    }

    /**
     * Getting an alter.ini options
     *
     * @return void
     */
    protected function loadOptions() {
        $this->authToken = $this->ubConfig->getAlterParam('DREAMKAS_AUTH_TOKEN');
        $this->cacheLifeTime = ($this->ubConfig->getAlterParam('DREAMKAS_CACHE_LIFETIME')) ? $this->ubConfig->getAlterParam('DREAMKAS_CACHE_LIFETIME') : 1800;
        $this->defaultCashMachineID = ($this->ubConfig->getAlterParam('DREAMKAS_DEFAULT_CASH_MACHINE_ID')) ? $this->ubConfig->getAlterParam('DREAMKAS_DEFAULT_CASH_MACHINE_ID') : '';
        $this->defaultTaxType = ($this->ubConfig->getAlterParam('DREAMKAS_DEFAULT_TAX_TYPE')) ? $this->ubConfig->getAlterParam('DREAMKAS_DEFAULT_TAX_TYPE') : '';
        $this->alwaysFiscalizeAll = wf_getBoolFromVar($this->ubConfig->getAlterParam('DREAMKAS_ALWAYS_FISCALIZE_ALL'));
        $this->notysEnabled = wf_getBoolFromVar($this->ubConfig->getAlterParam('DREAMKAS_NOTIFICATIONS_ENABLED'));
        $this->notysCachingTimeout = ($this->ubConfig->getAlterParam('DREAMKAS_CACHE_CHECK_INTERVAL')) ? $this->ubConfig->getAlterParam('DREAMKAS_CACHE_CHECK_INTERVAL') : 8;
    }

    /**
     * Returns reference to UbillingMessageHelper object
     *
     * @return object
     */
    public function getUbMsgHelperInstance() {
        return $this->messages;
    }

    /**
     * Returns essential data suitable for caching
     *
     * @return array
     */
    public function getDataForCache() {
        $cacheArray = array();

        $cashiers4Cache = $this->getCashiers();
        $sellPos4Cache = $this->getSellingPositions();
        $cashMachines4Cache = $this->getCashMachines();

        if (!empty($cashiers4Cache)) {
            $cacheArray['cashiers'] = $cashiers4Cache;
        }

        if (!empty($sellPos4Cache)) {
            $cacheArray['sellingpositions'] = $sellPos4Cache;
        }

        if (!empty($sellPos4Cache)) {
            $cacheArray['cashmachines'] = $cashMachines4Cache;
        }

        //$this->updateFiscalOperationsLocalStorage();

        return ($cacheArray);
    }

    /**
     * Forcibly updates cached data
     */
    public function refreshCacheForced() {
        $this->ubCache->set(self::DREAMKAS_CACHE_KEY, $this->getDataForCache(), $this->cacheLifeTime);
        $this->dataCahched = $this->ubCache->get(self::DREAMKAS_CACHE_KEY, $this->cacheLifeTime);
    }

    /**
     * Returns cashiers array via Dreamkas API
     *
     * @return mixed
     */
    protected function getCashiers() {
        $urlString = self::URL_API . 'cashiers';

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $urlString);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $this->basicHTTPHeaders);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        $result = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        //PHP 8.0+ has no need to close curl resource anymore
        if (PHP_VERSION_ID < 80000) {
            curl_close($curl); // Deprecated in PHP 8.5
        }

        $result = json_decode($result, true);

        if (substr($httpCode, 0, 1) != '2') {
            log_register('DREAMKAS getting cashiers error: ' . $this->errorToString($result));
            $result = array();
        }

        return ($result);
    }

    /**
     * Returns cash machines array via Dreamkas API
     *
     * @return mixed
     */
    public function getCashMachines($cmID = '') {
        $urlString = self::URL_API . 'devices' . ((empty($cmID)) ? '' : '/' . $cmID);

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $urlString);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $this->basicHTTPHeaders);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        $result = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        //PHP 8.0+ has no need to close curl resource anymore
        if (PHP_VERSION_ID < 80000) {
            curl_close($curl);
        }

        $tCMArr = json_decode($result, true);
        $result = array();

        if (!empty($tCMArr)) {
            if (substr($httpCode, 0, 1) == '2') {
                if (empty($cmID)) {
                    foreach ($tCMArr as $eachItem) {
                        $this->cashMachines[$eachItem['id']] = $eachItem;
                    }

                    $result = $this->cashMachines;
                } else {
                    $result = $tCMArr;
                }
            } else {
                log_register('DREAMKAS getting cash machines error: ' . $this->errorToString($tCMArr));
            }
        }

        return ($result);
    }

    /**
     * Returns fiscal operations array via Dreamkas API
     *
     * @return mixed
     */
    protected function getFiscalOperations($dateFrom = '', $dateTo = '') {
        if (!empty($dateFrom) and $dateFrom == $dateTo) {
            $dateTo = $dateTo . ' 23:59:59';
        } elseif (!empty($dateFrom) and empty($dateTo)) {
            $dateTo = $dateFrom . ' 23:59:59';
        }

        // for now - this is useless, 'cause Dreamkas API doesn't support fiscal operations filtering by time range
        // but there is hope it may change in future
        $urlParams = empty($dateFrom) ? '' : '?from=' . date('Y-m-d\TH:i:s.Z\Z', strtotime($dateFrom));
        $urlParams .= empty($dateTo) ? '' : (empty($dateFrom) ? '?' : '&') . 'to=' . date('Y-m-d\TH:i:s.Z\Z', strtotime($dateTo));

        $urlString = self::URL_API . 'operations' . $urlParams;

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $urlString);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $this->basicHTTPHeaders);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        //curl_setopt($curl, CURLOPT_TIMEOUT, 12);
        $result = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        //PHP 8.0+ has no need to close curl resource anymore
        if (PHP_VERSION_ID < 80000) {
            curl_close($curl);
        }

        $result = json_decode($result, true);

        if (substr($httpCode, 0, 1) != '2') {
            log_register('DREAMKAS getting fiscal operations error: ' . $this->errorToString($result));
            $result = array();
        }

        return ($result);
    }

    /**
     * Fills $this->localFiscalOperations with fiscal operations initiated and stored locally
     *
     * @return array
     */
    protected function getFiscalOperationsLocal($whereClause = '') {
        $whereClause = (empty($whereClause)) ? '' : " WHERE " . $whereClause . " ";
        $tQuery = "SELECT * FROM `dreamkas_operations`" . $whereClause . " ORDER BY `date_create` DESC";
        $tQueryResult = simple_queryall($tQuery);

        if (!empty($tQueryResult)) {
            $this->localFiscalOperations = array();

            foreach ($tQueryResult as $eachRec) {
                $this->localFiscalOperations[$eachRec['operation_id']] = $eachRec;
            }
        }

        return ($this->localFiscalOperations);
    }

    /**
     * Returns the actual query body of fiscal operation
     *
     * @param $operationID
     *
     * @return string
     */
    public function getFiscalOperationLocalBody($operationID) {
        $tQuery = "SELECT `operation_body` FROM `dreamkas_operations` WHERE `operation_id` = '" . $operationID . "'";
        $tQueryResult = simple_queryall($tQuery);
        $result = '';

        if (!empty($tQueryResult)) {
            foreach ($tQueryResult as $eachRec) {
                $result = base64_decode($eachRec['operation_body']);
            }
        }

        return ($result);
    }

    /**
     * Returns the all actual data of fiscal operation
     *
     * @param $operationID
     *
     * @return false|string
     */
    public function getFiscalOperationLocalData($operationID) {
        $tQuery = "SELECT * FROM `dreamkas_operations` WHERE `operation_id` = '" . $operationID . "'";
        $tQueryResult = simple_queryall($tQuery);
        $result = '';

        if (!empty($tQueryResult)) {
            foreach ($tQueryResult as $eachRec) {
                $result = $eachRec;
            }
        }

        return ($result);
    }

    /**
     * Simply increments fiscal operation repeats counter
     *
     * @param $operationID
     *
     * @return void
     */
    public function incrFiscalOperationRepeatsCount($operationID) {
        $tQuery = "UPDATE `dreamkas_operations` SET `repeat_count` = `repeat_count` + 1 WHERE `operation_id` = '" . $operationID . "'";
        nr_query($tQuery);
    }

    /**
     * Returns last error message
     *
     * @return string
     */
    public function getLastErrorMessage() {
        return ($this->lastErrorMEssage);
    }

    /**
     * Returns selling positions array via Dreamkas API
     *
     * @return mixed
     */
    public function getSellingPositions($goodsID = '') {
        $urlString = self::URL_API . 'v2/products' . ((empty($goodsID)) ? '' : '/' . $goodsID);
        //$urlString = self::URL_API . 'products' . ((empty($goodsID)) ? '' : '/' . $goodsID);

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $urlString);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $this->basicHTTPHeaders);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        $result = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        //PHP 8.0+ has no need to close curl resource anymore
        if (PHP_VERSION_ID < 80000) {
            curl_close($curl);
        }

        $tSelPosArr = json_decode($result, true);
        $result = array();

        if (!empty($tSelPosArr)) {
            if (substr($httpCode, 0, 1) == '2') {
                if (empty($goodsID)) {
                    foreach ($tSelPosArr as $eachItem) {
                        $this->sellingPositions[$eachItem['id']] = $eachItem;
                    }

                    $result = $this->sellingPositions;
                } else {
                    $result = $tSelPosArr;
                }
            } else {
                log_register('DREAMKAS getting selling positions error: ' . $this->errorToString($tSelPosArr));
            }
        }

        return ($result);
    }

    /**
     * Returns receipts array via Dreamkas API
     *
     * @return mixed
     */
    protected function getReceipts($dateFrom = '', $dateTo = '', $cashDeviceID = '', $limit = 1000) {
        if (!empty($dateFrom) and $dateFrom == $dateTo) {
            $dateTo = $dateTo . ' 23:59:59';
        } elseif (!empty($dateFrom) and empty($dateTo)) {
            $dateTo = $dateFrom . ' 23:59:59';
        }

        $limit = '?limit=' . $limit;
        $urlString = self::URL_API . 'receipts' . $limit .
                ((empty($dateFrom)) ? '' : '&from=' . date('Y-m-d\TH:i:s.Z\Z', strtotime($dateFrom))) .
                ((empty($dateTo)) ? '' : '&to=' . date('Y-m-d\TH:i:s.Z\Z', strtotime($dateTo))) .
                ((empty($cashDeviceID)) ? '' : '&devices=' . $cashDeviceID);

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $urlString);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $this->basicHTTPHeaders);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        $result = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        //PHP 8.0+ has no need to close curl resource anymore
        if (PHP_VERSION_ID < 80000) {
            curl_close($curl);
        }

        $result = json_decode($result, true);

        if (substr($httpCode, 0, 1) != '2') {
            log_register('DREAMKAS getting receipts error: ' . $this->errorToString($result));
            $result = array();
        }

        return ($result);
    }

    /**
     * Returns receipt detail info array via Dreamkas API
     *
     * @return mixed
     */
    public function getReceiptDetails($receiptID) {
        $urlString = self::URL_API . 'receipts/' . $receiptID;

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $urlString);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $this->basicHTTPHeaders);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        $result = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        //PHP 8.0+ has no need to close curl resource anymore
        if (PHP_VERSION_ID < 80000) {
            curl_close($curl);
        }

        return (json_decode($result, true));
    }

    /**
     * gets ubilling system key into private key prop
     *
     * @return string
     */
    protected function getUBSerial() {
        $hostid_q = "SELECT * from `ubstats` WHERE `key`='ubid'";
        $hostid = simple_query($hostid_q);
        if (!empty($hostid)) {
            if (isset($hostid['value'])) {
                return ($hostid['value']);
            }
        }
    }

    protected function getWebHooks($webhookID = '') {
        $urlString = self::URL_API . 'webhooks' . ((empty($webhookID)) ? '' : '/' . $webhookID);

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $urlString);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $this->basicHTTPHeaders);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        $result = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        //PHP 8.0+ has no need to close curl resource anymore
        if (PHP_VERSION_ID < 80000) {
            curl_close($curl);
        }

        $tWebHookArr = json_decode($result, true);
        $result = array();

        if (!empty($tWebHookArr)) {
            if (substr($httpCode, 0, 1) == '2') {
                if (empty($webhookID)) {
                    foreach ($tWebHookArr as $eachItem) {
                        $this->webhooks[$eachItem['id']] = $eachItem;
                    }

                    $result = $this->webhooks;
                } else {
                    $result = $tWebHookArr;
                }
            } else {
                log_register('DREAMKAS getting webhooks error: ' . $this->errorToString($tWebHookArr));
            }
        }

        return ($result);
    }

    /**
     * Returns an HTML-code string containing selector control
     *
     * @return mixed
     */
    protected function getSelectorWebControl($selectorData, $selectorName, $selectedItem = '', $selectorID = '', $selectorClass = '', $selectorLabel = '', $insBR = false) {
        $result = wf_Selector($selectorName, $selectorData, $selectorLabel, $selectedItem, $insBR, true, $selectorID, $selectorClass);

        return ($result);
    }

    /**
     * Returns an HTML-code string containing selector control
     *
     * @param string $selectorID
     * @param string $selectorClass
     * @param string $title
     * @param bool $insBR
     *
     * @return string
     */
    public function getTaxTypesVATSelector($selectorID = '', $selectorClass = '', $title = '', $insBR = false, $selectedItem = '') {
        $labelTitle = (empty($title)) ? __('Choose VAT tax type') : $title;
        $ctrlID = (empty($selectorID)) ? 'DreamkasVATTypeSelector' : $selectorID;
        $ctrlClass = (empty($selectorClass)) ? '__DreamkasVATTypeSelector' : $selectorClass;

        $result = wf_Selector('drsvattypes', $this->paymentTypes, $labelTitle, $selectedItem, $insBR, true, $ctrlID, $ctrlClass);

        return ($result);
    }

    /**
     * Fills up $this->cashMachines4Selector array for further use
     *
     * @return array
     */
    protected function getCashMachines4Selector() {
        if (empty($this->cashMachines)) {
            $this->cashMachines = (isset($this->dataCahched['cashmachines'])) ? $this->dataCahched['cashmachines'] : array();
        }

        if (!empty($this->cashMachines)) {
            foreach ($this->cashMachines as $eachCM) {
                $this->cashMachines4Selector[$eachCM['id']] = $eachCM['id'] . ' ' . $eachCM['name'];
            }
        }

        return ($this->cashMachines4Selector);
    }

    /**
     * Fills up $this->sellingPositionsIDsNames array for further use
     *
     * @return array
     */
    protected function getSellPosIDsNames() {
        if (empty($this->sellingPositions)) {
            $this->sellingPositions = (isset($this->dataCahched['sellingpositions'])) ? $this->dataCahched['sellingpositions'] : array();
        }

        if (!empty($this->sellingPositions)) {
            foreach ($this->sellingPositions as $sellingPosition) {
                $this->sellingPositionsIDsNames[$sellingPosition['id']] = $sellingPosition['name'];
            }
        }

        return ($this->sellingPositionsIDsNames);
    }

    /**
     * Fills up $this->sellPos2SrvTypeMapping array with services to selling positions mapping
     *
     * @return void
     */
    protected function getSellPos2SrvTypeMapping() {
        $tQuery = "SELECT * FROM `dreamkas_services_relations`";
        $tQueryResult = simple_queryall($tQuery);

        if (!empty($tQueryResult)) {
            foreach ($tQueryResult as $eachRow) {
                $this->sellPos2SrvTypeMapping[$eachRow['service']] = $eachRow;
            }
        }
    }

    /**
     * Returns Banksta2 record ID from `dreamkas_banksta2_relations` table, if exists
     *
     * @param $fiscopID
     *
     * @return int|mixed
     */
    public function getBS2RecIDbyFiscOpID($fiscopID) {
        $result = 0;

        if (empty($this->bs2RelationsUnProcFiscOpKey)) {
            $this->getBS2RelationsUnProcessed();
        }

        if (!empty($this->bs2RelationsUnProcFiscOpKey) and isset($this->bs2RelationsUnProcFiscOpKey[$fiscopID])) {
            $result = $this->bs2RelationsUnProcFiscOpKey[$fiscopID]['bs2_rec_id'];
        }

        return($result);
    }

    /**
     * Fills $this->bs2RelationsProcessed placeholder with data
     */
    protected function getBS2RelationsProcessed() {
        $tQuery = "SELECT * FROM `dreamkas_banksta2_relations` WHERE `receipt_id` IS NOT NULL AND `receipt_id` != ''";
        $tQueryResult = simple_queryall($tQuery);

        if (!empty($tQueryResult)) {
            foreach ($tQueryResult as $eachRow) {
                $this->bs2RelationsProcessed[$eachRow['bs2_rec_id']] = $eachRow;
            }
        }
    }

    /**
     * Fills $this->bs2RelationsUnProcessed placeholder with data
     */
    protected function getBS2RelationsUnProcessed() {
        $tQuery = "SELECT * FROM `dreamkas_banksta2_relations` WHERE `receipt_id` IS NULL OR `receipt_id` = ''";
        $tQueryResult = simple_queryall($tQuery);

        if (!empty($tQueryResult)) {
            foreach ($tQueryResult as $eachRow) {
                $this->bs2RelationsUnProcessed[$eachRow['bs2_rec_id']] = $eachRow;
                $this->bs2RelationsUnProcFiscOpKey[$eachRow['operation_id']] = $eachRow;
            }
        }
    }

    /**
     * Just inserts a new relational record if a fiscal operation from Banksta2 was made
     *
     * @param $bs2RecID
     * @param $fiscopID
     */
    protected function setBS2Relations($bs2RecID, $fiscopID, $fiscopReceiptID = '') {
        $this->getBS2RelationsUnProcessed();

        if (!empty($this->bs2RelationsUnProcessed) and isset($this->bs2RelationsUnProcessed[$bs2RecID])) {
            $tQuery = "UPDATE `dreamkas_banksta2_relations` SET 
                                `operation_id` = '" . $fiscopID . "',
                                `receipt_id` = '" . $fiscopReceiptID . "'
                            WHERE `bs2_rec_id` = " . $bs2RecID;
        } else {
            $tQuery = "INSERT INTO `dreamkas_banksta2_relations` (`bs2_rec_id`, `operation_id`, `receipt_id`) 
                                                       VALUES(" . $bs2RecID . ", '" . $fiscopID . "', '" . $fiscopReceiptID . "')";
        }

        nr_query($tQuery);
    }

    /**
     * To full fill avidity and greed
     *
     * @param $ukvUserID
     * @param $ukvObject
     *
     * @return mixed
     */
    public function getUKVUserData($ukvUserID, $ukvObject) {
        return ($ukvObject->getUserData($ukvUserID));
    }

    /**
     * To full fill avidity and greed
     *
     * @param $login
     *
     * @return string
     */
    public function getInetUserMobile($login) {
        return (zb_UserGetMobile($login));
    }

    /**
     * To full fill avidity and greed
     *
     * @param $login
     *
     * @return string
     */
    public function getInetUserEmail($login) {
        return (zb_UserGetEmail($login));
    }

    /**
     * Checks if sell position is mapped to some or certain service already
     *
     * @param $goodsID
     * @param string $service
     *
     * @return int|string
     */
    protected function checkSellPosIsMapped2SrvType($goodsID, $service = '') {
        $result = '';

        if (empty($this->sellPos2SrvTypeMapping)) {
            $this->getSellPos2SrvTypeMapping();
        }

        if (!empty($this->sellPos2SrvTypeMapping)) {
            foreach ($this->sellPos2SrvTypeMapping as $eachSrv => $eachGoodsArr) {
                if (empty($service) and $eachGoodsArr['goods_id'] == $goodsID) {
                    $result = $eachSrv;
                    break;
                }

                if ($eachSrv != $service and $eachGoodsArr['goods_id'] == $goodsID) {
                    $result = $eachSrv;
                    break;
                }
            }
        }

        return ($result);
    }

    /**
     * Maps certain selling position to certain service(Inet or UKV)
     *
     * @param $service
     * @param $goodsID
     * @param $goodsName
     * @param $goodsType
     * @param $goodsPrice
     * @param $goodsTax
     * @param $goodsVendorCode
     *
     * @return string
     */
    public function setSellingPositionSrvType($service, $goodsID, $goodsName, $goodsType, $goodsPrice, $goodsTax, $goodsVendorCode) {
        $result = '';

        If ($goodsType != self::RECEIPT_PRODUCT_TYPE) {
            $result = __('Sell positions of type ' . self::RECEIPT_PRODUCT_TYPE . ' allowed only');
        }

        $alreadyMappedTo = $this->checkSellPosIsMapped2SrvType($goodsID, $service);

        if (!empty($alreadyMappedTo)) {
            $result = __('This sell position is already mapped to ' . $alreadyMappedTo);
        }

        if (empty($result)) {
            if (isset($this->sellPos2SrvTypeMapping[$service])) {
                $tQuery = "UPDATE `dreamkas_services_relations` SET                              
                              `goods_id` = '" . $goodsID . "',
                              `goods_name` = '" . $goodsName . "',
                              `goods_type` = '" . $goodsType . "',
                              `goods_price` = " . $goodsPrice . ",
                              `goods_tax` = '" . $goodsTax . "',
                              `goods_vendorcode` = '" . $goodsVendorCode . "'
                          WHERE `service` = '" . $service . "'
                      ";
                nr_query($tQuery);

                log_register('DREAMKAS service ' . $service . ' sell position CHANGED from ' .
                        $this->sellPos2SrvTypeMapping[$service]['goods_name'] . ' to ' . $goodsName);
            } else {
                $tQuery = "INSERT INTO `dreamkas_services_relations` (`service`, `goods_id`, `goods_name`, `goods_type`, `goods_price`, `goods_tax`, `goods_vendorcode`) 
                                                              VALUES ('" . $service . "', '" . $goodsID . "', '" . $goodsName . "', '" . $goodsType . "', " . $goodsPrice . ", '" . $goodsTax . "', '" . $goodsVendorCode . "')";
                nr_query($tQuery);

                log_register('DREAMKAS service ' . $service . ' sell position SET to ' . $goodsName);
            }
        }

        return ($result);
    }

    /**
     * Deletes certain selling position to service mapping
     *
     * @param $service
     *
     * @return void
     */
    public function delSellingPositionSrvType($service) {
        if (!empty($service)) {
            $tQuery = "DELETE FROM `dreamkas_services_relations` WHERE `service` = '" . $service . "'";
            nr_query($tQuery);

            log_register('DREAMKAS service ' . $service . ' sell position DELETED');
        }
    }

    /**
     * Converts Dreamkas server error message to string for logging and debugging
     *
     * @param $errorArr
     *
     * @return string
     */
    protected function errorToString($errorArr) {
        $errorStr = '';

        if (!empty($errorArr)) {
            foreach ($errorArr as $eachItem => $eachValue) {
                if (strtolower($eachItem) == 'data' and ! empty($eachValue)) {
                    $tArr = $eachValue['errors'];

                    foreach ($tArr as $errItem) {
                        foreach ($errItem as $item => $val) {
                            if (!empty($item) and ! empty($val)) {
                                $errorStr .= $item . ': ' . $val . "\n";
                            }
                        }
                    }
                } else {
                    if (!empty($eachItem) and ! empty($eachValue)) {
                        $errorStr .= $eachItem . ': ' . $eachValue . "\n";
                    }
                }
            }


            if (empty($errorStr)) {
                $errorStr = print_r($errorArr, true);
            }
        }

        $this->putNotificationData2Cache($errorStr, 'error', __('DREAMKAS ERROR'));
        $this->lastErrorMEssage = $errorStr;

        return ($errorStr);
    }

    /**
     * Check fiscalize preparation routine
     *
     * @param $cashMachineID
     * @param $taxType
     * @param $paymentType
     * @param array $sellPosIDsPrices SellPosID => array('price' => SellPosPrice, 'quantity' => SellPosQuantity)
     * @param array $addUserContacts
     *
     * @return string/JSON
     */
    public function prepareCheckFiscalData($cashMachineID, $taxType, $paymentType, $sellPosIDsPrices, $addUserContacts = array()) {
        $checkBody = array();
        $checkJSON = json_encode(array());

        if (!empty($sellPosIDsPrices)) {
            $checkBody['deviceId'] = $cashMachineID;
            $checkBody['type'] = self::RECEIPT_OPERATION_TYPE;
            $checkBody['timeout'] = self::RECEIPT_FISCALIZE_TIMEOUT;
            $checkBody['taxMode'] = $taxType;
            $checkBody['positions'] = array();
            $checkBody['payments'] = array();
            $checkBody['attributes'] = array();
            $sellPosBody = array();
            $sellPaymentsBody = array();
            $userContacts = array();
            $checkTotal = 0;

            // gathering positions body
            foreach ($sellPosIDsPrices as $eachID => $each) {
                if (isset($this->sellingPositions[$eachID])) {
                    $tSellPosArr = $this->sellingPositions[$eachID];
                    $sellPosPrice = (isset($each['price'])) ? $each['price'] : $tSellPosArr['price'];
                    $sellPosQuantity = (isset($each['quantity'])) ? $each['quantity'] : $tSellPosArr['quantity'] / self::RECEIPT_QUANTITY_DIVIDER;

                    if (empty($sellPosPrice) and empty($sellPosQuantity)) {
                        continue;
                    }

                    $sellPosBody['name'] = $tSellPosArr['name'];
                    $sellPosBody['type'] = self::RECEIPT_PRODUCT_TYPE;
                    $sellPosBody['quantity'] = $sellPosQuantity;
                    $sellPosBody['price'] = $sellPosPrice;
                    $sellPosBody['tax'] = $tSellPosArr['tax'];

                    $sellPaymentsBody['sum'] = $sellPosPrice;
                    $sellPaymentsBody['type'] = $paymentType;

                    $checkBody['positions'][] = $sellPosBody;
                    $checkBody['payments'][] = $sellPaymentsBody;
                    $checkTotal += $sellPaymentsBody['sum'];

                    // because Dreamkas API doesn't allow empty phone or e-mail fields
                    // we need to figure out which of them are not empty and add filled only
                    if (isset($addUserContacts['phone']) and ! empty($addUserContacts['phone'])) {
                        $userContacts['phone'] = $addUserContacts['phone'];
                    }

                    if (isset($addUserContacts['email']) and ! empty($addUserContacts['email'])) {
                        $userContacts['email'] = $addUserContacts['email'];
                    }

                    if (!empty($userContacts)) {
                        $checkBody['attributes'] = $userContacts;
                    }

                    if (!empty($checkBody['positions'])) {
                        $checkBody['total'] = array('priceSum' => $checkTotal);
                        $checkJSON = json_encode($checkBody);
                    }
                } else {
                    log_register('DREAMKAS CHECK FISCALAZE PREPARATION ERROR: seems specified selling positions ' . $eachID . ' are not found');
                }
            }
        } else {
            log_register('DREAMKAS CHECK FISCALAZE PREPARATION ERROR: empty selling positions parameter passed');
        }

        return ($checkJSON);
    }

    /**
     * Check fiscalizing routine
     *
     * @param $preparedCheckDataJSON
     * @param int $banksta2RecID
     * @param string $repeatedFiscopID
     *
     */
    public function fiscalizeCheck($preparedCheckDataJSON, $banksta2RecID = 0, $repeatedFiscopID = '') {
        $tmpMessageStr = '';
        $tmpMessageType = '';

        if (!empty($preparedCheckDataJSON)) {
            // getting [total][price] for log
            $tArr = json_decode($preparedCheckDataJSON, true);
            $checkTotalPrice = (isset($tArr['total']['priceSum'])) ? $tArr['total']['priceSum'] / 100 : '';
            $checkEmail = (isset($tArr['attributes']['email'])) ? ' email: [' . $tArr['attributes']['email'] . '] ' : '';
            $checkPhone = (isset($tArr['attributes']['phone'])) ? ' phone: [' . $tArr['attributes']['phone'] . '] ' : '';
            $urlString = self::URL_API . 'receipts';
            $banksta2LogStr = '';

            $curl = curl_init();
            curl_setopt($curl, CURLOPT_POST, true);
            curl_setopt($curl, CURLOPT_URL, $urlString);
            curl_setopt($curl, CURLOPT_HTTPHEADER, $this->basicHTTPHeaders);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $preparedCheckDataJSON);
            $result = curl_exec($curl);

            $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            //PHP 8.0+ has no need to close curl resource anymore
            if (PHP_VERSION_ID < 80000) {
                curl_close($curl);
            }

            $result = json_decode($result, true);

            if (substr($httpCode, 0, 1) == '2') {
                if (isset($result['id']) and isset($result['createdAt']) and strtolower($result['status']) != 'error') {
                    $operationDate = date('Y-m-d H:i:s', strtotime($result['createdAt']));
                    $fiscopReceiptID = (isset($result['data']['receiptId'])) ? $result['data']['receiptId'] : '';

                    $tQuery = "INSERT INTO `dreamkas_operations` (`operation_id`, `date_create`, `status`, `receipt_id`, `operation_body`, `repeated_fiscop_id`)
                                                          VALUES ('" . $result['id'] . "', '" . $operationDate . "', '" . $result['status'] . "', '" . $fiscopReceiptID . "', '" . base64_encode($preparedCheckDataJSON) . "', '" . $repeatedFiscopID . "') ";
                    nr_query($tQuery);

                    if (!empty($banksta2RecID)) {
                        $this->setBS2Relations($banksta2RecID, $result['id'], $fiscopReceiptID);
                        $banksta2LogStr = ' BANKSTA2 record ID: ' . $banksta2RecID;
                    }

                    $tmpMessageType = 'info';
                    $tmpMessageStr = 'DREAMKAS CHECK FISCALAZING: operation added with ID [' . $result['id'] . ']. Check total sum: [' . $checkTotalPrice . ']. Check contacts: ' . $checkPhone . $checkEmail . $banksta2LogStr;
                } else {
                    $tmpMessageType = 'error';
                    $tmpMessageStr = 'DREAMKAS CHECK FISCALAZING ERROR. Check total sum: [' . $checkTotalPrice . ']. Check contacts: ' . $checkPhone . $checkEmail . ' SERVER ERROR MESSAGE: ' . $this->errorToString($result);
                }
            } else {
                $tmpMessageType = 'error';
                $tmpMessageStr = 'DREAMKAS CHECK FISCALAZING ERROR. Check total sum: [' . $checkTotalPrice . ']. Check contacts: ' . $checkPhone . $checkEmail . ' SERVER ERROR MESSAGE: ' . $this->errorToString($result);
            }
        } else {
            $tmpMessageType = 'error';
            $tmpMessageStr = 'DREAMKAS CHECK FISCALAZING ERROR: empty prepared check JSON parameter passed';
        }

        if (!empty($tmpMessageStr)) {
            log_register($tmpMessageStr);
            $this->putNotificationData2Cache($tmpMessageStr, $tmpMessageType, __('DREAMKAS INFO'));
        }
    }

    protected function updateFiscalOperationsLocalStorage($fopsData = array()) {
        $this->getBS2RelationsUnProcessed();

        if (empty($fopsData)) {
            $fopsData = $this->getFiscalOperations();
        }

        $fopsDataLocal = $this->getFiscalOperationsLocal("(`status` != 'SUCCESS' and `status` != 'ERROR') or (`status` = 'SUCCESS' and (`receipt_id` IS NULL or `receipt_id` = ''))");
        //$fopsDataLocal = $this->getFiscalOperationsLocal("`status` != 'ERROR'");
        $fopsBS2Data = $this->bs2RelationsUnProcFiscOpKey;

        if (!empty($fopsData)) {
            if (!empty($fopsDataLocal)) {
                foreach ($fopsData as $eachFiscOp) {
                    $fiscopID = $eachFiscOp['id'];
                    $fiscopStatus = $eachFiscOp['status'];
                    $fiscopDateFinish = (empty($eachFiscOp['completedAt'])) ? '0000-00-00 00:00:00' : date('Y-m-d H:i:s', strtotime($eachFiscOp['completedAt']));
                    $fiscopErrorCode = (isset($eachFiscOp['data']['error']['code'])) ? $eachFiscOp['data']['error']['code'] : '';
                    $fiscopErrorMsg = (isset($eachFiscOp['data']['error']['message'])) ? $eachFiscOp['data']['error']['message'] : '';
                    $fiscopReceiptID = (isset($eachFiscOp['data']['receiptId'])) ? $eachFiscOp['data']['receiptId'] : '';

                    if (isset($fopsDataLocal[$fiscopID])) {
                        $tQuery = "UPDATE `dreamkas_operations` SET 
                                      `status` = '" . $fiscopStatus . "', 
                                      `date_finish` = '" . $fiscopDateFinish . "',
                                      `error_code` = '" . $fiscopErrorCode . "',
                                      `error_message` = '" . $fiscopErrorMsg . "',
                                      `receipt_id` = '" . $fiscopReceiptID . "'
                                    WHERE `operation_id` = '" . $fiscopID . "'                                       
                              ";
                        nr_query($tQuery);

                        if (isset($fopsBS2Data[$fiscopID])) {
                            $tQuery = "UPDATE `dreamkas_banksta2_relations` SET 
                                      `receipt_id` = '" . $fiscopReceiptID . "'
                                    WHERE `operation_id` = '" . $fiscopID . "'
                                  ";
                            nr_query($tQuery);
                        }
                    }
                }
            }
        } else {
            log_register('DREAMKAS updating local fiscal operations status FAILED: server returned empty answer');
        }
    }

    /**
     * Returns a special JS function for controls processing on banksta2 payments processing table
     *
     * @return string
     */
    public function get_BS2FiscalizePaymentCtrlsJS() {
        $js = wf_tag('script', false, '', 'type="text/javascript"');
        $js .= '
                function endisFiscalizingControls(checkCtrlID) {                                
                    var controlDisabled = !$(\'#\'+checkCtrlID).is(\':checked\');
                    var controlIndex = checkCtrlID.substring(checkCtrlID.indexOf(\'_\') + 1);
                    
                    $(\'#DreamkasCashMachineSelector_\'+controlIndex).prop("disabled", controlDisabled);
                    $(\'#DreamkasTaxTypeSelector_\'+controlIndex).prop("disabled", controlDisabled);
                    $(\'#DreamkasPaymTypeSelector_\'+controlIndex).prop("disabled", controlDisabled);
                    $(\'#DreamkasSellPosSelector_\'+controlIndex).prop("disabled", controlDisabled);
                    
                    $(\'#FiscalizePaymHidden_\'+controlIndex).val((controlDisabled) ? 0 : 1);
                }
                ';
        $js .= wf_tag('script', true);

        return ($js);
    }

    /**
     * Pushes notifications data to cash for further usage
     *
     * @param $notyBody
     * @param $notyType
     */
    public function putNotificationData2Cache($notyBody, $notyType = 'info', $notyTitle = 'DREAMKAS') {
        $tKey = wf_delimiter();
        $tArr = array();

        if (!empty($notyBody)) {
            $tArr[$tKey] = array('text' => $notyBody, 'type' => $notyType, 'title' => $notyTitle);
            $this->ubCache->set(self::DREAMKAS_NOTYS_CAHCE_KEY, $tArr, $this->notysCachingTimeout);
        }
    }

    /**
     * Returns an HTML form for payment fiscalizing for integration as addition to payment forms
     *
     * @param $serviceType
     * @param $processingBanksta2
     * @param $bankstaRecID
     * @param $bankstaRecProcessed
     *
     * @return string
     */
    public function web_FiscalizePaymentCtrls($serviceType, $processingBanksta2 = false, $bankstaRecID = '', $bankstaRecProcessed = false) {
        $selectSellPositionID = (isset($this->sellPos2SrvTypeMapping[strtolower($serviceType)]['goods_id'])) ? $this->sellPos2SrvTypeMapping[strtolower($serviceType)]['goods_id'] : '';
        $processedClassMark = ($bankstaRecProcessed ? ' __BankstaRecProcessed ' : '');

        if ($processingBanksta2) {
            $cells = wf_TableCell(__('Fiscalize this payment?') . wf_nbsp() .
                    wf_CheckInput('fiscalizepayment_' . $bankstaRecID, '', false, $this->alwaysFiscalizeAll, 'FiscalizeManualPaym_' . $bankstaRecID, $processedClassMark), '', 'row2');
            $cells .= wf_TableCell(__('Choose cash machine') . wf_nbsp() .
                    $this->getSelectorWebControl($this->cashMachines4Selector, 'drscashmachines_' . $bankstaRecID, $this->defaultCashMachineID, 'DreamkasCashMachineSelector_' . $bankstaRecID, '__DreamkasCashMachineSelector'), '', 'row2', '', '3');
            $cells .= wf_TableCell(__('Choose tax type') . wf_nbsp() .
                    $this->getSelectorWebControl($this->taxTypes, 'drstaxtypes_' . $bankstaRecID, $this->defaultTaxType, 'DreamkasTaxTypeSelector_' . $bankstaRecID, '__DreamkasTaxTypeSelector'), '', 'row2', '', '3');
            $cells .= wf_TableCell(__('Choose payment type') . wf_nbsp() .
                    $this->getSelectorWebControl($this->paymentTypes, 'drspaymtypes_' . $bankstaRecID, 'CASHLESS', 'DreamkasPaymTypeSelector_' . $bankstaRecID, '__DreamkasPaymTypeSelector'), '', 'row2', '', '3');
            $cells .= wf_TableCell(__('Choose selling position') . wf_nbsp() .
                    $this->getSelectorWebControl($this->sellingPositionsIDsNames, 'drssellpos_' . $bankstaRecID, $selectSellPositionID, 'DreamkasSellPosSelector_' . $bankstaRecID, '__DreamkasSellPosSelector'), '', 'row2', '', '3');

            $row = wf_TableRow($cells);

            $form = $row;
            $form .= wf_HiddenInput('dofiscalizepayment_' . $bankstaRecID, '0', 'FiscalizePaymHidden_' . $bankstaRecID);
            $form .= wf_tag('script', false, '', 'type="text/javascript"');
            $form .= '
                    $(document).ready(function() {
                        endisFiscalizingControls(\'FiscalizeManualPaym_' . $bankstaRecID . '\');
                    });
                    
                    $(\'#FiscalizeManualPaym_' . $bankstaRecID . '\').change(function() {
                        endisFiscalizingControls(\'FiscalizeManualPaym_' . $bankstaRecID . '\');
                    });
                    ';
            // be sure to call get_BS2FiscalizePaymentCtrlsJS() after banksta2 payments processing table is assembled
            $form .= wf_tag('script', true);
        } else {
            $cells = wf_TableCell(__('Fiscalize this payment?'), '', 'row2');
            $cells .= wf_TableCell(wf_CheckInput('fiscalizepayment', '', false, $this->alwaysFiscalizeAll, 'FiscalizeManualPaym'), '', 'row3');
            $rows = wf_TableRow($cells);

            $cells = wf_TableCell(__('Choose cash machine'), '', 'row2');
            $cells .= wf_TableCell($this->getSelectorWebControl($this->cashMachines4Selector, 'drscashmachines', $this->defaultCashMachineID, 'DreamkasCashMachineSelector', '__DreamkasCashMachineSelector'), '', 'row3');
            $rows .= wf_TableRow($cells);

            $cells = wf_TableCell(__('Choose tax type'), '', 'row2');
            $cells .= wf_TableCell($this->getSelectorWebControl($this->taxTypes, 'drstaxtypes', $this->defaultTaxType, 'DreamkasTaxTypeSelector', '__DreamkasTaxTypeSelector'), '', 'row3');
            $rows .= wf_TableRow($cells);

            $cells = wf_TableCell(__('Choose payment type'), '', 'row2');
            $cells .= wf_TableCell($this->getSelectorWebControl($this->paymentTypes, 'drspaymtypes', 'CASH', 'DreamkasPaymTypeSelector', '__DreamkasPaymTypeSelector'), '', 'row3');
            $rows .= wf_TableRow($cells);

            $cells = wf_TableCell(__('Choose selling position'), '', 'row2');
            $cells .= wf_TableCell($this->getSelectorWebControl($this->sellingPositionsIDsNames, 'drssellpos', $selectSellPositionID, 'DreamkasSellPosSelector', '__DreamkasSellPosSelector'), '', 'row3');
            $rows .= wf_TableRow($cells);

            $table = wf_TableBody($rows, '100%', 0, '');

            $form = $table;
            $form .= wf_HiddenInput('dofiscalizepayment', '0', 'FiscalizeManualPaymHidden');
            $form .= wf_tag('script', false, '', 'type="text/javascript"');
            $form .= '
                $(document).ready(function() {
                    endisFiscalizingControls();
                });
                
                $(\'#FiscalizeManualPaym\').change(function() {
                    endisFiscalizingControls();
                });
                
                function endisFiscalizingControls() {
                    var controlDisabled = !$(\'#FiscalizeManualPaym\').is(\':checked\');
                    
                    $(\'#DreamkasCashMachineSelector\').prop("disabled", controlDisabled);
                    $(\'#DreamkasTaxTypeSelector\').prop("disabled", controlDisabled);
                    $(\'#DreamkasPaymTypeSelector\').prop("disabled", controlDisabled);
                    $(\'#DreamkasSellPosSelector\').prop("disabled", controlDisabled);
                    
                    $(\'#FiscalizeManualPaymHidden\').val((controlDisabled) ? 0 : 1);
                }                
                ';
            $form .= wf_tag('script', true);
        }

        return ($form);
    }

    /**
     * Returns table row with payment fiscal data
     *
     * @param $bs2RecID
     *
     * @return string
     */
    public function web_ReceiptDetailsTableRow($bs2RecID) {
//        $row = wf_TableRow('');
        $row = '';

        if (empty($this->bs2RelationsProcessed)) {
            $this->getBS2RelationsProcessed();
        }

        if (isset($this->bs2RelationsProcessed[$bs2RecID]) and ! empty($this->bs2RelationsProcessed[$bs2RecID]['receipt_id'])) {
            $lnkID = wf_InputId();
            $ajaxInfoParams = array('showdetailedrcpt' => $this->bs2RelationsProcessed[$bs2RecID]['receipt_id']);
            $actions = wf_Link('#', wf_img('skins/icon_search_small.gif', __('Show details'), 'vertical-align: middle'), false, '', ' id="' . $lnkID . '" ');
            $actions .= wf_JSAjaxModalOpener(self::URL_ME, $ajaxInfoParams, $lnkID, true);

            $cells = wf_TableCell(__('Fiscal operation ID') . ':' . wf_nbsp(2) . $this->bs2RelationsProcessed[$bs2RecID]['operation_id'], '', '', 'style="border: solid #008a77; border-width: 1px 0 1px 1px; padding: 4px;"', '5');
            $cells .= wf_TableCell(__('Check ID') . ':' . wf_nbsp(2) . $this->bs2RelationsProcessed[$bs2RecID]['receipt_id'] . wf_nbsp(4) . $actions, '', '', 'style="border: solid #008a77; border-width: 1px 1px 1px 0; padding: 4px;"', '5');

            $row = wf_TableRow($cells);
        }

        return ($row);
    }

    public function web_FiscalOperationDetailsTableRow($bs2RecID) {
        $row = '';

        if (empty($this->bs2RelationsUnProcessed)) {
            $this->getBS2RelationsUnProcessed();
        }

        if (isset($this->bs2RelationsUnProcessed[$bs2RecID])) {
            $fiscopID = $this->bs2RelationsUnProcessed[$bs2RecID]['operation_id'];
            $fiscopData = $this->getFiscalOperationLocalData($fiscopID);
            $fiscopStatus = $fiscopData['status'];
            $fiscopDateCreate = $fiscopData['date_create'];

            $cells = wf_TableCell(wf_tag('b', false) . '#' . $bs2RecID . ':' . wf_tag('b', true) . wf_nbsp(4)
                    . __('Fiscal operation ID') . ':' . wf_nbsp(2) . $fiscopID
                    . wf_nbsp(8) . __('Creation date') . ':' . wf_nbsp(2) . $fiscopDateCreate
                    . wf_nbsp(8) . __('Current status') . ':' . wf_nbsp(2) . $fiscopStatus, '', '', 'style="border: solid #b84c04; border-width: 1px; padding: 4px;"', '11');

            $row = wf_TableRow($cells);
        }

        return ($row);
    }

    /**
     * Returns main buttons controls for Dreamkas
     *
     * @return string
     */
    public function web_MainButtonsControls() {
        $cacheLnkID = wf_InputId();
        $controls = wf_Link(self::URL_DREAMKAS_RECEIPTS, wf_img('skins/menuicons/receipt_small_compl.png') . wf_nbsp() . __('Issued checks'), false, 'ubButton') . wf_nbsp(2);
        $controls .= wf_Link(self::URL_DREAMKAS_OPERATIONS, wf_img('skins/icon_note.gif') . wf_nbsp() . __('Fiscal operations'), false, 'ubButton') . wf_nbsp(2);
        $controls .= wf_Link(self::URL_DREAMKAS_GOODS, wf_img_sized('skins/shopping_cart.png', '', '16', '16') . wf_nbsp() . __('Selling positions'), false, 'ubButton') . wf_nbsp(2);
        //$controls.= wf_delimiter(0);
        $controls .= wf_Link(self::URL_DREAMKAS_CASHIERS, wf_img_sized('skins/cashier.png', '', '16', '16') . wf_nbsp() . __('Cashiers'), false, 'ubButton') . wf_nbsp(2);
        $controls .= wf_Link(self::URL_DREAMKAS_CASH_MACHINES, wf_img_sized('skins/cash_machine.png', '', '16', '16') . wf_nbsp() . __('Cash machines'), false, 'ubButton');
        $controls .= wf_delimiter();
        $controls .= wf_Link(self::URL_DREAMKAS_WEBHOOKS, wf_img_sized('skins/ymaps/globe.png', '', '16', '16') . wf_nbsp() . __('Webhooks'), false, 'ubButton');
        $controls .= wf_Link('#', wf_img('skins/refresh.gif') . ' ' . __('Refresh cache data'), false, 'ubButton', 'id="' . $cacheLnkID . '"');
        $controls .= wf_JSAjaxModalOpener(self::URL_ME, array('dreamkasforcecacheupdate' => 'true'), $cacheLnkID, true);

        return ($controls);
    }

    /**
     * Returns a companion to fiscal operation form HTML form for fiscal operation data filtering
     *
     * @return string
     */
    public function web_FiscalOperationsFilter() {
        $formID = wf_InputId();
        $ajaxUrlStr = self::URL_ME . '&foperationslistajax=true';
        $jqdtId = 'jqdt_' . md5($ajaxUrlStr);

        $dateFrom = (ubRouting::checkGet('fopsdatefrom', false) ? ubRouting::get('fopsdatefrom') : date('Y-m-d', strtotime(curdate() . "-1 day")));
        $dateTo = (ubRouting::checkGet('fopsdateto', false) ? ubRouting::get('fopsdateto') : curdate());

        // filter controls for dates
        $inputs = wf_DatePickerPreset('fopsdatefrom', $dateFrom);
        $inputs .= __('Creation date from') . wf_nbsp(3);
        $inputs .= wf_DatePickerPreset('fopsdateto', $dateTo);
        $inputs .= __('Creation date to') . wf_nbsp(4);
        $inputs .= wf_SubmitClassed(true, 'ubButton', '', __('Show'));
        $inputs .= wf_tag('script', false, '', 'type="text/javascript"');
        $inputs .= '
                    $(\'#' . $formID . '\').submit(function(evt) {
                        evt.preventDefault();
                        var FrmData = $(\'#' . $formID . '\').serialize();

                        $(\'#' . $jqdtId . '\').DataTable().ajax.url(\'' . $ajaxUrlStr . '\' + \'&\' + FrmData).load();  
                        $(\'#' . $jqdtId . '\').DataTable().ajax.url(\'' . $ajaxUrlStr . '\');
                    });
                  ';
        $inputs .= wf_tag('script', true);

        $form = wf_Form('', 'POST', $inputs, 'glamour', '', $formID) . wf_delimiter(0);

        return ($form);
    }

    /**
     * Returns a companion to receipts form HTML form for receipts data filtering
     *
     * @return string
     */
    public function web_ReceiptsFilter() {
        $formID = wf_InputId();
        $ajaxUrlStr = self::URL_ME . '&receiptslistajax=true';
        $jqdtId = 'jqdt_' . md5($ajaxUrlStr);

        // filter controls for dates, cash ids and so on
        $inputs = wf_DatePicker('rcptdatefrom');
        $inputs .= __('Date from') . wf_nbsp(3);
        $inputs .= wf_DatePicker('rcptdateto');
        $inputs .= __('Date to') . wf_nbsp(4);
        $inputs .= wf_TextInput('rcptmaxcount', __('Number of checks to get (1000 max)'), '1000', false, '4', 'digits');
        $inputs .= wf_nbsp(3);
        $inputs .= wf_TextInput('rcptdeviceid', __('Cash machine ID'), '', false, '4', 'digits');
        $inputs .= wf_nbsp(3);
        $inputs .= wf_SubmitClassed(true, 'ubButton', '', __('Show'));
        $inputs .= wf_tag('script', false, '', 'type="text/javascript"');
        $inputs .= '
                    $(\'#' . $formID . '\').submit(function(evt) {
                        evt.preventDefault();
                        var FrmData = $(\'#' . $formID . '\').serialize();
                        $(\'#' . $jqdtId . '\').DataTable().ajax.url(\'' . $ajaxUrlStr . '\' + \'&\' + FrmData).load();  
                        $(\'#' . $jqdtId . '\').DataTable().ajax.url(\'' . $ajaxUrlStr . '\');
                    });
                  ';
        $inputs .= wf_tag('script', true);

        $form = wf_Form('', 'POST', $inputs, 'glamour', '', $formID) . wf_delimiter(0);

        return ($form);
    }

    public function web_WebhooksForm() {
        $lnkId = wf_InputId();
        $addServiceJS = wf_tag('script', false, '', 'type="text/javascript"');
        $addServiceJS .= wf_JSAjaxModalOpener(self::URL_ME, array('whcreate' => 'true'), $lnkId, false, 'POST');
        $addServiceJS .= wf_tag('script', true);

        show_window(__('Webhooks'), wf_Link('#', web_add_icon() . ' ' .
                        __('Add webhook'), false, 'ubButton', 'id="' . $lnkId . '"') .
                wf_delimiter() . $addServiceJS . $this->renderWebhooksJQDT()
        );
    }

    /**
     * JSON for cashiers JQDT
     */
    public function renderCashiersListJSON() {
        $cashiersData = (isset($this->dataCahched['cashiers'])) ? $this->dataCahched['cashiers'] : array();
        $json = new wf_JqDtHelper();

        if (!empty($cashiersData)) {
            $data = array();

            foreach ($cashiersData as $eachCashier) {
                $data[] = $eachCashier['tabNumber'];
                $data[] = $eachCashier['name'];
                $data[] = $eachCashier['inn'];
                $data[] = empty($eachCashier['deviceId']) ? '-' : $eachCashier['deviceId'];

                $json->addRow($data);
                unset($data);
            }
        }

        $json->getJson();
    }

    /**
     * JQDT for cashiers list form
     *
     * @return string
     */
    public function renderCashiersJQDT() {
        $ajaxUrlStr = self::URL_ME . '&cashiersslistajax=true';
        $columns = array();
        $opts = '"order": [[ 0, "desc" ]],
                "columnDefs": [ {"targets": "_all", "className": "dt-center"} ]';

        $columns[] = __('Tab number');
        $columns[] = __('Name');
        $columns[] = __('INN');
        $columns[] = __('Cash machine ID');

        return (wf_JqDtLoader($columns, $ajaxUrlStr, false, __('Cashiers'), 100, $opts));
    }

    /**
     * JSON for cash machines JQDT
     */
    public function renderCashMachinesListJSON() {
        $cashMachinesData = (isset($this->dataCahched['cashmachines'])) ? $this->dataCahched['cashmachines'] : array();
        $json = new wf_JqDtHelper();

        if (!empty($cashMachinesData)) {
            $data = array();

            foreach ($cashMachinesData as $eachCM) {
                $data[] = $eachCM['id'];
                $data[] = ($eachCM['isOnline']) ? web_green_led() : web_red_led();
                $data[] = $eachCM['name'];
                $data[] = $eachCM['modelCode'];
                $data[] = $eachCM['modelName'];
                $data[] = date('Y-m-d H:i:s', strtotime($eachCM['kktExpireDate']));

                $lnkID = wf_InputId();
                $ajaxInfoParams = array('showdetailedCM' => $eachCM['id']);

                $actions = wf_Link('#', wf_img('skins/icon_search_small.gif', __('Show details')), false, '', ' id="' . $lnkID . '" ');
                $actions .= wf_JSAjaxModalOpener(self::URL_ME, $ajaxInfoParams, $lnkID, true);

                $data[] = $actions;

                $json->addRow($data);
                unset($data);
            }
        }

        $json->getJson();
    }

    /**
     * JQDT for cash machines list form
     *
     * @return string
     */
    public function renderCashMachinesJQDT() {
        $ajaxUrlStr = self::URL_ME . '&cashmachineslistajax=true';
        $columns = array();
        $opts = '"order": [[ 0, "desc" ]],
                "columnDefs": [ {"targets": "_all", "className": "dt-center"} ]';

        $columns[] = __('Cash machine ID');
        $columns[] = __('Online');
        $columns[] = __('Cash machine name');
        $columns[] = __('Model code');
        $columns[] = __('Model name');
        $columns[] = __('Registration date expires');
        $columns[] = __('Actions');

        return (wf_JqDtLoader($columns, $ajaxUrlStr, false, __('Cash machines'), 100, $opts));
    }

    /**
     * JSON for selling positions JQDT
     */
    public function renderSellPositionsListJSON() {
        $sellingPositions = (isset($this->dataCahched['sellingpositions'])) ? $this->dataCahched['sellingpositions'] : array();
        $json = new wf_JqDtHelper();

        if (!empty($sellingPositions)) {
            $ajaxURLStr = self::URL_ME . '&goodslistajax=true';
            $JQDTId = 'jqdt_' . md5($ajaxURLStr);
            $data = array();

            foreach ($sellingPositions as $eachItem) {
                $sellPosMapped2Srv = $this->checkSellPosIsMapped2SrvType($eachItem['id']);
                $disableLink = (!empty($sellPosMapped2Srv)) ? 'style="opacity: 0.35; pointer-events: none"' : '';
                $enableDelLnk = (empty($sellPosMapped2Srv)) ? 'style="opacity: 0.35; pointer-events: none"' : '';

                $data[] = empty($eachItem['category']) ? '-' : $eachItem['category'];
                $data[] = $eachItem['name'];
                $data[] = $eachItem['type'];
                $data[] = $eachItem['quantity'];
                $data[] = $eachItem['price'];
                $data[] = (isset($eachItem['barcodes'][0])) ? $eachItem['barcodes'][0] : '';
                $data[] = (isset($eachItem['vendorCodes'][0])) ? $eachItem['vendorCodes'][0] : '';
                $data[] = $eachItem['tax'];
                $data[] = date('Y-m-d H:i:s', strtotime($eachItem['createdAt']));
                $data[] = date('Y-m-d H:i:s', strtotime($eachItem['updatedAt']));
                $data[] = ($sellPosMapped2Srv == 'internet') ? __('Internet') : (($sellPosMapped2Srv == 'ukv') ? __('UKV') : '');

                $infoLnkID = wf_InputId();
                $setInetLnkID = wf_InputId();
                $setUKVLnkID = wf_InputId();
                $delMappingLnkID = wf_InputId();

                $ajaxInfoParams = array('showdetailedGoods' => $eachItem['id']);
                $ajaxDelMappingParams = array('delselpossrvmapping' => 'true', 'servicetype' => $sellPosMapped2Srv);
                $ajaxSetMappingParams = array(
                    'goodsid' => $eachItem['id'],
                    'goodsName' => $eachItem['name'],
                    'goodsType' => $eachItem['type'],
                    'goodsPrice' => $eachItem['price'],
                    'goodsTax' => $eachItem['tax'],
                    'goodsVendorCode' => (isset($eachItem['vendorCodes'][0])) ? $eachItem['vendorCodes'][0] : ''
                );

                $actions = wf_Link('#', wf_img('skins/icon_search_small.gif', __('Show details')), false, '', ' id="' . $infoLnkID . '" ');
                $actions .= wf_Link('#', wf_img('skins/ymaps/globe.png', __('Link to Internet service')), false, '', ' id="' . $setInetLnkID . '" ' . $disableLink);
                $actions .= wf_Link('#', wf_img('skins/menuicons/tv.png', __('Link to UKV service')), false, '', ' id="' . $setUKVLnkID . '" ' . $disableLink);
                $actions .= wf_Link('#', web_delete_icon(__('Delete mapping')), false, '', ' id="' . $delMappingLnkID . '" ' . $enableDelLnk);

                $actions .= wf_tag('script', false, '', 'type="text/javascript"');
                $actions .= wf_JSAjaxModalOpener(self::URL_ME, $ajaxInfoParams, $infoLnkID);
                $actions .= wf_JSAjaxModalOpener(self::URL_ME . '&setselpossrvmapping=internet', $ajaxSetMappingParams, $setInetLnkID, false, 'GET', 'click', false, false, $JQDTId);
                $actions .= wf_JSAjaxModalOpener(self::URL_ME . '&setselpossrvmapping=ukv', $ajaxSetMappingParams, $setUKVLnkID, false, 'GET', 'click', false, false, $JQDTId);
                $actions .= wf_JSAjaxModalOpener(self::URL_ME, $ajaxDelMappingParams, $delMappingLnkID, false, 'GET', 'click', false, false, $JQDTId);
                $actions .= wf_tag('script', true);

                $data[] = $actions;

                $json->addRow($data);
                unset($data);
            }
        }

        $json->getJson();
    }

    /**
     * JQDT for selling positions list form
     *
     * @return string
     */
    public function renderSellPositionsJQDT() {
        $ajaxURLStr = self::URL_ME . '&goodslistajax=true';
        $columns = array();
        $opts = '"order": [[ 1, "asc" ]],
                "columnDefs": [ {"targets": [2, 3, 4, 5, 6, 7, 8, 9, 10, 11], "className": "dt-center"},
                                {"targets": [11], "width": "70px"}                
                                ]';

        $columns[] = __('Category');
        $columns[] = __('Selling position');
        $columns[] = __('Type');
        $columns[] = __('Quantity');
        $columns[] = __('Price');
        $columns[] = __('Barcode');
        $columns[] = __('Vendor code');
        $columns[] = __('Tax type');
        $columns[] = __('Creation date');
        $columns[] = __('Last edit date');
        $columns[] = __('Mapped to service');
        $columns[] = __('Actions');

        return (wf_JqDtLoader($columns, $ajaxURLStr, false, __('Selling positions'), 100, $opts));
    }

    /**     OLD
     * JSON for fiscal operations JQDT
     */
    /*    public function renderFiscalOperationsListJSON_old($dateFrom = '', $dateTo = '') {
      $fopsData = $this->getFiscalOperations($dateFrom, $dateTo);
      $fopsDataLocal = $this->getFiscalOperationsLocal();
      $json = new wf_JqDtHelper();

      if (!empty($fopsData)) {
      $this->updateFiscalOperationsLocalStorage($fopsData);
      $fopsData = $this->getFiscalOperationsLocal();
      $ajaxURLStr = self::URL_ME . '&foperationslistajax=true';
      $JQDTId = 'jqdt_' . md5($ajaxURLStr);
      $data = array();
      //$fopsData = $fopsData['data'];

      foreach ($fopsData as $eachFOperation) {
      $fiscopID = $eachFOperation['id'];

      $data[] = $fiscopID;
      $data[] = date('Y-m-d H:i:s', strtotime($eachFOperation['createdAt']));
      $data[] = date('Y-m-d H:i:s', strtotime($eachFOperation['completedAt']));
      $data[] = $eachFOperation['status'];

      if (isset($eachFOperation['data']['error'])) {
      $data[] = $eachFOperation['data']['error']['code'];
      $data[] = $eachFOperation['data']['error']['message'];
      } else {
      $data[] = '';
      $data[] = '';
      }

      if (isset($eachFOperation['data']['receiptId'])) {
      $data[] = $eachFOperation['data']['receiptId'];
      } else {
      $data[] = '';
      }

      if (isset($fopsDataLocal[$fiscopID])) {
      $data[] = $fopsDataLocal[$fiscopID]['repeat_count'];
      } else {
      $data[] = '';
      }

      $disableLink = (strtolower($eachFOperation['status']) == 'error') ? '' : 'style="opacity: 0.35; pointer-events: none"';

      $infoLnkID = wf_InputId();
      $repeatOpLnkID = wf_InputId();
      $ajaxInfoParams = array('showdetailedFiscOp' => $fiscopID);
      $ajaxRepeatOpParams = array('repeatFiscOp' => $fiscopID);

      $actions = wf_Link('#', wf_img('skins/icon_search_small.gif', __('Show details')), false, '', ' id="' . $infoLnkID . '" ');
      $actions .= wf_Link('#', wf_img('skins/refresh.gif', __('Repeat this operation')), false, '', ' id="' . $repeatOpLnkID . '" ' . $disableLink);

      $actions .= wf_tag('script', false, '', 'type="text/javascript"');
      $actions .= wf_JSAjaxModalOpener(self::URL_ME, $ajaxInfoParams, $infoLnkID);
      //$actions .= wf_JSAjaxModalOpener(self::URL_ME, $ajaxRepeatOpParams, $repeatOpLnkID, false, 'GET', 'click', false, false, $JQDTId);
      $actions .= wf_JSAjaxModalOpener(self::URL_ME, $ajaxRepeatOpParams, $repeatOpLnkID);
      $actions .= wf_tag('script', true);

      $data[] = $actions;

      $json->addRow($data);
      unset($data);
      }
      }

      $json->getJson();
      } */

    /**
     * JSON for fiscal operations JQDT
     *
     * @param string $dateFrom
     * @param string $dateTo
     */
    public function renderFiscalOperationsListJSON($dateFrom = '', $dateTo = '') {
        $json = new wf_JqDtHelper();

        $whereStr = '';
        if (!empty($dateFrom) and ! empty($dateTo)) {
            $whereStr = " `date_create` BETWEEN '" . $dateFrom . " 00:00:00' AND '" . $dateTo . "  23:59:59' ";
        } elseif (!empty($dateFrom) and empty($dateTo)) {
            $whereStr = " `date_create` >= '" . $dateFrom . "  00:00:00' ";
        } elseif (empty($dateFrom) and ! empty($dateTo)) {
            $whereStr = " `date_create` <= '" . $dateTo . "  23:59:59' ";
        }

        $fopsDataExt = $this->getFiscalOperations($dateFrom, $dateTo);
        if (!empty($fopsDataExt)) {
            $this->updateFiscalOperationsLocalStorage($fopsDataExt);
        }

        $fopsData = $this->getFiscalOperationsLocal($whereStr);
        if (!empty($fopsData)) {
            $ajaxURLStr = self::URL_ME . '&foperationslistajax=true';
            $JQDTId = 'jqdt_' . md5($ajaxURLStr);
            $data = array();

            foreach ($fopsData as $eachFOperation) {
                $fiscopID = $eachFOperation['operation_id'];

                $data[] = $fiscopID;
                $data[] = $eachFOperation['date_create'];
                $data[] = $eachFOperation['date_finish'];
                $data[] = $eachFOperation['status'];
                $data[] = $eachFOperation['error_code'];
                $data[] = $eachFOperation['error_message'];
                $data[] = $eachFOperation['receipt_id'];
                $data[] = $eachFOperation['repeated_fiscop_id'];
                $data[] = $eachFOperation['repeat_count'];

                $disableLink = (strtolower($eachFOperation['status']) == 'error') ? '' : 'style="opacity: 0.35; pointer-events: none"';

                $infoLnkID = wf_InputId();
                $repeatOpLnkID = wf_InputId();
                $ajaxInfoParams = array('showdetailedFiscOp' => $fiscopID);
                $ajaxRepeatOpParams = array('repeatFiscOp' => $fiscopID);

                $actions = wf_Link('#', wf_img('skins/icon_search_small.gif', __('Show details')), false, '', ' id="' . $infoLnkID . '" ');
                $actions .= wf_Link('#', wf_img('skins/refresh.gif', __('Repeat this operation')), false, '', ' id="' . $repeatOpLnkID . '" ' . $disableLink);

                $actions .= wf_tag('script', false, '', 'type="text/javascript"');
                $actions .= wf_JSAjaxModalOpener(self::URL_ME, $ajaxInfoParams, $infoLnkID);
                //$actions .= wf_JSAjaxModalOpener(self::URL_ME, $ajaxRepeatOpParams, $repeatOpLnkID, false, 'GET', 'click', false, false, $JQDTId);
                $actions .= wf_JSAjaxModalOpener(self::URL_ME, $ajaxRepeatOpParams, $repeatOpLnkID);
                $actions .= wf_tag('script', true);

                $data[] = $actions;
                $json->addRow($data);

                unset($data);
            }
        }

        $json->getJson();
    }

    /**
     * JQDT for fiscal operations list form
     *
     * @return string
     */
    public function renderFiscalOperationsJQDT() {
        $ajaxURLStr = self::URL_ME . '&foperationslistajax=true';
        $columns = array();
        $opts = '"order": [[ 1, "desc" ]],
                "columnDefs": [ {"targets": "_all", "className": "dt-center"},
                                {"targets": [1, 2], "width": "80px"} ]';

        $columns[] = __('Operation ID');
        $columns[] = __('Creation date');
        $columns[] = __('Completion date');
        $columns[] = __('Status');
        $columns[] = __('Error code');
        $columns[] = __('Error message');
        $columns[] = __('Check ID');
        $columns[] = __('Repeated operation ID');
        $columns[] = __('Repeated tries count');
        $columns[] = __('Actions');

        return (wf_JqDtLoader($columns, $ajaxURLStr, false, __('Fiscal operations'), 100, $opts));
    }

    /**
     * JSON for receipts JQDT
     */
    public function renderReceiptsListJSON($dateFrom = '', $dateTo = '', $cashDeviceID = '', $limit = 1000) {
        $receiptsData = $this->getReceipts($dateFrom, $dateTo, $cashDeviceID, $limit);
        $json = new wf_JqDtHelper();

        if (!empty($receiptsData)) {
            $data = array();
            $receiptsData = $receiptsData['data'];

            foreach ($receiptsData as $eachReceipt) {
                $i = 0;
                $receiptPositions = $eachReceipt['positions'];
                $receiptPayments = $eachReceipt['payments'];
                $receiptPositionsLen = count($receiptPositions);
                $receiptPaymentsLen = count($receiptPayments);

                $data[] = $eachReceipt['deviceId'];
                $data[] = $eachReceipt['shopId'];
                $data[] = $eachReceipt['localDate'];
                $data[] = $eachReceipt['shiftId'];
                $data[] = $eachReceipt['cashier']['name'];
                $data[] = $eachReceipt['number'];

                foreach ($receiptPositions as $postion) {
                    $i++;

                    if ($receiptPositionsLen == 1 or $receiptPositionsLen == $i) {
                        $tmpBR = '';
                    } else {
                        $tmpBR = wf_delimiter(1);
                    }

                    $data[] = $postion['name'] . wf_nbsp(2) . '-' . wf_nbsp(2) . ($postion['price'] / 100) . $tmpBR;
                }

                $i = 0;
                foreach ($receiptPayments as $payment) {
                    $i++;

                    if ($receiptPaymentsLen == 1 or $receiptPaymentsLen == $i) {
                        $tmpBR = '';
                    } else {
                        $tmpBR = wf_delimiter(1);
                    }

                    $data[] = $payment['type'] . wf_nbsp(2) . '-' . wf_nbsp(2) . ($payment['amount'] / 100) . $tmpBR;
                }

                $data[] = $eachReceipt['amount'] / 100;

                //$data[] = wf_Link(self::URL_DREAMKAS_RECEIPT_DETAILS . $eachReceipt['id'], wf_img('skins/icon_search_small.gif', __('Show details')), false, '');

                $lnkID = wf_InputId();
                $ajaxInfoParams = array('showdetailedrcpt' => $eachReceipt['id']);

                $actions = wf_Link('#', wf_img('skins/icon_search_small.gif', __('Show details')), false, '', ' id="' . $lnkID . '" ');
                $actions .= wf_JSAjaxModalOpener(self::URL_ME, $ajaxInfoParams, $lnkID, true);

                $data[] = $actions;

                $json->addRow($data);
                unset($data);
            }
        }

        $json->getJson();
    }

    /**
     * JQDT for receipts list form
     *
     * @return string
     */
    public function renderReceiptsJQDT() {
        $ajaxURLStr = self::URL_ME . '&receiptslistajax=true';
        $columns = array();
        $opts = '"order": [[ 2, "desc" ]],
                "columnDefs": [ {"targets": "_all", "className": "dt-center"} ]';

        $columns[] = __('Cash machine ID');
        $columns[] = __('Seller ID');
        $columns[] = __('Operation date');
        $columns[] = __('Shift ID');
        $columns[] = __('Cashier');
        $columns[] = __('Check in shift');
        $columns[] = __('Check positions and prices');
        $columns[] = __('Check payments details');
        $columns[] = __('Check total');
        $columns[] = __('Actions');

        return (wf_JqDtLoader($columns, $ajaxURLStr, false, __('Checks'), 100, $opts));
    }

    public function renderWebhooksListJSON() {
        $webhooksData = $this->getWebHooks();
        $json = new wf_JqDtHelper();

        if (!empty($webhooksData)) {
            $ajaxURLStr = self::URL_ME . '&webhookslistajax=true';
            $JQDTId = 'jqdt_' . md5($ajaxURLStr);
            $data = array();

            foreach ($webhooksData as $eachWebhook) {
                $webhookOpts = $eachWebhook['types'];
                $data[] = $eachWebhook['url'];
                $data[] = ($eachWebhook['isActive']) ? web_green_led() : web_red_led();

                foreach ($webhookOpts as $webhookOpt => $eachValue) {
                    $data[] = ($eachValue) ? web_green_led() : web_red_led();
                }

                $lnkID = wf_InputId();
                $ajaxInfoParams = array('whedit' => true, 'whid' => $eachWebhook['id']);

                $actions = wf_JSAlert('#', web_delete_icon(), 'Removing this may lead to irreparable results', 'deleteWebhook(\'' . $eachWebhook['id'] . '\', \'' . self::URL_ME . '\', \'delWebhook\', \'' . wf_InputId() . '\')') . wf_nbsp();
                $actions .= wf_Link('#', web_edit_icon(), false, '', 'id="' . $lnkID . '"') . wf_nbsp();
                $actions .= wf_JSAjaxModalOpener(self::URL_ME, $ajaxInfoParams, $lnkID, true, 'POST', 'click', false, false, $JQDTId);

                $data[] = $actions;

                $json->addRow($data);
                unset($data);
            }
        }

        $json->getJson();
    }

    public function renderWebhooksJQDT() {
        $ajaxURLStr = self::URL_ME . '&webhookslistajax=true';
        $JQDTId = 'jqdt_' . md5($ajaxURLStr);
        $errorModalWindowId = wf_InputId();
        $columns = array();
        $opts = '"order": [[ 0, "desc" ]],
                "columnDefs": [ {"targets": [1, 2, 3, 4, 5, 6, 7, 8, 9], "className": "dt-center"},
                                {"width": "30%", "className": "dt-head-center jqdt_word_wrap", "targets": [0]}, 
                              ]';

        $columns[] = __('Webhook URL');
        $columns[] = __('Webhook active');
        $columns[] = __('Notify for products');
        $columns[] = __('Notify for devices');
        $columns[] = __('Notify for encashments');
        $columns[] = __('Notify for receipts');
        $columns[] = __('Notify for shifts');
        $columns[] = __('Notify for operations');
        $columns[] = __('Notify for device registrations');
        $columns[] = __('Actions');

        $result = wf_JqDtLoader($columns, $ajaxURLStr, false, __('Webhooks'), 100, $opts);

        $result .= wf_tag('script', false, '', 'type="text/javascript"');
        $result .= wf_JSEmptyFunc();
        $result .= wf_JSElemInsertedCatcherFunc();
        $result .= ' function chekEmptyVal(ctrlClassName) {
                        $(document).on("focus keydown", ctrlClassName, function(evt) {
                            if ( $(ctrlClassName).css("border-color") == "rgb(255, 0, 0)" ) {
                                $(ctrlClassName).val("");
                                $(ctrlClassName).css("border-color", "");
                                $(ctrlClassName).css("color", "");
                            }
                        });
                    }
                     
                    onElementInserted(\'body\', \'.__WHEmptyCheck\', function(element) {
                        chekEmptyVal(\'.__WHEmptyCheck\');
                    });
                    
                    onElementInserted(\'body\', \'.__ChkCtrl\', function(element) {
                        makeParamsEncodedBind(\'.__ChkCtrl\');
                    });                    
                                       
                    $(document).on("submit", ".__WHForm", function(evt) {
                        evt.preventDefault();
                        
                        var URLParams = $(".__WHURLParams").val(); 
                        var LastURL7Chars = URLParams.substring(URLParams.length - 7);
                        
                        if (LastURL7Chars == \'&param=\') {
                            alert(\'' . __('Specify at least one notification type') . '\');
                        } else {
                            // some ugly hack...
                            if (empty($(".__WHFullURL").val())) {
                                makeParamsEncoded();
                            }                        
                        
                            var FrmAction        = $(".__WHForm").attr("action");
                            var FrmData          = $(".__WHForm").serialize() + \'&errfrmid=' . $errorModalWindowId . '\';
                            //var modalWindowId  = $(".__WHForm").closest(\'div\').attr(\'id\');
                            
                        
                            var emptyCheckClass = \'.__WHEmptyCheck\';
                        
                            if ( empty( $(emptyCheckClass).val() ) || $(emptyCheckClass).css("border-color") == "rgb(255, 0, 0)" ) {                            
                                $(emptyCheckClass).css("border-color", "red");
                                $(emptyCheckClass).css("color", "grey");
                                $(emptyCheckClass).val("' . __('Mandatory field') . '");                            
                            } else {
                                $.ajax({
                                type: "POST",
                                url: FrmAction,
                                data: FrmData,
                                success: function(result) {
                                            if ( !empty(result) ) {                                            
                                                $(document.body).append(result);                                                
                                                $( \'#' . $errorModalWindowId . '\' ).dialog("open");                                                
                                            } else {
                                                $(\'#' . $JQDTId . '\').DataTable().ajax.reload();
                                                //$("[name=swgroupname]").val("");
                                                
                                                if ( $(".__CloseFrmOnSubmitChk").is(\':checked\') ) {
                                                    $( \'#\'+$(".__WHFormModalWindowId").val() ).dialog("close");
                                                }
                                            }
                                        }                        
                                });
                            }
                        }
                    });
                    
                    function makeParamsEncodedBind(ctrlClassName) {
                        $(document).on("change", ctrlClassName, function(evt) {
                            evt.stopPropagation();
                            evt.stopImmediatePropagation();
                            
                            makeParamsEncoded();
                        });  
                    }
                    
                    function makeParamsEncoded() {                        
                        var StaticPart = $(".__WHURLParamsStatic").val();
                        var ParamsStr = \'\';
                        var ParamsArr = [];
                        var ParamsValsStr = \'\';
                        var ParamsValsArr = {};
                        
                        $(\'[name$="schk"]\').each(function(chkindex, chkelement) {
                            var ElemName = $(chkelement).attr("name");
                            var ParamName = ElemName.substring(2, ElemName.length - 3);
                            
                            if ($(chkelement).is(\':checked\')) {
                                ParamsArr.push(ParamName);
                                ParamsStr += ParamName;
                            }
                            
                            ParamsValsArr[ParamName] = $(chkelement).is(\':checked\');
                        });
                        
                        if (empty(ParamsStr)) {
                            $(".__WHURLParams").val(StaticPart);
                            $(".__WHFullURL").val(\'\');
                            $(".__WHNotifyOpts").val(\'\');                                
                        } else {
                            ParamsStr = btoa(JSON.stringify(ParamsArr));                                
                            ParamsValsStr = btoa(JSON.stringify(ParamsValsArr));
                                                            
                            $(".__WHURLParams").val(StaticPart + ParamsStr);
                            $(".__WHFullURL").val($(".__WHURLSelf").val() + StaticPart + ParamsStr);
                            $(".__WHNotifyOpts").val(ParamsValsStr);
                        }
                    }
                    
                    function deleteWebhook(WHId, ajaxURL, actionName, errFrmId) {
                        var ajaxData = \'&\'+ actionName +\'=true&whid=\' + WHId + \'&errfrmid=\' + errFrmId                    
                    
                        $.ajax({
                                type: "POST",
                                url: ajaxURL,
                                data: ajaxData,
                                success: function(result) {                                    
                                            if ( !empty(result) ) {                                            
                                                $(document.body).append(result);
                                                $(\'#\'+errFrmId).dialog("open");
                                            }
                                            
                                            $(\'#' . $JQDTId . '\').DataTable().ajax.reload();
                                         }
                        });
                    }                  
                  ';
        $result .= wf_tag('script', true);

        return ($result);
    }

    public function renderWebhookAddForm($modalWindowId) {
        $formId = 'Form_' . wf_InputId();
        $closeFormChkId = 'CloseFrmChkID_' . wf_InputId();
        $immutableURLPart = '/?module=remoteapi&key=' . $this->getUBSerial() . '&action=dreamkas&param=';

        $cells = wf_TableCell(__('URL to your Ubilling instance'), '', '', 'align="center"');
        $rows = wf_TableRow($cells);

        $cells = wf_TableCell(wf_TextInput('whurl', '', '', false, '50', '', '__WHURLSelf __WHEmptyCheck', 'WHURLSelf')
                . wf_delimiter(0) . '<b>+</b>', '', '', 'align="center"');
        $rows .= wf_TableRow($cells);

        $cells = wf_TableCell(__('URL params'), '', '', 'align="center"');
        $rows .= wf_TableRow($cells);

        $cells = wf_TableCell(wf_TextInput('whurlparams', '', $immutableURLPart, true, '100', '', '__WHURLParams', 'WHURLParams', 'readonly'));
        $rows .= wf_TableRow($cells);

        $inputs = wf_TableBody($rows);
        //$inputs.=
        $inputs .= wf_tag('h3') . __('Notify about events') . ':' . wf_tag('h3', true);
        $inputs .= wf_CheckInput('whproductschk', __('Goods'), true, false, 'WHGoodsChk', '__WHGoodsChk __ChkCtrl');
        $inputs .= wf_CheckInput('whdeviceschk', __('Cashmachines'), true, false, 'WHCashmachinesChk', '__WHCashmachinesChk __ChkCtrl');
        $inputs .= wf_CheckInput('whencashmentschk', __('Encashments'), true, false, 'WHEncashmentsChk', '__WHEncashmentsChk __ChkCtrl');
        $inputs .= wf_CheckInput('whreceiptschk', __('Receipts'), true, false, 'WHReceiptsChk', '__WHReceiptsChk __ChkCtrl');
        $inputs .= wf_CheckInput('whshiftschk', __('Shifts'), true, false, 'WHShiftsChk', '__WHShiftsChk __ChkCtrl');
        $inputs .= wf_CheckInput('whoperationschk', __('Operations'), true, false, 'WHOperationsChk', '__WHOperationsChk __ChkCtrl');
        $inputs .= wf_CheckInput('whdeviceRegistrationschk', __('Registration data changes'), true, false, 'WHRegdatachangesChk', '__WHRegdatachangesChk __ChkCtrl');
        $inputs .= wf_delimiter(0);
        $inputs .= wf_CheckInput('whisactive', __('Webhook is active'), true, true, 'WHActiveChk', '__WHActiveChk');
        $inputs .= wf_delimiter(0);

        $inputs .= wf_CheckInput('formclose', __('Close form after operation'), false, true, $closeFormChkId, '__CloseFrmOnSubmitChk');

        $inputs .= wf_HiddenInput('whurlparamsstatic', $immutableURLPart, 'WHURLParamsStatic', '__WHURLParamsStatic');
        $inputs .= wf_HiddenInput('whfullurl', '', 'WHFullURL', '__WHFullURL');
        $inputs .= wf_HiddenInput('whnotifyopts', '', 'WHNotifyOpts', '__WHNotifyOpts');
        $inputs .= wf_HiddenInput('', $modalWindowId, '', '__WHFormModalWindowId');
        $inputs .= wf_HiddenInput('whcreate', 'true');
        $inputs .= wf_delimiter();
        $inputs .= wf_Submit(__('Create'));
        $form = wf_Form(self::URL_ME, 'POST', $inputs, 'glamour __WHForm', '', $formId);

        return ($form);
    }

    public function renderWebhookEditForm($webhookID, $modalWindowId) {
        $webhookData = $this->getWebHooks($webhookID);
        $immutableURLPart = '/?module=remoteapi&key=' . $this->getUBSerial() . '&action=dreamkas&param=';

        $whActive = (empty($webhookData['isActive'])) ? false : true;
        $whURLChunks = (empty($webhookData['url'])) ? array() : explode('?', $webhookData['url']);
        $whNotifyTypes = (isset($webhookData['types'])) ? $webhookData['types'] : array();

        if (empty($whURLChunks)) {
            $whURLPart = '';
            $whParamsPart = $immutableURLPart;
        } else {
            $whURLPart = substr($whURLChunks[0], 0, -1);
            $whParamsPart = '/?' . $whURLChunks[1];
        }

        if (empty($whNotifyTypes)) {
            $whNotifyProducts = false;
            $whNotifyDevices = false;
            $whNotifyEncashments = false;
            $whNotifyReceipts = false;
            $whNotifyShifts = false;
            $whNotifyOperations = false;
            $whNotifyDeviceRegistrations = false;
        } else {
            $whNotifyProducts = (empty($whNotifyTypes['products'])) ? false : true;
            $whNotifyDevices = (empty($whNotifyTypes['devices'])) ? false : true;
            $whNotifyEncashments = (empty($whNotifyTypes['encashments'])) ? false : true;
            $whNotifyReceipts = (empty($whNotifyTypes['receipts'])) ? false : true;
            $whNotifyShifts = (empty($whNotifyTypes['shifts'])) ? false : true;
            $whNotifyOperations = (empty($whNotifyTypes['operations'])) ? false : true;
            $whNotifyDeviceRegistrations = (empty($whNotifyTypes['deviceRegistrations'])) ? false : true;
        }

        $formId = 'Form_' . wf_InputId();
        $closeFormChkId = 'CloseFrmChkID_' . wf_InputId();
        $immutableURLPart = '/?module=remoteapi&key=' . $this->getUBSerial() . '&action=dreamkas&param=';

        $cells = wf_TableCell(__('URL to your Ubilling instance'), '', '', 'align="center"');
        $rows = wf_TableRow($cells);

        $cells = wf_TableCell(wf_TextInput('whurl', '', $whURLPart, false, '50', '', '__WHURLSelf __WHEmptyCheck', 'WHURLSelf')
                . wf_delimiter(0) . '<b>+</b>', '', '', 'align="center"');
        $rows .= wf_TableRow($cells);

        $cells = wf_TableCell(__('URL params'), '', '', 'align="center"');
        $rows .= wf_TableRow($cells);

        $cells = wf_TableCell(wf_TextInput('whurlparams', '', $whParamsPart, true, '100', '', '__WHURLParams', 'WHURLParams', 'readonly'));
        $rows .= wf_TableRow($cells);

        $inputs = wf_TableBody($rows);
        $inputs .= wf_tag('h3') . __('Notify about events') . ':' . wf_tag('h3', true);
        $inputs .= wf_CheckInput('whproductschk', __('Goods'), true, $whNotifyProducts, 'WHGoodsChk', '__WHGoodsChk __ChkCtrl');
        $inputs .= wf_CheckInput('whdeviceschk', __('Cashmachines'), true, $whNotifyDevices, 'WHCashmachinesChk', '__WHCashmachinesChk __ChkCtrl');
        $inputs .= wf_CheckInput('whencashmentschk', __('Encashments'), true, $whNotifyEncashments, 'WHEncashmentsChk', '__WHEncashmentsChk __ChkCtrl');
        $inputs .= wf_CheckInput('whreceiptschk', __('Receipts'), true, $whNotifyReceipts, 'WHReceiptsChk', '__WHReceiptsChk __ChkCtrl');
        $inputs .= wf_CheckInput('whshiftschk', __('Shifts'), true, $whNotifyShifts, 'WHShiftsChk', '__WHShiftsChk __ChkCtrl');
        $inputs .= wf_CheckInput('whoperationschk', __('Operations'), true, $whNotifyOperations, 'WHOperationsChk', '__WHOperationsChk __ChkCtrl');
        $inputs .= wf_CheckInput('whdeviceRegistrationschk', __('Registration data changes'), true, $whNotifyDeviceRegistrations, 'WHRegdatachangesChk', '__WHRegdatachangesChk __ChkCtrl');
        $inputs .= wf_delimiter(0);
        $inputs .= wf_CheckInput('whisactive', __('Webhook is active'), true, $whActive, 'WHActiveChk', '__WHActiveChk');
        $inputs .= wf_delimiter(0);

        $inputs .= wf_CheckInput('formclose', __('Close form after operation'), false, true, $closeFormChkId, '__CloseFrmOnSubmitChk');

        $inputs .= wf_HiddenInput('whurlparamsstatic', $immutableURLPart, 'WHURLParamsStatic', '__WHURLParamsStatic');
        $inputs .= wf_HiddenInput('whfullurl', '', 'WHFullURL', '__WHFullURL');
        $inputs .= wf_HiddenInput('whnotifyopts', '', 'WHNotifyOpts', '__WHNotifyOpts');
        $inputs .= wf_HiddenInput('', $modalWindowId, '', '__WHFormModalWindowId');
        $inputs .= wf_HiddenInput('whedit', 'true');
        $inputs .= wf_HiddenInput('whid', $webhookID);
        $inputs .= wf_delimiter();
        $inputs .= wf_Submit(__('Edit'));
        $form = wf_Form(self::URL_ME, 'POST', $inputs, 'glamour __WHForm', '', $formId);

        return ($form);
    }

    public function createeditdeleteWebhook($whURL, $whActive, $whOpts = '', $whID = '', $whDelete = false) {
        $urlString = self::URL_API . 'webhooks' . ((empty($whID)) ? '' : '/' . $whID);
        $webhookBody = array();
        $errorMsg = '';

        if (empty($whID)) {
            $action = 'ADDITION';
        } elseif (!empty($whID) and ! $whDelete) {
            $action = 'EDITING';
        } elseif (!empty($whID) and $whDelete) {
            $action = 'DELETING';
        }

        if (!empty($whOpts) or ( !empty($whID) and $whDelete)) {
            if (!$whDelete) {
                $whOpts = json_decode(base64_decode($whOpts));
                $webhookBody['url'] = $whURL;
                $webhookBody['isActive'] = $whActive;

                foreach ($whOpts as $whOpt => $eachValue) {
                    $webhookBody['types'][$whOpt] = $eachValue;
                }

                $webhookBody = json_encode($webhookBody);
            }

            $curl = curl_init();
            curl_setopt($curl, CURLOPT_POST, true);
            curl_setopt($curl, CURLOPT_URL, $urlString);
            curl_setopt($curl, CURLOPT_HTTPHEADER, $this->basicHTTPHeaders);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

            if (!empty($whID)) {
                if ($whDelete) {
                    curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "DELETE");
                } else {
                    curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "PATCH");
                }
            }

            if (!$whDelete) {
                curl_setopt($curl, CURLOPT_POSTFIELDS, $webhookBody);
            }

            $result = curl_exec($curl);
            $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            //PHP 8.0+ has no need to close curl resource anymore
            if (PHP_VERSION_ID < 80000) {
                curl_close($curl);
            }

            $result = json_decode($result, true);

            if (substr($httpCode, 0, 1) != '2') {
                $errorMsg = 'DREAMKAS WEBHOOK ' . $action . ' ERROR. SERVER ERROR MESSAGE: ' . $this->errorToString($result);
                log_register($errorMsg);
            }
        } else {
            $errorMsg = 'DREAMKAS WEBHOOK ' . $action . ' ERROR: empty webhook options';
            log_register($errorMsg);
        }

        return ($errorMsg);
    }

    public function processWebhookRequest($requestData, $paramSection = '') {
        if (!empty($requestData)) {
            $this->refreshCacheForced();

            $requestData = json_decode($requestData, true);
            $notifyAction = $requestData['action'];
            $notifyType = $requestData['type'];
            $notifyData = $requestData['data'];

            $this->processWebhookChange($notifyType, $notifyAction, $notifyData);
        } else {
            log_register('DREAMKAS WEBHOOK PROCESSING ERROR: empty request data received.');
        }
    }

    protected function processWebhookChange($whType, $whAction, $whData) {
        $logStr = '';
        $logTitle = __('DREAMKAS WEBHOOK') . ' ' . __($whType) . ' [' . __($whAction) . ']' . ' ';

        switch ($whType) {
            case 'PRODUCT':
                $this->getSellPosIDsNames();
                $this->getSellPos2SrvTypeMapping();

                $logStr .= '| [Category]: ' . $whData['category'] . ' | ';
                $logStr .= '[Name]: ' . $whData['name'] . ' | ';
                $logStr .= '[Type]: ' . $whData['type'] . ' | ';
                $logStr .= '[Quantity]: ' . $whData['quantity'] . ' | ';
                $logStr .= '[Price]: ' . $whData['price'] . ' | ';
                $logStr .= (isset($whData['barcodes'][0])) ? '[Barcode]: ' . $whData['barcodes'][0] . ' | ' : '';
                $logStr .= (isset($whData['vendorCodes'][0])) ? '[Vendorcode]: ' . $whData['vendorCodes'][0] . ' | ' : '';
                $logStr .= '[Tax]: ' . $whData['tax'] . ' | ';
                $logStr .= '[Creation date]: ' . date('Y-m-d H:i:s', strtotime($whData['createdAt'])) . ' | ';
                $logStr .= '[Update date]: ' . date('Y-m-d H:i:s', strtotime($whData['updatedAt'])) . ' | ';
                break;

            case 'DEVICE':
                $this->getCashMachines4Selector();
                break;

            case 'ENCASHMENT':
                break;

            case 'SHIFT':
                break;

            case 'RECEIPT':
                $this->updateFiscalOperationsLocalStorage();

                $receiptPositions = $whData['positions'];
                $receiptPayments = $whData['payments'];

                $logStr .= '| [Check ID]: ' . $whData['_id'] . ' | ';
                $logStr .= '[Device ID]: ' . $whData['deviceId'] . ' | ';
                $logStr .= '[Shop ID]: ' . $whData['shopId'] . ' | ';
                $logStr .= '[Local date]: ' . $whData['localDate'] . ' | ';
                $logStr .= '[Shift ID]: ' . $whData['shiftId'] . ' | ';
                $logStr .= '[Cashier]: ' . $whData['cashier']['name'] . ' | ';
                $logStr .= '[Number]: ' . $whData['number'] . ' | ';
                $logStr .= '[Amount]: ' . $whData['amount'] / 100 . ' | ';

                foreach ($receiptPositions as $postion) {
                    $logStr .= $postion['name'] . wf_nbsp(2) . '-' . wf_nbsp(2) . ($postion['price'] / 100) . ' | ';
                }

                foreach ($receiptPayments as $payment) {
                    $logStr .= $payment['type'] . wf_nbsp(2) . '-' . wf_nbsp(2) . ($payment['amount'] / 100) . ' | ';
                }
                break;

            case 'OPERATION':
                $this->updateFiscalOperationsLocalStorage($whData);

                $logStr .= '| [Creation date]: ' . date('Y-m-d H:i:s', strtotime($whData['createdAt'])) . ' | ';
                $logStr .= '[Completion date]: ' . date('Y-m-d H:i:s', strtotime($whData['completedAt'])) . ' | ';
                $logStr .= '[Status]: ' . $whData['status'] . ' | ';
                $logStr .= (isset($whData['type'])) ? '[Type]: ' . $whData['type'] . ' | ' : '';
                $logStr .= (isset($whData['data']['receiptId'])) ? '[Receipt ID]: ' . $whData['data']['receiptId'] . ' | ' : '';
                $logStr .= (isset($whData['data']['error'])) ? '[Error code]: ' . $whData['data']['error']['code'] . ' | ' : '';
                $logStr .= (isset($whData['data']['error'])) ? '[Error message]: ' . $whData['data']['error']['message'] . ' | ' : '';
                break;
        }

        log_register($logTitle . $logStr);
        $this->putNotificationData2Cache($logStr, 'info', $logTitle);
    }

}
