<?php

if (cfr('SWITCHESEDIT')) {


    function web_SwitchesIntegrityReport() {
        $result = '';
        $result.= wf_BackLink('?module=switches');
        $messages = new UbillingMessageHelper();
        $allParents = array();
        $allIds = array();
        $query = "SELECT * from `switches`";
        $all = simple_queryall($query);

        if (!empty($all)) {
            //filling parent ids array
            foreach ($all as $io => $each) {
                if (!empty($each['parentid'])) {
                    $allParents[$each['parentid']] = $each['id'];
                }
            }

            //filling registered ids array
            foreach ($all as $io => $each) {
                $allIds[$each['id']] = $each['ip'];
            }

            $result.=$messages->getStyledMessage(__('Total switches in database') . ': ' . sizeof($all), 'info');
            $result.=$messages->getStyledMessage(__('Parent switches found') . ': ' . sizeof($allParents), 'info');
            
            //checking uplinks geo availability
            foreach ($all as $io => $each) {
                if (isset($allParents[$each['id']])) {
                    if (empty($each['geo'])) {
                        $result.=$messages->getStyledMessage(__('Geo location') . ' ' . __('is empty') . ': [ ' . wf_Link('?module=switches&edit=' . $each['id'], $each['id']) . '] ' . $each['ip'] . ' - ' . $each['location'], 'error');
                    }
                }
            }


            //checking uplinks switches availability
            foreach ($all as $io => $each) {
                if (!empty($each['parentid'])) {
                    if (!isset($allIds[$each['parentid']])) {
                        $result.=$messages->getStyledMessage(__('Uplink switch is deleted from database').': [ ' . wf_Link('?module=switches&edit=' . $each['id'], $each['id']) . '] ' . ' - ' . $each['ip'] . ' ' . $each['location'] .', '.__('uplink deleted'). ' : [ ' . $each['parentid'].' ]','error');
                    }
                }
            }
        }


        return ($result);
    }

    show_window(__('Switches integrity check'), web_SwitchesIntegrityReport());
} else {
    show_error(__('Access denied'));
}
?>