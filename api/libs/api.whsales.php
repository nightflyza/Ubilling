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
     * Contains all available warehouse item types
     *
     * @var array
     */
    protected $allItemTypes = array();

    /**
     * Contains all available sales sub-reports as id=>reportItemTypes
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
     * Some predefined constants like routes, URLs, etc here
     */
    const TABLE_SUBREPORTS = 'wh_salesreports';
    const TABLE_REPORT_ITEMS = 'wh_salesitems';
    const RIGHT_EDIT = 'WAREHOUSEDIR';
    const URL_ME = '?module=whsales';
    const ROUTE_REPORT_RENDER = 'viewreport';
    const ROUTE_REPORT_EDIT = 'editreportid';
    const ROUTE_REPORT_DEL = 'deletereportid';
    const PROUTE_NEWREPORT = 'newreportname';

    public function __construct() {
        $this->initMessages();
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

    /*     * name
     * Inits sub-reports database layer
     * 
     * @return void
     */

    protected function initReportsDb() {
        $this->reportsDb = new NyanORM(self::TABLE_SUBREPORTS);
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
     * Loads all existing itemtypes from warehouse
     * 
     * @return void
     */
    protected function loadItemTypes() {
        $this->allItemTypes = $this->warehouse->getAllItemTypes();
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
            if (!empty($this->allItemTypes)) {
                //TODO
            } else {
                $result .= $this->messages->getStyledMessage(__('Warehouse item types') . ' ' . __('Not found'), 'warning');
            }
        } else {
            $result .= $this->messages->getStyledMessage(__('Report') . ' ' . __('ID') . ' [' . $reportId . '] ' . __('Not exists'), 'error');
        }
        return($result);
    }

}
