<?php
if($system->checkForRight('SQLCONSOLE')) {

//construct query forms
$sqlinputs=wf_Link("?module=sqlconsole", 'SQL Console', false, 'ubButton');
$sqlinputs.=wf_Link("?module=sqlconsole&devconsole=true", 'PHP Console', true, 'ubButton');
$sqlinputs.=wf_TextArea('sqlq', '', '', true, '80x10');
$sqlinputs.=wf_CheckInput('tableresult', 'Display query result as table', true, false);
$sqlinputs.=wf_Submit('Process query');
$sqlform=wf_Form('', 'POST', $sqlinputs, 'glamour');

$phpinputs=wf_Link("?module=sqlconsole", 'SQL Console', false, 'ubButton');
$phpinputs.=wf_Link("?module=sqlconsole&devconsole=true", 'PHP Console', true, 'ubButton');
$phpinputs.=wf_TextArea('phpq', '', '', true, '80x10');
$phpinputs.=wf_CheckInput('phphightlight', 'Hightlight this PHP code', true, true);
$phpinputs.=wf_Submit('Run this code inside framework');
$phpform=wf_Form('', 'POST', $phpinputs, 'glamour');

//show needed form
if (!isset ($_GET['devconsole'])) {
    show_window(__('SQL Console'),$sqlform);
} else {
    show_window(__('Developer Console'),$phpform);
}

// SQL console processing
if (isset($_POST['sqlq'])) {
    $newquery=trim($_POST['sqlq']);
    
    if (!empty ($newquery)) {
    $stripquery=substr($newquery,0,70).'..';
    log_register('SQLCONSOLE '.$stripquery);
    $query_result=simple_queryall($newquery);
    log_register('SQLCONSOLE QUERYDONE');
    if (!empty ($query_result)) {
        if (!isset ($_POST['tableresult'])) {
        //raw array result
        $vdump=var_export($query_result,true);
        } else {
            //show query result as table
            $tablerows='';
            foreach ($query_result as $eachresult) {
                $tablecells=wf_TableCell('');
                $tablecells.=wf_TableCell('');
                $tablerows.=wf_TableRow($tablecells, 'row1');
                foreach ($eachresult as $io=>$key) {
                $tablecells=wf_TableCell($io);
                $tablecells.=wf_TableCell($key);
                $tablerows.=wf_TableRow($tablecells, 'row3');
                }
               
            }
            $vdump=wf_TableBody($tablerows, '100%', '0', '');
        }
        
    } else {
        $vdump=__('Query returned empty result');
    }
   
    } else {
       $vdump=__('Empty query');
    }
    
     show_window(__('Result'),'<pre>'.$vdump.'</pre>');
}


//PHP console processing
if (isset($_POST['phpq'])) {
    $phpcode=trim($_POST['phpq']);
    if (!empty($phpcode)) {
        //show our code for debug
        if (isset($_POST['phphightlight'])) {
            show_window(__('Running this'),  highlight_string('<?php'."\n".$phpcode."\n".'?>',true));
        }
        //executing it
        $stripcode=substr($phpcode,0,70).'..';
        log_register('DEVCONSOLE '.$stripcode);
        eval($phpcode);
        log_register('DEVCONSOLE DONE');
        
    } else {
        show_window(__('Result'), __('Empty code part received'));
    }
}


}
else {
	show_error(__('Access denied'));
}
?>