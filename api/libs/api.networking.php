<?php
function multinet_show_available_networks() {
  global $ubillingConfig;
  $alter = $ubillingConfig->getAlter();
  // Выбираем все сети
  $query = "SELECT * from `networks`";
  $networks = simple_queryall($query);
  // Заголовок таблицы
  $cells = wf_TableCell(__('ID'));
  $cells .= wf_TableCell(__('First IP'));
  $cells .= wf_TableCell(__('Last IP'));
  $cells .= wf_TableCell(__('Network/CIDR'));
  $cells .= wf_TableCell(__('Network type'));
  if ( $alter['FREERADIUS_ENABLED'] )
    $cells .= wf_TableCell(__('Use Radius'));
  $cells .= wf_TableCell(__('Actions'));
  $rows = wf_TableRow($cells, 'row1');
  // Содержимое таблицы
  if ( !empty($networks) ) {
    foreach ( $networks as $network ) {
      $cells = wf_TableCell($network['id']);
      $cells .= wf_TableCell($network['startip']);
      $cells .= wf_TableCell($network['endip']);
      $cells .= wf_TableCell($network['desc']);
      $cells .= wf_TableCell($network['nettype']);
      if ( $alter['FREERADIUS_ENABLED'] )
        $cells .= wf_TableCell(web_bool_led($network['use_radius']));
      $actions  = wf_JSAlert('?module=multinet&deletenet=' . $network['id'], web_delete_icon(), 'Removing this may lead to irreparable results');
      $actions .= wf_JSAlert('?module=multinet&editnet=' . $network['id'], web_edit_icon(), 'Are you serious');
      if ( $alter['FREERADIUS_ENABLED'] && $network['use_radius'] )
        $actions .= wf_Link('?module=freeradius&netid=' . $network['id'], web_icon_freeradius('Set RADIUS-attributes'));
      $cells .= wf_TableCell($actions);
      $rows .= wf_TableRow($cells, 'row3');
    }
  }
  // Результат - таблица
  $result = wf_TableBody($rows, '100%', '0', 'sortable');
  // Отображаем результат
  show_window(__('Networks'), $result);
}

function multinet_show_neteditform($netid) {
    $netid=vf($netid);
    $altcfg=  rcms_parse_ini_file(CONFIG_PATH."alter.ini");
    $netdata=multinet_get_network_params($netid);
    
    $useRadArr=array('0'=>__('No'), '1'=>__('Yes'));
    
    $sup=  wf_tag('sup').'*'.wf_tag('sup', true);
    $inputs=  wf_HiddenInput('netedit', 'true');
    $inputs.= wf_TextInput('editstartip', __('First IP').$sup, $netdata['startip'], true, '20');
    $inputs.= wf_TextInput('editendip', __('Last IP').$sup, $netdata['endip'], true, '20');
    $inputs.= multinet_nettype_selector($netdata['nettype']).' '.__('Network type').  wf_tag('br');
    $inputs.= wf_TextInput('editdesc', __('Network/CIDR').$sup, $netdata['desc'], true, '20');
    if ($altcfg['FREERADIUS_ENABLED']) {
        $inputs.= wf_Selector('edituse_radius', $useRadArr, __('Use Radius'), $netdata['use_radius'], true);
    } else {
        $inputs.=wf_HiddenInput('edituse_radius', '0');
    }
    $inputs.= wf_Submit(__('Save'));
    
    $form=  wf_Form('', "POST", $inputs, 'glamour');
    
    $form.=wf_Link('?module=multinet', 'Back', true, 'ubButton');
    show_window(__('Edit'), $form);
}

function multinet_show_serviceeditform($serviceid) {
    $serviceid=vf($serviceid);
    $servicedata=multinet_get_service_params($serviceid);
    $form='
        <form action="" method="POST" class="glamour">
        <input type="hidden" name="serviceedit" value="true">
        '.multinet_network_selector($servicedata['netid']).' '.__('Service network').' <br>
        <input type="text" name="editservicename" size="15" value="'.$servicedata['desc'].'"> '.__('Service description').'<sup>*</sup> <br>
        <input type="submit" value="'.__('Save').'">
        </form>
         <div style="clear:both;"></div>
        ';
    $form.=wf_Link('?module=multinet', 'Back', true, 'ubButton');
    
    show_window(__('Edit'), $form);
}

function multinet_delete_host($ip) {
    //$ip=mysql_real_escape_string($ip);
    $query="DELETE from `nethosts` WHERE `ip`='".$ip."'";
    nr_query($query);
    log_register("DELETE MultiNetHost ".$ip);
}

function multinet_show_network_delete_form() {
    $allnets=multinet_get_all_networks();
    if (!empty ($allnets)) {
    $form='
        <form method="POST" action="" class="row3">
        <input type="hidden" name="deletenet" value="true">
        '.multinet_network_selector().'
        <input type="submit" value="'.__('Delete').'">
        </form>
        ';
    show_window(__('Delete network'), $form);
    }
}

function multinet_network_selector($currentnetid='') {
    $allnetworks=multinet_get_all_networks();
    $result='<select name="networkselect">';
    if (!empty ($allnetworks)) {
        foreach ($allnetworks as $io=>$eachnetwork) {
          if ($currentnetid==$eachnetwork['id']) {
              $flag='SELECTED';
          } else {
              $flag='';
          }
          $result.='<option value="'.$eachnetwork['id'].'" '.$flag.'>'.$eachnetwork['desc'].'</option>';
        }
    }
    $result.='</select>';
    return ($result);
}

function multinet_get_all_networks() {
    $query="SELECT * from `networks`";
    $result=simple_queryall($query);
    return($result);
}

function multinet_nettype_selector($curnettype='') {
    $params=array(
        'dhcpstatic'=>'DHCP static hosts',
        'dhcpdynamic'=>'DHCP dynamic hosts',
        'dhcp82'=>'DHCP option 82',
        'dhcp82_vpu'=>'DHCP option 82 + vlan per user', 
        'pppstatic'=>'PPP static network',
        'pppdynamic'=>'PPP dynamic network',
        'other'=>'Other type'
    );
    
     $result=  wf_Selector('nettypesel', $params, '', $curnettype, false);
     
     return ($result);
}

