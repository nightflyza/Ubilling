<?php
if(cfr('DOCSIS')) {
 $altercfg=rcms_parse_ini_file(CONFIG_PATH.'/alter.ini');
 
 
if ($altercfg['DOCSIS_SUPPORT']) {

    function docsis_AjaxModemDataSource() {
        $query="SELECT * from `modems`";
        $alladdress=  zb_AddressGetFulladdresslist();
        $alluserips= zb_UserGetAllIPs();
        $alluserips=  array_flip($alluserips);
        
        $allmodems=  simple_queryall($query);
        $i=1;
        $totalcount=sizeof($allmodems);
        $result='{ 
                  "aaData": [';
        
        if (!empty($allmodems)) {
            foreach ($allmodems as $io=>$each) {
                $ending=($i!=$totalcount) ? ',' : '' ;
                
                if (isset($alluserips[$each['userbind']])) {
                    @$useraddress=$alladdress[$alluserips[$each['userbind']]];
                }  else {
                    $useraddress='';
                }
                
                $actions='<a href=?module=docsis&showmodem='.$each['id'].'><img src=skins/icon_edit.gif></a>';
                $result.='
                    [
                    "'.$each['id'].'",
                    "'.$each['maclan'].'",
                    "'.$each['date'].'",
                    "'.$each['ip'].'",
                    "'.$each['userbind'].'",
                    "'.$useraddress.'",
                    "'.$actions.'"
                    ]'.$ending.'
                    ';
                $i++;
            }
        }
        $result.='] 
        }';
        die($result);
    }
    
    
     function docsis_ModemsList() {
      
      $jq_dt='
          <script type="text/javascript" charset="utf-8">
                
		$(document).ready(function() {
		$(\'#docsismodemshp\').dataTable( {
 	       "oLanguage": {
			"sLengthMenu": "'.__('Show').' _MENU_",
			"sZeroRecords": "'.__('Nothing found').'",
			"sInfo": "'.__('Showing').' _START_ '.__('to').' _END_ '.__('of').' _TOTAL_ '.__('modems').'",
			"sInfoEmpty": "'.__('Showing').' 0 '.__('to').' 0 '.__('of').' 0 '.__('modems').'",
			"sInfoFiltered": "('.__('Filtered').' '.__('from').' _MAX_ '.__('Total').')",
                        "sSearch":       "'.__('Search').'",
                        "sProcessing":   "'.__('Processing').'...",
                        "oPaginate": {
                        "sFirst": "'.__('First').'",
                        "sPrevious": "'.__('Previous').'",
                        "sNext": "'.__('Next').'",
                        "sLast": "'.__('Last').'"
                    },
		},
           
                "aoColumns": [
                null,
                null,
                null,
                null,
                null,
                null,
                null
            ],      
         
        "bPaginate": true,
        "bLengthChange": true,
        "bFilter": true,
        "bSort": true,
        "bInfo": true,
        "bAutoWidth": false,
        "bProcessing": true,
        "bStateSave": false,
        "iDisplayLength": 50,
        "sAjaxSource": \'?module=docsis&ajax=true\',
	"bDeferRender": true,
        "bJQueryUI": true

                } );
		} );
		</script>

          ';
      
      $result=$jq_dt.'
          <table width="100%"  id="docsismodemshp" class="display compact">
          <thead>
                <tr class="row1">
                  <td>'.__('ID').'</td>
                  <td>'.__('MAC Lan').'</td>
                  <td>'.__('Date').'</td>
                  <td>'.__('IP').'</td>
                  <td>'.__('Linked user').'</td>
                  <td>'.__('Full address').'</td>
                  <td>'.__('Actions').'</td>
                </tr>
           </thead>     
            </table>
          ';
     show_window(__('Available DOCSIS modems'),$result);
  }
  
  function docsis_ModemAdd($maclan) {
       $maclan=  mysql_real_escape_string($maclan);
       //random mac for usb
       $macusb=  '14:'.'88'.':'.rand(10,99).':'.rand(10,99).':'.rand(10,99).':'.rand(10,99);
       $altercfg=rcms_parse_ini_file(CONFIG_PATH.'/alter.ini');
       $netid=$altercfg['DOCSIS_MODEM_NETID'];
       $nextfreeip=  multinet_get_next_freeip('nethosts', 'ip', $netid);
       $note='';
       $userbind='';
       $basetemplate='short';
       $date=curdate();
       //check for free ip in subnet
       if (!empty($nextfreeip)) {
       $nic=  str_replace('.', 'x', $nextfreeip);
       //check is mac unique?
       if ((multinet_mac_free($maclan)) AND (check_mac_format($maclan)))  {
        $query="INSERT INTO `modems` (
                `id` ,
                `maclan` ,
                `macusb` ,
                `date` ,
                `ip` ,
                `conftemplate` ,
                `userbind` ,
                `nic` ,
                `note`
                )
                VALUES (
                NULL , '".$maclan."', '".$maclan."', '".$date."', '".$nextfreeip."', '".$basetemplate."', '' , '".$nic."', ''
                );";
        nr_query($query);
        $lastid=  simple_get_lastid('modems');
        log_register("DOCSIS MODEM ADD MAC".$maclan." IP ".$nextfreeip."[".$lastid."]");
        multinet_add_host($netid, $nextfreeip, $maclan, '');
        multinet_rebuild_all_handlers();
        rcms_redirect("?module=docsis&showmodem=".$lastid);
       } else {
           show_window(__('Error'), __('This MAC is currently used').' '.__('This MAC have wrong format'));
       }
           
       } else {
           show_window(__('Error'),__('No free IP available in selected pool'));
       }
  }
  
  function docsis_ModemGetData($modemid) {
      $modemid=vf($modemid,3);
      $query="SELECT * from `modems` WHERE `id`='".$modemid."'";
      $result=  simple_query($query);
      return ($result);
  }
  
  function docsis_ModemDelete($modemid) {
      $modemid=vf($modemid,3);
      $modemdata= docsis_ModemGetData($modemid);
      if (!empty($modemdata)) {
        $modemip=$modemdata['ip'];
        $query="DELETE from `modems` WHERE `id`='".$modemid."'";
        nr_query($query);
        log_register("DOCSIS MODEM DELETE IP ".$modemip." [".$modemid."]");
        multinet_delete_host($modemip);
        multinet_rebuild_all_handlers();
      }
      
  }
  
  function docsis_ModemAddForm() {
      $inputs=   wf_TextInput('newmaclan', __('MAC Lan'), '', true, '20');
      $inputs.=  wf_Submit(__('Create new modem'));
      $result=   wf_Form("", 'POST', $inputs, 'glamour');
      return ($result);
  }
  
  function docsis_ModemSnmpGet() {
     $community=  zb_StorageGet('DOCSIS_MODEM_COMMUNITY');
     //if first run
     if (empty($community)) {
         $community='public';
         zb_StorageSet('DOCSIS_MODEM_COMMUNITY', $community);
         log_register("DOCSIS MODEM SNMP SET `".$community."`");
     }
     return ($community);
  }
  
  function docsis_ModemSnmpSet($community) {
      zb_StorageSet('DOCSIS_MODEM_COMMUNITY', $community);
      log_register("DOCSIS MODEM SNMP SET `".$community."`");
  }
  
  function docsis_ModemSnmpWalkGet() {
     $path=  zb_StorageGet('DOCSIS_SNMPWALK_PATH');
     //if first run
     if (empty($path)) {
         $path='/usr/local/bin/snmpwalk';
         zb_StorageSet('DOCSIS_SNMPWALK_PATH', $path);
         log_register("DOCSIS SNMPWALK SET `".$path."`");
     }
     return ($path);
  }
  
  function docsis_ModemSnmpWalkSet($path) {
      zb_StorageSet('DOCSIS_SNMPWALK_PATH', $path);
      log_register("DOCSIS SNMPWALK SET `".$path."`");
  }
      
  
  function docsis_ModemSnmpForm() {
      $community=  docsis_ModemSnmpGet();
      $snmpwalkpath= docsis_ModemSnmpWalkGet();
      $inputs=  wf_TextInput('newmodemcommunity', 'Modems SNMP community', $community, true, '20');
      $inputs.= wf_TextInput('newsnmpwalkpath', 'snmpwalk Path', $snmpwalkpath, true, '20');
      $inputs.= wf_Submit('Save');
      $result=  wf_Form("", 'POST', $inputs, 'glamour');
      return ($result);
  }

