<?php

if ($system->checkForRight('ONLINE')) {
    $alter_conf = $ubillingConfig->getAlter();
    $hp_mode = $alter_conf['ONLINE_HP_MODE'];

    /**
     * Renders user list JQuery DataTables container
     *
     * @global array $alter_conf
     *
     * @return string
     */
    function renderUserListContainer() {
        global $alter_conf;
        $saveState = 'false';
        if (isset($alter_conf['ONLINE_SAVE_STATE'])) {
            if ($alter_conf['ONLINE_SAVE_STATE']) {
                $saveState = 'true';
            }
        }

        $hp_mode = $alter_conf['ONLINE_HP_MODE'];

        $ShowContractField = false;
        if (isset($alter_conf['ONLINE_SHOW_CONTRACT_FIELD']) && $alter_conf['ONLINE_SHOW_CONTRACT_FIELD']) {
            $ShowContractField = true;
        }

        $showUserPhones = false;
        if (isset($alter_conf['ONLINE_SHOW_PHONES']) && $alter_conf['ONLINE_SHOW_PHONES']) {
            $showUserPhones = true;
        }

        $columnDefs = '';
        $showONUSignals = false;
        $showWIFISignals = false;

        if (isset($alter_conf['PON_ENABLED']) && $alter_conf['PON_ENABLED'] &&
            isset($alter_conf['ONLINE_SHOW_ONU_SIGNALS']) && $alter_conf['ONLINE_SHOW_ONU_SIGNALS']) {
            $showONUSignals = true;
            $colNum1 = (($ShowContractField and $showUserPhones) ? '5' : (($ShowContractField xor $showUserPhones) ? '4' : '3'));

            $columnDefs.= '{"targets": ' . $colNum1 . ',
                                "render": function ( data, type, row ) {                                          
                                            var sigColor = \'#000\';
                                                                                                    
                                            if (data > 0 || data < -27) {
                                                sigColor = \'#ab0000\';
                                            } else if (data > -27 && data < -25) {
                                                sigColor = \'#FF5500\';
                                            } else {
                                                sigColor = \'#005502\';
                                            }
                                                                                                    
                                            return \'<span style="color:\' + sigColor + \'">\' + data + \'</span>\';
                                        }
                            } ';
        }

        if (isset($alter_conf['MTSIGMON_ENABLED']) && $alter_conf['MTSIGMON_ENABLED'] &&
            isset($alter_conf['ONLINE_SHOW_WIFI_SIGNALS']) && $alter_conf['ONLINE_SHOW_WIFI_SIGNALS']) {
            $showWIFISignals = true;

            // fuckin' XOR magic goes below this line. don't touch it(especially the parentheses) or you'll be cursed with hours of debugging
            // But to be serious - here we're trying to avoid a huge amount of "IFs" while checking the "ON" status of 3 optional columns:
            // $ShowContractField, $showUserPhones and $showONUSignals. And that's all is not just to show off with XOR or something.
            // Just because they go after each other in Online table and we need to apply some JQDT renderer function
            // for coloring only to $showWIFISignals - we need to determine certainly the number of $showWIFISignals column.
            // 1. We check, if all of 3 optional columns are "ON" - then $column2 will equal to "6". If not all of 3 optional columns are "ON" - we need to check further:
            // 2. If any 2 of 3 optional columns are "ON" and only one of those 3 is "OFF" - then $column2 will equal to "5"
            // 3. If only one of 3 optional columns is "ON" - then $column2 will equal to "4"
            // 4. Finally, if none of 3 optional columns are "ON" - then $column2 will equal to "3"
            $colNum2 = (($ShowContractField and $showUserPhones and $showONUSignals) ? '6' :
                        ((($ShowContractField and $showUserPhones) xor ($showUserPhones and $showONUSignals) xor ($ShowContractField and $showONUSignals)) ? '5' :
                        (($ShowContractField xor $showUserPhones xor $showONUSignals) ? '4' : '3')));

            $columnDefs.= (empty($columnDefs) ? '' : ', ');
            $columnDefs.= '{"targets": ' . $colNum2 . ',
                                "render": function ( data, type, row ) {
                                            var signalArr = data.split(\' / \');
                                            var signal = \'\';
                                            
                                            if (1 in signalArr) {                                                
                                                signal = (parseInt(signalArr[0]) > parseInt(signalArr[1])) ? signalArr[1] : signalArr[0]; 
                                            } else {
                                                signal = signalArr[0];
                                            }
                                            
                                            var sigColor = \'#000\';
                                                                                                    
                                            if (signal < -79) {
                                                sigColor = \'#ab0000\';
                                            } else if (signal > -80 && signal < -74) {
                                                sigColor = \'#FF5500\';
                                            } else {
                                                sigColor = \'#005502\';
                                            }
                                                                                                    
                                            return \'<span style="color:\' + sigColor + \'">\' + data + \'</span>\';
                                        }
                            }, ';
        }

        $showLastFeeCharge = false;
        if (isset($alter_conf['ONLINE_SHOW_LAST_FEECHARGE']) && $alter_conf['ONLINE_SHOW_LAST_FEECHARGE']) {
            $showLastFeeCharge = true;
        }

        $columnDefs = '"columnDefs": [ ' . $columnDefs . '], ';

        //alternate center styling
        $alternateStyle = '';
        if (isset($alter_conf['ONLINE_ALTERNATE_VIEW'])) {
            if ($alter_conf['ONLINE_ALTERNATE_VIEW']) {
                $alternateStyle = wf_tag('style', false) . '#onlineusershp  td { text-align:center !important; }' . wf_tag('style', true);
            }
        }

        if ($alter_conf['DN_ONLINE_DETECT']) {
            $columnFilters = '
             null, ' .
                    ( ($hp_mode == 1 && $ShowContractField) ? 'null,' : '' ) .
                    ' null, ' .
                ( ($hp_mode == 1 && $showUserPhones) ? 'null,' : '' ) .
                ' { "sType": "ip-address" }, ' .
                ( ($hp_mode == 1 && $showONUSignals) ? 'null, ' : '' ) .
                ( ($hp_mode == 1 && $showWIFISignals) ? 'null, ' : '' ) .
                ' null,
                null,
                null,
                { "sType": "file-size" },
                null,
                null ' .
                ( ($hp_mode == 1 && $showLastFeeCharge) ? ', null' : '' );
        } else {
            $columnFilters = '
             null, ' .
                    ( ($hp_mode == 1 && $ShowContractField) ? 'null,' : '' ) .
                    ' null, ' .
                ( ($hp_mode == 1 && $showUserPhones) ? 'null,' : '' ) .
                ' { "sType": "ip-address" }, ' .
                ( ($hp_mode == 1 && $showONUSignals) ? 'null, ' : '' ) .
                ( ($hp_mode == 1 && $showWIFISignals) ? 'null, ' : '' ) .
                ' null,
                null,
                { "sType": "file-size" },
                null,                
                null ' .
                ( ($hp_mode == 1 && $showLastFeeCharge) ? ', null' : '' );
        }

        $dtcode = '
       		<script type="text/javascript" charset="utf-8">

                jQuery.fn.dataTableExt.oSort[\'file-size-asc\']  = function(a,b) {
                var x = a.substring(0,a.length - 2);
                var y = b.substring(0,b.length - 2);

                var x_unit = (a.substring(a.length - 2, a.length) == "Mb" ?
                1000 : (a.substring(a.length - 2, a.length) == "Gb" ? 1000000 : 1));
                var y_unit = (b.substring(b.length - 2, b.length) == "Mb" ?
                1000 : (b.substring(b.length - 2, b.length) == "Gb" ? 1000000 : 1));

                x = parseInt( x * x_unit );
                y = parseInt( y * y_unit );

                return ((x < y) ? -1 : ((x > y) ?  1 : 0));
                };

                jQuery.fn.dataTableExt.oSort[\'file-size-desc\'] = function(a,b) {
                var x = a.substring(0,a.length - 2);
                var y = b.substring(0,b.length - 2);

                var x_unit = (a.substring(a.length - 2, a.length) == "Mb" ?
                1000 : (a.substring(a.length - 2, a.length) == "Gb" ? 1000000 : 1));
                var y_unit = (b.substring(b.length - 2, b.length) == "Mb" ?
                1000 : (b.substring(b.length - 2, b.length) == "Gb" ? 1000000 : 1));

                x = parseInt( x * x_unit);
                y = parseInt( y * y_unit);

                return ((x < y) ?  1 : ((x > y) ? -1 : 0));
                };

                jQuery.fn.dataTableExt.oSort[\'ip-address-asc\']  = function(a,b) {
                var m = a.split("."), x = "";
                var n = b.split("."), y = "";
                for(var i = 0; i < m.length; i++) {
                var item = m[i];
                if(item.length == 1) {
                x += "00" + item;
                } else if(item.length == 2) {
                x += "0" + item;
                } else {
                x += item;
                }
            }
            for(var i = 0; i < n.length; i++) {
                var item = n[i];
                if(item.length == 1) {
                y += "00" + item;
                } else if(item.length == 2) {
                y += "0" + item;
                } else {
                y += item;
            }
        }
        return ((x < y) ? -1 : ((x > y) ? 1 : 0));
        };

        jQuery.fn.dataTableExt.oSort[\'ip-address-desc\']  = function(a,b) {
            var m = a.split("."), x = "";
            var n = b.split("."), y = "";
            for(var i = 0; i < m.length; i++) {
                var item = m[i];
                if(item.length == 1) {
                    x += "00" + item;
                } else if (item.length == 2) {
                    x += "0" + item;
                } else {
                    x += item;
                }
            }
            for(var i = 0; i < n.length; i++) {
                var item = n[i];
                if(item.length == 1) {
                y += "00" + item;
            } else if (item.length == 2) {
            y += "0" + item;
            } else {
            y += item;
            }
        }
        return ((x < y) ? 1 : ((x > y) ? -1 : 0));
    };



		$(document).ready(function() {
		$(\'#onlineusershp\').dataTable( { 
 	       ' . $columnDefs . '
 	       "oLanguage": {
			"sLengthMenu": "' . __('Show') . ' _MENU_",
			"sZeroRecords": "' . __('Nothing found') . '",
			"sInfo": "' . __('Showing') . ' _START_ ' . __('to') . ' _END_ ' . __('of') . ' _TOTAL_ ' . __('users') . '",
			"sInfoEmpty": "' . __('Showing') . ' 0 ' . __('to') . ' 0 ' . __('of') . ' 0 ' . __('users') . '",
			"sInfoFiltered": "(' . __('Filtered') . ' ' . __('from') . ' _MAX_ ' . __('Total') . ')",
                        "sSearch":       "' . __('Search') . '",
                        "sProcessing":   "' . __('Processing') . '...",
                        "oPaginate": {
                        "sFirst": "' . __('First') . '",
                        "sPrevious": "' . __('Previous') . '",
                        "sNext": "' . __('Next') . '",
                        "sLast": "' . __('Last') . '"
                        },
            },
            "aoColumns": [
                  ' . $columnFilters . '
            ],
        "bPaginate": true,
        "bLengthChange": true,
        "bFilter": true,
        "bSort": true,
        "bInfo": true,
        "bAutoWidth": false,
        "bProcessing": true,
        "iDisplayLength": 50,
        "sAjaxSource": \'?module=online&ajax\',
	"bDeferRender": true,
        "bJQueryUI": true,
        "pagingType": "full_numbers",
        "lengthMenu": [[10, 25, 50, 100, 200, -1], [10, 25, 50, 100, 200, "' . __('All') . '"]],
        "bStateSave": ' . $saveState . '

                } );
		} );

		</script>

       ';
        $result = $dtcode;
        $result.= wf_tag('table', false, 'display compact', 'width="100%" id="onlineusershp"');
        //dn activity check
        if ($alter_conf['DN_ONLINE_DETECT']) {
            $onlineCells = wf_TableCell(__('Users online'));
        } else {
            $onlineCells = '';
        }

        $result.= wf_tag('thead', false);
        $result.= wf_tag('tr', false, 'row2');
        $result.= wf_TableCell(__('Full address'));
        $result.= ( ($hp_mode == 1 && $ShowContractField) ? wf_TableCell(__('Contract')) : '' );
        $result.= wf_TableCell(__('Real Name'));
        $result.= ( ($hp_mode == 1 && $showUserPhones) ? wf_TableCell(__("Phones")) : '' );
        $result.= wf_TableCell(__('IP'));
        $result.= ( ($hp_mode == 1 && $showONUSignals) ? wf_TableCell(__("ONU Signal")) : '' );
        $result.= ( ($hp_mode == 1 && $showWIFISignals) ? wf_TableCell(__("Signal") . ' WiFi') : '' );
        $result.= wf_TableCell(__('Tariff'));
        $result.= wf_TableCell(__('Active'));
        $result.= $onlineCells;
        $result.= wf_TableCell(__('Traffic'));
        $result.= wf_TableCell(__('Balance'));
        $result.= wf_TableCell(__('Credit'));
        $result.= ( ($hp_mode == 1 && $showLastFeeCharge) ? wf_TableCell(__("Last fee charge")) : '' );
        $result.= wf_tag('tr', true);
        $result.= wf_tag('thead', true);
        $result.= wf_tag('table', true);

        $result.= $alternateStyle;

        return ($result);
    }

    /**
     * Renders json data for user list. Manual HTML assebly instead of astral calls - for performance reasons.
     *
     * @global array $alter_conf
     *
     * @return string
     */
    function zb_AjaxOnlineDataSourceSafe() {
        global $alter_conf;
        $ubCache = new UbillingCache();
        $ishimuraOption = MultiGen::OPTION_ISHIMURA;
        $ishimuraTable = MultiGen::NAS_ISHIMURA;
        $additionalTraffic = array();
        if (@$alter_conf[$ishimuraOption]) {
            $query_hideki = "SELECT `login`,`D0`,`U0` from `" . $ishimuraTable . "` WHERE `month`='" . date("n") . "' AND `year`='" . curyear() . "'";
            $dataHideki = simple_queryall($query_hideki);
            if (!empty($dataHideki)) {
                foreach ($dataHideki as $io => $each) {
                    $additionalTraffic[$each['login']] = $each['D0'] + $each['U0'];
                }
            }
        }
        $allcontracts = array();
        $allcontractdates = array();

        $ShowContractField = false;
        $ShowContractDate = false;
        if (isset($alter_conf['ONLINE_SHOW_CONTRACT_FIELD']) && $alter_conf['ONLINE_SHOW_CONTRACT_FIELD']) {
            $ShowContractField = true;

            if (isset($alter_conf['ONLINE_SHOW_CONTRACT_DATE']) && $alter_conf['ONLINE_SHOW_CONTRACT_DATE']) {
                $ShowContractDate = true;
            }
        }

        if ($ShowContractField) {
            if ($ShowContractDate) {
                $query = "SELECT `contracts`.*, `contractdates`.`date` AS `contractdate`
                                        FROM `contracts`
                                        LEFT JOIN `contractdates` ON `contractdates`.`contract` = `contracts`.`contract`;
                          ";
            } else {
                $query = "SELECT * FROM `contracts`;";
            }

            $tmpContracts = simple_queryall($query);

            if (!empty($tmpContracts)) {
                foreach ($tmpContracts as $io => $eachcontract) {
                    $allcontracts[$eachcontract['login']] = $eachcontract['contract'];

                    if ($ShowContractDate) {
                        $allcontractdates[$eachcontract['login']] = $eachcontract['contractdate'];
                    }
                }
            }
        }

        $showUserNotes = false;
        $adCommentsON = false;
        if (isset($alter_conf['ONLINE_SHOW_USERNOTES']) && $alter_conf['ONLINE_SHOW_USERNOTES']) {
            $showUserNotes = true;

            if (isset($alter_conf['ADCOMMENTS_ENABLED']) && $alter_conf['ADCOMMENTS_ENABLED']) {
                $adCommentsON = true;
                $adcomments = new ADcomments('USERNOTES');
            }

            $query = "SELECT * from `notes`";
            $tmpUserNotes = simple_queryall($query);

            if (!empty($tmpUserNotes)) {
                foreach ($tmpUserNotes as $io => $eachUN) {
                    $adCommentsCount = 0;

                    if (!empty($eachUN['note'])) {
                        if ($adCommentsON) {
                            $adCommentsCount = $adcomments->getCommentsCount($eachUN['login']);
                        }

                        $adCommentsLink = (empty($adCommentsCount)) ? '' : wf_nbsp() . wf_Link('?module=notesedit&username=' . $eachUN['login'], wf_tag('sup') . $adCommentsCount . wf_tag('sup', true));
                        $allUserNotes[$eachUN['login']] = array('note' => $eachUN['note'], 'adcomment' => $adCommentsLink);
                    }
                }
            }
        }

        // get users's ONU and WIFI signal level
        $allONUSignals = array();
        $allWiFiSignals = array();
        $showONUSignals = false;
        $showWIFISignals = false;

        if (isset($alter_conf['PON_ENABLED']) && $alter_conf['PON_ENABLED'] &&
            isset($alter_conf['ONLINE_SHOW_ONU_SIGNALS']) && $alter_conf['ONLINE_SHOW_ONU_SIGNALS']) {
            $showONUSignals = true;
            $allONUSignals = PONizer::getAllONUSignals();
        }

        if (isset($alter_conf['MTSIGMON_ENABLED']) && $alter_conf['MTSIGMON_ENABLED'] &&
            isset($alter_conf['ONLINE_SHOW_WIFI_SIGNALS']) && $alter_conf['ONLINE_SHOW_WIFI_SIGNALS']) {
            $showWIFISignals = true;

            $WiFiSigmon = new MTsigmon();
            $allWiFiSignals = $WiFiSigmon->getAllWiFiSignals();
        }

        $allFees = array();
        $showLastFeeCharge = false;
        if (isset($alter_conf['ONLINE_SHOW_LAST_FEECHARGE']) && $alter_conf['ONLINE_SHOW_LAST_FEECHARGE']) {
            $showLastFeeCharge = true;
            $allFees = $ubCache->get('STG_FEE_CHARGE');
        }

        $showUserPhones = false;
        $allUserPhones = array();
        if (isset($alter_conf['ONLINE_SHOW_PHONES']) && $alter_conf['ONLINE_SHOW_PHONES']) {
            $showUserPhones = true;
            $allUserPhones = zb_GetAllOnlineTabPhones();
        }

        $query = "SELECT * FROM `users`";
        $query_fio = "SELECT * from `realname`";
        $allusers = simple_queryall($query);
        $allfioz = simple_queryall($query_fio);
        $fioz = zb_UserGetAllRealnames();
        $detect_address = zb_AddressGetFulladdresslist();
        $ucount = 0;
        $deadUsers = array();
        $displayFreezeFlag = (@$alter_conf['ONLINE_SHOW_FREEZE']) ? true : false;

        //alternate view of online module
        $addrDelimiter = '';
        if (isset($alter_conf['ONLINE_ALTERNATE_VIEW'])) {
            if ($alter_conf['ONLINE_ALTERNATE_VIEW']) {
                $addrDelimiter = wf_tag('br');
            }
        }
        //hide dead users array
        if ($alter_conf['DEAD_HIDE']) {
            if (!empty($alter_conf['DEAD_TAGID'])) {
                $tagDead = vf($alter_conf['DEAD_TAGID'], 3);
                $query_dead = "SELECT `login`,`tagid` from `tags` WHERE `tagid`='" . $tagDead . "'";
                $alldead = simple_queryall($query_dead);
                if (!empty($alldead)) {
                    foreach ($alldead as $idead => $eachDead) {
                        $deadUsers[$eachDead['login']] = $eachDead['tagid'];
                    }
                }
            }
        }

        $hidePictTitles = false;
        if (isset($alter_conf['ONLINE_HIDE_PICT_TITLES']) && $alter_conf['ONLINE_HIDE_PICT_TITLES']) {
            $hidePictTitles = true;
        }

        $jsonAAData = array();

        if (!empty($allusers)) {
            $totalusers = sizeof($allusers);
            foreach ($allusers as $io => $eachuser) {
                $tinet = 0;
                $ucount++;
                $cash = $eachuser['Cash'];
                $credit = $eachuser['Credit'];
                for ($classcounter = 0; $classcounter <= 9; $classcounter++) {
                    $dc = 'D' . $classcounter . '';
                    $uc = 'U' . $classcounter . '';
                    $tinet = $tinet + ($eachuser[$dc] + $eachuser[$uc]);
                }
                //ishimura traffic mixing
                $currentAdditionalTraff = (isset($additionalTraffic[$eachuser['login']])) ? $additionalTraffic[$eachuser['login']] : 0;
                $tinet = $tinet + $currentAdditionalTraff;

                $act = '<img src=skins/icon_active.gif>' . (($hidePictTitles) ? '' : __('Yes'));
                //finance check
                if ($cash < '-' . $credit) {
                    $act = '<img src=skins/icon_inactive.gif>' . (($hidePictTitles) ? '' : __('No'));
                }
                if ($displayFreezeFlag) {
                    if (@$alter_conf['ONLINE_SHOW_FREEZE_LAT']) {
                        $act .= $eachuser['Passive'] ? ' <img src=skins/icon_passive.gif>' . date('Y-m-d', $eachuser['LastActivityTime']) : '';
                    } else {
                        $act .= $eachuser['Passive'] ? ' <img src=skins/icon_passive.gif>' . (($hidePictTitles) ? '' : __('Freezed')) : '';
                    }
                }
                //online activity check
                if ($alter_conf['DN_ONLINE_DETECT']) {
                    $onlineFlag = '<img src=skins/icon_nostar.gif> ' . (($hidePictTitles) ? '' : __('No'));
                    if (file_exists(DATA_PATH . 'dn/' . $eachuser['login'])) {
                        $onlineFlag = '<img src=skins/icon_star.gif> ' . (($hidePictTitles) ? '' : __('Yes'));
                    }
                } else {
                    $onlineFlag = '';
                }
                @$clearuseraddress = $detect_address[$eachuser['login']];

                //additional finance links
                if ($alter_conf['FAST_CASH_LINK']) {
                    $fastcashlink = ' <a href=?module=addcash&username=' . $eachuser['login'] . '#cashfield><img src=skins/icon_dollar.gif border=0></a> ';
                } else {
                    $fastcashlink = '';
                }

                $onuSignal = '';
                $wifiSignal = '';
                $feeCharge = '';
                $userPhones = '';

                if ($showONUSignals and isset($allONUSignals[$eachuser['login']])) {
                    $onuSignal = $allONUSignals[$eachuser['login']];
                }

                if ($showWIFISignals and isset($allWiFiSignals[$eachuser['login']])) {
                    $wifiSignal = $allWiFiSignals[$eachuser['login']];
                }

                if ($showLastFeeCharge and isset($allFees[$eachuser['login']])) {
                    $feeCharge = $allFees[$eachuser['login']]['max_date'] . '<br />' . ($allFees[$eachuser['login']]['balance_to'] - $allFees[$eachuser['login']]['balance_from']);
                }

                if ($showUserPhones and isset($allUserPhones[$eachuser['login']])) {
                    $userPhones = $allUserPhones[$eachuser['login']];
                }

                if (!$alter_conf['DEAD_HIDE']) {
                    $jsonItem = array();
                    $jsonItem[] = '<a href=?module=traffstats&username=' . $eachuser['login'] . '><img src=skins/icon_stats.gif border=0 title=' . __('Stats') . '></a> <a href=?module=userprofile&username=' . $eachuser['login'] . '><img src=skins/icon_user.gif border=0 title=' . __('Profile') . '></a> ' . $fastcashlink . $addrDelimiter . $clearuseraddress;

                    if ($ShowContractField) {
                        $jsonItem[] = @$allcontracts[$eachuser['login']] . (($ShowContractDate) ? wf_tag('br') . @$allcontractdates[$eachuser['login']] : '');
                    }

                    $jsonItem[] = @$fioz[$eachuser['login']] . (($showUserNotes and isset($allUserNotes[$eachuser['login']]['note'])) ? wf_delimiter(0) . '( ' . $allUserNotes[$eachuser['login']]['note'] . ' )' . $allUserNotes[$eachuser['login']]['adcomment'] : '');

                    if ($showUserPhones) {
                        $jsonItem[] = $userPhones;
                    }

                    $jsonItem[] = $eachuser['IP'];

                    if ($showONUSignals) {
                        $jsonItem[] = $onuSignal;
                    }

                    if ($showWIFISignals) {
                        $jsonItem[] = $wifiSignal;
                    }

                    $jsonItem[] = $eachuser['Tariff'];
                    $jsonItem[] = $act;
                    if (!empty($onlineFlag)) {
                        $jsonItem[] = $onlineFlag;
                    }
                    $jsonItem[] = zb_TraffToGb($tinet);
                    $jsonItem[] = "" . round($eachuser['Cash'], 2);
                    $jsonItem[] = "" . round($eachuser['Credit'], 2);

                    if ($showLastFeeCharge) {
                        $jsonItem[] = $feeCharge;
                    }

                    $jsonAAData[] = $jsonItem;
                } else {
                    if (!isset($deadUsers[$eachuser['login']])) {
                        $jsonItem = array();
                        $jsonItem[] = '<a href=?module=traffstats&username=' . $eachuser['login'] . '><img src=skins/icon_stats.gif border=0 title=' . __('Stats') . '></a> <a href=?module=userprofile&username=' . $eachuser['login'] . '><img src=skins/icon_user.gif border=0 title=' . __('Profile') . '></a> ' . $fastcashlink . $clearuseraddress;

                        if ($ShowContractField) {
                            $jsonItem[] = $allcontracts[$eachuser['login']] . ( ($ShowContractDate) ? wf_tag('br') . $allcontractdates[$eachuser['login']] : '' );
                        }

                        $jsonItem[] = @$fioz[$eachuser['login']] . (($showUserNotes and isset($allUserNotes[$eachuser['login']]['note'])) ? wf_delimiter(0) . '( ' . $allUserNotes[$eachuser['login']]['note'] . ' )' . $allUserNotes[$eachuser['login']]['adcomment'] : '');

                        if ($showUserPhones) {
                            $jsonItem[] = $userPhones;
                        }

                        $jsonItem[] = $eachuser['IP'];

                        if ($showONUSignals) {
                            $jsonItem[] = $onuSignal;
                        }

                        if ($showWIFISignals) {
                            $jsonItem[] = $wifiSignal;
                        }

                        $jsonItem[] = $eachuser['Tariff'];
                        $jsonItem[] = $act;
                        if (!empty($onlineFlag)) {
                            $jsonItem[] = $onlineFlag;
                        }
                        $jsonItem[] = zb_TraffToGb($tinet);
                        $jsonItem[] = "" . round($eachuser['Cash'], 2);
                        $jsonItem[] = "" . round($eachuser['Credit'], 2);

                        if ($showLastFeeCharge) {
                            $jsonItem[] = $feeCharge;
                        }

                        $jsonAAData[] = $jsonItem;
                    }
                }
            }
        }
        /**
          Prevail, the time has come
          Crush the enemy, one by one
          Prevail, like a venomous snake
          Ready to strike and dominate
          Prevail, we conquer as one
          Pound the enemy, 'till it's done
          Prevail!
         */
        $result = array("aaData" => $jsonAAData);
        return(json_encode($result));
    }

// Ajax data source display
    if (isset($_GET['ajax'])) {
        if ($hp_mode) {
            //default rendering
            if ($hp_mode == 1) {
                die(zb_AjaxOnlineDataSourceSafe());
            }

            //fast with caching, for huge databases.
            if ($hp_mode == 2) {
                $defaultJsonCacheTime = 600;
                $onlineJsonCache = new UbillingCache();
                $fastJsonReply = $onlineJsonCache->getCallback('HPONLINEJSON', function () {
                    return (zb_AjaxOnlineDataSourceSafe());
                }, $defaultJsonCacheTime);
                die($fastJsonReply);
            }
        }
    }


    if (!$hp_mode) {
        show_warning(__('ONLINE_HP_MODE=0 no more supported. Use 1 - safe or 2 - fast for large databases modes.'));
    } else {
        show_window(__('Users online'), renderUserListContainer());
    }
} else {
    show_error(__('Access denied'));
}
?>
