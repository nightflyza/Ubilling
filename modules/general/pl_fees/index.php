<?php
if (cfr('PLFEES')) {
  
    if (isset($_GET['username'])) {
       $login=$_GET['username'];
       
       $alter_conf=rcms_parse_ini_file(CONFIG_PATH."alter.ini");
       
       $sudo=$billing_config['SUDO'];
       $cat=$billing_config['CAT'];
       $grep=$billing_config['GREP'];
       $stglog=$alter_conf['STG_LOG_PATH'];
       
       // monthly fees output
       $command=$sudo.' '.$cat.' '.$stglog.' | '.$grep.' "fee charge"'.' | '.$grep.' "'.$login.'" ';
       $rawdata=shell_exec($command);

        $tablecells=wf_TableCell(__('Date'));
        $tablecells.=wf_TableCell(__('Time'));
        $tablecells.=wf_TableCell(__('From'));
        $tablecells.=wf_TableCell(__('To'));
        $tablerows=wf_TableRow($tablecells, 'row1');
            
       if (!empty ($rawdata)) {
        $cleardata=exploderows($rawdata);
        foreach ($cleardata as $eachline) {
            $eachfee=explode(' ',$eachline);
            if (isset($eachfee[1])) {
            $feefrom=str_replace("'.", '', $eachfee[12]);
            $feeto=str_replace("'.", '', $eachfee[14]);
            $feefrom=str_replace("'", '', $feefrom);
            $feeto=str_replace("'", '', $feeto);
            $tablecells=wf_TableCell($eachfee[0]);
            $tablecells.=wf_TableCell($eachfee[1]);
            $tablecells.=wf_TableCell($feefrom);
            $tablecells.=wf_TableCell($feeto);
            $tablerows.=wf_TableRow($tablecells, 'row3');
            }
         }
        
       }
       $result=wf_TableBody($tablerows, '100%', '0', 'sortable');
       show_window(__('Money fees'),$result);
       
      
       
       
       
       show_window('',web_UserControls($login));
    }
    
} else {
      show_error(__('You cant control this module'));
}


?>