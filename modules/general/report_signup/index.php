<?php
if (cfr('REPORTSIGNUP')) {
   
    $altercfg=$ubillingConfig->getAlter();
    
    
    function zb_SignupsGet($where) {
        $query="SELECT * from `userreg` ".$where;
        $result=simple_queryall($query);
        return($result);
    }

       
    // returns array like $month_num=>$signup_count
    function zb_SignupsGetCountYear($year) {
        $months=months_array();
        $result=array();
        foreach ($months as $eachmonth=>$monthname) { 
        $query="SELECT COUNT(`id`) from `userreg` WHERE `date` LIKE '".$year."-".$eachmonth."%'";
        $monthcount=simple_query($query);
        $monthcount=$monthcount['COUNT(`id`)'];
        $result[$eachmonth]=$monthcount;
        }
        return($result);
    }
    
    // shows user signups by year with funny bars
    function web_SignupsGraphYear($year) {
        $year=vf($year);
        $yearcount=zb_SignupsGetCountYear($year);
        $maxsignups=max($yearcount);
        $allmonths=months_array();
        $totalcount=0;
        
        $tablecells=wf_TableCell('');
        $tablecells.=wf_TableCell(__('Month'));
        $tablecells.=wf_TableCell(__('Signups'));
        $tablecells.=wf_TableCell(__('Visual'), '50%');
        $tablerows=wf_TableRow($tablecells, 'row1');
        
        foreach ($yearcount as $eachmonth=>$count) {
            $totalcount=$totalcount+$count;
            $tablecells=wf_TableCell($eachmonth);
            $tablecells.=wf_TableCell(wf_Link('?module=report_signup&month='.$year.'-'.$eachmonth,rcms_date_localise($allmonths[$eachmonth])));
            $tablecells.=wf_TableCell($count);
            $tablecells.=wf_TableCell(web_bar($count, $maxsignups), '', '', 'sorttable_customkey="'.$count.'"');
            $tablerows.=wf_TableRow($tablecells, 'row3');
        }
        
        $result=  wf_TableBody($tablerows, '100%', '0', 'sortable');
        $result.= __('Total').': '.$totalcount; 
        show_window(__('User signups by year').' '.$year, $result);
    }
    
    
    
    function web_SignupsShowCurrentMonth() {
        global $altercfg;
        $alltariffs=zb_TariffsGetAllUsers();
        $cmonth=curmonth();
        $where="WHERE `date` LIKE '".$cmonth."%' ORDER by `date` DESC;";
        $signups=zb_SignupsGet($where);
        $curdate=  curdate();
        $tablecells=wf_TableCell(__('ID'));
        $tablecells.=wf_TableCell(__('Date'));
        $tablecells.=wf_TableCell(__('Administrator'));
        if ($altercfg['SIGREP_CONTRACT']) {
          $tablecells.=wf_TableCell(__('Contract'));   
          $allcontracts= array_flip(zb_UserGetAllContracts());
        }
        $tablecells.=wf_TableCell(__('Login'));
        $tablecells.=wf_TableCell(__('Tariff'));
        $tablecells.=wf_TableCell(__('Full address'));
        $tablerows=wf_TableRow($tablecells, 'row1');
        
            if (!empty ($signups)) {
                foreach ($signups as $io=>$eachsignup) {
             
                    $tablecells=wf_TableCell($eachsignup['id']);
                    $tablecells.=wf_TableCell($eachsignup['date']);
                    $tablecells.=wf_TableCell($eachsignup['admin']);
                     if ($altercfg['SIGREP_CONTRACT']) {
                        $tablecells.=wf_TableCell(@$allcontracts[$eachsignup['login']]);   
                    }
                    $tablecells.=wf_TableCell($eachsignup['login']);
                    $tablecells.=wf_TableCell(@$alltariffs[$eachsignup['login']]);
                    $profilelink=wf_Link('?module=userprofile&username='.$eachsignup['login'], web_profile_icon().' '.$eachsignup['address']);
                    $tablecells.=wf_TableCell($profilelink);
                    if (ispos($eachsignup['date'], $curdate)) {
                        $rowClass='todaysig';
                    } else {
                        $rowClass='row3';
                    }
                    //ugly check - is user removed?
                    if (empty($sigTariff)) {
                        $rowClass='sigdeleteduser';
                    }
                        
                    $tablerows.=wf_TableRow($tablecells, $rowClass);
                }
            }
         
        $result=wf_TableBody($tablerows, '100%', '0', 'sortable');
        show_window(__('Current month user signups'),$result);
    }
    
     function web_SignupsShowAnotherYearMonth($cmonth) {
        global $altercfg;
        $alltariffs=zb_TariffsGetAllUsers();
        $cmonth=  mysql_real_escape_string($cmonth);
        $where="WHERE `date` LIKE '".$cmonth."%' ORDER by `date` DESC;";
        $signups=zb_SignupsGet($where);
        $curdate=  curdate();
        $tablecells=wf_TableCell(__('ID'));
        $tablecells.=wf_TableCell(__('Date'));
        $tablecells.=wf_TableCell(__('Administrator'));
        if ($altercfg['SIGREP_CONTRACT']) {
          $tablecells.=wf_TableCell(__('Contract'));   
          $allcontracts= array_flip(zb_UserGetAllContracts());
        }
        $tablecells.=wf_TableCell(__('Login'));
        $tablecells.=wf_TableCell(__('Tariff'));
        $tablecells.=wf_TableCell(__('Full address'));
        $tablerows=wf_TableRow($tablecells, 'row1');
        
            if (!empty ($signups)) {
                foreach ($signups as $io=>$eachsignup) {
             
                    $tablecells=wf_TableCell($eachsignup['id']);
                    $tablecells.=wf_TableCell($eachsignup['date']);
                    $tablecells.=wf_TableCell($eachsignup['admin']);
                     if ($altercfg['SIGREP_CONTRACT']) {
                        $tablecells.=wf_TableCell(@$allcontracts[$eachsignup['login']]);   
                    }
                    $tablecells.=wf_TableCell($eachsignup['login']);
                    @$sigTariff=$alltariffs[$eachsignup['login']];
                    $tablecells.=wf_TableCell($sigTariff);
                    $profilelink=wf_Link('?module=userprofile&username='.$eachsignup['login'], web_profile_icon().' '.$eachsignup['address']);
                    $tablecells.=wf_TableCell($profilelink);
                    if (ispos($eachsignup['date'], $curdate)) {
                        $rowClass='todaysig';
                    } else {
                        $rowClass='row3';
                    }
                    //ugly check - is user removed?
                    if (empty($sigTariff)) {
                        $rowClass='sigdeleteduser';
                    }
                        
                    $tablerows.=wf_TableRow($tablecells, $rowClass);
                }
            }
         
        $result=wf_TableBody($tablerows, '100%', '0', 'sortable');
        show_window(__('User signups by month').' '.$cmonth,$result);
    }
    
    function web_SignupsShowToday() {
         $query="SELECT COUNT(`id`) from `userreg` WHERE `date` LIKE '".  curdate()."%'";
         $sigcount=simple_query($query);
         $sigcount=$sigcount['COUNT(`id`)'];
         show_window(__('Today signups').': '.$sigcount,'');
    }
    
        if (!isset($_POST['yearsel'])) {
        $year=curyear();
        } else {
        $year=$_POST['yearsel'];
        }

    $yearinputs=wf_YearSelector('yearsel');
    $yearinputs.=wf_Submit(__('Show'));
    $yearform=wf_Form('?module=report_signup', 'POST', $yearinputs, 'glamour');
    
    show_window(__('Year'), $yearform);
    web_SignupsShowToday();
    web_SignupsGraphYear($year);
    web_SignupGraph();
    
    if (!wf_CheckGet(array('month'))) {
        web_SignupsShowCurrentMonth();
    } else {
        web_SignupsShowAnotherYearMonth($_GET['month']);
    }
    
      zb_BillingStats(true);
    
} else {
      show_error(__('You cant control this module'));
}

?>
