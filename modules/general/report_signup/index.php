<?php
if (cfr('REPORTSIGNUP')) {
   
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
        
        $tablecells=wf_TableCell('');
        $tablecells.=wf_TableCell(__('Month'));
        $tablecells.=wf_TableCell(__('Signups'));
        $tablecells.=wf_TableCell(__('Visual'), '50%');
        $tablerows=wf_TableRow($tablecells, 'row1');
        
        foreach ($yearcount as $eachmonth=>$count) {
            $tablecells=wf_TableCell($eachmonth);
            $tablecells.=wf_TableCell(rcms_date_localise($allmonths[$eachmonth]));
            $tablecells.=wf_TableCell($count);
            $tablecells.=wf_TableCell(web_bar($count, $maxsignups), '', '', 'sorttable_customkey="'.$count.'"');
            $tablerows.=wf_TableRow($tablecells, 'row3');
        }
        
        $result=wf_TableBody($tablerows, '100%', '0', 'sortable');
        show_window(__('User signups by year').' '.$year, $result);
    }
    
    
    
    function web_SignupsShowCurrentMonth() {
        $alltariffs=zb_TariffsGetAllUsers();
        $cmonth=curmonth();
        $where="WHERE `date` LIKE '".$cmonth."%'";
        $signups=zb_SignupsGet($where);
       
        $tablecells=wf_TableCell(__('ID'));
        $tablecells.=wf_TableCell(__('Date'));
        $tablecells.=wf_TableCell(__('Administrator'));
        $tablecells.=wf_TableCell(__('Login'));
        $tablecells.=wf_TableCell(__('Tariff'));
        $tablecells.=wf_TableCell(__('Full address'));
        $tablerows=wf_TableRow($tablecells, 'row1');
        
            if (!empty ($signups)) {
                foreach ($signups as $io=>$eachsignup) {
             
                    $tablecells=wf_TableCell($eachsignup['id']);
                    $tablecells.=wf_TableCell($eachsignup['date']);
                    $tablecells.=wf_TableCell($eachsignup['admin']);
                    $tablecells.=wf_TableCell($eachsignup['login']);
                    $tablecells.=wf_TableCell(@$alltariffs[$eachsignup['login']]);
                    $profilelink=wf_Link('?module=userprofile&username='.$eachsignup['login'], web_profile_icon().' '.$eachsignup['address']);
                    $tablecells.=wf_TableCell($profilelink);
                    $tablerows.=wf_TableRow($tablecells, 'row3');
                }
            }
            
        $result=wf_TableBody($tablerows, '100%', '0', 'sortable');
        show_window(__('Current month user signups'),$result);
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
    $yearform=wf_Form('', 'POST', $yearinputs, 'glamour');
    
    show_window(__('Year'), $yearform);
    web_SignupsShowToday();
    web_SignupsGraphYear($year);
    web_SignupGraph();
  
    web_SignupsShowCurrentMonth();
    
      zb_BillingStats(true);
    
} else {
      show_error(__('You cant control this module'));
}

?>
