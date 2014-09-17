<?php

if ($system->checkForRight('ONLINE')) {
// HP mode
    $alter_conf = $ubillingConfig->getAlter();
    $hp_mode = $alter_conf['ONLINE_HP_MODE'];

    function stg_show_fulluserlist2() {
        global $alter_conf;
        $query = "SELECT * FROM `users`";
        $query_fio = "SELECT * from `realname`";
        $allusers = simple_queryall($query);
        $allfioz = simple_queryall($query_fio);
        $fioz = array();
        if (!empty($allfioz)) {
            foreach ($allfioz as $ia => $eachfio) {
                $fioz[$eachfio['login']] = $eachfio['realname'];
            }
        }

        $detect_address = zb_AddressGetFulladdresslist();
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
            $lat_col_head = '<td>' . __('LAT') . '</td>';
            $act_offset = 1;
        } else {
            $lat_col_head = '';
            $act_offset = 0;
        }
        //online stars
        if ($alter_conf['DN_ONLINE_DETECT']) {
            $true_online_header = '<td>' . __('Users online') . '</td>';
            $true_online_selector = ' col_' . (5 + $act_offset) . ': "select",';
        } else {
            $true_online_header = '';
            $true_online_selector = '';
        }
        //extended filters
        if ($alter_conf['ONLINE_FILTERS_EXT']) {
            $extfilters = ' <a href="javascript:showfilter();">' . __('Extended filters') . '</a>';
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
        $result.='<table width="100%" class="sortable" id="onlineusers">';
        $result.='
  <tr class="row1">
  <td>' . __('Full address') . '</td>
  <td>' . __('Real Name') . '</td>
  <td>IP</ip></td>
  <td>' . __('Tariff') . '</td>
  ' . $lat_col_head . '
  <td>' . __('Active') . '</td>
  ' . $true_online_header . '
  <td>' . __('Traffic') . '</td>
  <td>' . __('Balance') . '</td>
  <td>' . __('Credit') . '</td>
  
  </tr>';
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
                    $user_lat = '<td>' . date("Y-m-d H:i:s", $eachuser['LastActivityTime']) . '</td>';
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
                    $online_cell = '<td sorttable_customkey="' . $online_flag . '">' . web_bool_star($online_flag, true) . '</td>';
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
                        $corporate = '<a href="?module=corporate&userlink=' . $alllinkedusers[$eachuser['login']] . '">' . web_corporate_icon() . '</a>';
                    } else {
                        $corporate = '';
                    }

                    //is  user parent?
                    if (isset($allparentusers[$eachuser['login']])) {
                        $corporate = '<a href="?module=corporate&userlink=' . $allparentusers[$eachuser['login']] . '">' . web_corporate_icon('Corporate parent') . '</a>';
                    }
                } else {
                    $corporate = '';
                }

                //fast cash link
                if ($fastcash) {
                    $financelink = '<a href="?module=addcash&username=' . $eachuser['login'] . '#profileending"><img src="skins/icon_dollar.gif" border="0" title="' . __('Finance operations') . '"></a>';
                } else {
                    $financelink = '';
                }

                $result.='
        <tr class="row3" ' . $lighter . '>
         <td>
     <a href="?module=traffstats&username=' . $eachuser['login'] . '">' . web_stats_icon() . '</a>
     ' . $financelink . '         
     <a href="?module=userprofile&username=' . $eachuser['login'] . '">' . web_profile_icon() . '</a>
     
      ' . $corporate . '
         ' . @$detect_address[$eachuser['login']] . '</td>
         <td>' . @$fioz[$eachuser['login']] . '</td>
         <td sorttable_customkey="' . ip2int($eachuser['IP']) . '">' . $eachuser['IP'] . '</td>
         <td>' . $eachuser['Tariff'] . '</td>
         ' . $user_lat . '
         <td>' . $act . '</td>
         ' . $online_cell . '
         <td sorttable_customkey="' . $tinet . '">' . stg_convert_size($tinet) . '</td>
         <td>' . round($eachuser['Cash'], 2) . '</td>
         <td>' . round($eachuser['Credit'], 2) . '</td>

         </tr>
        ';
            }
        }
        
        
    if ( $alter_conf['DN_ONLINE_DETECT'] ) {
        $true_online_counter = '<td>' . __('Users online') . ' ' . $trueonline . '</td>';
    } else {
        $true_online_counter = null;
    }
    
        $result.='
    </table>
    <table width="100%">
    <tr class="row1">
         <td>' . __('Total') . ': ' . $ucount . '</td>
         <td>' . __('Active users') . ' ' . ($ucount - $inacacount) . ' / ' . __('Inactive users') . ' ' . $inacacount . '</td>
         ' . $true_online_counter . '
         <td>' . __('Traffic') . ': ' . stg_convert_size($totaltraff) . '</td>
         <td>' . __('Total') . ': ' . round($tcash, 2) . '</td>
         <td>' . __('Credit total') . ': ' . $tcredit . '</td>
         </tr>
        ';
        //extended filters again
        if ($alter_conf['ONLINE_FILTERS_EXT']) {
            $filtercode = '
            <script language="javascript" type="text/javascript">
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
        //]]>
        </script>';
        } else {
            $filtercode = '';
        }

        $result.='</table>' . $filtercode;
        return ($result);
    }

