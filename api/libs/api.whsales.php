<?php

/**
 * Customizable warehouse items sales report
 */
class WHSales {

    /**
     * Warehouse instance placeholder
     * 
     * @var object
     */
    protected $warehouse = '';

    /**
     * System messages helper instance placeholder
     *
     * @var object
     */
    protected $messages = '';

    /**
     * Sub-reports database abstraction layer
     *
     * @var object
     */
    protected $reportsDb = '';

    /**
     * Sub-reports itemtypes abstraction layer
     *
     * @var object
     */
    protected $reportItemsDb = '';

    /**
     * Contains all available warehouse item types as id=>itemtypeData
     *
     * @var array
     */
    protected $allItemTypes = array();

    /**
     * Contains all available item type names as id=>itemTypeName
     *
     * @var array
     */
    protected $allItemTypeNames = array();

    /**
     * Contains all item types categories as itemtypeId=>categoryName
     *
     * @var array
     */
    protected $allItemCategories = array();

    /**
     * Contains all available sales sub-reports as id=>reportItemTypes[itemTypeId]=>recordId
     *
     * @var array
     */
    protected $allReports = array();

    /**
     * Contains all available sales sub-reports names as id=>name
     *
     * @var array
     */
    protected $allReportNames = array();

    /**
     * Contains year to render data
     *
     * @var int
     */
    protected $showYear = '';

    /**
     * Some predefined constants like routes, URLs, etc here
     */
    const TABLE_SUBREPORTS = 'wh_salesreports';
    const TABLE_REPORT_ITEMS = 'wh_salesitems';
    const RIGHT_EDIT = 'WAREHOUSEDIR';
    const URL_ME = '?module=whsales';
    const ROUTE_REPORT_RENDER = 'viewreport';
    const ROUTE_REPORT_EDIT = 'editreportid';
    const ROUTE_REPORT_DEL = 'deletereportid';
    const ROUTE_ITEM_DEL = 'deletereportitemid';
    const PROUTE_NEWREPORT = 'newreportname';
    const PROUTE_EDITREPORTNAME = 'editreportname';
    const PROUTE_NEWREPORTITEM = 'addnewitemtoreportid';
    const PROUTE_NEWREPORTITEMID = 'addthisitemtoreport';
    const PROUTE_YEAR = 'settargetyear';

    public function __construct() {
        $this->initMessages();
        $this->setYear();
        $this->initWarehouse();
        $this->initReportsDb();
        $this->initReportsItemsDb();
        $this->loadItemTypes();
        $this->loadReports();
    }

    /**
     * Inits warehouse instance for further usage
     * 
     * @return void
     */
    protected function initWarehouse() {
        $this->warehouse = new Warehouse();
    }

    /**
     * Inits message helper
     * 
     * @return void
     */
    protected function initMessages() {
        $this->messages = new UbillingMessageHelper();
    }

    /**
     * Inits sub-reports database layer
     * 
     * @return void
     */
    protected function initReportsDb() {
        $this->reportsDb = new NyanORM(self::TABLE_SUBREPORTS);
    }

    /**
     * Sets current instance target year
     * 
     * @return void
     */
    protected function setYear() {
        if (ubRouting::checkPost(self::PROUTE_YEAR)) {
            $this->showYear = ubRouting::post(self::PROUTE_YEAR, 'int');
        } else {
            $this->showYear = curyear();
        }
    }

    /**
     * Inits sub-reports database layer
     * 
     * @return void
     */
    protected function initReportsItemsDb() {
        $this->reportItemsDb = new NyanORM(self::TABLE_REPORT_ITEMS);
    }

    /**
     * Loads all existing itemtypes and categories from warehouse
     * 
     * @return void
     */
    protected function loadItemTypes() {
        $this->allItemTypes = $this->warehouse->getAllItemTypes();
        $categoriesTmp = $this->warehouse->getAllItemCategories();
        if (!empty($this->allItemTypes)) {
            foreach ($this->allItemTypes as $io => $each) {
                $this->allItemTypeNames[$each['id']] = $each['name'];
                if (isset($categoriesTmp[$each['categoryid']])) {
                    $this->allItemCategories[$each['id']] = $categoriesTmp[$each['categoryid']];
                }
            }
        }
    }

