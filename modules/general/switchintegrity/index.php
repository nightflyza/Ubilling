<?php

if (cfr('SWITCHESEDIT')) {



    /**
     * Renders switches integrity report
     * 
     * @return string
     */
    function web_SwitchesIntegrityReport() {
        $result = '';
        $result .= wf_BackLink('?module=switches');
        $messages = new UbillingMessageHelper();
        $allParents = array();
        $allLinks = array();
        $allIds = array();
        $allDeleted = array();
        $selfLoop = array();

        $query = "SELECT * from `switches`";
        $all = simple_queryall($query);

        if (!empty($all)) {
            //filling parent ids array
            foreach ($all as $io => $each) {
                if (!empty($each['parentid'])) {
                    $allParents[$each['parentid']] = $each['id'];
                }
            }

            //filling alllinks array
            foreach ($all as $io => $each) {
                $allLinks[$each['id']] = $each['parentid'];
                if ($each['id'] == $each['parentid']) {
                    $selfLoop[$each['id']] = $each['parentid'];
                }
            }

            //filling registered ids array
            foreach ($all as $io => $each) {
                $allIds[$each['id']] = $each['ip'];
            }

            $result .= $messages->getStyledMessage(__('Total switches in database') . ': ' . sizeof($all), 'info');
            $result .= $messages->getStyledMessage(__('Parent switches found') . ': ' . sizeof($allParents), 'info');

            //checking uplinks geo availability
            foreach ($all as $io => $each) {
                if (isset($allParents[$each['id']])) {
                    if (empty($each['geo'])) {
                        $result .= $messages->getStyledMessage(__('Geo location') . ' ' . __('is empty') . ': ' . web_SwitchProfileLink($each['id']) . ' ' . $each['ip'] . ' - ' . $each['location'], 'error');
                    }
                }
            }


            //checking uplinks switches availability
            foreach ($all as $io => $each) {
                if (!empty($each['parentid'])) {
                    if (!isset($allIds[$each['parentid']])) {
                        $allDeleted[$each['parentid']] = $each['parentid'];
                        $result .= $messages->getStyledMessage(__('Uplink switch is deleted from database') . ': ' . web_SwitchProfileLink($each['id']) . ' - ' . $each['ip'] . ' ' . $each['location'] . ', ' . __('uplink deleted') . ' : [ ' . $each['parentid'] . ' ]', 'error');
                    }
                }
            }


            ///checking uplinks switches possible loop
            if (empty($allDeleted)) {
                if (empty($selfLoop)) {
                    $roads = array();
                    $failRoads = array();
                    if (!empty($allLinks)) {
                        foreach ($allLinks as $id => $parentid) {

                            $roads[$id][] = $parentid;
                            $trace = $parentid;
                            while (!empty($trace)) {
                                if (isset($allLinks[$trace])) {
                                    if ((array_search($allLinks[$trace], $roads[$id])) == false) {
                                        $roads[$id][] = $allLinks[$trace];
                                    } else {
                                        $failRoads[$id] = $allLinks[$trace];
                                        $trace = '';
                                    }

                                    $trace = (isset($allLinks[$trace])) ? $allLinks[$trace] : '';
                                }
                            }
                        }
                    }


                    if (!empty($failRoads)) {
                        $failRoads = array_flip($failRoads);
                        $resultLoop = '';
                        foreach ($failRoads as $io => $each) {
                            $resultLoop .= web_SwitchProfileLink($io);
                        }

                        $result .= $messages->getStyledMessage(__('Following switches have loops between') . ': ' . $resultLoop, 'error');
                    }
                } else {
                    $resultLoop = '';
                    foreach ($selfLoop as $io => $each) {
                        $resultLoop .= web_SwitchProfileLink($io);
                    }
                    $result .= $messages->getStyledMessage(__('Because some of switches have itself as parent, check is skipped') . ': ' . $resultLoop, 'error');
                }
            } else {
                $result .= $messages->getStyledMessage(__('Because some of uplink switches deleted loop, check is skipped'), 'error');
            }

            return ($result);
        }
    }

    show_window(__('Switches integrity check'), web_SwitchesIntegrityReport());
} else {
    show_error(__('Access denied'));
}
?>