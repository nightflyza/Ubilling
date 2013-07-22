<?php
    if (cfr('REPORTTRAFFIC')) {

        function web_TstatsShow() {
            $allclasses = zb_DirectionsGetAll();
            $classtraff = array();
            $traffCells = wf_TableCell(__('Traffic classes'), '20%');
            $traffCells.= wf_TableCell(__('Traffic'), '20%');
            $traffCells.= wf_TableCell(__('Traffic classes'));
            $traffRows = wf_TableRow($traffCells, 'row1');

            if (!empty($allclasses)) {
                foreach ($allclasses as $eachclass) {
                    $d_name = 'D' . $eachclass['rulenumber'];
                    $u_name = 'U' . $eachclass['rulenumber'];
                    $query_d = "SELECT SUM(`" . $d_name . "`) FROM `users`";
                    $query_u = "SELECT SUM(`" . $u_name . "`) FROM `users`";
                    $classdown = simple_query($query_d);
                    $classdown = $classdown['SUM(`' . $d_name . '`)'];
                    $classup = simple_query($query_u);
                    $classup = $classup['SUM(`' . $u_name . '`)'];
                    $classtraff[$eachclass['rulename']] = $classdown + $classup;
                }

                if ( ! empty($classtraff) ) {
                    $total = max($classtraff);
                    foreach ($classtraff as $name => $count) {
                        $traffCells = wf_TableCell($name);
                        $traffCells.= wf_TableCell(stg_convert_size($count), '', '', 'sorttable_customkey="' . $count . '"');
                        $traffCells.= wf_TableCell(web_bar($count, $total), '', '', 'sorttable_customkey="' . $count . '"');
                        $traffRows.= wf_TableRow($traffCells, 'row3');
                    }
                }
            }
            $result = wf_TableBody($traffRows, '100%', 0, 'sortable');
            show_window(__('Traffic report'), $result);
        }

        function web_TstatsNas() {
            // MAKE MySQL QUERY:
            $query = 'SELECT * from `nas` WHERE `bandw` != "" GROUP by `bandw`';
            $nasses = simple_queryall($query);

            if ( ! empty($nasses)) {
                
                $graphRows = NULL;
                
                foreach ($nasses as $nas) {
                    // GET BANDWIDTH URL:
                    $bwd = $nas['bandw'];
                    
                    switch ($nas['nastype']) {
                        case 'local':
                        case 'radius':
                        case 'rscriptd':
                            // GRAPHS EXTENTION:
                            $ext = '.png';

                            // MODAL WINDOW SIZE:
                            $width = 920;
                            $height = 620;

                            // GENERATE GRAPHS URLs:
                            $d_day      = $bwd . 'Total-1-R' . $ext;
                            $d_week     = $bwd . 'Total-2-R' . $ext;
                            $d_month    = $bwd . 'Total-3-R' . $ext;
                            $d_year     = $bwd . 'Total-4-R' . $ext;
                            $u_day      = $bwd . 'Total-1-S' . $ext;
                            $u_week     = $bwd . 'Total-2-S' . $ext;
                            $u_month    = $bwd . 'Total-3-S' . $ext;
                            $u_year     = $bwd . 'Total-4-S' . $ext;

                            // GENERATE MODAL WINDOW CONTENT:
                            $daygraph   = __('Downloaded') . wf_img($d_day) . wf_tag('br') . __('Uploaded') . wf_tag('br') . wf_img($u_day);
                            $weekgraph  = __('Downloaded') . wf_img($d_week) . wf_tag('br') . __('Uploaded') . wf_tag('br') . wf_img($u_week);
                            $monthgraph = __('Downloaded') . wf_img($d_month) . wf_tag('br') . __('Uploaded') . wf_tag('br') . wf_img($u_month);
                            $yeargraph  = __('Downloaded') . wf_img($d_year) . wf_tag('br') . __('Uploaded') . wf_tag('br') . wf_img($u_year);
                            break;
                        case 'mikrotik':
                            // INTERFACE TO SHOW GRAPH:
                            $iface = NULL;
                            $nasOptions = zb_mikrotikExtConfGetOptions($nas['id']);

                            if ( ! empty($nasOptions['graph_interface']) ) {
                                $iface = $nasOptions['graph_interface'];
                            } else show_window(__('Error'),__('For NAS').' `'.$nas['nasname'].'` '.__('was not set correct graph interface'));

                            // GRAPHS EXTENTION:
                            $ext = '.gif';

                            // MODAL WINDOW SIZE:
                            $width = 530;
                            $height = 230;

                            // GENERATE GRAPHS URLs:
                            $daily      = $bwd . '/../iface/' . $iface . '/daily' . $ext;
                            $weekly     = $bwd . '/../iface/' . $iface . '/weekly' . $ext;
                            $monthly    = $bwd . '/../iface/' . $iface . '/monthly' . $ext;
                            $yearly     = $bwd . '/../iface/' . $iface . '/yearly' . $ext;

                            // GENERATE MODAL WINDOW CONTENT:
                            $daygraph   = wf_img($daily);
                            $weekgraph  = wf_img($weekly);
                            $monthgraph = wf_img($monthly);
                            $yeargraph  = wf_img($yearly);
                            break;
                    }

                    // GENERATE BUTTONS OPENING MODAL WINDOW:
                    $gday   = wf_modal(__('Graph by day'), __('Graph by day'), $daygraph, '', $width, $height);
                    $gweek  = wf_modal(__('Graph by week'), __('Graph by week'), $weekgraph, '', $width, $height);
                    $gmonth = wf_modal(__('Graph by month'), __('Graph by month'), $monthgraph, '', $width, $height);
                    $gyear  = wf_modal(__('Graph by year'), __('Graph by year'), $yeargraph, '', $width, $height);

                    // PLACE BUTTONS TO HTML TABLE:
                    $graphCells  = wf_TableCell($nas['nasname'], '', 'row2');
                    $graphCells .= wf_TableCell($gday);
                    $graphCells .= wf_TableCell($gweek);
                    $graphCells .= wf_TableCell($gmonth);
                    $graphCells .= wf_TableCell($gyear);
                    $graphRows  .= wf_TableRow($graphCells, 'row3');
                }

                $result = wf_TableBody($graphRows, '100%', 0, '');

                show_window(__('Network Access Servers'), $result);
            }
        }

        web_TstatsShow();
        web_TstatsNas();

    } else show_error(__('You cant control this module'));
?>