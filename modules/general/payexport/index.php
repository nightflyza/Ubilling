<?php
if (cfr('PAYEXPORT')) {
    
$alter_conf=rcms_parse_ini_file(CONFIG_PATH."alter.ini");

if ($alter_conf['EXPORT_ENABLED']) {   
   show_window(__('Export payments data'),zb_ExportForm());
   
   if ((isset($_POST['fromdate'])) AND (isset($_POST['todate']))) {
       $from_date=$_POST['fromdate'];
       $to_date=$_POST['todate'];
       
       //export types
       //xml
       if ($alter_conf['EXPORT_FORMAT']=='xml') {
       $export_result=zb_ExportPayments($from_date, $to_date);
       }
       
       //dbf
       if ($alter_conf['EXPORT_FORMAT']=='dbf') {
       //need to be written
       }
       
       $export_file='exports/'.time().'.export';
       $exported_link='<a href="'.$export_file.'">'.__('Download').'</a>';
       file_write_contents($export_file, $export_result);
       show_window(__('Exported data download'), $exported_link);
       

   }
} else {
    show_error(__('Payments export not enabled'));
}
 
    
} else {
      show_error(__('You cant control this module'));
}

?>
