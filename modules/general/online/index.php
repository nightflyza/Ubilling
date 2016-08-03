<?php

if ($system->checkForRight('ONLINE')) {
// HP mode
    $alter_conf = $ubillingConfig->getAlter();
    $hp_mode = $alter_conf['ONLINE_HP_MODE'];

    function stg_show_fulluserlistOld() {
        global $alter_conf;
        $allusers = zb_UserGetAllStargazerData();
        $allrealnames = zb_UserGetAllRealnames();
        $alladdress = zb_AddressGetFulladdresslist();

        if ($alter_conf['USER_LINKING_ENABLED']) {
            $alllinkedusers = cu_GetAllLinkedUsers();
            $allparentusers = cu_GetAllParentUsers();
        }

        $totaltraff_i = 0;
        $totaltraff_m = 0;
        $totaltraff = 0;
        $ucount = 0;
        $trueonline = 0;
        $inacacount = 0;
        $tcredit = 0;
        $tcash = 0;

        // LAT column
        if ($alter_conf['ONLINE_LAT']) {
            $lat_col_head = wf_TableCell(__('LAT'));
            $act_offset = 1;
        } else {
            $lat_col_head = '';
            $act_offset = 0;
        }
        //online stars
        if ($alter_conf['DN_ONLINE_DETECT']) {
            $true_online_header = wf_TableCell(__('Users online'));
            $true_online_selector = ' col_' . (5 + $act_offset) . ': "select",';
        } else {
            $true_online_header = '';
            $true_online_selector = '';
        }
        //extended filters
        if ($alter_conf['ONLINE_FILTERS_EXT']) {
            $extfilters = wf_Link('javascript:showfilter();', __('Extended filters'), false);
        } else {
            $extfilters = '';
        }
        //additional finance links
        if ($alter_conf['FAST_CASH_LINK']) {
            $fastcash = true;
        } else {
            $fastcash = false;
        }



        $result = $extfilters;
        $result.= wf_tag('table', false, 'sortable', 'width="100%" id="onlineusers"');

        $headerCells = wf_TableCell(__('Full address'));
        $headerCells.= wf_TableCell(__('Real Name'));
        $headerCells.= wf_TableCell(__('IP'));
        $headerCells.= wf_TableCell(__('Tariff'));
        $headerCells.= $lat_col_head;
        $headerCells.= wf_TableCell(__('Active'));
        $headerCells.=$true_online_header;
        $headerCells.= wf_TableCell(__('Traffic'));
        $headerCells.= wf_TableCell(__('Balance'));
        $headerCells.= wf_TableCell(__('Credit'));
        $headerRow = wf_TableRow($headerCells, 'row1');
        $result.=$headerRow;

        if (!empty($allusers)) {
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
                $totaltraff = $totaltraff + $tinet;
                $tcredit = $tcredit + $credit;
                $tcash = $tcash + $cash;

                $act = web_green_led() . ' ' . __('Yes');
                //finance check
                if ($cash < '-' . $credit) {
                    $act = web_red_led() . ' ' . __('No');
                    $inacacount++;
                }

                if ($alter_conf['ONLINE_LAT']) {
                    $user_lat = wf_TableCell(date("Y-m-d H:i:s", $eachuser['LastActivityTime']));
                } else {
                    $user_lat = '';
                }

                //online check
                if ($alter_conf['DN_ONLINE_DETECT']) {
                    if (file_exists(DATA_PATH . 'dn/' . $eachuser['login'])) {
                        $online_flag = 1;
                        $trueonline++;
                    } else {
                        $online_flag = 0;
                    }
                    $online_cell = wf_TableCell(web_bool_star($online_flag, true), '', '', 'sorttable_customkey="' . $online_flag . '"');
                } else {
                    $online_cell = '';
                    $online_flag = 0;
                }

                if ($alter_conf['ONLINE_LIGHTER']) {
                    $lighter = 'onmouseover="this.className = \'row2\';" onmouseout="this.className = \'row3\';" ';
                } else {
                    $lighter = '';
                }

                //user linking indicator 
                if ($alter_conf['USER_LINKING_ENABLED']) {

                    //is user child? 
                    if (isset($alllinkedusers[$eachuser['login']])) {
                        $corporate = wf_Link('?module=corporate&userlink=' . $alllinkedusers[$eachuser['login']], web_corporate_icon(), false);
                    } else {
                        $corporate = '';
                    }

                    //is  user parent?
                    if (isset($allparentusers[$eachuser['login']])) {
                        $corporate = wf_Link('?module=corporate&userlink=' . $allparentusers[$eachuser['login']], web_corporate_icon('Corporate parent'), false);
                    }
                } else {
                    $corporate = '';
                }

                //fast cash link
                if ($fastcash) {
                    $financelink = wf_Link('?module=addcash&username=' . $eachuser['login'] . '#profileending', wf_img('skins/icon_dollar.gif', __('Finance operations')), false);
                } else {
                    $financelink = '';
                }


                $result.= wf_tag('tr', false, 'row3', $lighter);
                $result.= wf_tag('td', false);
                $result.= wf_Link('?module=traffstats&username=' . $eachuser['login'], web_stats_icon(), false);
                $result.= $financelink;
                $result.= wf_Link('?module=userprofile&username=' . $eachuser['login'], web_profile_icon(), false);
                $result.= $corporate;
                $result.= @$alladdress[$eachuser['login']];
                $result.= wf_tag('td', true);
                $result.= wf_TableCell(@$allrealnames[$eachuser['login']]);
                $result.= wf_TableCell($eachuser['IP'], '', '', 'sorttable_customkey="' . ip2int($eachuser['IP']) . '"');
                $result.= wf_TableCell($eachuser['Tariff']);
                $result.= $user_lat;
                $result.= wf_TableCell($act);
                $result.= $online_cell;
                $result.= wf_TableCell(stg_convert_size($tinet), '', '', 'sorttable_customkey="' . $tinet . '"');
                $result.= wf_TableCell(round($eachuser['Cash'], 2));
                $result.= wf_TableCell(round($eachuser['Credit'], 2));
                $result.= wf_tag('tr', true);
            }
        }


        if ($alter_conf['DN_ONLINE_DETECT']) {
            $true_online_counter = wf_TableCell(__('Users online') . ' ' . $trueonline);
        } else {
            $true_online_counter = null;
        }

        $result.= wf_tag('table', true);



        $footerCells = wf_TableCell(__('Total') . ': ' . $ucount);
        $footerCells.= wf_TableCell(__('Active users') . ' ' . ($ucount - $inacacount) . ' / ' . __('Inactive users') . ' ' . $inacacount);
        $footerCells.= $true_online_counter;
        $footerCells.= wf_TableCell(__('Traffic') . ': ' . stg_convert_size($totaltraff));
        $footerCells.= wf_TableCell(__('Total') . ': ' . round($tcash, 2));
        $footerCells.= wf_TableCell(__('Credit total') . ': ' . $tcredit);
        $footerRows = wf_TableRow($footerCells, 'row1');

        $result.= wf_TableBody($footerRows, '100%', '0');
        //extended filters again
        if ($alter_conf['ONLINE_FILTERS_EXT']) {
            $filtercode = wf_tag('script', false, '', 'language="javascript" type="text/javascript"');
            $filtercode.= '
            //<![CDATA[
            function showfilter() {
            var onlinefilters = {
		btn: false,
          	col_' . (4 + $act_offset) . ': "select",
               ' . $true_online_selector . '
		btn_text: ">"
               }
                setFilterGrid("onlineusers",0,onlinefilters);
             }
            //]]>';
            $filtercode.=wf_tag('script', true);
        } else {
            $filtercode = '';
        }

        $result.=$filtercode;
        return ($result);
    }

