<?php
/**
 * Class ReportSelling
 */
class ReportSelling
{
    private $reportData = [];
    private $reportParams = [];

    /**
     * ReportSelling constructor
     */
    public function __construct()
    {
        $this->reportParams['selling'] = null;
        $this->reportParams['idfrom'] = null;
        $this->reportParams['idto'] = null;
        $this->reportParams['datefrom'] =date('Y-m-01');
        $this->reportParams['dateto'] = date("Y-m-d");
    }

    /**
     * @param array $reportParams
     */
    public function generateReport($reportParams)
    {
        $this->reportParams = $reportParams;
        $this->reportData = zb_SellingReport($reportParams);
    }

    /**
     * Renders report
     *
     * @return string
     */
    public function render()
    {
        $cells = wf_TableCell(__('Selling'));
        $cells .= wf_TableCell(__('Selling count cards'));
        $cells .= wf_TableCell(__('Sum'));
        $cells .= wf_TableCell(__('Activated'));
        $cells .= wf_TableCell(__('For sum'));
        $cells .= wf_TableCell(__('Remains'));
        $cells .= wf_TableCell(__('For sum'));
        $rows = wf_TableRow($cells, 'row1');

        $result = $this->panel();

        if (!empty($this->reportData)) {
            foreach ($this->reportData as $report) {
                $cells = wf_TableCell($report['name']);
                $cells .= wf_TableCell($report['count_total']);
                $cells .= wf_TableCell($report['cash_total']);
                $cells .= wf_TableCell($report['cash_sel']);
                $cells .= wf_TableCell($report['count_sel']);
                $cells .= wf_TableCell($report['count_balance']);
                $cells .= wf_TableCell($report['cash_balabce']);
                $rows .= wf_TableRow($cells, 'row3');
            }
        }

        $result.= wf_TableBody($rows, '100%', '0', 'sortable');

        return ($result);
    }

    /**
     * Criteria for the selling report
     *
     * @return string
     */
    public function criteriaForReportRender()
    {
        $inputs = __('Selling') . ' ';
        $inputs .= wf_Selector('report[selling]', zb_BuilderSelectSellingData(), '', $this->reportParams['selling'], false);
        $inputs .= ' ' . __('ID') . ' ';
        $inputs .= ' ' . __('From') . ' ';
        $inputs .= wf_TextInput('report[idfrom]', '', $this->reportParams['idfrom'], false, '5');
        $inputs .= ' ' . __('To') . ' ';
        $inputs .= wf_TextInput('report[idto]', '', $this->reportParams['idto'], false, '5');
        $inputs .= ' ' . __('Date') . ' ';
        $inputs .= ' ' . __('From') . ' ';
        $inputs .= wf_DatePickerPreset('report[datefrom]', $this->reportParams['datefrom']);
        $inputs .= ' ' . __('To') . ' ';
        $inputs .= wf_DatePickerPreset('report[dateto]', $this->reportParams['dateto']);
        $inputs .= wf_Submit('Show');
        $result = wf_Form('', 'POST', $inputs, 'glamour');

        return $result;
    }

    /**
     * Returns module control panel
     *
     * @return string
     */
    private function panel()
    {
        $result = wf_Link('?module=report_finance', __('Back'), false, 'ubButton');

        return ($result);
    }
}

if (cfr('REPORTFINANCE')) {

    $reportSelling = new ReportSelling();
    //config data
    if (wf_CheckPost(['report'])) {
        $reportSelling->generateReport($_POST['report']);
    }

    show_window(__('Selling report'), $reportSelling->criteriaForReportRender());
    show_window(__('Selling report'), $reportSelling->render());

} else {
    show_error(__('You cant control this module'));
}

?>