    /**
     * Loads all existing reports
     * 
     * @return void
     */
    protected function loadReports() {
        $tmpReportNames = $this->reportsDb->getAll();
        if (!empty($tmpReportNames)) {
            foreach ($tmpReportNames as $io => $each) {
                $this->allReportNames[$each['id']] = $each['name'];
                $this->allReports[$each['id']] = array();
            }
        }

        $tmpReportTypes = $this->reportItemsDb->getAll();
        if (!empty($tmpReportTypes)) {
            foreach ($tmpReportTypes as $io => $each) {
                $this->allReports[$each['reportid']][$each['itemtypeid']] = $each['id'];
            }
        }
    }

    /**
     * Renders available sub-reports list with some controls
     * 
     * @return string
     */
    public function renderReportsList() {
        $result = '';
        if (!empty($this->allReportNames)) {
            $cells = wf_TableCell(__('Report'));
            if (cfr(self::RIGHT_EDIT)) {
                $cells .= wf_TableCell(__('Actions'));
            }
            $rows = wf_TableRow($cells, 'row1');
            foreach ($this->allReportNames as $eachId => $eachReportName) {
                $cells = wf_TableCell(wf_Link(self::URL_ME . '&' . self::ROUTE_REPORT_RENDER . '=' . $eachId, $eachReportName));
                if (cfr(self::RIGHT_EDIT)) {
                    $delUrl = self::URL_ME . '&' . self::ROUTE_REPORT_DEL . '=' . $eachId;
                    $editUrl = self::URL_ME . '&' . self::ROUTE_REPORT_EDIT . '=' . $eachId;
                    $actControls = wf_JSAlert($delUrl, web_delete_icon(), $this->messages->getDeleteAlert()) . ' ';
                    $actControls .= wf_Link($editUrl, web_edit_icon());
                    $cells .= wf_TableCell($actControls);
                }
                $rows .= wf_TableRow($cells, 'row5');
            }
            $result .= wf_TableBody($rows, '100%', 0, 'sortable');
        } else {
            $result .= $this->messages->getStyledMessage(__('Nothing to show'), 'warning');
        }
        return($result);
    }

    /**
     * Creates new sales sub-report in database
     * 
     * @param string $name
     * 
     * @return void/string
     */
    public function createReport($name) {
        $nameF = ubRouting::filters($name, 'mres');
        $result = '';
        if (!empty($nameF)) {
            $this->reportsDb->data('name', $nameF);
            $this->reportsDb->create();
            $newId = $this->reportsDb->getLastId();

            log_register('WHSALES CREATE REPORT `' . $name . '` AS [' . $newId . ']');
        } else {
            $result .= __('Name') . ' ' . __('is empty');
        }
        return($result);
    }

    /**
     * Renames sales sub-report in database
     * 
     * @param int $reportId
     * @param string $name
     * 
     * @return void/string
     */
    public function renameReport($reportId, $name) {
        $reportId = ubRouting::filters($reportId, 'int');
        $nameF = ubRouting::filters($name, 'mres');
        $result = '';
        if (!empty($nameF) AND ! empty($reportId)) {
            $this->reportsDb->where('id', '=', $reportId);
            $this->reportsDb->data('name', $nameF);
            $this->reportsDb->save();

            log_register('WHSALES RENAME REPORT `' . $name . '` AS [' . $reportId . ']');
        } else {
            $result .= __('Name') . ' ' . __('is empty');
        }
        return($result);
    }