//backported from old releases "as is"
function docsis_ModemDiagShow($modemid) {
    $modemid=vf($modemid,3);      
    
    $modemdata=  docsis_ModemGetData($modemid);
    $ip=$modemdata['ip'];
    $community=  docsis_ModemSnmpGet();

    //modem status
    // if == 12 operational
    $statusc="/usr/local/bin/snmpwalk -r 0 -t 1 -v2c -c ".$community." ".$ip." 1.3.6.1.2.1.10.127.1.2.2.1.1.2 | /usr/bin/awk '{print $4}'";
    $status=shell_exec($statusc);
    if ($status==12) {
      $status=  wf_tag('font', false, '', 'color="#009933"').__('Operational').wf_tag('font', true);
    } else {
      $status=  wf_tag('font', false, '', 'color="#CC0000"').__('Offline').wf_tag('font', true);
    }


    //modem uptime
    $uptimec="/usr/local/bin/snmpwalk -r 0 -t 1 -v2c -c ".$community." ".$ip." 1.3.6.1.2.1.1.3.0 | /usr/bin/awk '{print $5 $6 $7}'";
    $uptime=shell_exec($uptimec);

    //modem down freq
    $dsfreqc="/usr/local/bin/snmpwalk -r 0 -t 1 -v2c -c ".$community." ".$ip." 1.3.6.1.2.1.10.127.1.1.1.1.2.3 | /usr/bin/awk '{print $4}'";
    $dsfreq=shell_exec($dsfreqc);
    $dsfreq=$dsfreq/1000000;


    //upstream freq
    $usfreqc="/usr/local/bin/snmpwalk -r 0 -t 1 -v2c -c ".$community." ".$ip." 1.3.6.1.2.1.10.127.1.1.2.1.2.4 | /usr/bin/awk '{print $4}'";
    $usfreq=shell_exec($usfreqc);
    $usfreq=$usfreq/1000000;



    //downstream SNR
    // real_snr=snr/10 , must be <35
    $dssnrc="/usr/local/bin/snmpwalk -r 0 -t 1 -v2c -c ".$community." ".$ip." 1.3.6.1.2.1.10.127.1.1.4.1.5.3 | /usr/bin/awk '{print $4}'";
    $dssnr=shell_exec($dssnrc);
    $dssnr=$dssnr/10;
    if ($dssnr<45) {
    $dssnr=wf_tag('font', false, '', 'color="#009933"').$dssnr.wf_tag('font', true);
    } else {
    $dssnr=wf_tag('font', false, '', 'color="#CC0000"').$dssnr.wf_tag('font', true);
    }


    //modem DS power
    // real_respower=respower/10
    //Need level > -13 and < 17 dBmV!
    $dspowerc="/usr/local/bin/snmpwalk -r 0 -t 1 -v2c -c ".$community." ".$ip." 1.3.6.1.2.1.10.127.1.1.1.1.6 | /usr/bin/awk '{print $4}'";
    $dspower=shell_exec($dspowerc);
    $dspower=$dspower/10;
    if (($dspower<17) AND ($dspower>-13)) {
    $dspower=wf_tag('font', false, '', 'color="#009933"').$dspower.wf_tag('font', true);
    } else {
    $dspower=wf_tag('font', false, '', 'color="#CC0000"').$dspower.wf_tag('font', true);
    }

    //modem US power
    //real_power=power/10
    //Need level < 51 dBmV!
    $uspowerc="/usr/local/bin/snmpwalk -r 0 -t 1 -v2c -c ".$community." ".$ip." 1.3.6.1.2.1.10.127.1.2.2.1.3.2 | /usr/bin/awk '{print $4}'";
    $uspower=shell_exec($uspowerc);
    $uspower=$uspower/10;
    if (($uspower<51)  AND ($uspower>36)) {
    $uspower=wf_tag('font', false, '', 'color="#009933"').$uspower.wf_tag('font', true);
    } else {
    $uspower=wf_tag('font', false, '', 'color="#CC0000"').$uspower.wf_tag('font', true);
    }


    // ds modul
    //if == 4  QAM256
    //if == 3 QAM64
    $dsmodulc="/usr/local/bin/snmpwalk -r 0 -t 1 -v2c -c ".$community." ".$ip." 1.3.6.1.2.1.10.127.1.1.1.1.4.3 | /usr/bin/awk '{print $4}'";
    $dsmodul=shell_exec($dsmodulc);
    if ($dsmodul==4) {
    $dsmodul='QAM256';
    }
    if ($dsmodul==3) {
    $dsmodul='QAM64';
    }


    //ds annex
    // 3 == 
    // 4 == Annex B
    // 5 == Annex C
    $dsannexc="/usr/local/bin/snmpwalk -r 0 -t 1 -v2c -c ".$community." ".$ip." 1.3.6.1.2.1.10.127.1.1.1.1.7.3 | /usr/bin/awk '{print $4}'";
    $dsannex=shell_exec($dsannexc);
    if ($dsannex==3) {
    $dsannex='Annex A';
    }
    if ($dsannex==4) {
    $dsannex='Annex B';
    }
    if ($dsannex==5) {
    $dsannex='Annex C';
    }


    //=======================
    //ct in
    $rfinc="/usr/local/bin/snmpwalk -Os -v2c -c ".$community." ".$ip." ifHCInOctets.2 | /usr/bin/awk '{print $4}'";
    $rfin=shell_exec($rfinc);

    //ct out
    $rfout="/usr/local/bin/snmpwalk -Os -v2c -c ".$community." ".$ip." ifHCOutOctets.2 | /usr/bin/awk '{print $4}'";
    $rfout=shell_exec($rfout);
    
    $cells=  wf_TableCell(__('IP'));
    $cells.= wf_TableCell($ip);
    $rows=  wf_TableRow($cells, 'row3');
    
    $cells=  wf_TableCell(__('Modem status'));
    $cells.= wf_TableCell($status);
    $rows.=  wf_TableRow($cells, 'row3');
    
    $cells=  wf_TableCell(__('Modem uptime'));
    $cells.= wf_TableCell($uptime);
    $rows.=  wf_TableRow($cells, 'row3');
    
    $cells=  wf_TableCell(__('DS Freq'));
    $cells.= wf_TableCell($dsfreq);
    $rows.=  wf_TableRow($cells, 'row3');
    
    $cells=  wf_TableCell(__('US Freq'));
    $cells.= wf_TableCell($usfreq);
    $rows.=  wf_TableRow($cells, 'row3');
    
    $cells=  wf_TableCell(__('DS SNR'));
    $cells.= wf_TableCell($dssnr);
    $rows.=  wf_TableRow($cells, 'row3');
    
    $cells=  wf_TableCell(__('DS power'));
    $cells.= wf_TableCell($dspower);
    $rows.=  wf_TableRow($cells, 'row3');
    
    $cells=  wf_TableCell(__('US power'));
    $cells.= wf_TableCell($uspower);
    $rows.=  wf_TableRow($cells, 'row3');
    
    $cells=  wf_TableCell(__('DS Modulation'));
    $cells.= wf_TableCell($dsmodul);
    $rows.=  wf_TableRow($cells, 'row3');
    
    $cells=  wf_TableCell(__('DS Annex'));
    $cells.= wf_TableCell($dsannex);
    $rows.=  wf_TableRow($cells, 'row3');
    
    $cells=  wf_TableCell(__('RF In'));
    $cells.= wf_TableCell($rfin);
    $rows.=  wf_TableRow($cells, 'row3');
    
    $cells=  wf_TableCell(__('RF Out'));
    $cells.= wf_TableCell($rfout);
    $rows.=  wf_TableRow($cells, 'row3');

    
    $result=  wf_TableBody($rows, '100%', '0');
    die ($result);
  }
  
  function docsis_ControlsShow() {
      $controls=  wf_modal(__('Add new modem'), __('Add new modem'), docsis_ModemAddForm(), 'ubButton', '300', '150');
      $controls.= wf_modal(__('Monitoring options'), __('SNMP configuration'), docsis_ModemSnmpForm(), 'ubButton', '450', '200');
      show_window('', $controls);
  }

  function docsis_ModemProfileShow($modemid) {
      $modemid=vf($modemid,3);
      $data=  docsis_ModemGetData($modemid);
      $netdata=array();
      $netdata_q="SELECT * from `nethosts` where `ip`='".$data['ip']."'";
      $netdata=  simple_queryall($netdata_q);
      $netdata=  print_r($netdata,true);
      $netdata=  nl2br($netdata);
      $alluserips=  zb_UserGetAllIPs();
      $alluserips=  array_flip($alluserips);
      $result=  wf_Link("?module=docsis", __('Back'), false, 'ubButton');
      $ajaxcontainer= wf_AjaxLoader(). wf_AjaxLink("?module=docsis&ajaxsnmp=".$modemid, __('Renew modem data'),'ajaxdata', true, 'ubButton').wf_tag('div', false, '', 'id="ajaxdata"').wf_tag('div',true);
      $result.=wf_modal(__('Modem diagnostics'), __('Modem diagnostics'), $ajaxcontainer, 'ubButton', '500', '400');
      $result.=wf_modal(__('Networking data'), __('Networking data'), $netdata, 'ubButton', '500', '400');
      $result.=wf_delimiter();
      
      if (!empty($data)) {
          $cells=  wf_TableCell(__('ID'));
          $cells.= wf_TableCell($data['id'].' '.wf_JSAlert("?module=docsis&deletemodem=".$modemid, web_delete_icon(), __('Removing this may lead to irreparable results')));
          $rows=  wf_TableRow($cells, 'row3');
          
          $cells=  wf_TableCell(__('IP'));
          $cells.= wf_TableCell($data['ip']);
          $rows.=  wf_TableRow($cells, 'row3');
          
          $cells=  wf_TableCell(__('MAC Lan'));
          $cells.= wf_TableCell($data['maclan']);
          $rows.=  wf_TableRow($cells, 'row3');
          
          $cells=  wf_TableCell(__('Date'));
          $cells.= wf_TableCell($data['date']);
          $rows.=  wf_TableRow($cells, 'row3');
          
          if (isset($alluserips[$data['userbind']])) {
              $bindedLogin=$alluserips[$data['userbind']];
              $profileLink= ' '.wf_Link('?module=userprofile&username='.$bindedLogin, web_profile_icon().' '.$bindedLogin, false, '');
          } else {
              $profileLink='';
          }
          $cells=  wf_TableCell(__('Linked user'));
          $cells.= wf_TableCell($data['userbind'].$profileLink);
          $rows.=  wf_TableRow($cells, 'row3');
          
          $cells=  wf_TableCell(__('Notes'));
          $cells.= wf_TableCell($data['note']);
          $rows.=  wf_TableRow($cells, 'row3');
          
          $result.=  wf_TableBody($rows, '100%', '0', '');
                  
          
          $inputs=  wf_TextInput('edituserbind', __('Linked user'), $data['userbind'], true, '40');
          $inputs.= wf_TextInput('editnote', __('Notes'), $data['note'], true, '40');
          $inputs.=wf_Submit(__('Save'));
          $form=  wf_Form("", 'POST', $inputs, 'glamour');
          
          $result.=$form;
          
          show_window(__('Modem profile'), $result);
          
      } else {
          show_window(__('Error'), __('Strange exeption'));
      }
  }
  
  