// hp mode 
    function stg_show_fulluserlist_hp() {
        global $alter_conf;
        $saveState='false';
        if (isset($alter_conf['ONLINE_SAVE_STATE'])) {
            if ($alter_conf['ONLINE_SAVE_STATE']) {
                $saveState='true';
            }
        }
        
        //alternate center styling
        $alternateStyle='';
        if (isset($alter_conf['ONLINE_ALTERNATE_VIEW'])) {
            if ($alter_conf['ONLINE_ALTERNATE_VIEW']) {
            $alternateStyle=wf_tag('style',false).'#onlineusershp  td { text-align:center !important; }'.wf_tag('style', true);
            }
        }
        
        if ($alter_conf['DN_ONLINE_DETECT']) {
            $columnFilters = '
             null,
                null,
                { "sType": "ip-address" },
                null,
                null,
                null,
                { "sType": "file-size" },
                null,
                null
            ';
        } else {
            $columnFilters = '
             null,
                null,
                { "sType": "ip-address" },
                null,
                null,
                { "sType": "file-size" },
                null,
                null
            ';
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
        "bStateSave": '.$saveState.'

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
   
        $result.= wf_tag('thead',false);
        $result.= wf_tag('tr',false,'row2');
        $result.= wf_TableCell(__('Full address'));
        $result.= wf_TableCell(__('Real Name'));
        $result.= wf_TableCell(__('IP'));
        $result.= wf_TableCell(__('Tariff'));
        $result.= wf_TableCell(__('Active'));
        $result.= $onlineCells;
        $result.= wf_TableCell(__('Traffic'));
        $result.= wf_TableCell(__('Balance'));
        $result.= wf_TableCell(__('Credit'));
        $result.= wf_tag('tr',true);
        $result.= wf_tag('thead',true);
        $result.= wf_tag('table',true);
        
        $result.= $alternateStyle;

        return ($result);
    }

    function zb_AjaxOnlineDataSource() {
        // Speed debug
//          $mtime = microtime();
//          $mtime = explode(" ",$mtime);
//          $mtime = $mtime[1] + $mtime[0];
//          $starttime = $mtime;
      
        global $alter_conf;
        $query = "SELECT * from `users`";
        $query_fio = "SELECT * from `realname`";
        $allusers = simple_queryall($query);
        $allfioz = simple_queryall($query_fio);
        $fioz = zb_UserGetAllRealnames();
        $detect_address = zb_AddressGetFulladdresslist();
        $ucount = 0;
        $deadUsers = array();
        
        //alternate view of online module
        $addrDelimiter='';
        if (isset($alter_conf['ONLINE_ALTERNATE_VIEW'])) {
            if ($alter_conf['ONLINE_ALTERNATE_VIEW']) {
                $addrDelimiter=  wf_tag('br');
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


                $act = '<img src=skins/icon_active.gif>' . __('Yes');
                //finance check
                if ($cash < '-' . $credit) {
                    $act = '<img src=skins/icon_inactive.gif>' . __('No');
                }

                //online activity check
                if ($alter_conf['DN_ONLINE_DETECT']) {
                    $onlineFlag = '"<img src=skins/icon_nostar.gif> ' . __('No') . '",';
                    if (file_exists(DATA_PATH . 'dn/' . $eachuser['login'])) {
                        $onlineFlag = '"<img src=skins/icon_star.gif> ' . __('Yes') . '",';
                    }
                } else {
                    $onlineFlag = '';
                }

                @$clearuseraddress = $detect_address[$eachuser['login']];
                $clearuseraddress = trim($clearuseraddress);
                $clearuseraddress = str_replace("'", '`', $clearuseraddress);
                $clearuseraddress = mysql_real_escape_string($clearuseraddress);

                //additional finance links
                if ($alter_conf['FAST_CASH_LINK']) {
                    $fastcashlink = ' <a href=?module=addcash&username=' . $eachuser['login'] . '#profileending><img src=skins/icon_dollar.gif border=0></a> ';
                } else {
                    $fastcashlink = '';
                }
		
                if (!$alter_conf['DEAD_HIDE']) {

               	$jsonItem = array();
		array_push($jsonItem, '<a href=?module=traffstats&username=' . $eachuser['login'] . '><img src=skins/icon_stats.gif border=0 title=' . __('Stats') . '></a> <a href=?module=userprofile&username=' . $eachuser['login'] . '><img src=skins/icon_user.gif border=0 title=' . __('Profile') . '></a> ' . $fastcashlink .$addrDelimiter. $clearuseraddress . '');
		array_push($jsonItem, mysql_real_escape_string(trim($fioz[$eachuser['login']])));
		array_push($jsonItem, $eachuser['IP']);
		array_push($jsonItem, $eachuser['Tariff']);
                array_push($jsonItem, $act);

		if( !empty($onlineFlag) ){
			array_push($jsonItem, $onlineFlag);
		}
		array_push($jsonItem, zb_TraffToGb($tinet));
		array_push($jsonItem, "".round($eachuser['Cash'], 2)."");
		array_push($jsonItem, "".round($eachuser['Credit'], 2)."");
		array_push($jsonAAData, $jsonItem);
                } else {
                    if (!isset($deadUsers[$eachuser['login']])) {
			$jsonItem = array();
		        array_push($jsonItem, '<a href=?module=traffstats&username=' . $eachuser['login'] . '><img src=skins/icon_stats.gif border=0 title=' . __('Stats') . '></a> <a href=?module=userprofile&username=' . $eachuser['login'] . '><img src=skins/icon_user.gif border=0 title=' . __('Profile') . '></a> ' . $fastcashlink . $clearuseraddress . '');
			array_push($jsonItem, mysql_real_escape_string(trim($fioz[$eachuser['login']])));
			array_push($jsonItem, $eachuser['IP']);
			array_push($jsonItem, $eachuser['Tariff']);
                        array_push($jsonItem, $act);

			if( !empty($onlineFlag) ){
				array_push($jsonItem, $onlineFlag);
                        }
			array_push($jsonItem, zb_TraffToGb($tinet));
			array_push($jsonItem, "".round($eachuser['Cash'], 2)."");
			array_push($jsonItem, "".round($eachuser['Credit'], 2)."");
	 		array_push($jsonAAData, $jsonItem);
                    }
                }
            }
        }

	$result = array("aaData" => $jsonAAData);
        print(json_encode($result));
        
//          $mtime = microtime();
//          $mtime = explode(" ",$mtime);
//          $mtime = $mtime[1] + $mtime[0];
//          $endtime = $mtime;
//          $totaltime = ($endtime - $starttime);
//          echo "This result generated in ".$totaltime." seconds";
        

        die();
    }

// Ajax data source display
    if (isset($_GET['ajax'])) {
        if ($hp_mode) {
            zb_AjaxOnlineDataSource();
        }
    }


    if (!$hp_mode) {
        show_window(__('Users online'), stg_show_fulluserlistOld());
    } else {
        show_window(__('Users online'), stg_show_fulluserlist_hp());
    }
} else show_error(__('Access denied'));
?>