function multinet_show_networks_form() {
    $altcfg=  rcms_parse_ini_file(CONFIG_PATH."alter.ini");
    
    $useRadArr=array('0'=>__('No'), '1'=>__('Yes'));
    
    $sup=  wf_tag('sup').'*'.wf_tag('sup', true);
    $inputs=  wf_HiddenInput('addnet', 'true');
    $inputs.= wf_TextInput('firstip', __('First IP').$sup, '', true, '20');
    $inputs.= wf_TextInput('lastip', __('Last IP').$sup, '', true, '20');
    $inputs.= multinet_nettype_selector().' '.__('Network type').wf_tag('br');
    $inputs.= wf_TextInput('desc', __('Network/CIDR').$sup, '', true, '20');
    if ($altcfg['FREERADIUS_ENABLED']) {
    $inputs.= wf_Selector('use_radius', $useRadArr, __('Use Radius'), '', true);
    $inputs.= wf_tag('br');
    } else {
        $inputs.=wf_HiddenInput('use_radius', '0');
    }
    $inputs.= wf_Submit(__('Add'));
    $form=  wf_Form("", 'POST', $inputs, 'glamour');
    
    show_window(__('Add network'),$form);
}

function multinet_show_available_services() {
 $allservices=multinet_get_services();
  
  $tablecells=  wf_TableCell(__('ID'));
  $tablecells.=  wf_TableCell(__('Network'));
  $tablecells.=  wf_TableCell(__('Service name'));
  $tablecells.=  wf_TableCell(__('Actions'));
  $tablerows=  wf_TableRow($tablecells, 'row1');
  
 if (!empty ($allservices)) {
     foreach ($allservices as $io=>$eachservice) {
     $netdesc=multinet_get_network_params($eachservice['netid']);
     
     
    $tablecells=  wf_TableCell($eachservice['id']);
    $tablecells.=  wf_TableCell($netdesc['desc']);
    $tablecells.=  wf_TableCell($eachservice['desc']);
    
    $actionlinks=  wf_JSAlert('?module=multinet&deleteservice='.$eachservice['id'], web_delete_icon(), 'Removing this may lead to irreparable results');
    $actionlinks.=  wf_JSAlert('?module=multinet&editservice='.$eachservice['id'], web_edit_icon(), 'Are you serious');
    
    $tablecells.=  wf_TableCell($actionlinks);
    $tablerows.=  wf_TableRow($tablecells, 'row3');
     }
 }

 $result=  wf_TableBody($tablerows,'100%','0','sortable');
 show_window(__('Services'), $result);
}

function multinet_get_services() {
$query="SELECT * from `services` ORDER BY `id`";
$result=simple_queryall($query);
return ($result);
}

function multinet_show_service_delete_form() {
    $allservices=multinet_get_services();
    if (!empty ($allservices)) {
    $form='
        <form method="POST" action="" class="row3">
        <input type="hidden" name="servicedelete" value="true">
        '.multinet_service_selector().'
        <input type="submit" value="'.__('Delete').'">
        </form>
        ';
    show_window(__('Delete service'),$form);
    }
}

function multinet_service_selector() {
    $allservices=multinet_get_services();
    $result='<select name="serviceselect">';
    if (!empty ($allservices)) {
        foreach ($allservices as $io=>$eachservice) {
          $result.='<option value="'.$eachservice['id'].'">'.$eachservice['desc'].'</option>';
        }
    }
    $result.='</select>';
    return ($result);
}

function multinet_show_service_add_form() {
    $form='
        <form action="" method="POST" class="glamour">
        <input type="hidden" name="serviceadd" value="true">
        '.multinet_network_selector().' '.__('Service network').' <br>
        <input type="text" name="servicename" size="15"> '.__('Service description').'<sup>*</sup> <br>
        <input type="submit" value="'.__('Add').'">
        </form>
        <div style="clear:both;"></div>
        ';

    show_window(__('Add service'), $form);
}

function multinet_add_network($desc,$firstip,$lastip,$nettype,$use_radius) {
$desc=mysql_real_escape_string($desc);
$firstip=vf($firstip);
$lastip=vf($lastip);
$nettype=vf($nettype);
$query=" INSERT INTO `networks` (
    `id`,
    `desc`,
    `startip`,
    `endip`,
    `nettype`,
    `use_radius`
    )
    VALUES (
    NULL, '".$desc."', '".$firstip."', '".$lastip."', '".$nettype."', '".$use_radius."'
    )
    ";
nr_query($query);
log_register('ADD MultiNetNet `'.$desc.'`');
}

function multinet_network_is_used($network_id) {
    $network_id=vf($network_id,3);
    $query="SELECT * from `nethosts` WHERE `netid`='".$network_id."'";
    $allhosts=  simple_query($query);
    if (!empty($allhosts)) {
        return (true);
    } else {
        return (false);
    }
}

function multinet_delete_network($network_id) {
    $network_id=vf($network_id,3);
    $query="DELETE FROM `networks` WHERE `id`='".$network_id."'";
    nr_query($query);
    log_register('DELETE MultiNetNet ['.$network_id.']');
}

function multinet_add_service($net,$desc) {
    $query="INSERT INTO `services` (
        `id`,
        `netid`,
        `desc` )
        VALUES (
        NULL, '".$net."', '".$desc."'
        )
        ";
    nr_query($query);
    log_register('ADD MultiNetNetService '.$desc);
}

function multinet_get_network_params($network_id) {
    $query='SELECT * from `networks` WHERE `id`="'.$network_id.'"';
    $result=simple_query($query);
    return($result);
}

function multinet_get_service_params($serviceid) {
    $query='SELECT * from `services` WHERE `id`="'.$serviceid.'"';
    $result=simple_query($query);
    return($result);
}



function multinet_delete_service($service_id) {
    $service_id=vf($service_id,3);
    $query="DELETE FROM `services` WHERE `id`='".$service_id."'";
    nr_query($query);
    log_register('DELETE MultiNetService ['.$service_id.']');
}

function multinet_get_dhcp_networks() {
    $query="SELECT * from `networks` where `nettype` LIKE 'dhcp%'";
    $alldhcps=simple_queryall($query);
    return($alldhcps);
}


