<?php

class UHW {

    /**
     * Returns UHW control panel widget
     * 
     * @return string
     */
    public function panel() {
        $result = wf_Link('?module=uhw', wf_img('skins/ukv/report.png') . ' ' . __('Usage report'), false, 'ubButton');
        $result.= wf_Link('?module=uhw&showbrute=true', wf_img('skins/icon_key.gif') . ' ' . __('Brute attempts'), false, 'ubButton');
        return ($result);
    }

    /**
     * Returns JSON reply for jquery datatables with full list of available UHW usages
     * 
     * @return string
     */
    public function ajaxGetData() {
        $query = "SELECT * from `uhw_log` ORDER by `id` DESC;";
        $alluhw = simple_queryall($query);
        $alladdress = zb_AddressGetFulladdresslist();
        $allrealnames = zb_UserGetAllRealnames();

        $result = '{ 
                  "aaData": [ ';

        if (!empty($alluhw)) {
            foreach ($alluhw as $io => $each) {
                $profileLink = wf_Link('?module=userprofile&username=' . $each['login'], web_profile_icon() . ' ' . $each['login'], false);
                $profileLink = str_replace('"', '', $profileLink);
                $profileLink = str_replace("'", '', $profileLink);
                $profileLink = trim($profileLink);

                $userAddress = @$alladdress[$each['login']];
                $userAddress = str_replace("'", '`', $userAddress);
                $userAddress = str_replace('"', '``', $userAddress);
                $userAddress = trim($userAddress);

                $userRealname = @$allrealnames[$each['login']];
                $userRealname = str_replace("'", '`', $userRealname);
                $userRealname = str_replace('"', '``', $userRealname);
                $userRealname = trim($userRealname);

                $result.='
                    [
                    "' . $each['id'] . '",
                    "' . $each['date'] . '",
                    "' . $each['password'] . '",
                    "' . $profileLink . '",
                    "' . $userAddress . '",
                    "' . $userRealname . '",
                    "' . $each['ip'] . '",
                    "' . $each['nhid'] . '",
                    "' . $each['oldmac'] . '",
                    "' . $each['newmac'] . '"
                    ],';
            }
        }

        $result = substr($result, 0, -1);

        $result.='] 
        }';

        return ($result);
    }

    /**
     * Returns container of succefull UHW usages
     * 
     * @return string
     */
    public function renderUsageList() {
        $result = '';
        $columns = array('ID', 'Date', 'Password', 'Login', 'Address', 'Real name', 'IP', 'NHID', 'Old MAC', 'New MAC');
        $opts = '"order": [[ 0, "desc" ]]';
        $result = wf_JqDtLoader($columns, '?module=uhw&ajax=true', false, 'users', 100, $opts);
        return ($result);
    }

    /**
     * Deletes uhw brute attempt from DB by its id
     * 
     * @param int $bruteid
     * 
     * @return void
     */
    public function deleteBrute($bruteid) {
        $bruteid = vf($bruteid, 3);
        $query = "DELETE from `uhw_brute` WHERE `id`='" . $bruteid . "'";
        nr_query($query);
        log_register("UHW BRUTE DELETE [" . $bruteid . "]");
    }

    /**
     * Flushes all UHW brute attempts
     * 
     * @retrun void
     */
    public function flushAllBrute() {
        $query = "TRUNCATE TABLE `uhw_brute` ;";
        nr_query($query);
        log_register("UHW CLEANUP BRUTE");
    }

    /**
     * Shows list of available UHW brute attempts with cleanup controls
     * 
     * @return string
     */
    public function renderBruteAttempts() {
        $query = "SELECT * from `uhw_brute` ORDER by `id` ASC";
        $allbrutes = simple_queryall($query);

        $tablecells = wf_TableCell(__('ID'));
        $tablecells.=wf_TableCell(__('Date'));
        $tablecells.=wf_TableCell(__('Password'));
        $tablecells.=wf_TableCell(__('MAC'));
        $tablecells.=wf_TableCell(__('Actions'));
        $tablerows = wf_TableRow($tablecells, 'row1');

        if (!empty($allbrutes)) {
            foreach ($allbrutes as $io => $each) {
                $tablecells = wf_TableCell($each['id']);
                $tablecells.=wf_TableCell($each['date']);
                $tablecells.=wf_TableCell(strip_tags($each['password']));
                $tablecells.=wf_TableCell($each['mac']);
                $actlinks = wf_JSAlert('?module=uhw&showbrute=true&delbrute=' . $each['id'], web_delete_icon(), 'Are you serious');
                $tablecells.=wf_TableCell($actlinks);
                $tablerows.= wf_TableRow($tablecells, 'row3');
            }
        }

        $result = wf_TableBody($tablerows, '100%', 0, 'sortable');
        return ($result);
    }

}

?>