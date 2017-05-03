<?php

// check for right of current admin on this module
if (cfr('CARDREPORT')) {


    $altcfg = rcms_parse_ini_file(CONFIG_PATH . "alter.ini");
    if ($altcfg['PAYMENTCARDS_ENABLED']) {

        /**
         * Renders year-month search interface
         * 
         * @return void
         */
        function web_CardShowDateForm() {
            $curmonth = (wf_CheckPost(array('monthsel'))) ? vf($_POST['monthsel'], 3) : date("m");
            $curyear = (wf_CheckPost(array('yearsel'))) ? vf($_POST['yearsel'], 3) : date("Y");
            $inputs = wf_YearSelectorPreset('yearsel', __('Year'), false, $curyear).' ';
            $inputs.=wf_MonthSelector('monthsel', 'Month', $curmonth, false);
            $inputs.=wf_Submit('Show');
            $form = wf_Form("", 'POST', $inputs, 'glamour');
            show_window(__('Date'), $form);
        }

        /**
         * Renders search results for used cards
         * 
         * @param int $year
         * @param string $month
         * 
         * @return void
         */
        function web_CardShowUsageByMonth($year, $month) {
            $month = loginDB_real_escape_string($month);
            $year = loginDB_real_escape_string($year);
            $query = "SELECT * from `cardbank` WHERE `usedate` LIKE '%" . $year . "-" . $month . "-%'";
            $allusedcards = simple_queryall($query);
            $allrealnames = zb_UserGetAllRealnames();
            $alladdress = zb_AddressGetFulladdresslist();
            $totalsumm = 0;
            $totalcount = 0;
            $csvdata = '';

            $tablecells = wf_TableCell(__('ID'));
            $tablecells.=wf_TableCell(__('Serial number'));
            $tablecells.=wf_TableCell(__('Cash'));
            $tablecells.=wf_TableCell(__('Usage date'));
            $tablecells.=wf_TableCell(__('Used login'));
            $tablecells.=wf_TableCell(__('Full address'));
            $tablecells.=wf_TableCell(__('Real name'));
            $tablerows = wf_TableRow($tablecells, 'row1');


            if (!empty($allusedcards)) {
                $csvdata = __('ID') . ';' . __('Serial number') . ';' . __('Cash') . ';' . __('Usage date') . ';' . __('Used login') . ';' . __('Full address') . ';' . __('Real name') . "\n";

                foreach ($allusedcards as $io => $eachcard) {
                    $tablecells = wf_TableCell($eachcard['id']);
                    $tablecells.=wf_TableCell($eachcard['serial']);
                    $tablecells.=wf_TableCell($eachcard['cash']);
                    $tablecells.=wf_TableCell($eachcard['usedate']);
                    $profilelink = wf_Link("?module=userprofile&username=" . $eachcard['usedlogin'], web_profile_icon() . ' ' . $eachcard['usedlogin'], false);
                    $tablecells.=wf_TableCell($profilelink);
                    @$useraddress = $alladdress[$eachcard['usedlogin']];
                    $tablecells.=wf_TableCell($useraddress);
                    @$userrealname = $allrealnames[$eachcard['usedlogin']];
                    $tablecells.=wf_TableCell($userrealname);
                    $tablerows.=wf_TableRow($tablecells, 'row3');
                    $totalcount++;
                    $totalsumm = $totalsumm + $eachcard['cash'];
                    $csvdata.=$eachcard['id'] . ';' . $eachcard['serial'] . ';' . $eachcard['cash'] . ';' . $eachcard['usedate'] . ';' . $eachcard['usedlogin'] . ';' . $useraddress . ';' . $userrealname . "\n";
                }
            }

            if (!empty($csvdata)) {
                $exportFilename = 'exports/cardreport_' . $year . '-' . $month . '.csv';
                $csvdata = iconv('utf-8', 'windows-1251', $csvdata);
                file_put_contents($exportFilename, $csvdata);
                $exportLink = wf_Link('?module=cardreport&dloadcsv=' . base64_encode($exportFilename), wf_img('skins/excel.gif', __('Export')), false, '');
            } else {
                $exportLink = '';
            }

            $result = wf_TableBody($tablerows, '100%', '0', 'sortable');
            $result.=__('Total') . ': ' . $totalcount . ' ' . __('payments') . ', ' . __('with total amount') . ': ' . $totalsumm;
            show_window(__('Payment cards usage report') . ' ' . $exportLink, $result);
        }

        web_CardShowDateForm();

        //selecting month and date
        if (wf_CheckPost(array('yearsel', 'monthsel'))) {
            $needyear = $_POST['yearsel'];
            $needmonth = $_POST['monthsel'];
        } else {
            $needyear = curyear();
            $needmonth = date("m");
        }


        //download exported search
        if (wf_CheckGet(array('dloadcsv'))) {
            zb_DownloadFile(base64_decode($_GET['dloadcsv']), 'docx');
        }

        web_CardShowUsageByMonth($needyear, $needmonth);
    } else {
        show_error(__('This module is disabled'));
    }
} else {
    show_error(__('You cant control this module'));
}
?>