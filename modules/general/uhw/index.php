<?php

if (cfr('UHW')) {

    /**
     * Shows UHW control panel widget
     * 
     * @return void
     */
    function uhw_ShowPanel() {
        $panel = wf_Link('?module=uhw', __('Usage report'), false, 'ubButton');
        $panel.= wf_Link('?module=uhw&showbrute=true', __('Brute attempts'), false, 'ubButton');
        show_window('', $panel);
    }

    /**
     * Returns count of available UHW usages
     * 
     * @return int
     */
    function uhw_GetCount() {
        $query = "SELECT COUNT(`id`) from `uhw_log`";
        $result = simple_query($query);
        $result = $result['COUNT(`id`)'];
        return ($result);
    }

 
    /**
     * Returns JSON reply for jquery datatables with full list of available UHW usages
     * 
     * @return string
     */
    function uhw_AjaxGetData() {
        $query = "SELECT * from `uhw_log` ORDER by `id` DESC;";
        $alluhw = simple_queryall($query); 
        $alladdress = zb_AddressGetFulladdresslist();
        $allrealnames = zb_UserGetAllRealnames();
        
        $result='{ 
                  "aaData": [ ';
        
         if (!empty($alluhw)) {
            foreach ($alluhw as $io => $each) {
                $profileLink=wf_Link('?module=userprofile&username='.$each['login'], web_profile_icon().' '.$each['login'], false);
                $profileLink= str_replace('"', '', $profileLink);
                $profileLink= str_replace("'", '', $profileLink);
                $profileLink= trim($profileLink);
                
                $userAddress=@$alladdress[$each['login']];
                $userAddress=  str_replace("'", '`', $userAddress);
                $userAddress=  str_replace('"', '``', $userAddress);
                $userAddress= trim($userAddress);
                
                $userRealname=@$allrealnames[$each['login']];
                $userRealname=  str_replace("'", '`', $userRealname);
                $userRealname=  str_replace('"', '``', $userRealname);
                $userRealname= trim($userRealname);
                
                 $result.='
                    [
                    "'.$each['id'].'",
                    "'.$each['date'].'",
                    "'.$each['password'].'",
                    "'.  $profileLink.'",
                    "'.$userAddress.'",
                    "'.$userRealname.'",
                    "'.$each['ip'].'",
                    "'.$each['nhid'].'",
                    "'.$each['oldmac'].'",
                    "'.$each['newmac'].'"
                    ],';
            }
         }
            
          $result=substr($result, 0, -1);
          
          $result.='] 
        }';

        return ($result);
    }

    /**
     * Shows container of succefull UHW usages
     * 
     * @return void
     */
    function uhw_ShowUsageList() {
        $result = '';

        $jq_dt = '
          <script type="text/javascript" charset="utf-8">
                
		$(document).ready(function() {
		$(\'#uhwlisthp\').dataTable( {
 	       "oLanguage": {
			"sLengthMenu": "' . __('Show') . ' _MENU_",
			"sZeroRecords": "' . __('Nothing found') . '",
			"sInfo": "' . __('Showing') . ' _START_ ' . __('to') . ' _END_ ' . __('of') . ' _TOTAL_ ' . __('users') . '",
			"sInfoEmpty": "' . __('Showing') . ' 0 ' . __('to') . ' 0 ' . __('of') . ' 0 ' . __('users') . '",
			"sInfoFiltered": "(' . __('Filtered') . ' ' . __('from') . ' _MAX_ ' . __('Total') . ')",
                        "sSearch":       "' . __('Search') . '",
                        "sProcessing":   "' . __('Processing') . '...",
                        "oPaginate": {
                        "sFirst": "'.__('First').'",
                        "sPrevious": "'.__('Previous').'",
                        "sNext": "'.__('Next').'",
                        "sLast": "'.__('Last').'"
                    },
		},
           
                "aoColumns": [
                null,
                null,
                null,
                null,
                null,
                null,
                null,
                null,
                null,
                null
            ],      
         
        "bPaginate": true,
        "bLengthChange": true,
        "bFilter": true,
        "bSort": true,
        "bInfo": true,
        "bAutoWidth": false,
        "bProcessing": true,
        "bStateSave": true,
        "iDisplayLength": 50,
        "sAjaxSource": \'?module=uhw&ajax=true\',
	"bDeferRender": true,
        "bJQueryUI": true

                } );
		} );
		</script>
          ';

        $result = $jq_dt;
        $result.= wf_tag('table', false, 'display compact', 'id="uhwlisthp"');
        $result.= wf_tag('thead', false);

        $tablecells = wf_TableCell(__('ID'));
        $tablecells.=wf_TableCell(__('Date'));
        $tablecells.=wf_TableCell(__('Password'));
        $tablecells.=wf_TableCell(__('Login'));
        $tablecells.=wf_TableCell(__('Address'));
        $tablecells.=wf_TableCell(__('Real name'));
        $tablecells.=wf_TableCell(__('IP'));
        $tablecells.=wf_TableCell(__('NHID'));
        $tablecells.=wf_TableCell(__('Old MAC'));
        $tablecells.=wf_TableCell(__('New MAC'));
        $result.= wf_TableRow($tablecells);

        $result.= wf_tag('thead', true);
        $result.= wf_tag('table', true);

        show_window(__('UHW successful log'), $result);
    }

    /**
     * Deletes uhw brute attempt from DB by its id
     * 
     * @param int $bruteid
     * 
     * @return void
     */
    function uhw_DeleteBrute($bruteid) {
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
    function uhw_CleanAllBrute() {
        $query = "TRUNCATE TABLE `uhw_brute` ;";
        nr_query($query);
        log_register("UHW CLEANUP BRUTE");
    }

    /**
     * Shows list of available UHW brute attempts with cleanup controls
     * 
     * @return void
     */
    function uhw_ShowBrute() {
        $query = "SELECT * from `uhw_brute` ORDER by `id` DESC";
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
        $cleanupLink = wf_JSAlert('?module=uhw&showbrute=true&cleanallbrute=true', wf_img('skins/icon_cleanup.png', __('Cleanup')), 'Are you serious');
        show_window(__('Brute attempts') . ' ' . $cleanupLink, $result);
    }

    //module control panel display
    uhw_ShowPanel();

    if (!wf_CheckGet(array('showbrute'))) {
        //json reply
        if (wf_CheckGet(array('ajax'))) {
            die(uhw_AjaxGetData());
        }
        //list all UHW usage list
        uhw_ShowUsageList();
    } else {
        //deleting brute
        if (wf_CheckGet(array('delbrute'))) {
            uhw_DeleteBrute($_GET['delbrute']);
            rcms_redirect("?module=uhw&showbrute=true");
        }

        //cleanup of all brutes
        if (wf_CheckGet(array('cleanallbrute'))) {
            uhw_CleanAllBrute();
            rcms_redirect("?module=uhw&showbrute=true");
        }

        uhw_ShowBrute();
    }
} else {
    show_error(__('You cant control this module'));
}
?>