    /**
     * Deletes existing report from database
     * 
     * @param int $reportId
     * 
     * @return void/error
     */
    public function deleteReport($reportId) {
        $reportId = ubRouting::filters($reportId, 'int');
        $result = '';
        if (!empty($reportId)) {
            if (isset($this->allReportNames[$reportId])) {
                $reportName = $this->allReportNames[$reportId];
                //flushing report
                $this->reportsDb->where('id', '=', $reportId);
                $this->reportsDb->delete();
                //flushing itemtypes
                $this->reportItemsDb->where('reportid', '=', $reportId);
                $this->reportItemsDb->delete();

                log_register('WHSALES DELETE REPORT `' . $reportName . '` AS [' . $reportId . ']');
            } else {
                $result .= __('Report') . ' ' . __('ID') . ' [' . $reportId . '] ' . __('Not exists');
            }
        } else {
            $result .= __('Report') . ' ' . __('ID') . ' ' . __('is empty');
        }
        return($result);
    }

    /**
     * Renders new report creation form
     * 
     * @return string
     */
    public function renderCreationForm() {
        $result = '';
        if (cfr(self::RIGHT_EDIT)) {
            $inputs = wf_TextInput(self::PROUTE_NEWREPORT, __('Name'), '', false, 20);
            $inputs .= wf_Submit(__('Create'));
            $form = wf_Form('', 'POST', $inputs, 'glamour');
            $result .= wf_modalAuto(web_icon_create(__('Create new report')), __('Create new report'), $form);
        }
        return($result);
    }

    /**
     * Renders form for addition some new item type to existing report
     * 
     * @param int $reportId
     * 
     * @return string
     */
    protected function renderItemTypeAddForm($reportId) {
        $result = '';
        $itemsAvail = $this->allItemTypeNames;
        if (isset($this->allReports[$reportId])) {
            //report exists?
            foreach ($this->allReports[$reportId] as $eachItemTypeId => $eachRecordId) {
                if (isset($itemsAvail[$eachItemTypeId])) {
                    //already in report
                    unset($itemsAvail[$eachItemTypeId]);
                }
            }

            if (!empty($itemsAvail)) {
                $itemsSelector = array();
                //appending category to selector
                foreach ($itemsAvail as $eachItemTypeId => $eachItemTypeName) {
                    $itemCategory = (isset($this->allItemCategories[$eachItemTypeId])) ? $this->allItemCategories[$eachItemTypeId] . ' ' : '';
                    $itemsSelector[$eachItemTypeId] = $itemCategory . $eachItemTypeName;
                }

                $inputs = wf_HiddenInput(self::PROUTE_NEWREPORTITEM, $reportId);
                $inputs .= wf_SelectorSearchable(self::PROUTE_NEWREPORTITEMID, $itemsSelector, __('Warehouse item type'), '', false) . ' ';
                $inputs .= wf_Submit(__('Append'));
                $result .= wf_Form('', 'POST', $inputs, 'glamour');
            }
        }
        return($result);
    }

    /**
     * Returns item type name by its ID if it exists
     * 
     * @param int $itemTypeId
     * 
     * @return string
     */
    protected function getItemName($itemTypeId) {
        $result = '';
        if (isset($this->allItemTypeNames[$itemTypeId])) {
            $result = $this->allItemTypeNames[$itemTypeId];
        }
        return($result);
    }

    /**
     * Deletes some itemtype record ID from database
     * 
     * @param int $reportId
     * @param int $itemRecordId
     * 
     * @return void
     */
    public function deleteReportItem($reportId, $itemRecordId) {
        $reportId = ubRouting::filters($reportId, 'int');
        $itemRecordId = ubRouting::filters($itemRecordId, 'int');
        $this->reportItemsDb->where('id', '=', $itemRecordId);
        $this->reportItemsDb->delete();
        log_register('WHSALES DELETE REPORT [' . $reportId . '] ITEMRECID [' . $itemRecordId . ']');
    }

