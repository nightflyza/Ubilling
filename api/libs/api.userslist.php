<?php

/**
 * Renders user list JQuery DataTables container
 *
 * @global array $altCfg
 *
 * @return string
 */
function web_OnlineRenderUserListContainer() {
    global $altCfg;
    $saveState = 'false';
    if (isset($altCfg['ONLINE_SAVE_STATE'])) {
        if ($altCfg['ONLINE_SAVE_STATE']) {
            $saveState = 'true';
        }
    }

    $hp_mode = $altCfg['ONLINE_HP_MODE'];

    $ShowContractField = false;
    if (isset($altCfg['ONLINE_SHOW_CONTRACT_FIELD']) && $altCfg['ONLINE_SHOW_CONTRACT_FIELD']) {
        $ShowContractField = true;
    }

    $showUserPhones = false;
    if (isset($altCfg['ONLINE_SHOW_PHONES']) && $altCfg['ONLINE_SHOW_PHONES']) {
        $showUserPhones = true;
    }

    $columnDefs = '';
    $showONUSignals = false;
    $showWIFISignals = false;

    if (
        isset($altCfg['PON_ENABLED']) && $altCfg['PON_ENABLED'] &&
        isset($altCfg['ONLINE_SHOW_ONU_SIGNALS']) && $altCfg['ONLINE_SHOW_ONU_SIGNALS']
    ) {
        $showONUSignals = true;
        $colNum1 = (($ShowContractField and $showUserPhones) ? '5' : (($ShowContractField xor $showUserPhones) ? '4' : '3'));

        $columnDefs .= '{"targets": ' . $colNum1 . ',
                                "render": function ( data, type, row ) {                                          
                                            var sigColor = \'#000\';
                                                                                                    
                                            if (data > 0 || data < -27) {
                                                sigColor = \'#ab0000\';
                                            } else if (data > -27 && data < -25) {
                                                sigColor = \'#FF5500\';
                                            } else if (data == \'Offline\') {
                                                sigColor = \'#6500FF\';
                                            } else {
                                                sigColor = \'#005502\';
                                            }
                                                                                                    
                                            return \'<span style="color:\' + sigColor + \'">\' + data + \'</span>\';
                                        }
                            } ';
    }

    if (
        isset($altCfg['MTSIGMON_ENABLED']) && $altCfg['MTSIGMON_ENABLED'] &&
        isset($altCfg['ONLINE_SHOW_WIFI_SIGNALS']) && $altCfg['ONLINE_SHOW_WIFI_SIGNALS']
    ) {
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
        $colNum2 = (($ShowContractField and $showUserPhones and $showONUSignals) ? '6' : ((($ShowContractField and $showUserPhones) xor ($showUserPhones and $showONUSignals) xor ($ShowContractField and $showONUSignals)) ? '5' : (($ShowContractField xor $showUserPhones xor $showONUSignals) ? '4' : '3')));

        $columnDefs .= (empty($columnDefs) ? '' : ', ');
        $columnDefs .= '{"targets": ' . $colNum2 . ',
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
    if (isset($altCfg['ONLINE_SHOW_LAST_FEECHARGE']) && $altCfg['ONLINE_SHOW_LAST_FEECHARGE']) {
        $showLastFeeCharge = true;
    }

    $columnDefs = '"columnDefs": [ ' . $columnDefs . '], ';

    //alternate center styling
    $alternateStyle = '';
    if (isset($altCfg['ONLINE_ALTERNATE_VIEW'])) {
        if ($altCfg['ONLINE_ALTERNATE_VIEW']) {
            $alternateStyle = wf_tag('style', false) . '#onlineusershp  td { text-align:center !important; }' . wf_tag('style', true);
        }
    }

    if ($altCfg['DN_ONLINE_DETECT']) {
        $columnFilters = '
             null, ' .
            (($hp_mode == 1 && $ShowContractField) ? 'null,' : '') .
            ' null, ' .
            (($hp_mode == 1 && $showUserPhones) ? 'null,' : '') .
            ' { "sType": "ip-address" }, ' .
            (($hp_mode == 1 && $showONUSignals) ? 'null, ' : '') .
            (($hp_mode == 1 && $showWIFISignals) ? 'null, ' : '') .
            ' null,
                null,
                null,
                { "sType": "file-size" },
                null,
                null ' .
            (($hp_mode == 1 && $showLastFeeCharge) ? ', null' : '');
    } else {
        $columnFilters = '
             null, ' .
            (($hp_mode == 1 && $ShowContractField) ? 'null,' : '') .
            ' null, ' .
            (($hp_mode == 1 && $showUserPhones) ? 'null,' : '') .
            ' { "sType": "ip-address" }, ' .
            (($hp_mode == 1 && $showONUSignals) ? 'null, ' : '') .
            (($hp_mode == 1 && $showWIFISignals) ? 'null, ' : '') .
            ' null,
                null,
                { "sType": "file-size" },
                null,                
                null ' .
            (($hp_mode == 1 && $showLastFeeCharge) ? ', null' : '');
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
        "sAjaxSource": \'?module=online&ajax=true\',
	"bDeferRender": true,
        "bJQueryUI": true,
        "pagingType": "full_numbers",
        "lengthMenu": [[10, 25, 50, 100, 200, -1], [10, 25, 50, 100, 200, "' . __('All') . '"]],
        "bStateSave": ' . $saveState . '

                } );
		} );

		</script>

       ';

    $customStyling = '
            <style>
            .dataTable tr {
               height: 33px; !important;
               min-height: 33px; !important;
            }

            .dataTable td th {
             vertical-align: middle; !important;
            }
            
            .dataTable img {
             vertical-align: middle; !important;
             width:15px; !important;
             display: inline; !important;
             float:left; !important;
            }
            </style>';

    $result = $dtcode;
    $result .= $customStyling;
    $result .= wf_tag('table', false, 'display compact', 'width="100%" id="onlineusershp"');
    //dn activity check
    if ($altCfg['DN_ONLINE_DETECT']) {
        $onlineCells = wf_TableCell(__('Users online'));
    } else {
        $onlineCells = '';
    }

    $result .= wf_tag('thead', false);
    $result .= wf_tag('tr', false, 'row2');
    $result .= wf_TableCell(__('Full address'));
    $result .= (($hp_mode == 1 && $ShowContractField) ? wf_TableCell(__('Contract')) : '');
    $result .= wf_TableCell(__('Real Name'));
    $result .= (($hp_mode == 1 && $showUserPhones) ? wf_TableCell(__("Phones")) : '');
    $result .= wf_TableCell(__('IP'));
    $result .= (($hp_mode == 1 && $showONUSignals) ? wf_TableCell(__("ONU Signal")) : '');
    $result .= (($hp_mode == 1 && $showWIFISignals) ? wf_TableCell(__("Signal") . ' WiFi') : '');
    $result .= wf_TableCell(__('Tariff'));
    $result .= wf_TableCell(__('Active'));
    $result .= $onlineCells;
    $result .= wf_TableCell(__('Traffic'));
    $result .= wf_TableCell(__('Balance'));
    $result .= wf_TableCell(__('Credit'));
    $result .= (($hp_mode == 1 && $showLastFeeCharge) ? wf_TableCell(__("Last fee charge")) : '');
    $result .= wf_tag('tr', true);
    $result .= wf_tag('thead', true);
    $result .= wf_tag('table', true);

    $result .= $alternateStyle;

    return ($result);
}