// hp mode 
    function stg_show_fulluserlist_hp() {
        global $alter_conf;
        $query = "SELECT * from `users`";
        $query_fio = "SELECT * from `realname`";
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
                        "sProcessing":   "' . __('Processing') . '..."
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
        "bStateSave": false,
        "iDisplayLength": 50,
        "sAjaxSource": \'?module=online&ajax\',
	"bDeferRender": true,
        "bJQueryUI": true

                } );
		} );
       
		</script>

       ';
        $result = $dtcode;
        $result.='<table width="100%" id="onlineusershp">';
        //dn activity check
        if ($alter_conf['DN_ONLINE_DETECT']) {
            $onlineCells = '<td>' . __('Users online') . '</td>';
        } else {
            $onlineCells = '';
        }
        $result.='
  <thead>
  <tr class="row2">
  <td>' . __('Full address') . '</td>
  <td>' . __('Real Name') . '</td>
  <td>' . __('IP') . '</ip></td>
  <td>' . __('Tariff') . '</td>
  <td>' . __('Active') . '</td>
  ' . $onlineCells . '
  <td>' . __('Traffic') . '</td>
  <td>' . __('Balance') . '</td>
  <td>' . __('Credit') . '</td>
  
  </tr>
  </thead>';

        $result.='
    </table>   
        ';




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




        $result = '{';
        $result.='
       "aaData": [
  ';
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

                if ($ucount < $totalusers) {
                    $ending = ',';
                } else {
                    $ending = '';
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
                    $result.='
     [
     "<a href=?module=traffstats&username=' . $eachuser['login'] . '><img src=skins/icon_stats.gif border=0 title=' . __('Stats') . '></a> <a href=?module=userprofile&username=' . $eachuser['login'] . '><img src=skins/icon_user.gif border=0 title=' . __('Profile') . '></a> ' . $fastcashlink . $clearuseraddress . '",
     
         "' . @mysql_real_escape_string(trim($fioz[$eachuser['login']])) . '",
         "' . $eachuser['IP'] . '",
         "' . $eachuser['Tariff'] . '",
         "' . $act . '",
         ' . $onlineFlag . '    
         "' . zb_TraffToGb($tinet) . '",
         "' . round($eachuser['Cash'], 2) . '",
         "' . round($eachuser['Credit'], 2) . '"
         ]' . $ending . '
        ';
                } else {
                    if (!isset($deadUsers[$eachuser['login']])) {
                        $result.='
                 [
                 "<a href=?module=traffstats&username=' . $eachuser['login'] . '><img src=skins/icon_stats.gif border=0 title=' . __('Stats') . '></a> <a href=?module=userprofile&username=' . $eachuser['login'] . '><img src=skins/icon_user.gif border=0 title=' . __('Profile') . '></a> ' . $fastcashlink . $clearuseraddress . '",

                     "' . @mysql_real_escape_string(trim($fioz[$eachuser['login']])) . '",
                     "' . $eachuser['IP'] . '",
                     "' . $eachuser['Tariff'] . '",
                     "' . $act . '",
                     ' . $onlineFlag . '   
                     "' . zb_TraffToGb($tinet) . '",
                     "' . round($eachuser['Cash'], 2) . '",
                     "' . round($eachuser['Credit'], 2) . '"
                     ]' . $ending . '
                    ';
                    }
                }
            }
        }



        $result.='
    
    ]
    }
        ';



        print($result);
        
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
        show_window(__('Users online'), stg_show_fulluserlist2());
    } else {
        show_window(__('Users online'), stg_show_fulluserlist_hp());
    }
} else show_error(__('Access denied'));
?>