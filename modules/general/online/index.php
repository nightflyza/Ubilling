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

        //alternate center styling
        $alternateStyle = '';
        if (isset($alter_conf['ONLINE_ALTERNATE_VIEW'])) {
            if ($alter_conf['ONLINE_ALTERNATE_VIEW']) {
                $alternateStyle = wf_tag('style', false) . '#onlineusershp  td { text-align:center !important; }' . wf_tag('style', true);
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
        $result.= wf_TableCell(__('Real Name'));
        $result.= wf_TableCell(__('IP'));
        $result.= wf_TableCell(__('Tariff'));
        $result.= wf_TableCell(__('Active'));
        $result.= $onlineCells;
        $result.= wf_TableCell(__('Traffic'));
        $result.= wf_TableCell(__('Balance'));
        $result.= wf_TableCell(__('Credit'));
        $result.= wf_tag('tr', true);
        $result.= wf_tag('thead', true);
        $result.= wf_tag('table', true);

        $result.= $alternateStyle;

        return ($result);
    }

    /**
     * Renders json data for large databases. Not using json_encode & manual json assembly to minimaze execution time.
     * 
     * @global array $alter_conf
     * 
     * @return string
     */
    function zb_AjaxOnlineDataSourceFast() {
        global $alter_conf;

        $query = "SELECT * from `users`";
        $query_fio = "SELECT * from `realname`";
        $allusers = simple_queryall($query);
        $allfioz = simple_queryall($query_fio);
        $fioz = zb_UserGetAllRealnames();
        $detect_address = zb_AddressGetFulladdresslistCached();
        $ucount = 0;
        $deadUsers = array();

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
     "<a href=?module=traffstats&username=' . $eachuser['login'] . '><img src=skins/icon_stats.gif border=0 title=' . __('Stats') . '></a> <a href=?module=userprofile&username=' . $eachuser['login'] . '><img src=skins/icon_user.gif border=0 title=' . __('Profile') . '></a> ' . $fastcashlink . $addrDelimiter . $clearuseraddress . '",
     
         "' . @mysql_real_escape_string(trim($fioz[$eachuser['login']])) . '",
         "' . $eachuser['IP'] . '",
         "' . $eachuser['Tariff'] . '",
         "' . $act . '",
         ' . $onlineFlag . '    
         "' . zb_TraffToGb($tinet) . '",
         "' . round($eachuser['Cash'], 2) . '",
         "' . round($eachuser['Credit'], 2) . '"
         ],';
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
                     ],';
                    }
                }
            }
        }

        $result = substr($result, 0, -1);


        $result.='
    
    ]
    }
        ';
        return($result);
    }

    /**
      Мир тебя не полюбил и не полюбит никогда
      И что бы ты не делал, ведь будет так всегда
     */

    /**
     * Renders json data for user list
     * 
     * @global array $alter_conf
     * 
     * @return string
     */
    function zb_AjaxOnlineDataSourceSafe() {

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
                    $onlineFlag = '<img src=skins/icon_nostar.gif> ' . __('No');
                    if (file_exists(DATA_PATH . 'dn/' . $eachuser['login'])) {
                        $onlineFlag = '<img src=skins/icon_star.gif> ' . __('Yes');
                    }
                } else {
                    $onlineFlag = '';
                }
                @$clearuseraddress = $detect_address[$eachuser['login']];

                //additional finance links
                if ($alter_conf['FAST_CASH_LINK']) {
                    $fastcashlink = ' <a href=?module=addcash&username=' . $eachuser['login'] . '#profileending><img src=skins/icon_dollar.gif border=0></a> ';
                } else {
                    $fastcashlink = '';
                }

                if (!$alter_conf['DEAD_HIDE']) {
                    $jsonItem = array();
                    $jsonItem[] = '<a href=?module=traffstats&username=' . $eachuser['login'] . '><img src=skins/icon_stats.gif border=0 title=' . __('Stats') . '></a> <a href=?module=userprofile&username=' . $eachuser['login'] . '><img src=skins/icon_user.gif border=0 title=' . __('Profile') . '></a> ' . $fastcashlink . $addrDelimiter . $clearuseraddress;
                    $jsonItem[] = @$fioz[$eachuser['login']];
                    $jsonItem[] = $eachuser['IP'];
                    $jsonItem[] = $eachuser['Tariff'];
                    $jsonItem[] = $act;
                    if (!empty($onlineFlag)) {
                        $jsonItem[] = $onlineFlag;
                    }
                    $jsonItem[] = zb_TraffToGb($tinet);
                    $jsonItem[] = "" . round($eachuser['Cash'], 2);
                    $jsonItem[] = "" . round($eachuser['Credit'], 2);
                    $jsonAAData[] = $jsonItem;
                } else {
                    if (!isset($deadUsers[$eachuser['login']])) {
                        $jsonItem = array();
                        $jsonItem[] = '<a href=?module=traffstats&username=' . $eachuser['login'] . '><img src=skins/icon_stats.gif border=0 title=' . __('Stats') . '></a> <a href=?module=userprofile&username=' . $eachuser['login'] . '><img src=skins/icon_user.gif border=0 title=' . __('Profile') . '></a> ' . $fastcashlink . $clearuseraddress;
                        $jsonItem[] = @$fioz[$eachuser['login']];
                        $jsonItem[] = $eachuser['IP'];
                        $jsonItem[] = $eachuser['Tariff'];
                        $jsonItem[] = $act;
                        if (!empty($onlineFlag)) {
                            $jsonItem[] = $onlineFlag;
                        }
                        $jsonItem[] = zb_TraffToGb($tinet);
                        $jsonItem[] = "" . round($eachuser['Cash'], 2);
                        $jsonItem[] = "" . round($eachuser['Credit'], 2);
                        $jsonAAData[] = $jsonItem;
                    }
                }
            }
        }
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
                    return (zb_AjaxOnlineDataSourceFast());
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
} else
    show_error(__('Access denied'));
?>