    /**
     * Appends some itemtype record to existing report
     * 
     * @param int $reportId
     * @param int $itemTypeId
     * 
     * @return void/string on error
     */
    public function addReportItem($reportId, $itemTypeId) {
        $reportId = ubRouting::filters($reportId, 'int');
        $itemTypeId = ubRouting::filters($itemTypeId, 'int');
        $result = '';
        if (isset($this->allReports[$reportId])) {
            if (isset($this->allItemTypes[$itemTypeId])) {
                $this->reportItemsDb->data('reportid', $reportId);
                $this->reportItemsDb->data('itemtypeid', $itemTypeId);
                $this->reportItemsDb->create();
                $newRecId = $this->reportItemsDb->getLastId();
                log_register('WHSALES ADD REPORT [' . $reportId . '] ITEMTYPE [' . $itemTypeId . '] AS ITEMRECID [' . $newRecId . ']');
            }
        }
        return($result);
    }

    /**
     * Renders report rename form
     * 
     * @param int $reportId
     * 
     * @return string
     */
    protected function renderRenameForm($reportId) {
        $result = '';
        $reportId = ubRouting::filters($reportId, 'int');
        if (isset($this->allReports[$reportId])) {
            $inputs = wf_TextInput(self::PROUTE_EDITREPORTNAME, __('Name'), $this->getReportName($reportId), false, 20);
            $inputs .= wf_Submit(__('Save'));
            $result .= wf_Form('', 'POST', $inputs, 'glamour');
        }
        return($result);
    }

    /**
     * Renders existing report editing form
     * 
     * @param int $reportId
     * 
     * @return string
     */
    public function renderEditForm($reportId) {
        $reportId = ubRouting::filters($reportId, 'int');
        $result = '';
        if (isset($this->allReports[$reportId])) {
            $reportItemTypes = $this->allReports[$reportId];
            //rename form
            $result .= $this->renderRenameForm($reportId);
            $result .= wf_delimiter(0);

            if (!empty($this->allItemTypes)) {
                //list of itemtypes in report
                if (!empty($reportItemTypes)) {
                    $cells = wf_TableCell(__('Category'));
                    $cells .= wf_TableCell(__('Warehouse item type'));
                    $cells .= wf_TableCell(__('Actions'));
                    $rows = wf_TableRow($cells, 'row1');
                    foreach ($reportItemTypes as $eachItemTypeId => $eachItmRecordId) {
                        $eachItemCategory = (isset($this->allItemCategories[$eachItemTypeId])) ? $this->allItemCategories[$eachItemTypeId] : '';

                        $cells = wf_TableCell($eachItemCategory);
                        $cells .= wf_TableCell($this->getItemName($eachItemTypeId));
                        $delUrl = self::URL_ME . '&' . self::ROUTE_REPORT_EDIT . '=' . $reportId . '&' . self::ROUTE_ITEM_DEL . '=' . $eachItmRecordId;
                        $actLinks = wf_JSAlert($delUrl, web_delete_icon(), $this->messages->getDeleteAlert());
                        $cells .= wf_TableCell($actLinks);
                        $rows .= wf_TableRow($cells, 'row5');
                    }
                    $result .= wf_TableBody($rows, '100%', 0, 'sortable');
                } else {
                    $result .= $this->messages->getStyledMessage(__('Report doesnt contain any item types'), 'info');
                }
                //append form
                $result .= wf_delimiter(0);
                $result .= $this->renderItemTypeAddForm($reportId);
            } else {
                $result .= $this->messages->getStyledMessage(__('Warehouse item types') . ' ' . __('Not found'), 'warning');
            }
        } else {
            $result .= $this->messages->getStyledMessage(__('Report') . ' ' . __('ID') . ' [' . $reportId . '] ' . __('Not exists'), 'error');
        }
        return($result);
    }

    /**
     * Returns existing report name by its ID
     * 
     * @return string
     */
    public function getReportName($reportId) {
        $reportId = ubRouting::filters($reportId, 'int');
        $result = '';
        if (isset($this->allReportNames[$reportId])) {
            $result .= $this->allReportNames[$reportId];
        }
        return($result);
    }

