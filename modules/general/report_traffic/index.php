<?php

    if ( cfr('REPORTTRAFFIC') ) {

        function web_TstatsShow() {
            $allclasses  = zb_DirectionsGetAll();
            $classtraff  = array();
            $traffCells  = wf_TableCell(__('Traffic classes'), '20%');
            $traffCells .= wf_TableCell(__('Traffic'), '20%');
            $traffCells .= wf_TableCell(__('Traffic classes'));
            $traffRows   = wf_TableRow($traffCells, 'row1');

            if ( !empty($allclasses) ) {
                foreach ( $allclasses as $eachclass ) {
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

                if ( !empty($classtraff) ) {
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
            // Get NAS list with bandwidth setted up:
            $query = 'SELECT * FROM `nas` WHERE `bandw` != "" GROUP by `bandw`';
            $result = simple_queryall($query);

            // Check presence of any entry:
            if ( !empty($result) ) {
                $graphRows = null;

                foreach ( $result as $nas ) {
                    $bwd = $nas['bandw'];
                    switch ( $nas['nastype'] ) {
                        case 'local':
                        case 'radius':
                        case 'rscriptd':
                            // Extention:
                            $ext = '.png';
                            
                            // Links:
                            $d_day   = $bwd . 'Total-1-R' . $ext;
                            $d_week  = $bwd . 'Total-2-R' . $ext;
                            $d_month = $bwd . 'Total-3-R' . $ext;
                            $d_year  = $bwd . 'Total-4-R' . $ext;
                            $u_day   = $bwd . 'Total-1-S' . $ext;
                            $u_week  = $bwd . 'Total-2-S' . $ext;
                            $u_month = $bwd . 'Total-3-S' . $ext;
                            $u_year  = $bwd . 'Total-4-S' . $ext;
                            
                            // Modals:
                            $width      = 920;
                            $height     = 650;
                            $daygraph   = __('Downloaded') . wf_img($d_day)   . wf_tag('br') . __('Uploaded') . wf_tag('br') . wf_img($u_day);
                            $weekgraph  = __('Downloaded') . wf_img($d_week)  . wf_tag('br') . __('Uploaded') . wf_tag('br') . wf_img($u_week);
                            $monthgraph = __('Downloaded') . wf_img($d_month) . wf_tag('br') . __('Uploaded') . wf_tag('br') . wf_img($u_month);
                            $yeargraph  = __('Downloaded') . wf_img($d_year)  . wf_tag('br') . __('Uploaded') . wf_tag('br') . wf_img($u_year);
                            $graphLegend= wf_tag('br').wf_img('skins/bwdlegend.gif');
                            break;
                        case 'mikrotik':
                            $options = zb_NasOptionsGet($nas['id']);
                            if ( !empty($options['graph_interface']) ) {
                                // Extention:
                                $ext = '.gif';

                                // Links:
                                $daily   = $bwd . '/../iface/' . $options['graph_interface'] . '/daily'   . $ext;
                                $weekly  = $bwd . '/../iface/' . $options['graph_interface'] . '/weekly'  . $ext;
                                $monthly = $bwd . '/../iface/' . $options['graph_interface'] . '/monthly' . $ext;
                                $yearly  = $bwd . '/../iface/' . $options['graph_interface'] . '/yearly'  . $ext;

                                // Modals:
                                $width      = 530;
                                $height     = 230;
                                $daygraph   = wf_img($daily);
                                $weekgraph  = wf_img($weekly);
                                $monthgraph = wf_img($monthly);
                                $yeargraph  = wf_img($yearly);
                                $graphLegend= '';
                                break;
                            } else show_window(__('Error'), __('For NAS') . ' `' . $nas['nasname'] . '` ' . __('was not set correct graph interface'));
                    }

                    // Buttons:
                    $gday   = wf_modal(__('Graph by day'),   __('Graph by day'),   $daygraph.$graphLegend,   '', $width, $height);
                    $gweek  = wf_modal(__('Graph by week'),  __('Graph by week'),  $weekgraph.$graphLegend,  '', $width, $height);
                    $gmonth = wf_modal(__('Graph by month'), __('Graph by month'), $monthgraph.$graphLegend, '', $width, $height);
                    $gyear  = wf_modal(__('Graph by year'),  __('Graph by year'),  $yeargraph.$graphLegend,  '', $width, $height);

                    // Put buttons to table row:
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