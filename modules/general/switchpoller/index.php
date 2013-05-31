<?php
set_time_limit (0);

if(cfr('SWITCHPOLL')) {
    
    $allDevices=  sp_SnmpGetAllDevices();
    $allTemplates= sp_SnmpGetAllModelTemplates();
    $allTemplatesAssoc=  sp_SnmpGetModelTemplatesAssoc();
    $allusermacs=zb_UserGetAllMACs();
    $alladdress= zb_AddressGetFullCityaddresslist();
    
    //poll single device
    if (wf_CheckGet(array('switchid'))) {
        $switchId=vf($_GET['switchid'],3);
        if (!empty($allDevices)) {
            foreach ($allDevices as $ia=>$eachDevice) {
                if ($eachDevice['id']==$switchId){
                    //detecting device template
                    if (!empty($allTemplatesAssoc)) {
                        if (isset($allTemplatesAssoc[$eachDevice['modelid']])) {
                            $deviceTemplate=$allTemplatesAssoc[$eachDevice['modelid']];
                            show_window($eachDevice['ip'].' - '.$eachDevice['location'],  wf_Link('?module=switches', __('Back'), true, 'ubButton'));
                            sp_SnmpPollDevice($eachDevice['ip'], $eachDevice['snmp'], $allTemplates, $deviceTemplate,$allusermacs,$alladdress,false);
                        } else {
                            show_error(__('No').' '.__('SNMP template'));
                        }
                    }
                    
                }
            }
        }
        
    } else {
    //batch device polling
    if (!empty($allDevices)) {
        foreach ($allDevices as $io=>$eachDevice) {
             if (!empty($allTemplatesAssoc)) {
                        if (isset($allTemplatesAssoc[$eachDevice['modelid']])) {
                            $deviceTemplate=$allTemplatesAssoc[$eachDevice['modelid']];
                            sp_SnmpPollDevice($eachDevice['ip'], $eachDevice['snmp'], $allTemplates,$deviceTemplate,$allusermacs,$alladdress,true);
                        } 
                    }
            
        }
    }
    }
    
    
} else {
    show_error(__('Access denied'));
}

?>