    /**
     * Removes from outcomes non rendered years, move operations, not report itemtypes, etc
     * 
     * @param array $allOutcomes
     * @param array $reportItemIds
     * 
     * @return array
     */
    protected function filterOutcomes($allOutcomes, $reportItemIds) {
        $result = array();
        $yearMask = $this->showYear . '-';
        if (!empty($allOutcomes)) {
            foreach ($allOutcomes as $io => $each) {
                if (isset($reportItemIds[$each['itemtypeid']])) {
                    if ($each['desttype'] != 'storage' AND ispos($each['date'], $yearMask)) {
                        $result[$io] = $each;
                    }
                }
            }
        }
        return($result);
    }

    /**
     * Renders year selector form
     * 
     * @return string
     */
    protected function renderYearSelector() {
        $result = '';
        $inputs = wf_YearSelectorPreset(self::PROUTE_YEAR, __('Year'), false, ubRouting::post(self::PROUTE_YEAR)) . ' ';
        $inputs .= wf_Submit(__('Show'));
        $result .= wf_Form('', 'POST', $inputs, 'glamour');
        return($result);
    }

    /**
     * Returns selling stats by some itemTypeID from outcomes array
     * 
     * @param string $fromDate
     * @param string $toDate
     * @param int $itemTypeId
     * @param array $allOutcomes
     * 
     * @return array
     */
    protected function getItemTypeStat($fromDate, $toDate, $itemTypeId, $allOutcomes) {
        $result = array(
            'count' => 0,
            'price' => 0,
            'summ' => 0
        );

        if (!empty($allOutcomes)) {
            foreach ($allOutcomes as $io => $each) {
                if ($each['itemtypeid'] == $itemTypeId) {
                    if (zb_isDateBetween($fromDate, $toDate, $each['date'])) {
                        $result['count'] += $each['count'];
                        $result['price'] = $each['price'];
                        $result['summ'] += ($each['price'] * $each['count']);
                    }
                }
            }
        }
        return($result);
    }

    /**
     * Render sold items year chart
     * 
     * @param array $allOutcomes
     * 
     * @return string
     */
    protected function renderChartCount($allOutcomes) {
        $result = '';
        $chartData = array();
        $chartData[] = array(__('Month'), __('Count'));
        if (!empty($allOutcomes)) {
            $sellStats = array();
            $chartOptions = "
             'focusTarget': 'category',
                                'hAxis': {
                                'color': 'none',
                                    'baselineColor': 'none',
                            },
                                'vAxis': {
                                'color': 'none',
                                    'baselineColor': 'none',
                            },
                                'curveType': 'function',
                                'pointSize': 3,
                                'crosshair': {
                                trigger: 'none'
                            },";

            foreach ($allOutcomes as $io => $each) {
                $monthNum = date("Y-m", strtotime($each['date']));

                if (isset($sellStats[$monthNum])) {
                    $sellStats[$monthNum] += $each['count'];
                } else {
                    $sellStats[$monthNum] = $each['count'];
                }
            }
            if (!empty($sellStats)) {
                foreach ($sellStats as $eachMonth => $monthStat) {
                    $chartData[] = array($eachMonth, $monthStat);
                }
            }

            if (sizeof($chartData) > 1) {
                $result .= wf_gchartsLine($chartData, __('Count'), '100%;', '300px;', $chartOptions);
            }
        }
        return($result);
    }