// ajax calls handling
    if (wf_CheckGet(array('ajax'))) {
        docsis_AjaxModemDataSource();
    }
//ajax modem stats 
if (wf_CheckGet(array('ajaxsnmp'))) {
    docsis_ModemDiagShow($_GET['ajaxsnmp']);
}    
 //adding new modem
  if (wf_CheckPost(array('newmaclan'))) {
      docsis_ModemAdd($_POST['newmaclan']);
  }  
  
//deleting modem
  if (wf_CheckGet(array('deletemodem'))) {
      docsis_ModemDelete($_GET['deletemodem']);
      rcms_redirect("?module=docsis");
  }

//editing modem
  if ( (isset($_POST['edituserbind'])) AND (isset($_POST['editnote'])) ) {
      $editmodemid=vf($_GET['showmodem'],3);
      if (!empty($editmodemid)) {
      $newmodemuserbind=  mysql_real_escape_string($_POST['edituserbind']);
      $newmodemnote=      mysql_real_escape_string($_POST['editnote']);
      simple_update_field('modems', 'userbind', $newmodemuserbind, "WHERE `id`='".$editmodemid."'");
      simple_update_field('modems', 'note', $newmodemnote, "WHERE `id`='".$editmodemid."'");
      log_register("DOCSIS MODEM EDIT BIND `".$newmodemuserbind."` [".$editmodemid."]");
      rcms_redirect("?module=docsis&showmodem=".$editmodemid);
      
      } else {
          show_window(__('Error'), __('Strange exeption'));
      }
      
  }
 
 //setting modem snmp community
  if (wf_CheckPost(array('newmodemcommunity','newsnmpwalkpath'))) {
      docsis_ModemSnmpSet($_POST['newmodemcommunity']);
      docsis_ModemSnmpWalkSet($_POST['newsnmpwalkpath']);
      rcms_redirect("?module=docsis");
  }
    
//show controls
        docsis_ControlsShow();
//show modems list by default
   if (!wf_CheckGet(array('showmodem'))) {
     docsis_ModemsList();   
   } else {
       docsis_ModemProfileShow($_GET['showmodem']); 
   }    
    
   zb_BillingStats(true);
} else {
    show_window(__('Error'),__('DOCSIS support is not enabled'));
}
 
}
else {
	show_error(__('Access denied'));
}

?>