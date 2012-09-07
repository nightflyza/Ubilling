<?php
if (cfr('REPORTMASTER')) {
    
  function web_ReportMasterShow($reportfile,$report_name,$titles,$keys,$alldata,$address=0,$realnames=0,$rowcount=0) {
      $report_name=__($report_name).' <a href="?module=reportmaster&view='.$reportfile.'&printable=true" target="_BLANK"><img src="skins/printer_small.gif"></a>';
      $allrealnames=zb_UserGetAllRealnames();
      $alladdress=zb_AddressGetFulladdresslist();
      $i=0;
        $result='<table width="100%" class="sortable" border="0">';
        $result.='<tr class="row1">';
        foreach ($titles as $eachtitle) {
            $result.='<td>'.__($eachtitle).'</td>';
        }
        if ($address) {
              $result.='<td>'.__('Full address').'</td>';
        }
        if ($realnames) {
                $result.='<td>'.__('Real Name').'</td>';
        }
       
        $result.='</tr>';
        if (!empty ($alldata)) {
            foreach ($alldata as $io=>$eachdata) {
                $i++;
                $result.='<tr class="row3">';
                foreach ($keys as $eachkey) {
                if (array_key_exists($eachkey, $eachdata)) {
                    $result.='<td>'.$eachdata[$eachkey].'</td>';
                    }    
                }
             if ($address) {
              $result.='<td>'.@$alladdress[$eachdata['login']].'</td>';
             }
              if ($realnames) {
               $result.='<td><a href="?module=userprofile&username='.$eachdata['login'].'">'.  web_profile_icon().' '.@$allrealnames[$eachdata['login']].'</a></td>';
             }
            $result.='</tr>';
            }
        }
        $result.='</table>';
        if ($rowcount) {
            $result.='<strong>'.__('Total').': '.$i.'</strong>';
        }
        show_window($report_name, $result);
    }
    
    
      function web_ReportMasterShowPrintable($report_name,$titles,$keys,$alldata,$address=0,$realnames=0,$rowcount=0) {
      $report_name='<h2>'.__($report_name).'</h2>';
      $allrealnames=zb_UserGetAllRealnames();
      $alladdress=zb_AddressGetFulladdresslist();
      $i=0;
      $result='
            <style type="text/css">
        table.printrm {
	border-width: 1px;
	border-spacing: 2px;
	border-style: outset;
	border-color: gray;
	border-collapse: separate;
	background-color: white;
        }
        table.printrm th {
	border-width: 1px;
	padding: 1px;
	border-style: dashed;
	border-color: gray;
	background-color: white;
	-moz-border-radius: ;
        }
        table.printrm td {
	border-width: 1px;
	padding: 1px;
	border-style: dashed;
	border-color: gray;
	background-color: white;
	-moz-border-radius: ;
        }
        </style>


         <table width="100%"  class="printrm">';
        $result.='<tr>';
        foreach ($titles as $eachtitle) {
            $result.='<td>'.__($eachtitle).'</td>';
        }
        if ($address) {
              $result.='<td>'.__('Full address').'</td>';
        }
        if ($realnames) {
                $result.='<td>'.__('Real Name').'</td>';
        }
       
        $result.='</tr>';
        if (!empty ($alldata)) {
            foreach ($alldata as $io=>$eachdata) {
                $i++;
                $result.='<tr>';
                foreach ($keys as $eachkey) {
                if (array_key_exists($eachkey, $eachdata)) {
                    $result.='<td>'.$eachdata[$eachkey].'</td>';
                    }    
                }
             if ($address) {
              $result.='<td>'.@$alladdress[$eachdata['login']].'</td>';
             }
              if ($realnames) {
               $result.='<td>'.@$allrealnames[$eachdata['login']].'</td>';
             }
            $result.='</tr>';
            }
        }
        $result.='</table>';
        if ($rowcount) {
            $result.='<strong>'.__('Total').': '.$i.'</strong>';
        }
        print($report_name.$result);
        die();
    }
    
    
 function web_RMTriggerSelector($name,$state='') {
     $result=web_TriggerSelector($name, $state);
     return ($result);
 }
    

 
function web_ReportMasterShowReportsList() {
    $reports_path=DATA_PATH."reports/";
    $allreports=rcms_scandir($reports_path);
    $result='<table width="100%" border="0" class="sortable">';
    $result.='
                <tr class="row1">
                <td>'.__('Report name').'</td>
                <td>'.__('Actions').'</td>
                </tr>
                ';
    if (!empty ($allreports)) {
        foreach ($allreports as $eachreport) {
            $report_template=rcms_parse_ini_file($reports_path.$eachreport);
            $result.='
                <tr class="row3">
                <td><a href="?module=reportmaster&view='.$eachreport.'">'.__($report_template['REPORT_NAME']).'</a></td>
                <td>
                '.  wf_JSAlert('?module=reportmaster&delete='.$eachreport, web_delete_icon(), 'Are you serious').'
                <a href="?module=reportmaster&edit='.$eachreport.'">'.   web_edit_icon().'</a>
                </td>
                </tr>
                ';
        }
        
    }
    $result.='</table>';
    return ($result);
} 

function zb_RMDeleteReport($reportname) {
     $reportname=vf($reportname);
     $reports_path=DATA_PATH."reports/";
     unlink($reports_path.$reportname);
     log_register("ReportMaster DELETE ".$reportname);
}

function web_ReportMasterViewReport($reportname) {
    $reportname=vf($reportname);
    $reports_path=DATA_PATH."reports/";
    //if valid report
    if (file_exists($reports_path.$reportname)) {
        $report_template=rcms_parse_ini_file($reports_path.$reportname);
        $data_query=simple_queryall($report_template['REPORT_QUERY']);
        $keys=explode(',',$report_template['REPORT_KEYS']);
        $titles=explode(',',$report_template['REPORT_FIELD_NAMES']);
        web_ReportMasterShow($reportname,$report_template['REPORT_NAME'],$titles, $keys, $data_query,$report_template['REPORT_ADDR'],$report_template['REPORT_RNAMES'],$report_template['REPORT_ROW_COUNT']);
        
    } else {
        show_error(__('Unknown report'));
    }
    
}

function web_ReportMasterViewReportPrintable($reportname) {
    $reportname=vf($reportname);
    $reports_path=DATA_PATH."reports/";
    //if valid report
    if (file_exists($reports_path.$reportname)) {
        $report_template=rcms_parse_ini_file($reports_path.$reportname);
        $data_query=simple_queryall($report_template['REPORT_QUERY']);
        $keys=explode(',',$report_template['REPORT_KEYS']);
        $titles=explode(',',$report_template['REPORT_FIELD_NAMES']);
        web_ReportMasterShowPrintable($report_template['REPORT_NAME'],$titles, $keys, $data_query,$report_template['REPORT_ADDR'],$report_template['REPORT_RNAMES'],$report_template['REPORT_ROW_COUNT']);
     
    } else {
        show_error(__('Unknown report'));
    }
    
}

 function web_ReportMasterShowAddForm() {
     $form='
         <form action="" METHOD="POST">
         <input type="text" size="40" name="newreportname">  '.__('Report name').' <br>
         <input type="text" size="40" name="newsqlquery">  '.__('SQL Query').'<br>
         <input type="text" size="40" name="newdatakeys">  '.__('Data keys, separated by comma').'<br>
         <input type="text" size="40" name="newfieldnames">  '.__('Field names, separated by comma').'<br>
         '.web_RMTriggerSelector('newaddr').' '.__('Show full address by login key').'<br>
         '.web_RMTriggerSelector('newrnames').' '.__('Show Real Names by login key').'<br>
         '.web_RMTriggerSelector('newrowcount').' '.__('Show data query row count').'<br><br>
          <input type="submit" value="'.__('Create').'">
         </form>
         ';
     show_window(__('Create new report'),$form);
 }
 
 function web_ReportMasterShowEditForm($reportfile) {
     $reports_path=DATA_PATH."reports/";
     $report_template=rcms_parse_ini_file($reports_path.$reportfile);
     $form='
         <form action="" METHOD="POST">
         <input type="text" size="40" name="editreportname" value="'.$report_template['REPORT_NAME'].'">  '.__('Report name').' <br>
         <input type="text" size="40" name="editsqlquery" value="'.$report_template['REPORT_QUERY'].'">  '.__('SQL Query').'<br>
         <input type="text" size="40" name="editdatakeys" value="'.$report_template['REPORT_KEYS'].'">  '.__('Data keys, separated by comma').'<br>
         <input type="text" size="40" name="editfieldnames" value="'.$report_template['REPORT_FIELD_NAMES'].'">  '.__('Field names, separated by comma').'<br>
         '.web_RMTriggerSelector('editaddr',$report_template['REPORT_ADDR']).' '.__('Show full address by login key').'<br>
         '.web_RMTriggerSelector('editrnames',$report_template['REPORT_RNAMES']).' '.__('Show Real Names by login key').'<br>
         '.web_RMTriggerSelector('editrowcount',$report_template['REPORT_ROW_COUNT']).' '.__('Show data query row count').'<br><br>
          <input type="submit" value="'.__('Save').'">
         </form>
         ';
     show_window(__('Edit report'),$form);
 }

function zb_RMCreateReport($newreportname,$newsqlquery,$newdatakeys,$newfieldnames,$newaddr=0,$newrn=0,$newrowcount=0) {
    $reports_path=DATA_PATH."reports/";
    $newreportsavefile=time();
    $report_body='
        REPORT_NAME ='.$newreportname.'
        REPORT_QUERY='.$newsqlquery.'
        REPORT_KEYS='.$newdatakeys.'
        REPORT_FIELD_NAMES='.$newfieldnames.'
        REPORT_ADDR='.$newaddr.'
        REPORT_RNAMES='.$newrn.'
        REPORT_ROW_COUNT='.$newrowcount.'
        ';
    file_put_contents($reports_path.$newreportsavefile, $report_body);
    log_register("ReportMaster ADD ".$newreportsavefile);
}

function zb_RMEditReport($editreportsavefile,$editreportname,$editsqlquery,$editdatakeys,$editfieldnames,$editaddr=0,$editrn=0,$editrowcount=0) {
    $reports_path=DATA_PATH."reports/";
    $report_body='
        REPORT_NAME ='.$editreportname.'
        REPORT_QUERY='.$editsqlquery.'
        REPORT_KEYS='.$editdatakeys.'
        REPORT_FIELD_NAMES='.$editfieldnames.'
        REPORT_ADDR='.$editaddr.'
        REPORT_RNAMES='.$editrn.'
        REPORT_ROW_COUNT='.$editrowcount.'
        ';
    file_put_contents($reports_path.$editreportsavefile, $report_body);
    log_register("ReportMaster CHANGE ".$editreportsavefile);
}


function zb_RMExportUserbaseCsv() {
    $allusers=  zb_UserGetAllStargazerData();
    $allrealnames=  zb_UserGetAllRealnames();
    $alladdress=  zb_AddressGetFulladdresslist();
    $allcontracts=  zb_UserGetAllContracts();
    $allmac=array();
    $mac_q="SELECT * from `nethosts`";
            $allnh=  simple_queryall($mac_q);
            
            if (!empty($allnh)) {
                foreach ($allnh as $nh=>$eachnh) {
                    $allmac[$eachnh['ip']]=$eachnh['mac'];
                }
            }
            
    $result='';
    //options
    $delimiter=";";
    $in_charset='utf-8';
    $out_charset='windows-1251';
    /////////////////////
    if (!empty($allusers)) {
        $result.=__('Login').$delimiter.__('Password').$delimiter.__('IP').$delimiter.__('MAC').$delimiter.__('Tariff').$delimiter.__('Cash').$delimiter.__('Credit').$delimiter.__('Credit expire').$delimiter.__('Address').$delimiter.__('Real Name').$delimiter.__('Contract').$delimiter.__('AlwaysOnline').$delimiter.__('Disabled').$delimiter.__('User passive')."\n";
        foreach ($allusers as $io=>$eachuser) {
            //credit expirity
            if ($eachuser['CreditExpire']!=0) {
                $creditexpire=date("Y-m-d",$eachuser['CreditExpire']);
            } else {
                $creditexpire='';
            }
            //user mac
            if (isset($allmac[$eachuser['IP']])) {
                $usermac=$allmac[$eachuser['IP']];
            } else {
                $usermac='';
            }
            
            $result.=$eachuser['login'].$delimiter.$eachuser['Password'].$delimiter.$eachuser['IP'].$delimiter.$usermac.$delimiter.$eachuser['Tariff'].$delimiter.$eachuser['Cash'].$delimiter.$eachuser['Credit'].$delimiter.$creditexpire.$delimiter.@$alladdress[$eachuser['login']].$delimiter.@$allrealnames[$eachuser['login']].$delimiter.@$allcontracts[$eachuser['login']].$delimiter.$eachuser['AlwaysOnline'].$delimiter.$eachuser['Down'].$delimiter.$eachuser['Passive']."\n";
        }
    if ($in_charset!=$out_charset) {
        $result=  iconv($in_charset, $out_charset, $result);
    }
    // push data for excel handler
    header('Content-type: application/ms-excel');
    header('Content-Disposition: attachment; filename=userbase.csv');
    echo $result;
    die();
    }

}

// show reports list
if (cfr('REPORTMASTERADM')) {
    $export_link= wf_Link('?module=reportmaster&exportuserbase=excel',wf_img("skins/excel.gif",__('Export userbase')),false);
} else {
    $export_link='';
}

$newreport_link=  wf_Link('?module=reportmaster&add=true', web_add_icon(), false);
$action_links=' '.$export_link.' '.$newreport_link;
show_window(__('Available reports').$action_links, web_ReportMasterShowReportsList());

//userbase exporting
if (wf_CheckGet(array('exportuserbase'))) {
   zb_RMExportUserbaseCsv();
}


//create new report
if ((isset($_POST['newreportname'])) AND (isset($_POST['newsqlquery'])) AND (isset($_POST['newdatakeys'])) AND (isset($_POST['newfieldnames']))) {
 if (cfr('REPORTMASTERADM')) {
    zb_RMCreateReport($_POST['newreportname'], $_POST['newsqlquery'], $_POST['newdatakeys'], $_POST['newfieldnames'],$_POST['newaddr'],$_POST['newrnames'],$_POST['newrowcount']);
    rcms_redirect("?module=reportmaster");
    } else {
       show_error(__('You cant control this module')); 
    }
}

//delete existing report
if (isset($_GET['delete'])) {
    if (cfr('REPORTMASTERADM')) {
    zb_RMDeleteReport($_GET['delete']);
    rcms_redirect("?module=reportmaster");
     } else {
       show_error(__('You cant control this module')); 
    }
    
}

//if adding new report
if (isset($_GET['add'])) {
    if (cfr('REPORTMASTERADM')) {
    web_ReportMasterShowAddForm();
    } else {
       show_error(__('You cant control this module')); 
    }

}

//and if editing
if (isset($_GET['edit'])) {
    if (cfr('REPORTMASTERADM')) {
    web_ReportMasterShowEditForm($_GET['edit']);
     if ((isset($_POST['editreportname'])) AND (isset($_POST['editsqlquery'])) AND (isset($_POST['editdatakeys'])) AND (isset($_POST['editfieldnames']))) {
        zb_RMEditReport($_GET['edit'],$_POST['editreportname'], $_POST['editsqlquery'], $_POST['editdatakeys'], $_POST['editfieldnames'],$_POST['editaddr'],$_POST['editrnames'],$_POST['editrowcount']);
        rcms_redirect("?module=reportmaster");
    }
    } else {
       show_error(__('You cant control this module')); 
    }
}


// view reports
if (isset($_GET['view'])) {
    if (!isset($_GET['printable'])) {
    // natural view    
    web_ReportMasterViewReport($_GET['view']);
    } else {
    //or printable
    web_ReportMasterViewReportPrintable($_GET['view']);
        
    }
}
 

	
} else {
      show_error(__('You cant control this module'));
}

?>
