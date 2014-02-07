<?php
set_time_limit (0);

if(cfr('SWITCHPOLL')) {
    function web_FDBTableShowDataTable() {
      
      $jq_dt='
          <script type="text/javascript" charset="utf-8">
                
		$(document).ready(function() {
		$(\'#fdbcachehp\').dataTable( {
 	       "oLanguage": {
			"sLengthMenu": "'.__('Show').' _MENU_",
			"sZeroRecords": "'.__('Nothing found').'",
			"sInfo": "'.__('Showing').' _START_ '.__('to').' _END_ '.__('of').' _TOTAL_ '.__('entries').'",
			"sInfoEmpty": "'.__('Showing').' 0 '.__('to').' 0 '.__('of').' 0 '.__('entries').'",
			"sInfoFiltered": "('.__('Filtered').' '.__('from').' _MAX_ '.__('Total').')",
                        "sSearch":       "'.__('Search').'",
                        "sProcessing":   "'.__('Processing').'..."
		},
           
                "aoColumns": [
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
        "sAjaxSource": \'?module=switchpoller&ajax=true\',
	"bDeferRender": true,
        "bJQueryUI": true

                } );
		} );
		</script>

          ';
      
      $result=$jq_dt.'
          <table width="100%" class="sortable" id="fdbcachehp">
                <tr class="row1">
                  <td>'.__('Switch IP').'</td>
                  <td>'.__('Port').'</td>
                  <td>'.__('Location').'</td>
                  <td>'.__('MAC').'</td>
                  <td>'.__('User').'</td>
                </tr>
            </table>
          ';
     show_window(__('Current FDB cache'),$result);
  }
    
    $allDevices=  sp_SnmpGetAllDevices();
    $allTemplates= sp_SnmpGetAllModelTemplates();
    $allTemplatesAssoc=  sp_SnmpGetModelTemplatesAssoc();
    $allusermacs=zb_UserGetAllMACs();
    $alladdress= zb_AddressGetFullCityaddresslist();
    $alldeadswitches=  zb_SwitchesGetAllDead();
    $deathTime=  zb_SwitchesGetAllDeathTime();
    
    //poll single device
    if (wf_CheckGet(array('switchid'))) {
        $switchId=vf($_GET['switchid'],3);
        if (!empty($allDevices)) {
            foreach ($allDevices as $ia=>$eachDevice) {
                if ($eachDevice['id']==$switchId){
                    //detecting device template
                    if (!empty($allTemplatesAssoc)) {
                        if (isset($allTemplatesAssoc[$eachDevice['modelid']])) {
                            if (!isset($alldeadswitches[$eachDevice['ip']])) {
                              //cache cleanup
                                if (wf_CheckGet(array('forcecache'))) {
                                    $deviceRawSnmpCache=  rcms_scandir('./exports/', $eachDevice['ip'].'_*');
                                    if (!empty($deviceRawSnmpCache)) {
                                        foreach ($deviceRawSnmpCache as $ir=>$fileToDelete) {
                                            unlink('./exports/'.$fileToDelete);
                                        }
                                    }
                                    rcms_redirect('?module=switchpoller&switchid='.$eachDevice['id']);
                                }
                            $deviceTemplate=$allTemplatesAssoc[$eachDevice['modelid']];
                            $modActions=  wf_Link('?module=switches', __('Back'), false, 'ubButton');
                            $modActions.= wf_Link('?module=switchpoller&switchid='.$eachDevice['id'].'&forcecache=true', __('Force query'), false, 'ubButton');
                            show_window($eachDevice['ip'].' - '.$eachDevice['location'],  $modActions);
                            sp_SnmpPollDevice($eachDevice['ip'], $eachDevice['snmp'], $allTemplates, $deviceTemplate,$allusermacs,$alladdress,false);
                            } else {
                               show_window(__('Error'),__('Switch dead since').' '.@$deathTime[$eachDevice['ip']].  wf_delimiter().  wf_Link('?module=switches', __('Back'), false, 'ubButton'));
                            }
                        } else {
                            show_error(__('No').' '.__('SNMP template'));
                        }
                    }
                    
                }
            }
        }
        
    } else {
     
        
    //display all of available fdb tables
      $fdbData_raw=  rcms_scandir('./exports/', '*_fdb');
      if (!empty($fdbData_raw)) {
         //push ajax data
         if (wf_CheckGet(array('ajax'))) {
           die(sn_SnmpParseFdbCacheJson($fdbData_raw));
         } else {
             web_FDBTableShowDataTable();
         }
       
         
      } else {
          show_window(__('Error'), __('Nothing found'));
      }

    }
    
    
} else {
    show_error(__('Access denied'));
}

?>
