<?php
if (cfr('UHW')) {
    
    function uhw_ShowPanel() {
        $panel=  wf_Link('?module=uhw', __('Usage report'), false, 'ubButton');
        $panel.=  wf_Link('?module=uhw&showbrute=true', __('Brute attempts'), false, 'ubButton');
        show_window('', $panel);
    }
    
    function uhw_GetCount() {
        $query="SELECT COUNT(`id`) from `uhw_log`";
        $result=  simple_query($query);
        $result=$result['COUNT(`id`)'];
        return ($result);
    }
    
    function uhw_ShowAllSucefful() {
        
        $totalcount=uhw_GetCount();
        $perpage=100;
        
         //pagination 
         if (!isset ($_GET['page'])) {
          $current_page=1;
          } else {
          $current_page=vf($_GET['page'],3);
          }
          
         if ($totalcount>$perpage) {
          $paginator=wf_pagination($totalcount, $perpage, $current_page, "?module=uhw",'ubButton');
          $from=$perpage*($current_page-1);
          $to=$perpage;
          $query="SELECT * from `uhw_log` ORDER by `id` DESC LIMIT ".$from.",".$to.";";
          $alluhw=  simple_queryall($query);
         
          } else {
          $paginator='';
          $query="SELECT * from `uhw_log` ORDER by `id` DESC;";
          $alluhw=  simple_queryall($query);
        }
        
        $alladdress=  zb_AddressGetFulladdresslist();
        $allrealnames=  zb_UserGetAllRealnames();
        
        $tablecells=wf_TableCell(__('ID'));
        $tablecells.=wf_TableCell(__('Date'));
        $tablecells.=wf_TableCell(__('Password'));
        $tablecells.=wf_TableCell(__('Login'));
        $tablecells.=wf_TableCell(__('Address'));
        $tablecells.=wf_TableCell(__('Real name'));
        $tablecells.=wf_TableCell(__('IP'));
        $tablecells.=wf_TableCell(__('NHID'));
        $tablecells.=wf_TableCell(__('Old MAC'));
        $tablecells.=wf_TableCell(__('New MAC'));
        $tablerows=  wf_TableRow($tablecells, 'row1');
        
        
        if  (!empty($alluhw)) {
            foreach ($alluhw as $io => $each) {
                $tablecells=wf_TableCell($each['id']);
                $tablecells.=wf_TableCell($each['date']);
                $tablecells.=wf_TableCell($each['password']);
                $tablecells.=wf_TableCell(wf_Link('?module=userprofile&username='.$each['login'], web_profile_icon().' '.$each['login'], false));
                $tablecells.=wf_TableCell(@$alladdress[$each['login']]);
                $tablecells.=wf_TableCell(@$allrealnames[$each['login']]);
                $tablecells.=wf_TableCell($each['ip'],'','',  'sorttable_customkey="'.ip2int($each['ip']).'"');
                $tablecells.=wf_TableCell($each['nhid']);
                $tablecells.=wf_TableCell($each['oldmac']);
                $tablecells.=wf_TableCell($each['newmac']);
                $tablerows.=  wf_TableRow($tablecells, 'row3');

            }
            
            $result=  wf_TableBody($tablerows, '100%', 0, 'sortable');
            $result.=$paginator;
        } else {
            $result=__('No successful usages of Unknowh HardWare helper');
        }
        
       
        show_window(__('UHW successful log'), $result);
    }
    
    function uhw_DeleteBrute($bruteid) {
        $bruteid=vf($bruteid,3);
        $query="DELETE from `uhw_brute` WHERE `id`='".$bruteid."'";
        nr_query($query);
        log_register("UHW BRUTE DELETE [".$bruteid."]");
        
    }
    
    function uhw_CleanAllBrute() {
        $query="TRUNCATE TABLE `uhw_brute` ;";
        nr_query($query);
        log_register("UHW CLEANUP BRUTE");
        
    }
    function uhw_ShowBrute() {
        $query="SELECT * from `uhw_brute` ORDER by `id` DESC";
        $allbrutes=  simple_queryall($query);
        
        $tablecells=wf_TableCell(__('ID'));
        $tablecells.=wf_TableCell(__('Date'));
        $tablecells.=wf_TableCell(__('Password'));
        $tablecells.=wf_TableCell(__('MAC'));
        $tablecells.=wf_TableCell(__('Actions'));
        $tablerows=  wf_TableRow($tablecells, 'row1');
        
        if (!empty($allbrutes)) {
            foreach ($allbrutes as $io=>$each) {
                $tablecells=wf_TableCell($each['id']);
                $tablecells.=wf_TableCell($each['date']);
                $tablecells.=wf_TableCell(strip_tags($each['password']));
                $tablecells.=wf_TableCell($each['mac']);
                $actlinks=  wf_JSAlert('?module=uhw&showbrute=true&delbrute='.$each['id'], web_delete_icon(), 'Are you serious');
                $tablecells.=wf_TableCell($actlinks);
                $tablerows.=  wf_TableRow($tablecells, 'row3');
                
            }
        }
        
        $result=   wf_TableBody($tablerows, '100%', 0, 'sortable');
        $cleanupLink= wf_JSAlert('?module=uhw&showbrute=true&cleanallbrute=true', wf_img('skins/icon_cleanup.png', __('Cleanup')), 'Are you serious');
        show_window(__('Brute attempts').' '.$cleanupLink, $result);
        
    }
    
    
    uhw_ShowPanel();
    
    if  (!wf_CheckGet(array('showbrute'))) {
       uhw_ShowAllSucefful();  
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