function dhcp_show_available_nets() {
    $query="SELECT * from `dhcp`";
    $allnets=simple_queryall($query);
    $result='<table width="100%"  class="sortable" border="0" class="sortable">';
    $result.='
            <tr class="row1">
                <td>
                ID
                </td>
                  <td>
                '.__('Network/CIDR').'
                </td>
                <td>
             '.__('DHCP custom subnet template').'
                </td>
                <td>
                '.__('DHCP config name').'
                </td>
                <td>
                '.__('Actions').'
                </td>
            </tr>
            ';
    if (!empty ($allnets)) {
        foreach ($allnets as $io=>$eachnet) {
        $netdata=multinet_get_network_params($eachnet['netid']);
        $result.='
            <tr class="row3">
                <td>
                '.$eachnet['id'].'
                </td>
                  <td>
                '.$netdata['desc'].'
                </td>
                  <td>
               '.  web_bool_led($eachnet['dhcpconfig']).' 
                </td> 
                <td>
                '.$eachnet['confname'].'
                </td>
                <td>
                '.  wf_JSAlert('?module=dhcp&delete='.$eachnet['id'],  web_delete_icon(), 'Removing this may lead to irreparable results').'
                <a href="?module=dhcp&edit='.$eachnet['id'].'">'.  web_edit_icon().'</a>
                </td>
            </tr>
            ';
        }
    }
    $result.='</table>';
    show_window(__('Available DHCP networks'), $result);
}

function dhcp_add_network($netid,$dhcpconfig,$dhcpconfname) {
  $netid=vf($netid);
  $dhcpconfig=mysql_real_escape_string($dhcpconfig);
  $dhcpconfname=vf($dhcpconfname);
  $dhcpconfname=trim($dhcpconfname);
  $query="
      INSERT INTO `dhcp` (
        `id` ,
        `netid` ,
        `dhcpconfig` ,
        `confname`
        )
        VALUES (
        NULL , '".$netid."', '".$dhcpconfig."', '".$dhcpconfname."'
        );
      ";
  nr_query($query);
  log_register("ADD DHCPNet ".$netid);
}

function dhcp_show_add_form() {
    $form='
        <form action="" method="POST">
        '.  multinet_network_selector().' '.__('Network').'<br>
        <input type="hidden" name="adddhcp">
        <input type="hidden" name="dhcpconfig"> <!-- '.__('DHCP config').'<br> -->
        <input type="text" name="dhcpconfname"> '.__('DHCP config name').'<br>
        <input type="submit" value="'.__('Save').'">
        </form>
        ';
    show_window(__('Add DHCP network'), $form);
}
function dhcp_get_data($dhcpid) {
    $query="SELECT * from `dhcp` where `id`='".$dhcpid."'";
    $result=simple_query($query);
    return($result);
}

function dhcp_get_data_by_netid($netid) {
    $query="SELECT * from `dhcp` where `netid`='".$netid."'";
    $result=simple_query($query);
    return($result);
}

 function dhcp_show_edit_form($dhcpid) {
        $dhcpid=vf($dhcpid);
        $dhcpnetdata=dhcp_get_data($dhcpid);
        $form='
            <form action="" method="POST">
            <input type="text" name="editdhcpconfname" value="'.$dhcpnetdata['confname'].'"> '.__('DHCP config name').' <br>
            '.__('DHCP custom subnet template').' <br>    
            <textarea name="editdhcpconfig" cols="50" rows="10">'.$dhcpnetdata['dhcpconfig'].'</textarea>   
            <br>    
            <input type="button" value="'.__('Cleanup').'" onclick="this.form.elements[\'editdhcpconfig\'].value=\'\'">
            <input type="submit" value="'.__('Save').'">    
            </form>
            ';
        $form.=wf_delimiter();
        $form.=wf_Link("?module=dhcp", __('Back'),false,'ubButton');
        show_window(__('Edit custom subnet template'),$form);
    }
    
    function dhcp_update_data($dhcpid,$dhcpconfname,$dhcpconfig) {
        $dhcpid=vf($dhcpid);
        $dhcpconfname=mysql_real_escape_string($dhcpconfname);
        $dhcpconfname=trim($dhcpconfname);
        $dhcpconfig=mysql_real_escape_string($dhcpconfig);
        $query="UPDATE `dhcp` SET `dhcpconfig` = '".$dhcpconfig."',
         `confname` = '".$dhcpconfname."' WHERE `id` ='".$dhcpid."';";
        nr_query($query);
        log_register("CHANGE DHCPNet ".$dhcpid);
    }
    
    function dhcp_delete_net($dhcpid) {
        $dhcpid=vf($dhcpid);
        $query="DELETE from `dhcp` WHERE `id`='".$dhcpid."'";
        nr_query($query);
        log_register("DELETE DHCPNet ".$dhcpid);
    }

function handle_dhcp_rebuild_static($netid,$confname) {
    $query="SELECT * from `nethosts` WHERE `netid`='".$netid."'";
    // check haz it .conf name or not?
   if (!empty($confname)) {
    $confpath='multinet/'.$confname;
    $allhosts=simple_queryall($query);
    $result='';
    if (!empty ($allhosts)) {
        foreach ($allhosts as $io=>$eachhost) {
        $dhcphostname='m'.  str_replace('.', 'x', $eachhost['ip']);
         $result.='
   host '.$dhcphostname.' {
   hardware ethernet '.$eachhost['mac'].';
   fixed-address '.$eachhost['ip'].';
   }'."\n";
        }
       
        file_put_contents($confpath, $result);
        //deb('REWRITED NOT EMPTY:'.$confpath);
    } else {
        file_put_contents($confpath, $result);
        //deb('REWRITED EMPTY:'.$confpath);
    }
    }
}

