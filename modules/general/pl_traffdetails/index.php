<?php

if (cfr('PLDETAILS')) {


    if (isset($_GET['username'])) {
        $login = $_GET['username'];
        $userdata = zb_UserGetStargazerData($login);
        $cyear = curyear();
        $cmonth = date("m");
        $tablename = 'detailstat_' . $cmonth . '_' . $cyear . '';

        /**
         * Checks is detailstatstable exists 
         * 
         * @global string $tablename
         * @return bool
         */
        function ds_CheckTable() {
            global $tablename;
            $query = "SELECT CASE WHEN (SELECT COUNT(*) AS STATUS FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = (SELECT DATABASE()) AND TABLE_NAME = '" . $tablename . "') = 1 THEN (SELECT 1)  ELSE (SELECT 0) END AS result;";
            $result = simple_query($query);
            $result = (empty($result)) ? false : true;
            return ($result);
        }

        /**
         * Returns available days with stats from table
         * 
         * @global string $tablename
         * @return array
         */
        function ds_GetDays() {
            global $tablename;
            $query = "SELECT DISTINCT `day` from `" . $tablename . "`";
            $alldays = simple_queryall($query);
            return($alldays);
        }

        /**
         * Returns stats by day for some user
         * 
         * @global string $tablename
         * @param string $login
         * @param int $day
         * @param int $page
         * @return array
         */
        function ds_GetDayStats($login, $day, $page = 0) {
            global $tablename;
            $pagelimit = 100;
            $page = vf($page);
            $login = mysql_real_escape_string($login);
            $dey = vf($day);
            $query = "SELECT * from `" . $tablename . "` WHERE `login`='" . $login . "' AND `day`='" . $day . "' ORDER by `starttime` DESC";
            $daystats = simple_queryall($query);
            return($daystats);
        }

        /**
         * Returns count of summ dowloaded bytes by day for some user
         * 
         * @global string $tablename
         * @param string $login
         * @param int $day
         * @return int
         */
        function ds_GetDownSumm($login, $day) {
            global $tablename;
            $login = vf($login);
            $day = vf($day);
            $query = "SELECT SUM(`down`) from `" . $tablename . "` WHERE `login`='" . $login . "'  AND `day`='" . $day . "'";
            $summ = simple_query($query);
            return($summ['SUM(`down`)']);
        }

        /**
         * Returns count of summ uploaded bytes by day for some user
         * 
         * @global string $tablename
         * @param string $login
         * @param int $day
         * @return int
         */
        function ds_GetUpSumm($login, $day) {
            global $tablename;
            $login = vf($login);
            $day = vf($day);
            $query = "SELECT SUM(`up`) from `" . $tablename . "` WHERE `login`='" . $login . "'  AND `day`='" . $day . "'";
            $summ = simple_query($query);
            return($summ['SUM(`up`)']);
        }

        /**
         * Returns summ of money for some day used by user
         * 
         * @global string $tablename
         * @param string $login
         * @param int $day
         * @return string
         */
        function ds_GetCashSumm($login, $day) {
            global $tablename;
            $login = vf($login);
            $day = vf($day);
            $query = "SELECT SUM(`cash`) from `" . $tablename . "` WHERE `login`='" . $login . "'  AND `day`='" . $day . "'";
            $summ = simple_query($query);
            return($summ['SUM(`cash`)']);
        }

        /**
         * Renders stats for some user
         * 
         * @param string $login
         * @return string
         */
        function web_DSShow($login) {

            $login = vf($login);
            $days = ds_GetDays();
            $result = '';

            $cells = wf_TableCell(__('Day'));
            $cells.= wf_TableCell(__('Downloaded'));
            $cells.= wf_TableCell(__('Uploaded'));
            $cells.= wf_TableCell(__('Cash'));
            $rows = wf_TableRow($cells, 'row1');

            if (!empty($days)) {
                foreach ($days as $io => $eachday) {
                    $downsumm = ds_GetDownSumm($login, $eachday['day']);
                    $upsumm = ds_GetUpSumm($login, $eachday['day']);
                    $cashsumm = ds_GetCashSumm($login, $eachday['day']);
                    if (!$downsumm) {
                        $downsumm = 0;
                    }
                    if (!$upsumm) {
                        $upsumm = 0;
                    }
                    $dayLink = wf_Link('?module=pl_traffdetails&username=' . $login . '&day=' . $eachday['day'], $eachday['day']);
                    $cells = wf_TableCell($dayLink);
                    $cells.= wf_TableCell(stg_convert_size($downsumm), '', '', 'sorttable_customkey="' . $downsumm . '"');
                    $cells.= wf_TableCell(stg_convert_size($upsumm), '', '', 'sorttable_customkey="' . $upsumm . '"');
                    $cells.= wf_TableCell(round($cashsumm, 2));
                    $rows.= wf_TableRow($cells, 'row3');
                }
            }

            $result.=wf_TableBody($rows, '100%', 0, 'sortable');

            return($result);
        }

        /**
         * 
         * @param type $login
         * @param type $day
         * @param int $page
         * @return string
         */
        function web_DSShowDayStats($login, $day, $page = 0) {
            $traffclasse_raw = zb_DirectionsGetAll();
            $tc = array();
            if (!empty($traffclasse_raw)) {
                foreach ($traffclasse_raw as $io => $eachtc) {
                    $tc[$eachtc['rulenumber']] = $eachtc['rulename'];
                }
            }
            $login = mysql_real_escape_string($login);
            $page = vf($page);
            $day = vf($day);
            $daystats = ds_GetDayStats($login, $day, $page);
            $result = '';

            $cells = wf_TableCell(__('Session start'));
            $cells.= wf_TableCell(__('Session end'));
            $cells.= wf_TableCell(__('IP'));
            $cells.= wf_TableCell(__('Traffic classes'));
            $cells.= wf_TableCell(__('Downloaded') . '/' . __('Uploaded'));
            $cells.= wf_TableCell(__('Cash'));
            $rows = wf_TableRow($cells, 'row1');

            if (!empty($daystats)) {
                foreach ($daystats as $io => $eachtraff) {


                    $cells = wf_TableCell($eachtraff['startTime']);
                    $cells.= wf_TableCell($eachtraff['endTime']);
                    $whoisLink = wf_Link('?module=whois&ip=' . $eachtraff['IP'], wf_img('skins/icon_whois_small.png', __('Whois')));
                    $webLink = wf_Link('http://' . $eachtraff['IP'], $eachtraff['IP']);
                    $cells.= wf_TableCell($whoisLink . ' ' . $webLink);
                    $cells.= wf_TableCell(@$tc[$eachtraff['dir']]);
                    $cells.= wf_TableCell(stg_convert_size($eachtraff['down']) . ' / ' . stg_convert_size($eachtraff['up']), '', '', 'sorttable_customkey="' . ($eachtraff['down'] + $eachtraff['up']) . '"');
                    $cells.= wf_TableCell(round($eachtraff['cash'], 3));
                    $rows.= wf_TableRow($cells, 'row3');
                }
            }

            $result.=wf_TableBody($rows, '100%', 0, 'sortable');
            return ($result);
        }

        if (ds_CheckTable()) {
            show_window(__('Traffic detailed stats'), web_DSShow($login));
        } else {
            show_error(__('No detailstats database exists'));
        }

        if (isset($_GET['day'])) {
            $day = $_GET['day'];
            show_window(__('Detailed stats by day'), web_DSShowDayStats($login, $day));
        }

        show_window('', web_UserControls($login));
    }
} else {
    show_error(__('You cant control this module'));
}
?>
