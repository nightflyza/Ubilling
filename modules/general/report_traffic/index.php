<?php

if (cfr('REPORTTRAFFIC')) {

    function web_TstatsShow() {
        global $ubillingConfig;
        $altCfg = $ubillingConfig->getAlter();
        $ishimuraOption = MultiGen::OPTION_ISHIMURA;
        $ishimuraTable = MultiGen::NAS_ISHIMURA;

        $allclasses = zb_DirectionsGetAll();
        $classtraff = array();
        $traffCells = wf_TableCell(__('Traffic classes'), '20%');
        $traffCells .= wf_TableCell(__('Traffic'), '20%');
        $traffCells .= wf_TableCell(__('Traffic classes'));
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

                //Yep, no traffic classes at all. Just internet accounting here.
                if ($eachclass['rulenumber'] == 0) {
                    //ishimura data
                    if ($altCfg[$ishimuraOption]) {
                        $query_hideki = "SELECT SUM(`D0`) as `downloaded`, SUM(`U0`) as `uploaded` from `" . $ishimuraTable . "` WHERE  `month`='" . date("n") . "' AND `year`='" . curyear() . "'";
                        $dataHideki = simple_query($query_hideki);
                        if (isset($classtraff[$eachclass['rulename']])) {
                            @$classtraff[$eachclass['rulename']] += $dataHideki['downloaded'] + $dataHideki['uploaded'];
                        } else {
                            $classtraff[$eachclass['rulename']] = $dataHideki['downloaded'] + $dataHideki['uploaded'];
                        }
                    }

                    //or ophanim flow may be?
                    if ($altCfg[OphanimFlow::OPTION_ENABLED]) {
                        $ophanim = new OphanimFlow();
                        $ophTraff = $ophanim->getAllUsersAggrTraff();
                        if (!empty($ophTraff)) {
                            foreach ($ophTraff as $io => $each) {
                                $classtraff[$eachclass['rulename']] += $each;
                            }
                        }
                    }
                }
            }

            if (!empty($classtraff)) {
                $total = max($classtraff);
                foreach ($classtraff as $name => $count) {
                    $traffCells = wf_TableCell($name);
                    $traffCells .= wf_TableCell(stg_convert_size($count), '', '', 'sorttable_customkey="' . $count . '"');
                    $traffCells .= wf_TableCell(web_bar($count, $total), '', '', 'sorttable_customkey="' . $count . '"');
                    $traffRows .= wf_TableRow($traffCells, 'row3');
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
        if (!empty($result)) {
            $graphRows = null;

            foreach ($result as $nas) {
                $bwd = $nas['bandw'];
                switch ($nas['nastype']) {
                    case 'local':
                    case 'radius':
                    case 'rscriptd':
                        //normal bandwidthd
                        if (!ispos($bwd, 'mlgmths') AND !ispos($bwd, 'mlgmtppp') AND !ispos($bwd, 'mlgmtdhcp')) {
                            // Extention:
                            $ext = '.png';
                            // Modals:
                            $width = 940;
                            $height = 666;

                            // Links:
                            $d_day = $bwd . 'Total-1-R' . $ext;
                            $d_week = $bwd . 'Total-2-R' . $ext;
                            $d_month = $bwd . 'Total-3-R' . $ext;
                            $d_year = $bwd . 'Total-4-R' . $ext;
                            $u_day = $bwd . 'Total-1-S' . $ext;
                            $u_week = $bwd . 'Total-2-S' . $ext;
                            $u_month = $bwd . 'Total-3-S' . $ext;
                            $u_year = $bwd . 'Total-4-S' . $ext;

                            //OphanimFlow graphs
                            if (ispos($bwd, 'OphanimFlow') OR ispos($bwd, 'of/')) {
                                $d_day = $bwd . '/?module=graph&ip=0.0.0.0&dir=R&period=day';
                                $d_week = $bwd . '/?module=graph&ip=0.0.0.0&dir=R&period=week';
                                $d_month = $bwd . '/?module=graph&ip=0.0.0.0&dir=R&period=month';
                                $d_year = $bwd . '/?module=graph&ip=0.0.0.0&dir=R&period=year';
                                $u_day = $bwd . '/?module=graph&ip=0.0.0.0&dir=S&period=day';
                                $u_week = $bwd . '/?module=graph&ip=0.0.0.0&dir=S&period=week';
                                $u_month = $bwd . '/?module=graph&ip=0.0.0.0&dir=S&period=month';
                                $u_year = $bwd . '/?module=graph&ip=0.0.0.0&dir=S&period=year';
                                $width = 1600;
                                $height = 900;
                            }


                            $daygraph = __('Downloaded') . wf_img(zb_BandwidthdImgLink($d_day)) . wf_tag('br') . __('Uploaded') . wf_tag('br') . wf_img(zb_BandwidthdImgLink($u_day));
                            $weekgraph = __('Downloaded') . wf_img(zb_BandwidthdImgLink($d_week)) . wf_tag('br') . __('Uploaded') . wf_tag('br') . wf_img(zb_BandwidthdImgLink($u_week));
                            $monthgraph = __('Downloaded') . wf_img(zb_BandwidthdImgLink($d_month)) . wf_tag('br') . __('Uploaded') . wf_tag('br') . wf_img(zb_BandwidthdImgLink($u_month));
                            $yeargraph = __('Downloaded') . wf_img(zb_BandwidthdImgLink($d_year)) . wf_tag('br') . __('Uploaded') . wf_tag('br') . wf_img(zb_BandwidthdImgLink($u_year));
                            $graphLegend = wf_tag('br') . wf_img('skins/bwdlegend.gif');
                        } else {
                            //Multigen Mikrotik hotspot
                            $bwd = str_replace('mlgmths', 'graphs/iface/bridge', $bwd);
                            $bwd = str_replace('mlgmtppp', 'graphs/iface/bridge', $bwd);
                            $bwd = str_replace('mlgmtdhcp', 'graphs/iface/bridge', $bwd);

                            $ext = '.gif';
                            $daily = $bwd . '/daily' . $ext;
                            $weekly = $bwd . '/weekly' . $ext;
                            $monthly = $bwd . '/monthly' . $ext;
                            $yearly = $bwd . '/yearly' . $ext;

                            // Modals:
                            $width = 530;
                            $height = 250;
                            $daygraph = wf_img(zb_BandwidthdImgLink($daily));
                            $weekgraph = wf_img(zb_BandwidthdImgLink($weekly));
                            $monthgraph = wf_img(zb_BandwidthdImgLink($monthly));
                            $yeargraph = wf_img(zb_BandwidthdImgLink($yearly));
                            $graphLegend = '';
                        }
                        break;
                    case 'mikrotik':
                        if (!ispos($bwd, 'pppoe')) {
                            $options = zb_NasOptionsGet($nas['id']);
                            if (!empty($options['graph_interface'])) {
                                // Extention:
                                $ext = '.gif';

                                // Links:
                                $daily = $bwd . '/../iface/' . $options['graph_interface'] . '/daily' . $ext;
                                $weekly = $bwd . '/../iface/' . $options['graph_interface'] . '/weekly' . $ext;
                                $monthly = $bwd . '/../iface/' . $options['graph_interface'] . '/monthly' . $ext;
                                $yearly = $bwd . '/../iface/' . $options['graph_interface'] . '/yearly' . $ext;

                                // Modals:
                                $width = 530;
                                $height = 230;
                                $daygraph = wf_img($daily);
                                $weekgraph = wf_img($weekly);
                                $monthgraph = wf_img($monthly);
                                $yeargraph = wf_img($yearly);
                                $graphLegend = '';
                                break;
                            } else {
                                show_error(__('For NAS') . ' `' . $nas['nasname'] . '` ' . __('was not set correct graph interface'));
                            }
                        } else {
                            $width = 530;
                            $height = 230;
                            $daygraph = '';
                            $weekgraph = '';
                            $monthgraph = '';
                            $yeargraph = '';
                            $graphLegend = '';
                        }
                }

                if (!ispos($bwd, 'OphanimFlow') AND !ispos($bwd, 'of/')) {
                    $graphLegend = wf_tag('br') . wf_img('skins/bwdlegend.gif');
                } else {
                    $graphLegend = '';
                }

                // Buttons:
                $gday = wf_modal(__('Graph by day'), __('Graph by day'), $daygraph . $graphLegend, '', $width, $height);
                $gweek = wf_modal(__('Graph by week'), __('Graph by week'), $weekgraph . $graphLegend, '', $width, $height);
                $gmonth = wf_modal(__('Graph by month'), __('Graph by month'), $monthgraph . $graphLegend, '', $width, $height);
                $gyear = wf_modal(__('Graph by year'), __('Graph by year'), $yeargraph . $graphLegend, '', $width, $height);

                // Put buttons to table row:
                $graphCells = wf_TableCell($nas['nasname'], '', 'row2');
                $graphCells .= wf_TableCell($gday);
                $graphCells .= wf_TableCell($gweek);
                $graphCells .= wf_TableCell($gmonth);
                $graphCells .= wf_TableCell($gyear);
                $graphRows .= wf_TableRow($graphCells, 'row3');
            }

            $result = wf_TableBody($graphRows, '100%', 0, '');

            show_window(__('Network Access Servers'), $result);
        }
    }

    web_TstatsShow();
    web_TstatsNas();
} else {
    show_error(__('You cant control this module'));
}