function handle_dhcp_rebuild_option82($netid,$confname) {
    $query="SELECT * from `nethosts` WHERE `netid`='".$netid."'";
   if (!empty($confname)) {
    $confpath='multinet/'.$confname;
    $allhosts=simple_queryall($query);
    $result='';
    if (!empty ($allhosts)) {
        foreach ($allhosts as $io=>$eachhost) {
        $dhcphostname='m'.  str_replace('.', 'x', $eachhost['ip']);
        $options=explode('|',$eachhost['option']);
        $customTemplate=  file_get_contents(CONFIG_PATH."dhcp/option82.template");
        if (empty($customTemplate)) {
       $customTemplate='
       class "{HOSTNAME}" {
        match if binary-to-ascii (16, 8, "", option agent.remote-id) = "{REMOTEID}" and binary-to-ascii (10, 8, "", option agent.circuit-id) = "{CIRCUITID}";
       }

        pool {
        range {IP};
        allow members of "{HOSTNAME}";
        }
        '."\n";
        }
        
        if (isset($options[1])) {
            $parseTemplate=$customTemplate;
            $parseTemplate=str_ireplace('{HOSTNAME}', $dhcphostname, $parseTemplate);
            $parseTemplate=str_ireplace('{REMOTEID}', $options[0], $parseTemplate);
            $parseTemplate=str_ireplace('{CIRCUITID}', $options[1], $parseTemplate);
            $parseTemplate=str_ireplace('{IP}', $eachhost['ip'], $parseTemplate);
            
          $result.=$parseTemplate;  
        }
        
        }
       
        file_put_contents($confpath, $result);
        //deb('REWRITED NOT EMPTY:'.$confpath);
    } else {
        file_put_contents($confpath, $result);
        //deb('REWRITED EMPTY:'.$confpath);
    }
    }
}


function handle_dhcp_rebuild_option82_vpu($netid,$confname) {
    $query="SELECT * from `nethosts` WHERE `netid`='".$netid."'";
    if (!empty($confname)) {
	$confpath='multinet/'.$confname;
	$allhosts=simple_queryall($query);
	$result='';
	if (!empty ($allhosts)) {
            foreach ($allhosts as $io=>$eachhost) {
		$login=UserGetLoginByIP($eachhost['ip']);
		$netid=GetNetidByIp($eachhost['ip']);
		$remote=GetTermRemoteByNetid($netid);
		$vlan=UserGetVlan($login);
		$dhcphostname='m'.  str_replace('.', 'x', $eachhost['ip']);
		$customTemplate=  file_get_contents(CONFIG_PATH."dhcp/option82_vpu.template");
		if(!empty ($vlan)) {
                    if (empty($customTemplate)) {
			$customTemplate='
class "{HOSTNAME}" { match if binary-to-ascii (16, 8, "", option agent.remote-id) = "{REMOTEID}" and binary-to-ascii(10, 16, "", substring(option agent.circuit-id,2,2)) = "{CIRCUITID}"; }
pool {
range {IP};
allow members of "{HOSTNAME}";
}
host {HOSTNAME} {
fixed-address {IP};
}
'."\n";
                    }
                    if (isset($vlan)) {
			$parseTemplate=$customTemplate;
			$parseTemplate=str_ireplace('{HOSTNAME}', $dhcphostname, $parseTemplate);
			$parseTemplate=str_ireplace('{CIRCUITID}', $vlan, $parseTemplate);
			$parseTemplate=str_ireplace('{IP}', $eachhost['ip'], $parseTemplate);
                        $parseTemplate=str_ireplace('{REMOTEID}', $remote, $parseTemplate);
			$result.=$parseTemplate;
                    }
		}
            }
            file_put_contents($confpath, $result);
				//deb('REWRITED NOT EMPTY:'.$confpath);
        } else {
                file_put_contents($confpath, $result);
				 //deb('REWRITED EMPTY:'.$confpath);
        }
    }
}

function handle_ppp_rebuild_static($netid) {
    $query="SELECT * from `nethosts` WHERE `netid`='".$netid."'";
    $confpath='multinet/ppp.'.$netid.'.static';
    $allhosts=simple_queryall($query);
    $result='';
    if (!empty ($allhosts)) {
        foreach ($allhosts as $io=>$eachhost) {
            $accdata_q="SELECT `login`,`Password` from `users` WHERE `IP`='".$eachhost['ip']."'";
            $accdata=simple_query($accdata_q);
            $result.=$accdata['login'].' '.$accdata['Password'].' '.$eachhost['ip']."\n";
        }
    }
    file_put_contents($confpath, $result);
}

function handle_ppp_rebuild_dynamic($netid) {
    $query="SELECT * from `nethosts` WHERE `netid`='".$netid."'";
    $confpath='multinet/ppp.'.$netid.'.dynamic';
    $allhosts=simple_queryall($query);
    $result='';
    if (!empty ($allhosts)) {
        foreach ($allhosts as $io=>$eachhost) {
            $accdata_q="SELECT `login`,`Password` from `users` WHERE `IP`='".$eachhost['ip']."'";
            $accdata=simple_query($accdata_q);
            $result.=$accdata['login'].' '.$accdata['Password']."\n";
        }
    }
    file_put_contents($confpath, $result);
}

 function multinet_ParseTemplate($templatebody,$templatedata) {
       foreach ($templatedata as $field=>$data) {
           $templatebody=str_ireplace($field, $data, $templatebody);
       }
       return($templatebody);
   }
   

   
function multinet_cidr2mask($mask_bits)
{
if($mask_bits > 31 || $mask_bits < 0) return("0.0.0.0");
$host_bits = 32-$mask_bits;
$num_hosts = pow(2,$host_bits)-1;
$netmask = ip2long("255.255.255.255")-$num_hosts;
return long2ip($netmask);
}

