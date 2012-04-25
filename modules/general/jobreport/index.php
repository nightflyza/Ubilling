<?php
if($system->checkForRight('EMPLOYEE')) {

    function show_jobreport_cm($previous=false) {
        $all_emp_q="SELECT * from `employee` where `active`='1'";
        $all_jobtype_q="SELECT * from `jobtypes`";
        $all_jobtype=simple_queryall($all_jobtype_q);
        $all_emp=simple_queryall($all_emp_q);
        if ($previous) {
        $pmonth=date("m")-1;
        $cyear=date("Y-");
            if ($pmonth<10) {
            $cmonth=$cyear.'0'.$pmonth;
            } else {
            $cmonth=$cyear.$pmonth;
            }
        } else {
        $cmonth=date("Y-m");
        }
        $result='';
        
        if ((!empty ($all_emp)) AND (!empty ($all_jobtype))) {
            foreach ($all_emp as $io=>$each_emp) {
            foreach ($all_jobtype as $ao=>$each_jt) {
            $cm_emp_q="SELECT COUNT(`id`) from `jobs` WHERE `date` LIKE '%".$cmonth."%' AND `workerid`='".$each_emp['id']."' AND `jobid`='".$each_jt['id']."'";
            $bu=simple_query($cm_emp_q);
            $buf[$each_emp['name']][$each_jt['jobname']]=$bu['COUNT(`id`)'];
                     }
              }
        }
     if (!empty ($buf)) {
           foreach ($buf as $empl=>$wrk) {
            $data='<table width="100%" border="0">';
             foreach ($wrk as $hh=>$cnt) {
             $data.='
                 <tr class="row3">
                 <td>'.$hh.'</td>
                 <td width="50%"><img src="skins/bar.png" width="'.($cnt*2).'" height="14"></td>
                 <td width="5%">'.$cnt.'</td>
                 
                 </tr>
                  ';
             }
             $data.='</table>';
             show_window($empl, $data);
             
           }
     }
    
    }

    $month_sel='
        <a href="?module=jobreport">'.__('Current month').'</a> <br>
        <a href="?module=jobreport&previous=true">'.__('Previous month').'</a> <br>
         ';
show_window(__('Month'),$month_sel);

if (!isset($_GET['previous'])) {
    show_jobreport_cm();
} else {
    show_jobreport_cm($_GET['previous']);
}


}
else {
	show_error(__('Access denied'));
}
?>