/**
 * Renders json data for user list. Manual HTML assebly instead of astral calls - for performance reasons.
 *
 * @global array $altCfg
 *
 * @return string
 */
function zb_AjaxOnlineDataSourceSafe() {
    global $altCfg;
    $ubCache = new UbillingCache();
    $ishimuraOption = MultiGen::OPTION_ISHIMURA;
    $ishimuraTable = MultiGen::NAS_ISHIMURA;
    $additionalTraffic = array();
    if (@$altCfg[$ishimuraOption]) {
        $query_hideki = "SELECT `login`,`D0`,`U0` from `" . $ishimuraTable . "` WHERE `month`='" . date("n") . "' AND `year`='" . curyear() . "'";
        $dataHideki = simple_queryall($query_hideki);
        if (!empty($dataHideki)) {
            foreach ($dataHideki as $io => $each) {
                $additionalTraffic[$each['login']] = $each['D0'] + $each['U0'];
            }
        }
    }

    if (@$altCfg[OphanimFlow::OPTION_ENABLED]) {
        $ophanimFlow = new OphanimFlow();
        $ophTraf = $ophanimFlow->getAllUsersAggrTraff();
        if (!empty($ophTraf)) {
            foreach ($ophTraf as $ophLogin => $ophBytes) {
                if (isset($additionalTraffic[$ophLogin])) {
                    $additionalTraffic[$ophLogin] += $ophBytes;
                } else {
                    $additionalTraffic[$ophLogin] = $ophBytes;
                }
            }
        }
    }

    $allcontracts = array();
    $allcontractdates = array();

    $ShowContractField = false;
    $ShowContractDate = false;
    if (isset($altCfg['ONLINE_SHOW_CONTRACT_FIELD']) && $altCfg['ONLINE_SHOW_CONTRACT_FIELD']) {
        $ShowContractField = true;

        if (isset($altCfg['ONLINE_SHOW_CONTRACT_DATE']) && $altCfg['ONLINE_SHOW_CONTRACT_DATE']) {
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

    // getting user notes and adcomments to show
    $showUserNotes = false;
    $adCommentsON = false;
    if (isset($altCfg['ONLINE_SHOW_USERNOTES']) && $altCfg['ONLINE_SHOW_USERNOTES']) {
        $showUserNotes = true;

        if (isset($altCfg['ADCOMMENTS_ENABLED']) && $altCfg['ADCOMMENTS_ENABLED']) {
            $adCommentsON = true;
            $adcomments = new ADcomments('USERNOTES');
        }

        // collecting user notes
        $query = "SELECT * from `notes`";
        $tmpUserNotes = simple_queryall($query);

        if (!empty($tmpUserNotes)) {
            foreach ($tmpUserNotes as $io => $eachUN) {
                $allUserNotes[$eachUN['login']]['note'] = (empty($eachUN['note'])) ? '' : '( ' . $eachUN['note'] . ' )';
                $allUserNotes[$eachUN['login']]['adcomment'] = '';
            }
        }

        // collecting user adcomments
        $allAdComments = array();

        if ($adCommentsON) {
            // getting all adcomments for USERNOTES scope
            $allAdComments = $adcomments->getScopeItemsCommentsAll();

            if (!empty($allAdComments)) {
                foreach ($allAdComments as $eachLogin => $eachData) {
                    $adCommentsCount = count($eachData);

                    if (!isset($allUserNotes[$eachLogin])) {
                        $allUserNotes[$eachLogin]['note'] = '';
                    }

                    if (empty($allUserNotes[$eachLogin]['note'])) {
                        $adCommentsLink = wf_nbsp() . wf_Link('?module=notesedit&username=' . $eachLogin, __('Additional comments') . ':' . wf_nbsp(2) . $adCommentsCount);
                    } else {
                        $adCommentsLink = wf_nbsp() . wf_Link('?module=notesedit&username=' . $eachLogin, wf_tag('sup') . $adCommentsCount . wf_tag('sup', true));
                    }

                    $allUserNotes[$eachLogin]['adcomment'] = $adCommentsLink;
                }
            }
        }
    }

    // get users's ONU and WIFI signal level
    $allONUSignals = array();
    $allWiFiSignals = array();
    $showONUSignals = false;
    $showWIFISignals = false;

    if (
        isset($altCfg['PON_ENABLED']) && $altCfg['PON_ENABLED'] &&
        isset($altCfg['ONLINE_SHOW_ONU_SIGNALS']) && $altCfg['ONLINE_SHOW_ONU_SIGNALS']
    ) {
        $showONUSignals = true;
        $allONUSignals = PONizer::getAllONUSignals();
    }

    if (
        isset($altCfg['MTSIGMON_ENABLED']) && $altCfg['MTSIGMON_ENABLED'] &&
        isset($altCfg['ONLINE_SHOW_WIFI_SIGNALS']) && $altCfg['ONLINE_SHOW_WIFI_SIGNALS']
    ) {
        $showWIFISignals = true;

        $WiFiSigmon = new MTsigmon();
        $allWiFiSignals = $WiFiSigmon->getAllWiFiSignals();
    }

    $allFees = array();
    $showLastFeeCharge = false;
    if (isset($altCfg['ONLINE_SHOW_LAST_FEECHARGE']) && $altCfg['ONLINE_SHOW_LAST_FEECHARGE']) {
        $showLastFeeCharge = true;
        $allFees = $ubCache->get('STG_LAST_FEE_CHARGE');

        // yep, just trying to sustain legacy
        if (empty($allFees)) {
            $allFees = $ubCache->get('STG_FEE_CHARGE');
        }
    }

    $showUserPhones = false;
    $allUserPhones = array();
    if (isset($altCfg['ONLINE_SHOW_PHONES']) && $altCfg['ONLINE_SHOW_PHONES']) {
        $showUserPhones = true;
        $allUserPhones = zb_GetAllOnlineTabPhones();
    }

    $query = "SELECT * FROM `users`";
    $allusers = simple_queryall($query);
    $allRealNames = zb_UserGetAllRealnames();
    $detect_address = zb_AddressGetFulladdresslist();
    $ucount = 0;
    $deadUsers = array();
    $displayFreezeFlag = (@$altCfg['ONLINE_SHOW_FREEZE']) ? true : false;

    //alternate view of online module
    $addrDelimiter = '';
    if (isset($altCfg['ONLINE_ALTERNATE_VIEW'])) {
        if ($altCfg['ONLINE_ALTERNATE_VIEW']) {
            $addrDelimiter = wf_tag('br');
        }
    }
    //hide dead users array
    if ($altCfg['DEAD_HIDE']) {
        if (!empty($altCfg['DEAD_TAGID'])) {
            $tagDead = vf($altCfg['DEAD_TAGID'], 3);
            $query_dead = "SELECT `login`,`tagid` from `tags` WHERE `tagid`='" . $tagDead . "'";
            $alldead = simple_queryall($query_dead);
            if (!empty($alldead)) {
                foreach ($alldead as $idead => $eachDead) {
                    $deadUsers[$eachDead['login']] = $eachDead['tagid'];
                }
            }
        }
    }
    //true-online detection
     $dnFlag=false;
     if ($altCfg['DN_ONLINE_DETECT']) {
        $allDnUsers=array();
        $dnFlag=true;
        $dnRaw= rcms_scandir(DATA_PATH . '/dn/');
        if (!empty($dnRaw)) {
            $allDnUsers = array_flip($dnRaw);
        }
     }


    $hidePictTitles = false;
    if (isset($altCfg['ONLINE_HIDE_PICT_TITLES']) && $altCfg['ONLINE_HIDE_PICT_TITLES']) {
        $hidePictTitles = true;
    }

    $jsonAAData = array();

    if (!empty($allusers)) {
        foreach ($allusers as $io => $eachuser) {
            $tinet = 0;
            $ucount++;
            for ($classcounter = 0; $classcounter <= 9; $classcounter++) {
                $dc = 'D' . $classcounter . '';
                $uc = 'U' . $classcounter . '';
                $tinet = $tinet + ($eachuser[$dc] + $eachuser[$uc]);
            }
            //ishimura and ophanim traffic mixing
            $currentAdditionalTraff = (isset($additionalTraffic[$eachuser['login']])) ? $additionalTraffic[$eachuser['login']] : 0;
            $tinet = $tinet + $currentAdditionalTraff;

            //activity led check
            $act = '<img src=skins/icon_inactive.gif>' . (($hidePictTitles) ? '' : __('No'));
              if ($eachuser['Passive'] == 1 or $eachuser['Down'] == 1) {
                $act = '<img src=skins/yellow_led.png>' . (($hidePictTitles) ? '' : __('No'));
            } else {
                if ($eachuser['Cash'] >= '-' . $eachuser['Credit']) {
                   $act = '<img src=skins/icon_active.gif>' . (($hidePictTitles) ? '' : __('Yes'));
                }
            }
        
            if ($displayFreezeFlag) {
                if (@$altCfg['ONLINE_SHOW_FREEZE_LAT']) {
                    $act .= $eachuser['Passive'] ? ' <img src=skins/icon_passive.gif>' . date('Y-m-d', $eachuser['LastActivityTime']) : '';
                } else {
                    $act .= $eachuser['Passive'] ? ' <img src=skins/icon_passive.gif>' . (($hidePictTitles) ? '' : __('Freezed')) : '';
                }
            }
            
            //dn online activity check
            if ($dnFlag) {
                $onlineFlag = '<img src=skins/icon_nostar.gif> ' . (($hidePictTitles) ? '' : __('No'));
                if (isset($allDnUsers[$eachuser['login']])) {
                    $onlineFlag = '<img src=skins/icon_star.gif> ' . (($hidePictTitles) ? '' : __('Yes'));
                }
            } else {
                $onlineFlag = '';
            }
            @$clearuseraddress = $detect_address[$eachuser['login']];

            //additional finance links
            if ($altCfg['FAST_CASH_LINK']) {
                $fastcashlink = ' <a href=?module=addcash&username=' . $eachuser['login'] . '#cashfield><img src=skins/icon_dollar_16.gif  title=' . __('Money') . ' border=0></a>&nbsp;';
            } else {
                $fastcashlink = '';
            }

            $onuSignal = '';
            $wifiSignal = '';
            $feeCharge = '';
            $userPhones = '';

            if ($showONUSignals and isset($allONUSignals[$eachuser['login']])) {
                $onuSignal = $allONUSignals[$eachuser['login']];
                $onuSignal = preg_replace("#[^a-z0-9A-Z\-_\.\/]#Uis", '', $onuSignal);
            }

            if ($showWIFISignals and isset($allWiFiSignals[$eachuser['login']])) {
                $wifiSignal = $allWiFiSignals[$eachuser['login']];
                $wifiSignal = preg_replace("#[^a-z0-9A-Z\-_\.\/]#Uis", '', $wifiSignal);
            }

            if ($showLastFeeCharge and isset($allFees[$eachuser['login']])) {
                // legacy, legacy, legacy, legacy...
                if (isset($allFees[$eachuser['login']]['balance_to']) and isset($allFees[$eachuser['login']]['balance_from'])) {
                    $feeCharge = $allFees[$eachuser['login']]['max_date'] . '<br />' . ($allFees[$eachuser['login']]['balance_to'] - $allFees[$eachuser['login']]['balance_from']);
                } else {
                    $feeCharge = $allFees[$eachuser['login']]['max_date'] . '<br />' . $allFees[$eachuser['login']]['summ'];
                }
            }

            if ($showUserPhones and isset($allUserPhones[$eachuser['login']])) {
                $userPhones = $allUserPhones[$eachuser['login']];
            }

            if (!$altCfg['DEAD_HIDE']) {
                $jsonItem = array();
                $jsonItem[] = '<a href=?module=traffstats&username=' . $eachuser['login'] . '><img src=skins/icon_stats_16.gif border=0 title=' . __('Stats') . '></a> <a href=?module=userprofile&username=' . $eachuser['login'] . '><img src=skins/icon_user_16.gif border=0 title=' . __('Profile') . '></a> ' . $fastcashlink . $addrDelimiter . $clearuseraddress;

                if ($ShowContractField) {
                    $jsonItem[] = @$allcontracts[$eachuser['login']] . (($ShowContractDate) ? wf_tag('br') . @$allcontractdates[$eachuser['login']] : '');
                }

                $jsonItem[] = @$allRealNames[$eachuser['login']] . (($showUserNotes and isset($allUserNotes[$eachuser['login']]['note'])) ? wf_delimiter(0) . $allUserNotes[$eachuser['login']]['note'] . $allUserNotes[$eachuser['login']]['adcomment'] : '');

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
                    $jsonItem[] = '<a href=?module=traffstats&username=' . $eachuser['login'] . '><img src=skins/icon_stats_16.gif border=0 title=' . __('Stats') . '></a> <a href=?module=userprofile&username=' . $eachuser['login'] . '><img src=skins/icon_user_16.gif border=0 title=' . __('Profile') . '></a> ' . $fastcashlink . $clearuseraddress;

                    if ($ShowContractField) {
                        $jsonItem[] = $allcontracts[$eachuser['login']] . (($ShowContractDate) ? wf_tag('br') . $allcontractdates[$eachuser['login']] : '');
                    }

                    $jsonItem[] = @$allRealNames[$eachuser['login']] . (($showUserNotes and isset($allUserNotes[$eachuser['login']]['note'])) ? wf_delimiter(0) . $allUserNotes[$eachuser['login']]['note'] . $allUserNotes[$eachuser['login']]['adcomment'] : '');

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
        Trollkatt ei sol har skapt
        Jager vaaret
        Oede vinters maaneskimm
        Kjoelner sitt laken saa doedt
        Dagen er kvelt
        Natter er vores igjen
        Og vanaere lurer
        Bak hvert kaldt tre
        Under de hatske fjells
        Storkronede tinder
    */
    $result = array("aaData" => $jsonAAData);
    return (json_encode($result));
}