function multinet_rebuild_globalconf() {
    global $ubillingConfig;
    $altCfg=$ubillingConfig->getAlter();
    $global_template=file_get_contents("config/dhcp/global.template");
    $subnets_template=file_get_contents("config/dhcp/subnets.template");
    $alldhcpsubnets_q="SELECT `id`,`netid` from `dhcp` ORDER BY `id` ASC";
    $alldhcpsubnets=simple_queryall($alldhcpsubnets_q);
    $allMembers_q="SELECT `ip` from `nethosts` WHERE `option` != 'NULL'";
    $allMembers= simple_queryall($allMembers_q);
    $membersMacroContent='';
    $vlanMembersMacroContent='';
    
    if (!empty($allMembers)) {
        foreach ($allMembers as $ix=>$eachMember) {
            $memberClass='m'.str_replace('.', 'x', $eachMember['ip']);;
            $membersMacroContent.='deny members of "'.$memberClass.'";'."\n";
        }
    }
    
    if (isset($altCfg['VLANGEN_SUPPORT'])) {
        if ($altCfg['VLANGEN_SUPPORT']) {
            $vlanMembers_q="SELECT `ip` FROM `users` WHERE `login` IN(SELECT `login` FROM `vlanhosts`);";
            $allVlanMembers=  simple_queryall($vlanMembers_q);
            if (!empty($allVlanMembers)) {
                  foreach ($allVlanMembers as $ivl=>$eachVlanMember) {
                    $memberVlanClass='m'.str_replace('.', 'x', $eachVlanMember['ip']);;
                    $vlanMembersMacroContent.='deny members of "'.$memberVlanClass.'";'."\n";
                  }
            }
        }
    }
    
    
    
    $subnets='';
    if (!empty ($alldhcpsubnets)) {
        foreach ($alldhcpsubnets as $io=>$eachnet) {
            $netdata=multinet_get_network_params($eachnet['netid']);
            $templatedata['{STARTIP}']=$netdata['startip'];
            $templatedata['{ENDIP}']=$netdata['endip'];
            $templatedata['{CIDR}']=explode('/', $netdata['desc']);
            $templatedata['{NETWORK}']=$templatedata['{CIDR}'][0];
            $templatedata['{CIDR}']=$templatedata['{CIDR}'][1];
            $templatedata['{ROUTERS}']=int2ip(ip2int($templatedata['{STARTIP}'])+1);
            $templatedata['{MASK}']=multinet_cidr2mask($templatedata['{CIDR}']);
            $dhcpdata=dhcp_get_data_by_netid($eachnet['netid']);
            if (isset($dhcpdata['confname'])) {
            $templatedata['{HOSTS}']=$dhcpdata['confname'];
            // check if override?
            if (!empty ($dhcpdata['dhcpconfig'])) {
                $currentsubtpl=$dhcpdata['dhcpconfig'];
            } else {
                $currentsubtpl=$subnets_template;
            }
            $subnets.=multinet_ParseTemplate($currentsubtpl, $templatedata)."\n";
        }
        }
    }
    
    $globdata['{SUBNETS}']=$subnets;
    $globdata['{DENYMEMBERS}']=$membersMacroContent;
    $globdata['{DENYVLANGENMEMBERS}']=$vlanMembersMacroContent;
    $globconf=multinet_ParseTemplate($global_template,$globdata);
    file_write_contents("multinet/dhcpd.conf", $globconf);
}

function multinet_rebuild_all_handlers() {
    $allnets=multinet_get_all_networks();
    if (!empty ($allnets)) {
        foreach ($allnets as $io=>$eachnet) {
            if ($eachnet['nettype']=='dhcpstatic') {
               $dhcpdata=dhcp_get_data_by_netid($eachnet['id']);
               handle_dhcp_rebuild_static($eachnet['id'],$dhcpdata['confname']);
                //deb('REBUILD NETWORK:'.$eachnet['id'].'|'.$dhcpdata['confname']);
            }
            if ($eachnet['nettype']=='dhcp82') {
                $dhcpdata82=dhcp_get_data_by_netid($eachnet['id']);
                handle_dhcp_rebuild_option82($eachnet['id'], $dhcpdata82['confname']);
            }
            
            if ($eachnet['nettype']=='dhcp82_vpu') {
                $dhcpdata82_vpu=dhcp_get_data_by_netid($eachnet['id']);
                handle_dhcp_rebuild_option82_vpu($eachnet['id'], $dhcpdata82_vpu['confname']);
            }
            
             if ($eachnet['nettype']=='pppstatic') {
             handle_ppp_rebuild_static($eachnet['id']);
             }
             if ($eachnet['nettype']=='pppdynamic') {
             handle_ppp_rebuild_dynamic($eachnet['id']);
             }
        }
    }
    //rebuilding global conf 
    multinet_rebuild_globalconf();
    //restarting dhcpd
    multinet_RestartDhcp();
    //debarr(dhcp_get_data_by_netid(5));
    
}

function multinet_add_host($netid,$ip,$mac='NULL',$option='NULL') {
$query="
    INSERT INTO `nethosts` (
`id` ,
`ip` ,
`mac` ,
`netid` ,
`option`
)
VALUES (
NULL , '".$ip."', '".$mac."', '".$netid."', '".$option."'
);
";
nr_query($query);
log_register("ADD MultiNetHost ".$ip);
}

function multinet_change_mac($ip,$newmac) {
    $newmac=strtolower($newmac);
    $query="UPDATE `nethosts` SET `mac` = '".$newmac."' WHERE `ip` = '".$ip."' ;";
    nr_query($query);
    log_register("CHANGE MultiNetHostMac ".$ip." ".$newmac);
}

function multinet_expand_network($first_ip,$last_ip) {
  $first=ip2int($first_ip);
  $last=ip2int($last_ip);
  for ($i=$first;$i<=$last;$i++) {
   $totalnet[]=int2ip($i);
   }
   if (!empty ($totalnet)) {
      foreach ($totalnet as $eachip) {
        if (preg_match("#\.(0|1|255)$#", $eachip)) {
            //preg_match("#(0|1|255)$#", $eachip)
             unset ($eachip);
            }
            if (isset ($eachip)) {
                $filterednet[]=$eachip;
            }
      }
   }
  return($filterednet);
}

  function ip2int($src){
  $t = explode('.', $src);
  return count($t) != 4 ? 0 : 256 * (256 * ((float)$t[0] * 256 + (float)$t[1]) + (float)$t[2]) + (float)$t[3];
}

function int2ip($src){
  $s1 = (int)($src / 256);
  $i1 = $src - 256 * $s1;
  $src = (int)($s1 / 256);
  $i2 = $s1 - 256 * $src;
  $s1 = (int)($src / 256);
  return sprintf('%d.%d.%d.%d', $s1, $src - 256 * $s1, $i2, $i1);
}

function multinet_get_all_free_ip($table,$field,$network_id) {
$network_spec=multinet_get_network_params($network_id);
$first_ip=$network_spec['startip'];
$last_ip=$network_spec['endip'];
$clear_ips=array();
$full_network_pool=multinet_expand_network($first_ip, $last_ip);
$current_state_q='SELECT `'.$field.'` from `'.$table.'`';
$all_current_used_ip=simple_queryall($current_state_q);
if (!empty ($all_current_used_ip)) {
    foreach ($all_current_used_ip as $io=>$usedip) {
    $clear_ips[]=$usedip[$field];
    }
     $free_ip_pool=array_diff($full_network_pool,$clear_ips);
   } else {
     $free_ip_pool=$full_network_pool;
   }
  return($free_ip_pool);
}

function multinet_get_next_freeip($table,$field,$network_id) {
    $all_free_ips=multinet_get_all_free_ip($table, $field, $network_id);
    $temp = array_keys($all_free_ips);
    return(@$all_free_ips[$temp[0]]);
}

