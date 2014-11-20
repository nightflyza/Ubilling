<?php
set_time_limit (0);

if(cfr('SWITCHPOLL')) {
    
    /**
     * Returns FDB cache lister MAC filters setup form
     * 
     * @return string
     */
    function web_FDBTableFiltersForm() {
        $currentFilters='';
        $oldFilters=  zb_StorageGet('FDBCACHEMACFILTERS');
        if (!empty($oldFilters)) {
            $currentFilters=  base64_decode($oldFilters);
        }
        
        $inputs=__('One MAC address per line').  wf_tag('br');
        $inputs.=  wf_TextArea('newmacfilters', '', $currentFilters, true, '40x10');
        $inputs.= wf_HiddenInput('setmacfilters', 'true');
        $inputs.= wf_CheckInput('deletemacfilters', __('Cleanup'), true, false);
        $inputs.= wf_Submit(__('Save'));
        $result=  wf_Form('', 'POST', $inputs, 'glamour');
        
        return ($result);    
    }
    
    
    function web_FDBTableShowDataTable() {
      
      $jq_dt='
          <script type="text/javascript" charset="utf-8">
                
		$(document).ready(function() {
		$(\'#fdbcachehp\').dataTable( {
 	          "oLanguage": {
			"sLengthMenu": "' . __('Show') . ' _MENU_",
			"sZeroRecords": "' . __('Nothing found') . '",
			"sInfo": "' . __('Showing') . ' _START_ ' . __('to') . ' _END_ ' . __('of') . ' _TOTAL_ ' . __('users') . '",
			"sInfoEmpty": "' . __('Showing') . ' 0 ' . __('to') . ' 0 ' . __('of') . ' 0 ' . __('users') . '",
			"sInfoFiltered": "(' . __('Filtered') . ' ' . __('from') . ' _MAX_ ' . __('Total') . ')",
                        "sSearch":       "' . __('Search') . '",
                        "sProcessing":   "' . __('Processing') . '...",
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
                null
            ],      
         
        "bPaginate": true,
        "bLengthChange": true,
        "bFilter": true,
        "bSort": true,
        "bInfo": true,
        "bAutoWidth": false,
        "bProcessing": true,
        "bStateSave": true,
        "iDisplayLength": 50,
        "sAjaxSource": \'?module=switchpoller&ajax=true\',
	"bDeferRender": true,
        "bJQueryUI": true

                } );
		} );
		</script>

          ';

      $result=$jq_dt;
      $result.= wf_tag('table', false, 'display compact', 'id="fdbcachehp"');
      $result.= wf_tag('thead',false);
      $cells=  wf_TableCell(__('Switch IP'));
      $cells.= wf_TableCell(__('Port'));
      $cells.= wf_TableCell(__('Location'));
      $cells.= wf_TableCell(__('MAC'));
      $cells.= wf_TableCell(__('User'));
      $result.= wf_TableRow($cells);
      $result.= wf_tag('thead',true);
      $result.= wf_tag('table', true);
      $filtersForm=  wf_modalAuto(web_icon_search('MAC filters setup'), __('MAC filters setup'), web_FDBTableFiltersForm(), '');
      
     show_window(__('Current FDB cache').' '.$filtersForm,$result);
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
   
          //// mac filters setup
             if (wf_CheckPost(array('setmacfilters'))) {
              //setting new MAC filters
              if (!empty($_POST['newmacfilters'])) {
              $newFilters=  base64_encode($_POST['newmacfilters']);
              zb_StorageSet('FDBCACHEMACFILTERS', $newFilters);
              }
              //deleting old filters
              if (isset($_POST['deletemacfilters'])) {
                  zb_StorageDelete('FDBCACHEMACFILTERS');
              }
          }
          
          
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
