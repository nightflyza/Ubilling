<?php

if (cfr('SWITCHES')) {

    /**
     * Returns some switch psycho-pass
     * 
     * @param int $count
     * 
     * @return string
     */
    function zb_SwitchesPsychoPass($count) {
        $result = '';

        if ($count <= 3) {
            $color = '#00b12e';
        }
        if (($count > 3)) {
            $color = '#c3cf00';
        }
        if (($count > 5)) {
            $color = '#e56600';
        }

        if (($count > 7)) {
            $color = '#e53000';
        }

        if ($count == 0) {
            $color = '';
        }

        $result = wf_tag('font', false, '', 'color="' . $color . '"') . $count . wf_tag('font', true);
        return ($result);
    }

    if (ubRouting::checkGet('ajax')) {
        $alllinks = array();
        $allSwitches = array();
        $switchModels = zb_SwitchModelsGetAllTag();
        $tmpSwitches = zb_SwitchesGetAll();
        $psychoPower = array();
        $json = new wf_JqDtHelper();


        if (!empty($tmpSwitches)) {
            //transform array to id=>switchdata
            foreach ($tmpSwitches as $io => $each) {
                $allSwitches[$each['id']] = $each;
            }

            foreach ($tmpSwitches as $io => $each) {
                $alllinks[$each['id']] = $each['parentid'];
            }
        }



        if (!empty($allSwitches)) {
            foreach ($allSwitches as $ia => $each) {
                if (!empty($each['parentid'])) {
                    if (isset($allSwitches[$each['parentid']])) {
                        $traceId = $each['id'];
                        if (!empty($traceId)) {
                            $psychoPower[$traceId] = sizeof(zb_SwitchGetParents($alllinks, $traceId)) - 2; // minus self and last
                        }
                    }
                } else {
                    $psychoPower[$each['id']] = 0;
                }
            }
        }

        if (!empty($psychoPower)) {
            foreach ($psychoPower as $io => $parentCount) {
                $switchData = $allSwitches[$io];

                $data[] = $switchData['id'];
                $data[] = $switchData['ip'];
                $data[] = $switchData['location'];
                $data[] = @$switchModels[$switchData['modelid']];
                $data[] = $switchData['desc'];
                $data[] = zb_SwitchesPsychoPass($parentCount);
                $actLinks = wf_Link('?module=switches&edit=' . $switchData['id'], web_edit_icon());
                $data[] = $actLinks;
                $json->addRow($data);
                unset($data);
            }
            $json->getJson();
        }
    }



    $columns = array('ID', 'IP', 'Location', 'Model', 'Description', 'Psycho-Pass', 'Actions');
    $opts = '"order": [[ 5, "desc" ]]';

    $backControl = wf_BackLink('?module=switches') . wf_delimiter();
    show_window(__('Switches') . ': ' . __('Psycho-Pass'), $backControl . wf_JqDtLoader($columns, '?module=saikopasu&ajax=true', false, 'Switches', 100, $opts));
} else {
    show_error(__('Access denied'));
}