function multinet_get_service_networkid($service_id) {
    $service_id=vf($service_id);
    $query="SELECT `netid` from `services` WHERE `id`='".$service_id."'";
    $service_network=simple_query($query);
    $service_network=$service_network['netid'];
    return($service_network);
}

function stg_convert_size($fs)
{
    global $ubillingConfig;
    $alter_conf= $ubillingConfig->getAlter();
    $traffsize=trim($alter_conf['TRAFFSIZE']);
    if ($traffsize=='float') {
     if ($fs >= (1073741824*1024)) 
      $fs = round($fs / (1073741824*1024) * 100) / 100 . " Tb";
     elseif ($fs >= 1073741824) 
      $fs = round($fs / 1073741824 * 100) / 100 . " Gb";
     elseif ($fs >= 1048576)
      $fs = round($fs / 1048576 * 100) / 100 . " Mb";
     elseif ($fs >= 1024)
      $fs = round($fs / 1024 * 100) / 100 . " Kb";
     else
      $fs = $fs . " b";
     return ($fs);
    }
    
    if ($traffsize=='b') {
       return ($fs);
    }
    
    if ($traffsize=='Kb') {
       $fs = round($fs / 1024 * 100) / 100 . " Kb";
       return ($fs);
    }
    
     if ($traffsize=='Mb') {
        $fs = round($fs / 1048576 * 100) / 100 . " Mb";
       return ($fs);
    }
     if ($traffsize=='Gb') {
        $fs = round($fs / 1073741824 * 100) / 100 . " Gb";
       return ($fs);
    }
    
     if ($traffsize=='Tb') {
      $fs = round($fs / (1073741824*1024) * 100) / 100 . " Tb";
       return ($fs);
    }
         
     
}

// convert to only Gb, speedup mode
function zb_TraffToGb($fs) {
    $fs = round($fs / 1073741824,2)." Gb";
    return ($fs);
}

/**
 * Returns array of available tariff speeds as tariffname=>array(speeddown/speedup)
 * 
 * @return array
 */
function zb_TariffGetAllSpeeds() {
        $query="SELECT * from `speeds`";
        $allspeeds=simple_queryall($query);
        $result=array();
        if (!empty ($allspeeds)) {
            foreach ($allspeeds as $io=>$eachspeed) {
                $result[$eachspeed['tariff']]['speeddown']=$eachspeed['speeddown'];
                $result[$eachspeed['tariff']]['speedup']=$eachspeed['speedup'];
            }
            
        }
        return($result);
    }
    
 function zb_TariffCreateSpeed($tariff,$speeddown,$speedup) {
     $tariff=mysql_real_escape_string($tariff);
     $speeddown=vf($speeddown);
     $speedup=vf($speedup);
     $query="INSERT INTO `speeds` (
    `id` ,
    `tariff` ,
    `speeddown` ,
    `speedup`
     )
        VALUES (
        NULL , '".$tariff."', '".$speeddown."', '".$speedup."'
        );";
     nr_query($query);
     log_register('CREATE TariffSpeed `'.$tariff.'` '.$speeddown.' '.$speedup);
 }
 
 function zb_TariffDeleteSpeed($tariff) {
     $tariff=mysql_real_escape_string($tariff);
     $query="DELETE from `speeds` where `tariff`='".$tariff."'";
     nr_query($query);
     log_register('DELETE TariffSpeed `'.$tariff.'`');
 }
 
function zb_TariffGetAllSignupPrices() {
    $query = "SELECT * FROM `signup_prices_tariffs`";
    $results = simple_queryall($query);
    $return = array();
    if ( !empty($results) ) {
        foreach ($results as $result) {
            $return[$result['tariff']] = $result['price'];
        }
    }
    return ($return);
}

function zb_TariffCreateSignupPrice($tariff, $price) {
    $query = "INSERT INTO `signup_prices_tariffs` (`tariff`, `price`) VALUES ('" . $tariff . "', '" . $price . "')";
    nr_query($query);
    log_register('CREATE TariffSignupPrice ' . $tariff . ' ' . $price);
}