    /**
     * Render sold items year profit chart
     * 
     * @param array $allOutcomes
     * 
     * @return string
     */
    protected function renderChartProfit($allOutcomes) {
        $result = '';
        $chartData = array();
        $chartData[] = array(__('Month'), __('Money'));
        if (!empty($allOutcomes)) {
            $sellStats = array();
            $chartOptions = "
             'focusTarget': 'category',
                                'hAxis': {
                                'color': 'none',
                                    'baselineColor': 'none',
                            },
                                'vAxis': {
                                'color': 'none',
                                    'baselineColor': 'none',
                            },
                                'curveType': 'function',
                                'pointSize': 3,
                                'crosshair': {
                                trigger: 'none'
                            },";

            foreach ($allOutcomes as $io => $each) {
                $monthNum = date("Y-m", strtotime($each['date']));

                if (isset($sellStats[$monthNum])) {
                    $sellStats[$monthNum] += ($each['price'] * $each['count']);
                } else {
                    $sellStats[$monthNum] = ($each['price'] * $each['count']);
                }
            }
            if (!empty($sellStats)) {
                foreach ($sellStats as $eachMonth => $monthStat) {
                    $chartData[] = array($eachMonth, $monthStat);
                }
            }

            if (sizeof($chartData) > 1) {
                $result .= wf_gchartsLine($chartData, __('Money'), '100%;', '300px;', $chartOptions);
            }
        }
        return($result);
    }

