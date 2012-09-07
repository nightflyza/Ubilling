<?php
if($system->checkForRight('EMPLOYEE')) {

$donejobs=  ts_JGetJobsReport();
show_window(__('Job report'),  wf_FullCalendar($donejobs));

}
else {
	show_error(__('Access denied'));
}
?>