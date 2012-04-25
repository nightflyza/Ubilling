<?php
if(cfr('STGDOCSIS')) {
 $alter_config=rcms_parse_ini_file(CONFIG_PATH.'/alter.ini');
 $docsis_enable=$alter_config['DOCSIS_SUPPORT'];
 $docsis_modem_net=$alter_config['DOCSIS_MODEM_NETID'];
if ($docsis_enable) {
    
    //deprecated function from multinet
    function multinet_build_dhcpd_config($netid='') {
         multinet_rebuild_all_handlers();
         multinet_RestartDhcp();
    }

function docsis_add_new_modem($maclan,$macusb,$date,$ip,$template,$bind,$nic,$note) {
    global $docsis_modem_net;
        $query="
            INSERT INTO `modems` (
`id` ,
`maclan` ,
`macusb` ,
`date` ,
`ip` ,
`conftemplate` ,
`userbind` ,
`nic` ,
`note`
)
VALUES (
NULL , '".$maclan."', '".$macusb."', '".$date."', '".$ip."', '".$template."', '".$bind."', '".$nic."', '".$note."'
);
";
  nr_query($query);
  multinet_add_host($docsis_modem_net, $ip, $maclan, $nic);
  multinet_build_dhcpd_config($docsis_modem_net);
  stg_putlogevent('DOCSIS ADDMODEM '.$ip);
  }

  function docsis_modem_template_selector() {
    $query="SELECT * from `modem_templates`";
    $alltemplates=simple_queryall($query);
    $result='<select name="modemtemplate">';
    if (!empty ($alltemplates)) {
     foreach ($alltemplates as $io=>$eachtemplate) {
         $result.='<option value="'.$eachtemplate['name'].'">'.$eachtemplate['name'].'</option>';
     }
    }
    $result.='</select>';
    return($result);
}


function docsis_show_modem_add_form() {
    global $docsis_modem_net;
    $new_modem_ip=multinet_get_next_freeip('nethosts', 'ip', $docsis_modem_net);
    $curdate=date("Y-m-d");
    $new_modem_nic='m'.str_replace('.', '', $new_modem_ip);
    $new_maclan='00:'.rand(11,99).':'.rand(11,99).':'.rand(11,99).':'.rand(11,99).':'.rand(11,99);
    $new_macusb='00:'.rand(11,99).':'.rand(11,99).':'.rand(11,99).':'.rand(11,99).':'.rand(11,99);
    $form='
        <form method="POST" action="" class="row3">
        <input type="text" name="modemdate" size="16" value="'.$curdate.'"> '.__('Activation date').'<br><br>
        <input type="text" name="newmodemip" value="'.$new_modem_ip.'" size="16"> '.__('Modem IP').'<br><br>
        <input type="text" name="newmodemnic" value="'.$new_modem_nic.'" size="16"> '.__('Modem NIC').'<br><br>
        <input type="text" name="newmaclan" value="'.$new_maclan.'" size="16"> '.__('MAC Lan').'<br><br>
        <input type="text" name="newmacusb" value="'.$new_macusb.'" size="16"> '.__('MAC USB').'<br><br>
        <input type="text" name="newmodemnotes" value="" size="30"> '.__('Notes').'<br><br>
        '.docsis_modem_template_selector().' '.__('Modem config template').' <br><br>
        <input type="submit" value="'.__('Add modem').'"><br><br>
        <input type="hidden" name="reallyaddmodem" value="true">
        </form>
        ';

    show_window(__('Add new DOCSIS modem'),$form);
}

function docsis_get_modem_data_ip($modem_ip) {
    $query="SELECT * from `modems` WHERE `ip`='".$modem_ip."'";
    $result=simple_query($query);
    return ($result);
}


function docsis_get_modem_data_id($modem_id) {
    $query="SELECT * from `modems` WHERE `id`='".$modem_id."'";
    $result=simple_query($query);
    return ($result);
}


function docsis_modem_controls($modem_id) {
$controls='
<a href="?module=docsis&action=modemprofile&modemid='.$modem_id.'"><img src="skins/modems/link.gif" height="12" border="0" title="'.__('Edit').'"></a>
        ';
return($controls);
}

function docsis_show_list_modems($page=1,$order='id') {
    $perpage=100;
    $page=$page-1;
    $frompage=$page*$perpage;
    $paginator='';
    $pagertotal=simple_query("SELECT COUNT(`id`) from `modems`");
    $pagertotal=$pagertotal['COUNT(`id`)'];
    $query="SELECT * from `modems` ORDER BY `".$order."` LIMIT ".$frompage.",".$perpage;
    $allmodems=simple_queryall($query);
    $result='<table width="100%" border="0">';
    $result.='
             <tr class="row1">
             <td><a class="row4" href="?module=docsis&order=id">ID</a></td>
             <td><a class="row4" href="?module=docsis&order=nic">NIC</a></td>
             <td><a class="row4"  href="?module=docsis&order=ip">IP</a></td>
             <td><a class="row4"  href="?module=docsis&order=maclan">'.__('MAC Lan').'</a></td>
             <td><a class="row4"  href="?module=docsis&order=macusb">'.__('MAC USB').'</a></td>
             <td><a class="row4"  href="?module=docsis&order=date">'.__('Activation').'</a></td>
             <td><a class="row4"  href="?module=docsis&order=conftemplate">'.__('Config template').'</a></td>
             <td><a class="row4"  href="?module=docsis&order=userbind">'.__('Binded').'</a></td>
             <td><a class="row4"  href="?module=docsis&order=note">'.__('Notes').'</a></td>
             <td>'.__('Actions').'</td>
             </tr>
             ';
    if (!empty ($allmodems)) {
     foreach ($allmodems as $io=>$eachmodem) {
         if (!empty ($eachmodem['userbind'])) {
           $active='<img src="skins/icon_active.gif" border="0">';
         } else {
           $active='<img src="skins/icon_inactive.gif" border="0">';
         }
         $result.='
             <tr class="row3">
             <td>'.$eachmodem['id'].'</td>
             <td>'.$eachmodem['nic'].'</td>
             <td>'.$eachmodem['ip'].'</td>
             <td>'.$eachmodem['maclan'].'</td>
             <td>'.$eachmodem['macusb'].'</td>
             <td>'.$eachmodem['date'].'</td>
             <td>'.$eachmodem['conftemplate'].'</td>
             <td>'.$active.'</td>
             <td>'.$eachmodem['note'].'</td>
             <td>'.docsis_modem_controls($eachmodem['id']).'</td>
             </tr>
             ';
     }

     
     $paginator=rcms_pagination($pagertotal, $perpage, $page+1, '?module=docsis');
     }
    $result.='</table>';
    $result.=__('Total modems').' '.$pagertotal;
    $result.=$paginator;
    $result=$paginator.$result;
show_window(__('Available modems'), $result);
}

function docsis_show_controls() {
    $icon_path=CUR_SKIN_PATH.'taskbar/';
    $controls='
      <a href="?module=docsis&action=newmodem"><img src="skins/modems/add.gif" border="0" title="'.__('Add').'"></a><img src="'.$icon_path.'spacer.gif">
      <a href="?module=docsis"><img src="skins/modems/look.gif" border="0" title="'.__('View').'"></a><img src="'.$icon_path.'spacer.gif">
      <a href="?module=docsis&action=rebuildallconf"><img src="skins/modems/wrench.gif" border="0" title="'.__('Rebuild all configs').'"></a><img src="'.$icon_path.'spacer.gif">
      <a href="?module=docsis&action=templates"><img src="skins/modems/templates.gif" border="0" title="'.__('Config Templates').'"></a><img src="'.$icon_path.'spacer.gif">
      <center>
      <form action="?module=docsis&action=search" method="POST" class="row3">
      <input name="searchtype" value="ip" checked="checked" type="radio">IP
      <input name="searchtype" value="nic" type="radio"> NIC
      <input name="searchtype" value="maclan" type="radio"> '.__('MAC Lan').'
      <input name="searchtype" value="userbind" type="radio"> '.__('Binded').' 
      <input name="searchmodem" type="text" size="25"> <input type="submit" value="'.__('Search').'">
       </form>
      </center>
        ';
    show_window('',$controls);
}


function docsis_search_modem($field,$string) {
    $string=mysql_real_escape_string(trim($string));
    $query="SELECT * FROM `modems` WHERE `".$field."` LIKE '%".$string."'";
    $allresult=simple_queryall($query);
    $result='<table width="100%" border="0">';
    $result.='
             <tr class="row1">
             <td>ID</td>
             <td>NIC</td>
             <td>IP</td>
             <td>'.__('MAC Lan').'</td>
             <td>'.__('MAC USB').'</td>
             <td>'.__('Activation').'</td>
             <td>'.__('Config template').'</td>
             <td>'.__('Binded').'</td>
             <td>'.__('Notes').'</td>
             <td>'.__('Actions').'</td>
             </tr>
             ';
    if (!empty ($allresult)) {
     foreach ($allresult as $io=>$eachmodem) {
     $result.='
             <tr class="row3">
             <td>'.$eachmodem['id'].'</td>
             <td>'.$eachmodem['nic'].'</td>
             <td>'.$eachmodem['ip'].'</td>
             <td>'.$eachmodem['maclan'].'</td>
             <td>'.$eachmodem['macusb'].'</td>
             <td>'.$eachmodem['date'].'</td>
             <td>'.$eachmodem['conftemplate'].'</td>
             <td>'.$eachmodem['userbind'].'</td>
             <td>'.$eachmodem['note'].'</td>
             <td>'.docsis_modem_controls($eachmodem['id']).'</td>
             </tr>
             ';
     }
    }
    $result.='</table>';
    show_window(__('Search'),$result);
}


function docsis_show_modem_profile($modem_id) {
    $modem_data=docsis_get_modem_data_id($modem_id);
    
    $form='
         <form method="POST" action="" >
<table width="100%" border="0">
<tr class="row1">
<td>'.__('Parameter').'</td>
<td>'.__('Current value').'</td>
<td>'.__('Actions').'</td>
</tr>
<tr class="row3">
<td>ID</td>
<td>'.$modem_data['id'].'</td>
<td><input type="hidden" name="editmodemid" value="'.$modem_id.'"></td>
</tr>
<tr class="row3">
<td>'.__('Activation date').'</td>
<td>'.$modem_data['date'].'</td>
<td><input type="text" name="editdate" value="'.$modem_data['date'].'"></td>
</tr>
<tr class="row3">
<td>'.__('Modem NIC').'</td>
<td>'.$modem_data['nic'].'</td>
<td><input type="hidden" name="editnic" value="'.$modem_data['nic'].'">'.$modem_data['nic'].'</td>
</tr>
<tr class="row3">
<td>'.__('Mac Lan').'</td>
<td>'.$modem_data['maclan'].'</td>
<td><input type="text" name="editmaclan" value="'.$modem_data['maclan'].'"></td>
</tr>
<tr class="row3">
<td>'.__('Mac USB').'</td>
<td>'.$modem_data['macusb'].'</td>
<td><input type="text" name="editmacusb" value="'.$modem_data['macusb'].'"></td>
</tr>
<tr class="row3">
<td>'.__('IP').'</td>
<td>'.$modem_data['ip'].'</td>
<td><input type="hidden" name="editip" value="'.$modem_data['ip'].'">'.$modem_data['ip'].'</td>
</tr>
<tr class="row3">
<td>'.__('Modem config template').'</td>
<td>'.$modem_data['conftemplate'].'</td>
<td>'.docsis_modem_template_selector().'</td>
</tr>
<tr class="row3">
<td>'.__('Binded').'</td>
<td>'.$modem_data['userbind'].'</td>
<td><input type="text" name="edituserbind" value="'.$modem_data['userbind'].'"></td>
</tr>
<tr class="row3">
<td>'.__('Notes').'</td>
<td>'.$modem_data['note'].'</td>
<td><input type="text" name="editnotes" value="'.$modem_data['note'].'"></td>
</tr>
</table>
     <input type="submit" value="'.__('Change modem').'"><br><br>
      </form>
        ';
$form.='
    <form method="POST" action="?module=usersearch">
    <input type="hidden" name="searchpattern" value="'.$modem_data['userbind'].'">
    <input type="hidden" name="searchtype" value="searchip">
    <input type="submit" value="'.__('Go to modem user').'">
    </form>
    ';
   $form.='<a href="?module=docsis&action=modemdelete&modemid='.$modem_id.'"><img src="skins/modems/delete.gif"  align="right" border="0" title="'.__('Delete').'"></a>';
    show_window(__('Modem profile'),$form);
}

function docsis_change_modem_settings($modem_id,$maclan,$macusb,$ip,$date,$template,$bind,$note) {
    $cmodemdata=docsis_get_modem_data_id($modem_id);
    $cmodemoldlanmac=$cmodemdata['maclan'];
    $query="UPDATE `modems` SET
`maclan` = '".$maclan."',
`macusb` = '".$macusb."',
`date` = '".$date."',
`conftemplate` = '".$template."',
`userbind` = '".$bind."',
`note` = '".$note."'
 WHERE `id` =".$modem_id." LIMIT 1;";
    nr_query($query);
multinet_change_mac($cmodemoldlanmac, $maclan);
bill_execute('dhcpd_restart');
stg_putlogevent('DOCSIS CHANGEMODEM '.$ip);
}


function docsis_delete_modem($modem_id) {
    global $docsis_modem_net;
    $modem_data=docsis_get_modem_data_id($modem_id);
    $modem_ip=$modem_data['ip'];
    multinet_delete_host($modem_ip);
    multinet_build_dhcpd_config($docsis_modem_net);
    $query="DELETE FROM `modems` WHERE `id`='".$modem_id."'";
    nr_query($query);
    stg_putlogevent('DOCSIS DELMODEM '.$modem_ip);
    rcms_redirect('?module=docsis');
    }


    function docsis_get_all_modem_templates() {
        $query="SELECT * FROM `modem_templates`";
        $all_templates=simple_queryall($query);
        return ($all_templates);
    }

     function docsis_get_modem_template($template_name) {
        $query="SELECT * FROM `modem_templates` WHERE `name`='".$template_name."'";
        $template=simple_query($query);
        return ($template);
    }

    function docsis_show_templates_form() {
      $all_templates=docsis_get_all_modem_templates();
      $form='<table width="100%" border="0">';
      if (!empty ($all_templates)) {
       foreach ($all_templates as $io=>$eachtemplate) {
           $form.='
               <form method="POST" action="">
               <tr class="row1">
               <td><br><h2>'.__('Edit').' '.$eachtemplate['name'].'</h2><br></td>
               </tr>
               <input type="hidden" name="edittemplate" value="'.$eachtemplate['id'].'">
               <tr class="row2">
               <td><input type="text" name="edittemplatename" value="'.$eachtemplate['name'].'"></td>
               </tr>
               <tr class="row3">
               <td>
               <textarea name="edittemplatebody" cols="80" rows="10">'.$eachtemplate['body'].'</textarea><br>
               <input type="submit" value="'.__('Save').' '.$eachtemplate['name'].'"></form><br><br>
               <form action="" method="POST">
               <input type="hidden" name="deletetemplate" value="'.$eachtemplate['id'].'">
               <input type="submit" value="'.__('Delete').' '.$eachtemplate['name'].'">
               </form>
               </td>
               </tr>
               ';
       }
       }
      $form.='</table>';
      $form.='<table width="100%" border="0">
          <form method="POST" action="">
          <tr class="row1">
          <td>
          <input type="hidden" name="createtemplate" value="true">
          <h2>'.__('Add').'</h2>
          </td>
          </tr>
          <tr class="row3">
          <td><input type="text" name="newtemplatename"></td>
          </tr>
          <tr class="row3">
          <td><textarea name="newtemplatebody" cols="80" rows="10"></textarea><br>
          <input type="submit" value="'.__('Add').'">
          </td>
          </tr>
          </form>
          </table>
          ';
      
      show_window(__('Config Templates'),$form);
    }

    function docsis_create_new_template($name,$body) {
        $query="INSERT INTO `modem_templates` (
            `id`,`name`,`body`
            ) VALUES (
            NULL,
            '".$name."',
            '".$body."'
            );
            ";
        nr_query($query);
        stg_putlogevent('DOCSIS ADDTEMPLATE '.$name);
    }


    function docsis_delete_template($template_id) {
        $query="DELETE FROM `modem_templates` WHERE `id` ='".$template_id."'";
        nr_query($query);
        stg_putlogevent('DOCSIS DELTEMPLATE '.$template_id);
    }

    function docsis_change_template($template_id,$name,$body) {
        $query="UPDATE `modem_templates` set `name`='".$name."', `body`='".$body."' WHERE `id`='".$template_id."';";
        nr_query($query);
        stg_putlogevent('DOCSIS CHGTEMPLATE '.$name);
    }


    function docsis_parse_modem_template($modem_ip) {
        $modem_data=docsis_get_modem_data_ip($modem_ip);
        $config_template=$modem_data['conftemplate'];
        $template_body=docsis_get_modem_template($config_template);
        $template_body=$template_body['body'];
        $user_mac='';
        $user_ip='';
        if (isset($modem_data['userbind'])) {
          $user_ip=$modem_data['userbind'];
          $user_mac=zb_MultinetGetMAC($user_ip);
        }
        $template_body=str_replace('{USER_IP}', $user_ip, $template_body);
        $template_body=str_replace('{USER_MAC}', $user_mac, $template_body);
        return($template_body);
        
    }

    function docsis_save_modem_config($modem_ip) {
     global $alter_config;
     $cm_path=$alter_config['docsis_cm_source'];
     $modem_data=docsis_get_modem_data_ip($modem_ip);
     $config_name=$modem_data['nic'];
     $config_file=docsis_parse_modem_template($modem_ip);
     file_put_contents($cm_path.$config_name, $config_file);
    }

    function docsis_get_all_modems() {
        $query="SELECT * from `modems`";
        $allmodems=simple_queryall($query);
        return ($allmodems);
    }

    function docsis_compile_bin($nic) {
        global $alter_config;
        $cm_source=$alter_config['docsis_cm_source'].$nic;
        $cm_bin=$alter_config['docsis_cm_bin'].$nic.'.b';
        $cmd='./scripts/cm_compile '.$cm_source.' '.$cm_bin;
        shell_exec($cmd);
    }
    
    function docsis_rebuild_all_configs() {
        $allmmodems=docsis_get_all_modems();
        $result='<table width="100%" border="0">';
        if (!empty ($allmmodems)) {
            foreach ($allmmodems as $io=>$eachmodem) {
             docsis_save_modem_config($eachmodem['ip']);
             docsis_compile_bin($eachmodem['nic']);
             $result.='
                 <tr class="row3">
                 <td>'.$eachmodem['id'].'</td>
                 <td>'.$eachmodem['nic'].'</td>
                 <td>'.$eachmodem['ip'].'</td>
                 <td>'.$eachmodem['maclan'].'</td>
                 <td>'.$eachmodem['macusb'].'</td>
                 <td>'.$eachmodem['userbind'].'</td>
                 <td>'.$eachmodem['conftemplate'].'</td>
                 </tr>
                 ';
            }
        }
        $result.='</table>';
        show_window(__('All modem configs was rebuilded'),$result);
    }



if (isset($_POST['reallyaddmodem'])) {
$maclan=trim($_POST['newmaclan']);
$macusb=trim($_POST['newmacusb']);
$date=$_POST['modemdate'];
$ip=$_POST['newmodemip'];
$template=$_POST['modemtemplate'];
$nic=$_POST['newmodemnic'];
$note=mysql_real_escape_string(trim($_POST['newmodemnotes']));
$bind='';
docsis_add_new_modem($maclan, $macusb, $date, $ip, $template, $bind, $nic, $note);
docsis_save_modem_config($ip);
docsis_compile_bin($nic);
multinet_build_dhcpd_config($docsis_modem_net);
show_window(__('Result'),__('Modem added'));
}

if (isset($_POST['editmodemid'])) {
$modem_id=$_POST['editmodemid'];
$maclan=trim($_POST['editmaclan']);
$macusb=trim($_POST['editmacusb']);
$date=trim($_POST['editdate']);
$ip=trim($_POST['editip']);
$template=$_POST['modemtemplate'];
$nic=$_POST['editnic'];
$note=mysql_real_escape_string(trim($_POST['editnotes']));
$bind=$_POST['edituserbind'];
docsis_change_modem_settings($modem_id, $maclan, $macusb, $ip, $date, $template, $bind, $note);
docsis_save_modem_config($ip);
docsis_compile_bin($nic);
//multinet_build_dhcpd_config($docsis_modem_net);
multinet_rebuild_all_handlers();
multinet_RestartDhcp();
}

if (isset($_POST['createtemplate'])) {
docsis_create_new_template($_POST['newtemplatename'], $_POST['newtemplatebody']);
}

if (isset($_POST['deletetemplate'])) {
docsis_delete_template($_POST['deletetemplate']);
}

if (isset ($_POST['edittemplate'])) {
    docsis_change_template($_POST['edittemplate'], $_POST['edittemplatename'], $_POST['edittemplatebody']);
}

docsis_show_controls();

if (isset($_GET['action'])) {
if ($_GET['action']=='newmodem') {
    docsis_show_modem_add_form();
}
if ($_GET['action']=='modemprofile') {
    docsis_show_modem_profile($_GET['modemid']);
}
if ($_GET['action']=='modemdelete') {
   docsis_delete_modem($_GET['modemid']);
   }
if ($_GET['action']=='templates') {
    docsis_show_templates_form();
}

if ($_GET['action']=='rebuildallconf') {
    docsis_rebuild_all_configs();
    //multinet_build_dhcpd_config($docsis_modem_net);
    multinet_rebuild_all_handlers();
    multinet_RestartDhcp();
  }

if ($_GET['action']=='search') {
    docsis_search_modem($_POST['searchtype'], $_POST['searchmodem']);
}

} else {


if (isset ($_GET['order'])) {
    $order=$_GET['order'];
} else {
    $order='id';
}

if(isset($_GET['page'])) {
    $spage=$_GET['page'];
} else {
     $spage=1;
}
docsis_show_list_modems($spage,$order);
}
 
} else {
    show_window(__('Error'),__('Docsis support is not enabled'));
}
 
}
else {
	show_error(__('Access denied'));
}

?>