function zb_TariffDeleteSignupPrice($tariff) {
    $query = "DELETE FROM `signup_prices_tariffs` WHERE `tariff` = '" . $tariff . "'";
    nr_query($query);
    log_register('DELETE TariffSignupPrice ' . $tariff);
}
 
 function zb_MultinetGetMAC($ip) {
     $query="SELECT `mac` from `nethosts` WHERE `ip`='".$ip."'";
     $result=simple_query($query);
     $result=$result['mac'];
     return($result);
 }
 
 function zb_UserGetIP($login) {
     $userdata=zb_UserGetStargazerData($login);
     $userip=$userdata['IP'];
     return ($userip);
 }
 
  function zb_DirectionsGetAll() {
        $query="SELECT * from `directions`";
        $allrules=simple_queryall($query);
        return ($allrules);
    }
    
     function zb_DirectionDelete($directionid) {
      $directionid=vf($directionid);
      $query="DELETE FROM `directions` WHERE `id`='".$directionid."'";
      nr_query($query);
      log_register("DELETE TrafficClass ".$directionid);
      rcms_redirect("?module=rules");
  }
  
  function zb_DirectionGetData($directionid) {
      $directionid=vf($directionid);
      $query="SELECT * from `directions` WHERE `id`='".$directionid."'";
      $data=simple_query($query);
      return($data);
  }
  
  function zb_DirectionAdd($rulenumber,$rulename) {
      $rulenumber=vf($rulenumber);
      $rulename=mysql_real_escape_string($rulename);
      $query="
          INSERT INTO `directions` (
                        `id` ,
                        `rulenumber` ,
                        `rulename`
                        )
                        VALUES (
                        NULL , '".$rulenumber."', '".$rulename."'
                        ); ";
      nr_query($query);
      log_register("ADD TrafficClass ".$rulenumber.' '.$rulename);
  }
 
     function zb_NasAdd($netid,$nasip,$nasname,$nastype,$bandw) {
        $netid=vf($netid);
        $nasname=mysql_real_escape_string($nasname);
        $nastype=vf($nastype);
        $bandw=mysql_real_escape_string($bandw);
        $nasip=mysql_real_escape_string($nasip);
        $query="
            INSERT INTO `nas` (
            `id` ,
            `netid` ,
            `nasip` ,
            `nasname` ,
            `nastype` ,
            `bandw`
            )
            VALUES (
            NULL ,
            '".$netid."',
            '".$nasip."',
            '".$nasname."',
            '".$nastype."',
            '".$bandw."'
           );";
        nr_query($query);
        log_register("NAS ADD".$nasip);
    }   
    
    function zb_NasGetAllData() {
        $query="SELECT * from `nas`";
        $allnas=simple_queryall($query);
        return($allnas);
    }
    
    function zb_NasGetData($nasid) {
        $nasid=vf($nasid);
        $query="SELECT * from `nas` WHERE `id`='".$nasid."'";
        $result=simple_query($query);
        return($result);
     }
    /**
      * Gets NAS'es IP-address, using id:
      * 
      * @param  integer $nasid  NAS'es id
      * @return string  
      */
    function zb_NasGetIpById($nasid) {
        $nasid = vf($nasid);
        $query = "SELECT `nasip` FROM `nas` WHERE `id` = '" . $nasid . "'";
        $result = simple_query($query);
        return ($result['nasip']);
     }
     
     /**
     * 
     * Decodes and unserializes data from base64 encoding
     * 
     * @param   int     $nasid  NAS'es id to update options
     * @param   array   $data   Options
     * @return  array           Options
     * 
     */
    function zb_NasOptionsGet($nasid) {
        $result = array();
        if ( !empty($nasid) ) {
            $query  = "SELECT `options` FROM `nas` WHERE `id` = " . $nasid . ";";
            $result = simple_queryall($query);
            if ( !empty($result) ) {
                foreach ( $result as $data ) {
                    $decoded = base64_decode($data['options']);
                    $result = unserialize($decoded);
                }
            }
        }
        return $result;
    }
    
     function zb_NasDelete($nasid) {
         $nasid=vf($nasid);
         $query="DELETE from `nas` WHERE `id`='".$nasid."'";
         nr_query($query);
         log_register("NAS DELETE".$nasid);
     }
     
     function zb_NasConfigSave() {
        $ub_conf=rcms_parse_ini_file(CONFIG_PATH."billing.ini");
        $query="SELECT * from `nas` WHERE `nastype`='rscriptd'";
        $result='';
        $allnas=simple_queryall($query);
        if (!empty ($allnas)) {
            foreach ($allnas as $io => $eachnas) {
                $net_q=multinet_get_network_params($eachnas['netid']);
                $net_cidr=$net_q['desc'];
                $result.=$net_cidr.' '.$eachnas['nasip']."\n";
            }
        }
        file_put_contents('remote_nas.conf', $result);
        
        if ($ub_conf['STGNASHUP']) {
            $sig_command=$ub_conf['SUDO'].' '.$ub_conf['KILL'].' -1'.' `'.$ub_conf['CAT'].' '.$ub_conf['STGPID'].'`';
            shell_exec($sig_command);
            log_register("SIGHUP STG ");
        }
    } 
     
     function multinet_RestartDhcp() {
       $config=rcms_parse_ini_file(CONFIG_PATH.'billing.ini');  
       $sudo=$config['SUDO'];
       $dhcpd=$config['RC_DHCPD'];
       $command=$sudo.' '.$dhcpd.' restart';
       shell_exec($command);
       log_register("RESTART DHCPD");
     }
     
      function zb_NasGetByNet($netid) {
        $netid=vf($netid);
        $query="SELECT `id` from `nas` WHERE `netid`='".$netid."'";
        $nasid=simple_query($query);
        $nasid=$nasid['id'];
        return($nasid);
    }
    
    function zb_NetworkGetByIp($ip) {
        $allnets=multinet_get_all_networks();
        $result=false;
        if (!empty ($allnets)) {
            foreach ($allnets as $io=>$eachnet) {
                $completenet=multinet_expand_network($eachnet['startip'], $eachnet['endip']);
                if (in_array($ip, $completenet,true)) {
                    $result=$eachnet['id'];
                    break;
                   } else {
                    $result=false;
                  }
            }
        }
        
        return($result);        
    }
    
    /**
     * Gets the Bandwidthd URL by user's IP address from database
     * 
     * @param   str     $ip     User's IP address
     * @return  str             Bandwidthd URL
     */
    function zb_BandwidthdGetUrl($ip) {
        $netid      = zb_NetworkGetByIp($ip);
        $nasid      = zb_NasGetByNet($netid);
        $nasdata    = zb_NasGetData($nasid);
        $bandwidthd_url = $nasdata['bandw'];
        
        if ( !empty($bandwidthd_url) ) {
            return $bandwidthd_url;
        } else return false;
    }

    /**
     * Generates ghaph images links:
     * 
     * @param   str    $ip      User's IP address, for whitch links are generated
     * @return  array           Graph links
     */
    function zb_BandwidthdGenLinks($ip) {
        $bandwidthd_url = zb_BandwidthdGetUrl($ip);
        $netid   = zb_NetworkGetByIp($ip);
        $nasid   = zb_NasGetByNet($netid);
        $nasdata = zb_NasGetData($nasid);
        $nastype = $nasdata['nastype'];
        
        // RouterOS graph model:
        if ( $nastype == 'mikrotik' ) {
            // Get user's IP array:
            $alluserips = zb_UserGetAllIPs();
            $alluserips = array_flip($alluserips);
            
            // Generate graphs paths:
            $urls['dayr']   = $bandwidthd_url . '/' . $alluserips[$ip] . '/daily.gif';
            $urls['days']   = null;
            $urls['weekr']  = $bandwidthd_url . '/' . $alluserips[$ip] . '/weekly.gif';
            $urls['weeks']  = null;
            $urls['monthr'] = $bandwidthd_url . '/' . $alluserips[$ip] . '/monthly.gif';
            $urls['months'] = null;
            $urls['yearr']  = $bandwidthd_url . '/' . $alluserips[$ip] . '/yearly.gif';
            $urls['years']  = null;
        } else {
            // Banwidthd graphs model:
            $urls['dayr']   = $bandwidthd_url . '/' . $ip . '-1-R.png';
            $urls['days']   = $bandwidthd_url . '/' . $ip . '-1-S.png';
            $urls['weekr']  = $bandwidthd_url . '/' . $ip . '-2-R.png';
            $urls['weeks']  = $bandwidthd_url . '/' . $ip . '-2-S.png';
            $urls['monthr'] = $bandwidthd_url . '/' . $ip . '-3-R.png';
            $urls['months'] = $bandwidthd_url . '/' . $ip . '-3-S.png';
            $urls['yearr']  = $bandwidthd_url . '/' . $ip . '-4-R.png';
            $urls['years']  = $bandwidthd_url . '/' . $ip . '-4-S.png';
        }
        
        return($urls);
    }
    
 function explodeRows($data) {
  $result = explode("\n", $data);
  return ($result);
}