    /**
     * Renders existing sales report
     * 
     * @param int $reportId
     * 
     * @return string
     */
    public function renderReport($reportId) {
        $reportId = ubRouting::filters($reportId, 'int');
        $result = '';
        //default date intervals setting 
        $dateCurrentDay = curdate();
        $dateMonthBegin = curmonth() . '-01';
        $dateMonthEnd = curmonth() . '-' . date("t");
        $dateWeekBegin = date("Y-m-d", strtotime('monday this week'));
        $dateWeekEnd = date("Y-m-d", strtotime('sunday this week'));
        $dateYearBegin = $this->showYear . '-01-01';
        $dateYearEnd = $this->showYear . '-12-31';
        //year selector here
        $result .= $this->renderYearSelector();
        $result .= wf_delimiter(0);
        if (isset($this->allReports[$reportId])) {
            //here itemtypeId=>midprice
            $midPrices = array();
            $reportItemIds = $this->allReports[$reportId];
            if (!empty($reportItemIds)) {
                foreach ($reportItemIds as $eachItemId => $eachRecId) {
                    $eachItemMidPrice = $this->warehouse->getIncomeMiddlePrice($eachItemId);
                    $midPrices[$eachItemId] = $eachItemMidPrice;
                }

                $allOutcomes = $this->warehouse->getAllOutcomes();
                $yearOutcomes = $this->filterOutcomes($allOutcomes, $reportItemIds);
                if (!empty($yearOutcomes)) {
                    $sellStats = array(
                        'summarycount' => array(
                            'day' => 0,
                            'week' => 0,
                            'month' => 0,
                            'year' => 0,
                        ),
                        'summaryprice' => array(
                            'day' => 0,
                            'week' => 0,
                            'month' => 0,
                            'year' => 0,
                        ),
                    );

                    //sold item counts
                    $cells = wf_TableCell(__('Warehouse item type'), '40%');
                    $cells .= wf_TableCell(__('Day'), '15%');
                    $cells .= wf_TableCell(__('Week'), '15%');
                    $cells .= wf_TableCell(__('Month'), '15%');
                    $cells .= wf_TableCell(__('Year'), '15%');
                    $rows = wf_TableRow($cells, 'row1');

                    foreach ($reportItemIds as $eachItemId => $eachRecId) {
                        $sellStats['day'] = $this->getItemTypeStat($dateCurrentDay, $dateCurrentDay, $eachItemId, $yearOutcomes);
                        $sellStats['week'] = $this->getItemTypeStat($dateWeekBegin, $dateWeekEnd, $eachItemId, $yearOutcomes);
                        $sellStats['month'] = $this->getItemTypeStat($dateMonthBegin, $dateMonthEnd, $eachItemId, $yearOutcomes);
                        $sellStats['year'] = $this->getItemTypeStat($dateYearBegin, $dateYearEnd, $eachItemId, $yearOutcomes);

                        $sellStats['summarycount']['day'] += $sellStats['day']['count'];
                        $sellStats['summarycount']['week'] += $sellStats['week']['count'];
                        $sellStats['summarycount']['month'] += $sellStats['month']['count'];
                        $sellStats['summarycount']['year'] += $sellStats['year']['count'];


                        $cells = wf_TableCell($this->allItemTypeNames[$eachItemId]);
                        $cells .= wf_TableCell($sellStats['day']['count']);
                        $cells .= wf_TableCell($sellStats['week']['count']);
                        $cells .= wf_TableCell($sellStats['month']['count']);
                        $cells .= wf_TableCell($sellStats['year']['count']);
                        $rows .= wf_TableRow($cells, 'row5');
                    }

                    $cells = wf_TableCell(wf_tag('b') . __('Total') . ' ' . __('pieces') . wf_tag('b', true));
                    $cells .= wf_TableCell($sellStats['summarycount']['day']);
                    $cells .= wf_TableCell($sellStats['summarycount']['week']);
                    $cells .= wf_TableCell($sellStats['summarycount']['month']);
                    $cells .= wf_TableCell($sellStats['summarycount']['year']);
                    $rows .= wf_TableRow($cells, 'row2');

                    $result .= wf_tag('h2') . __('Count') . wf_tag('h2', true);
                    $result .= wf_TableBody($rows, '100%', 0, '');
                    $result .= wf_delimiter(0);

                    //sold items prices
                    $cells = wf_TableCell(__('Warehouse item type'), '40%');
                    $cells .= wf_TableCell(__('Day'), '15%');
                    $cells .= wf_TableCell(__('Week'), '15%');
                    $cells .= wf_TableCell(__('Month'), '15%');
                    $cells .= wf_TableCell(__('Year'), '15%');
                    $rows = wf_TableRow($cells, 'row1');

                    foreach ($reportItemIds as $eachItemId => $eachRecId) {
                        $sellStats['day'] = $this->getItemTypeStat($dateCurrentDay, $dateCurrentDay, $eachItemId, $yearOutcomes);
                        $sellStats['week'] = $this->getItemTypeStat($dateWeekBegin, $dateWeekEnd, $eachItemId, $yearOutcomes);
                        $sellStats['month'] = $this->getItemTypeStat($dateMonthBegin, $dateMonthEnd, $eachItemId, $yearOutcomes);
                        $sellStats['year'] = $this->getItemTypeStat($dateYearBegin, $dateYearEnd, $eachItemId, $yearOutcomes);


                        $sellStats['summaryprice']['day'] += $sellStats['day']['summ'];
                        $sellStats['summaryprice']['week'] += $sellStats['week']['summ'];
                        $sellStats['summaryprice']['month'] += $sellStats['month']['summ'];
                        $sellStats['summaryprice']['year'] += $sellStats['year']['summ'];


                        $cells = wf_TableCell($this->allItemTypeNames[$eachItemId]);
                        $cells .= wf_TableCell($sellStats['day']['summ']);
                        $cells .= wf_TableCell($sellStats['week']['summ']);
                        $cells .= wf_TableCell($sellStats['month']['summ']);
                        $cells .= wf_TableCell($sellStats['year']['summ']);
                        $rows .= wf_TableRow($cells, 'row5');
                    }

                    $cells = wf_TableCell(wf_tag('b') . __('Total') . ' ' . __('money') . wf_tag('b', true));
                    $cells .= wf_TableCell($sellStats['summaryprice']['day']);
                    $cells .= wf_TableCell($sellStats['summaryprice']['week']);
                    $cells .= wf_TableCell($sellStats['summaryprice']['month']);
                    $cells .= wf_TableCell($sellStats['summaryprice']['year']);
                    $rows .= wf_TableRow($cells, 'row2');

                    $result .= wf_tag('h2') . __('Money') . wf_tag('h2', true);
                    $result .= wf_TableBody($rows, '100%', 0, '');
                    $result .= wf_delimiter(0);

                    //profit prediction
                    $cells = wf_TableCell(__('Warehouse item type'), '40%');
                    $cells .= wf_TableCell(__('Day'), '15%');
                    $cells .= wf_TableCell(__('Week'), '15%');
                    $cells .= wf_TableCell(__('Month'), '15%');
                    $cells .= wf_TableCell(__('Year'), '15%');
                    $rows = wf_TableRow($cells, 'row1');

                    foreach ($reportItemIds as $eachItemId => $eachRecId) {
                        $sellStats['day'] = $this->getItemTypeStat($dateCurrentDay, $dateCurrentDay, $eachItemId, $yearOutcomes);
                        $sellStats['week'] = $this->getItemTypeStat($dateWeekBegin, $dateWeekEnd, $eachItemId, $yearOutcomes);
                        $sellStats['month'] = $this->getItemTypeStat($dateMonthBegin, $dateMonthEnd, $eachItemId, $yearOutcomes);
                        $sellStats['year'] = $this->getItemTypeStat($dateYearBegin, $dateYearEnd, $eachItemId, $yearOutcomes);

                        $sellStats['summarycount']['day'] += $sellStats['day']['count'];
                        $sellStats['summarycount']['week'] += $sellStats['week']['count'];
                        $sellStats['summarycount']['month'] += $sellStats['month']['count'];
                        $sellStats['summarycount']['year'] += $sellStats['year']['count'];

                        $sellStats['summaryprice']['day'] += $sellStats['day']['summ'];
                        $sellStats['summaryprice']['week'] += $sellStats['week']['summ'];
                        $sellStats['summaryprice']['month'] += $sellStats['month']['summ'];
                        $sellStats['summaryprice']['year'] += $sellStats['year']['summ'];


                        $cells = wf_TableCell($this->allItemTypeNames[$eachItemId] . ' (' . __('middle price') . ' ' . $midPrices[$eachItemId] . ')');
                        $cells .= wf_TableCell($sellStats['day']['summ'] - ($sellStats['day']['count'] * $midPrices[$eachItemId]));
                        $cells .= wf_TableCell($sellStats['week']['summ'] - ($sellStats['week']['count'] * $midPrices[$eachItemId]));
                        $cells .= wf_TableCell($sellStats['month']['summ'] - ($sellStats['month']['count'] * $midPrices[$eachItemId]));
                        $cells .= wf_TableCell($sellStats['year']['summ'] - ($sellStats['year']['count'] * $midPrices[$eachItemId]));
                        $rows .= wf_TableRow($cells, 'row5');
                    }

                    $cells = wf_TableCell(wf_tag('b') . __('Total') . ' ' . __('Profit') . ' ~' . wf_tag('b', true));
                    $cells .= wf_TableCell($sellStats['summaryprice']['day'] - ($sellStats['summarycount']['day'] * $midPrices[$eachItemId]));
                    $cells .= wf_TableCell($sellStats['summaryprice']['week'] - ($sellStats['summarycount']['week'] * $midPrices[$eachItemId]));
                    $cells .= wf_TableCell($sellStats['summaryprice']['month'] - ($sellStats['summarycount']['month'] * $midPrices[$eachItemId]));
                    $cells .= wf_TableCell($sellStats['summaryprice']['year'] - ($sellStats['summarycount']['year'] * $midPrices[$eachItemId]));
                    $rows .= wf_TableRow($cells, 'row2');

                    $result .= wf_tag('h2') . __('Profit') . wf_tag('h2', true);
                    $result .= wf_TableBody($rows, '100%', 0, '');
                    $result .= wf_delimiter(0);

                    //some charts here
                    $result .= $this->renderChartCount($yearOutcomes);
                    $result .= $this->renderChartProfit($yearOutcomes);
                } else {
                    $result .= $this->messages->getStyledMessage(__('Nothing to show'), 'info');
                }
            } else {
                $result .= $this->messages->getStyledMessage(__('Report doesnt contain any item types'), 'info');
            }
        } else {
            $result .= $this->messages->getStyledMessage(__('Report') . ' ' . __('ID') . ' [' . $reportId . '] ' . __('Not exists'), 'error');
        }
        $result .= wf_delimiter(0);
        $result .= wf_BackLink(self::URL_ME);
        return($result);
    }

}
