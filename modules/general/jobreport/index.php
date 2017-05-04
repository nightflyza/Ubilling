<?php
if($system->checkForRight('EMPLOYEE')) {
    
function ts_EmployeeMonthGraphs() {
    $curmonth=  curmonth();
    $employees =ts_GetAllEmployee();
    $month_jobs_q="SELECT `workerid`,`jobid` from `jobs` WHERE `date` LIKE '".$curmonth."%'";
    $alljobs=  simple_queryall($month_jobs_q);
    $jobtypes= ts_GetAllJobtypes();
    
    $jobdata=array();
    $result='';
    
    if (!empty($employees)) {
     if (!empty($alljobs)) {
         foreach ($alljobs as $io=>$eachjob) {
             if (isset($jobdata[$eachjob['workerid']][$eachjob['jobid']])) {
                 $jobdata[$eachjob['workerid']][$eachjob['jobid']]++;
             } else {
                 $jobdata[$eachjob['workerid']][$eachjob['jobid']]=1;
             }
             
         }
     }   
     
     //build graphs for each employee
     if (!empty($jobdata)) {
         foreach ($jobdata as $employee=>$each) {
             $employeeName=isset($employees[$employee]) ? $employees[$employee] : __('Deleted');
             $result.=wf_tag('h3',false).$employeeName.  wf_tag('h3', true);
             $rows='';
             if (!empty($each)) {
                   foreach ($each as $jobid=>$count) {
                     $cells=  wf_TableCell(@$jobtypes[$jobid],'40%');
                     $cells.=wf_TableCell($count,'20%');
                     $cells.=wf_TableCell(web_bar($count, sizeof($alljobs)),'40%');
                     $rows.=wf_TableRow($cells, 'row3');
                 }
                 
             }
             $result.=wf_TableBody($rows, '100%', 0);
             $result.=wf_delimiter();
         }
     }
     
    }
    
    return ($result);
   
}    



$jobgraphs=  wf_modal(wf_img('skins/icon_stats.gif',__('Graphs')), __('Graphs'), ts_EmployeeMonthGraphs(), '', '800', '600');    
    
$donejobs=  ts_JGetJobsReport();
show_window(__('Job report').' '.$jobgraphs,  wf_FullCalendar($donejobs));

}
else {
	show_error(__('Access denied'));
}
?>