function zb_NewMacShow() {
    global $ubillingConfig;
    $billing_config=$ubillingConfig->getBilling();
    $alter_config=$ubillingConfig->getAlter();
    $allarp=array();
    $sudo=$billing_config['SUDO'];
    $cat=$billing_config['CAT'];
    $grep=$billing_config['GREP'];
    $tail=$billing_config['TAIL'];
    $alter_conf=parse_ini_file(CONFIG_PATH.'alter.ini');
    $leases=$alter_conf['NMLEASES'];
    $leasemark=$alter_conf['NMLEASEMARK'];
    $command=$sudo.' '.$cat.' '.$leases.' | '.$grep.' "'.$leasemark.'" | '.$tail.' -n 200';
    $rawdata=shell_exec($command);
    $allusedMacs=  zb_getAllUsedMac();
    $result=''; 
    
//fdb cache preprocessing  
   $fdbData_raw=  rcms_scandir('./exports/', '*_fdb');
        if (!empty($fdbData_raw)) {
             $fdbArr= sn_SnmpParseFdbCacheArray($fdbData_raw);
             $fdbColumn=true;
            } else {
             $fdbArr= array();
             $fdbColumn=false;
            }
   
   $cells=  wf_TableCell(__('MAC'));
   if (!empty($fdbColumn)) {
       $cells.= wf_TableCell(__('Switch'));
   }
   if ($alter_config['MACVEN_ENABLED']) {
   $cells.= wf_TableCell(__('Manufacturer'));
   }
   $rows = wf_TableRow($cells, 'row1');
    
    if (!empty ($rawdata)) {
    $cleardata=exploderows($rawdata);
    foreach ($cleardata as $eachline) {
        preg_match('/[a-f0-9]{2}:[a-f0-9]{2}:[a-f0-9]{2}:[a-f0-9]{2}:[a-f0-9]{2}:[a-f0-9]{2}/i', $eachline, $matches);
        if (!empty ($matches)) {
            $allarp[]=$matches[0];
            }
        }
        $un_arr=array_unique($allarp);
         if (!empty ($un_arr)) {
             if ($alter_config['MACVEN_ENABLED']) {
             //adding ajax loader
              $result.=wf_AjaxLoader();
             }
             foreach ($un_arr as $io => $eachmac) {
                 if (zb_checkMacFree($eachmac,$allusedMacs)) {
                 $cells=  wf_TableCell(@$eachmac);
                  if (!empty($fdbColumn)) {
                    $cells.= wf_TableCell(sn_SnmpParseFdbExtract(@$fdbArr[$eachmac]));
                 }
                 
                 if ($alter_config['MACVEN_ENABLED']) {
                 $containerName='NMRSMCNT_'.  zb_rand_string(8);
                 $lookupVendorLink=  wf_AjaxLink('?module=macvendor&mac='.@$eachmac.'&raw=true', wf_img('skins/macven.gif', __('Device vendor')), $containerName, false, '');
                 $lookupVendorLink.= wf_tag('span', false, '', 'id="'.$containerName.'"').''.  wf_tag('span',true);
                 $cells.= wf_TableCell($lookupVendorLink,'350');
                 }
                 $rows.= wf_TableRow($cells, 'row3');
               
                 }
             }
         
        
        }
      }
      
    $result.=  wf_TableBody($rows,'100%', '0', 'sortable');
    
    
   return($result);
}

//check is mac unused?
    function multinet_mac_free($mac) {
    $query="SELECT `id` from `nethosts` WHERE `mac`='".$mac."'";
    $res=simple_query($query);
    if (!empty ($res)) {
        return(false);
    } else {
        return(true);
    }
   }

//get all used MAC addresses from database
   function zb_getAllUsedMac() {
       $query="SELECT `ip`,`mac` from `nethosts`";
       $all=  simple_queryall($query);
       $result=array();
       if (!empty($all)) {
           foreach ($all as $io=>$each) {
               $result[strtolower($each['mac'])]=$each['ip'];
           }
       }
       return ($result);
   }
   
   
//check is mac unused by full list
   function zb_checkMacFree($mac,$allused) {
       $mac=  strtolower($mac);
        if (isset($allused[$mac])) {
            return (false);
        } else {
            return (true);
        }
   }
   
//check mac for valid format   
function check_mac_format($mac) {
     $mask='/^[a-f0-9]{2}:[a-f0-9]{2}:[a-f0-9]{2}:[a-f0-9]{2}:[a-f0-9]{2}:[a-f0-9]{2}$/i';
    //really shitty mac
    if ($mac=='00:00:00:00:00:00') {
        return (false);
    } 
    
    if (preg_match($mask, $mac)) {
        return (true);
    } else {
        return (false);
    }
}   

//get all userips and netids
function zb_UserGetNetidsAll() {
    $query="SELECT * from `nethosts`";
    $result=array();
    $allhosts=simple_queryall($query);
    if (!empty ($allhosts)) {
        foreach ($allhosts as $io=>$eachhost) {
            $result[$eachhost['ip']]=$eachhost['netid'];
        }
    }
    return ($result);
}


function zb_StargazerSIGHUP() {
        $ub_conf=rcms_parse_ini_file(CONFIG_PATH."billing.ini");
        if ($ub_conf['STGNASHUP']) {
            $sig_command=$ub_conf['SUDO'].' '.$ub_conf['KILL'].' -1'.' `'.$ub_conf['CAT'].' '.$ub_conf['STGPID'].'`';
            shell_exec($sig_command);
            log_register("SIGHUP STG");
        }
    } 


function zb_ExtractIpAddress($data) {
  preg_match("/\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}/", $data, $matches);
  
  if (!empty($matches[0])) {
      return ($matches[0]);
  } else {
      return (false);
  }
}    
    
?>
