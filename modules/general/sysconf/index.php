<?php
if (cfr('SYSCONF')) {

    $alterconf=rcms_parse_ini_file(CONFIG_PATH."alter.ini");
    
function web_SettingsBillingForm() {
$billing_config=rcms_parse_ini_file(CONFIG_PATH."billing.ini");
global $alterconf;
//hide passwords
if ($alterconf['PASSWORDSHIDE']) {
    $stg_passwd=__('Hidden');
} else {
    $stg_passwd=$billing_config['STG_PASSWD'];
}

$form='
<h2>'.__('Ubilling setup'). '</h2>
<input type="text" name="editbaseconf" value="'.$billing_config['baseconf'].'" readonly>  '.__('Stargazer interaction handler').' <br>
<input type="text" name="editSGCONF" value="'.$billing_config['SGCONF'].'" readonly> '.__('sgconf path').' <br>
<input type="text" name="editSGCONFXML" value="'.$billing_config['SGCONFXML'].'" readonly> '.__('sgconf_xml path').' <br>
<input type="text" name="editSTG_HOST" value="'.$billing_config['STG_HOST'].'" readonly> '.__('Stargazer host').' <br>
<input type="text" name="editSTG_PORT" value="'.$billing_config['STG_PORT'].'" readonly> '.__('Stargazer port').' <br>
<input type="text" name="editXMLRPC_PORT" value="'.$billing_config['XMLRPC_PORT'].'" readonly> '.__('XML RPC port').' <br>
<input type="text" name="editSTG_LOGIN" value="'.$billing_config['STG_LOGIN'].'" readonly> '.__('Stargazer admin login').' <br>
<input type="text" name="editSTG_PASSWD" value="'.$stg_passwd.'" readonly> '.__('Stargazer admin password').' <br>
    
<h3>'.__('External programs').'</h3>
<input type="text" name="editSUDO" value="'.$billing_config['SUDO'].'" readonly> '.__('sudo path').' <br>
<input type="text" name="editTOP" value="'.$billing_config['TOP'].'" readonly> '.__('batch top path').' <br>
<input type="text" name="editCAT" value="'.$billing_config['CAT'].'" readonly> '.__('cat path').' <br>
<input type="text" name="editGREP" value="'.$billing_config['GREP'].'" readonly> '.__('grep path').' <br>
<input type="text" name="editRC_DHCPD" value="'.$billing_config['RC_DHCPD'].'" readonly> '.__('isc-dhcpd init path').' <br>
<input type="text" name="editUPTIME" value="'.$billing_config['UPTIME'].'" readonly> '.__('uptime path').' <br>
<input type="text" name="editPING" value="'.$billing_config['PING'].'" readonly> '.__('ping path').' <br>
<input type="text" name="editKILL" value="'.$billing_config['KILL'].'" readonly> '.__('kill path').' <br>
<input type="text" name="editSTGPID" value="'.$billing_config['STGPID'].'" readonly> '.__('stargazer PID path').' <br>
'.web_bool_led($billing_config['STGNASHUP'],false).__('reload stargazer configuration on NAS operations').' <br>
<input type="text" name="editPHPSYSINFO" value="'.$billing_config['PHPSYSINFO'].'" readonly> '.__('PHPSysInfo path').' <br>
<input type="text" name="editTASKBAR_ICON_SIZE" value="'.$billing_config['TASKBAR_ICON_SIZE'].'" readonly> '.__('Taskbar module icon size').' <br>
'.web_bool_led($billing_config['REGRANDOM_MAC'],false).__('Register new users with random MAC').' <br>
'.web_bool_led($billing_config['REGALWONLINE'],false).__('Register new users with AlwaysOnline flag').' <br>
'.web_bool_led($billing_config['REGDISABLEDSTAT'],false).__('Register users with disabled detailed stats').' <br>

        ';
    return($form);
}

function web_SettingsAlterForm() {
    global $alterconf;
    $form=' <br> <br>
        <h2>'.__('Custom features').'</h2>
        '.web_bool_led($alterconf['SIMPLENEWMACSELECTOR']).' '.__('Simple selector in MAC change dialogue').' <br>
        '.web_bool_led($alterconf['CITY_DISPLAY']).' '.__('Display city name in address fields').' <br>
        '.web_bool_led($alterconf['ZERO_TOLERANCE']).' '.__('Use zero apartment number as private house').' <br>
        <input type="text" name="editNMLEASES" value="'.$alterconf['NMLEASES'].'" readonly> '.__('New MAC leases file').' <br>
        '.web_bool_led($alterconf['PROFILE_PLUGINS']).' '.__('Profile plugins loader').' <br>
        '.web_bool_led($alterconf['NMREP_INMACCHG']).' '.__('New MAC report in MAC edit dialogue').' <br>
        '.web_bool_led($alterconf['DOCSIS_SUPPORT']).' '.__('Enable DOCSIS support').' <br>
         <input type="text" name="editDOCSIS_MODEM_NETID" value="'.$alterconf['DOCSIS_MODEM_NETID'].'" readonly> '.__('DOCSIS modems network ID').' <br>
         <input type="text" name="editdocsis_cm_source" value="'.$alterconf['docsis_cm_source'].'" readonly> '.__('DOCSIS modems config source path').' <br>
         <input type="text" name="editdocsis_cm_bin" value="'.$alterconf['docsis_cm_bin'].'" readonly> '.__('DOCIS modems config binary path').' <br>
         <input type="text" name="editTRAFFSIZE" value="'.$alterconf['TRAFFSIZE'].'" readonly> '.__('Traffic size display').' <br>
         <input type="text" name="editSTRICT_CREDIT_LIMIT" value="'.$alterconf['STRICT_CREDIT_LIMIT'].'" readonly> '.__('Strict credit limit').' <br>
         <input type="text" name="editSTG_LOG_PATH" value="'.$alterconf['STG_LOG_PATH'].'" readonly> '.__('Path to stargazer log file').' <br> 
        '.web_bool_led($alterconf['TARIFFCHGRESET']).' '.__('User reset after tariff change').' <br>
         '.web_bool_led($alterconf['PASSWORDSHIDE']).' '.__('Hide user passwords').' <br>
         '.web_bool_led($alterconf['DN_ONLINE_DETECT']).' '.__('Online detection via /content/dn').' <br>
         '.web_bool_led($alterconf['TRANSLATE_PAYMENTS_NOTES']).' '.__('Translation of payment notes').' <br>
         '.web_bool_led($alterconf['HIGHLIGHT_IMPORTANT']).' '.__('Highlight important user fields in profile').' <br>
         '.web_bool_led($alterconf['NMCHANGE']).' '.__('Generate nmchange sript while view tariffs report').' <br>
         '.web_bool_led($alterconf['ONLINE_LIGHTER']).' '.__('Highlight rows in online module').' <br>
         '.web_bool_led($alterconf['ONLINE_FILTERS_EXT']).' '.__('Extended filters').' <br>
         '.web_bool_led($alterconf['STRICT_CONTRACTS_PROTECT']).' '.__('Dont delete contracts with user').' <br>
         '.web_bool_led($alterconf['STRICT_CONTRACTS_UNIQUE']).' '.__('Check contracts unique').' <br>
         '.web_bool_led($alterconf['CATV_ENABLED']).' '.__('CaTV accounting support').' <br>
         '.web_bool_led($alterconf['ONLINE_LAT']).' '.__('LAT column in online module').' <br>
         '.web_bool_led($alterconf['MASSSEND_ENABLED']).' '.__('Mass sender enabled').' <br>
         '.web_bool_led($alterconf['MASSSEND_SAFE']).' '.__('Mass sender use ticketing').' <br>
         '.web_bool_led($alterconf['SAFE_REGMODE']).' '.__('Safe user register mode').' <br>
         '.web_bool_led($alterconf['ARPDIAG_ENABLED']).' '.__('Enabled arpdiag module').' <br>
         <input type="text" value="'.$alterconf['ARPDIAG_LOG'].'" readonly> '.__('arpdiag log file').' <br>
         '.web_bool_led($alterconf['TB_ICONCUSTOMSIZE']).' '.__('Administrators can set the size of icons on their own').' <br>
         '.web_bool_led($alterconf['MACCHANGERANDOMDEFAULT']).' '.__('Substitute the random MAC in the change dialog').' <br>
         '.web_bool_led($alterconf['RESETONCFCHANGE']).' '.__('Reset users on custom field change').' <br>
         '.web_bool_led($alterconf['RESETONTAGCHANGE']).' '.__('Reset users on tag change').' <br>
         '.web_bool_led($alterconf['MTSIGMON_ENABLED']).' '.__('Mikrokik signal monitor enabled').' <br>
         '.web_bool_led($alterconf['SIGREQ_ENABLED']).' '.__('Signup requests service enabled').' <br>
         '.web_bool_led($alterconf['TB_NEWTICKETNOTIFY']).' '.__('Taskbar notify for new tickets').' <br>
         '.web_bool_led($alterconf['TB_SWITCHMON']).' '.__('Taskbar notify for dead switches').' <br>    
         <input type="text" name="editNMLEASEMARK" value="'.$alterconf['TICKETS_PERPAGE'].'" readonly>  '.__('Tickets per page in helpdesk').' <br>
         '.web_bool_led($alterconf['ONLINE_HP_MODE']).' '.__('High perfomance online module mode').' <br>
         '.web_bool_led($alterconf['FAST_CASH_LINK']).' '.__('Fast financial links in online and search modules').' <br>
         <input type="text" name="editNMLEASEMARK" value="'.$alterconf['NMLEASEMARK'].'" readonly> '.__('The criterion to search for new MAC').' <br> 
         <input type="text" name="editARPING" value="'.$alterconf['ARPING'].'" readonly> '.__('arping path').' <br> 
         <input type="text" name="editARPING_IFACE" value="'.$alterconf['ARPING_IFACE'].'" readonly> '.__('arping interface').' <br> 
         <input type="text" name="editNOBACKUPTABLESLIKE" value="'.$alterconf['NOBACKUPTABLESLIKE'].'" readonly> '.__('Mask for tables to skip in backup').' <br> 
         <input type="text" name="editSW_PINGTIMEOUT" value="'.$alterconf['SW_PINGTIMEOUT'].'" readonly> '.__('Switches ping cache timeout').' <br> 
         <h3>'.__('User linking').'</h3>
         '.web_bool_led($alterconf['USER_LINKING_ENABLED']).' '.__('User linking enabled').' <br>
         <input type="text" name="editUSER_LINKING_FIELD" value="'.$alterconf['USER_LINKING_FIELD'].'" readonly> '.__('User linking field').' <br>
         <input type="text" name="editUSER_LINKING_CFID" value="'.$alterconf['USER_LINKING_CFID'].'" readonly> '.__('User linking custom profile field ID').' <br>
        '.web_bool_led($alterconf['USER_LINKING_TARIFF']).' '.__('Merge linked users tariff').' <br>         
        '.web_bool_led($alterconf['USER_LINKING_CASH']).' '.__('Merge linked users cash').' <br>
        '.web_bool_led($alterconf['USER_LINKING_CREDIT']).' '.__('Merge linked users credit').' <br>
         <h3>'.__('Payments export').'</h3>
         '.web_bool_led($alterconf['AGENTS_ASSIGN']).' '.__('Assign agents with different streets').' <br>
         '.web_bool_led($alterconf['EXPORT_ENABLED']).' '.__('Payments export').' <br>
         '.web_bool_led($alterconf['EXPORT_ONLY_POSITIVE']).' '.__('Export only positive payments').' <br>
         <input type="text" name="editDEFAULT_ASSIGN_AGENT" value="'.$alterconf['DEFAULT_ASSIGN_AGENT'].'" readonly> '.__('Default agent assign').' <br>
         <input type="text" name="editEXPORT_FROM_TIME" value="'.$alterconf['EXPORT_FROM_TIME'].'" readonly> '.__('Export time begin').' <br>
         <input type="text" name="editEXPORT_TO_TIME" value="'.$alterconf['EXPORT_TO_TIME'].'" readonly> '.__('Export time end').' <br>
         <input type="text" name="editEXPORT_FORMAT" value="'.$alterconf['EXPORT_FORMAT'].'" readonly> '.__('Export format').' <br>
         <input type="text" name="editEXPORT_TEMPLATE" value="'.$alterconf['EXPORT_TEMPLATE'].'" readonly> '.__('Template').' <br>
         <input type="text" name="editEXPORT_TEMPLATE_HEAD" value="'.$alterconf['EXPORT_TEMPLATE_HEAD'].'" readonly> '.__('Template head').' <br>
         <input type="text" name="editEXPORT_TEMPLATE_END" value="'.$alterconf['EXPORT_TEMPLATE_END'].'" readonly> '.__('Template end').' <br>
         <input type="text" name="editEXPORT_ENCODING" value="'.$alterconf['EXPORT_ENCODING'].'" readonly> '.__('Export encoding').' <br>
         <input type="text" name="editIMPORT_ENCODING" value="'.$alterconf['IMPORT_ENCODING'].'" readonly> '.__('Import encoding').' <br>
         '.web_bool_led($alterconf['OPENPAYZ_SUPPORT']).' '.__('OpenPayz support').' <br>
         <input type="text" name="editOPENPAYZ_MANUAL" value="'.$alterconf['OPENPAYZ_MANUAL'].'" readonly> '.__('OpenPayz manual mode').' <br>
         <input type="text" name="editOPENPAYZ_CASHTYPEID" value="'.$alterconf['OPENPAYZ_CASHTYPEID'].'" readonly> '.__('OpenPayz cash type ID').' <br>
         <h3>'.__('Bank statements processing').'</h3>  
        '.web_bool_led($alterconf['BS_ENABLED']).' '.__('Bank statements support').' <br>
        <input type="text" value="'.$alterconf['BS_INCHARSET'].'" readonly> '.__('Import encoding').' <br>
        <input type="text" value="'.$alterconf['BS_OUTCHARSET'].'" readonly> '.__('Export encoding').' <br>
        <input type="text" value="'.$alterconf['BS_CASHTYPE'].'" readonly> '.__('Cash type').' <br>
        <input type="text" value="'.$alterconf['BS_OPTIONS'].'" readonly> '.__('Bank statements options').' <br>
        <h3>'.__('NDS processing').'</h3>      
        '.web_bool_led($alterconf['NDS_ENABLED']).' '.__('NDS processing support enabled').' <br>
        <input type="text" value="'.$alterconf['NDS_TAGID'].'" readonly> '.__('NDS tag ID').' <br>
        <input type="text" value="'.$alterconf['NDS_TAX_PERCENT'].'" readonly> '.__('NDS tax rate').' <br>
        ';
    return($form);
}

function web_SettingsMysqlForm() {
 $mysql_config=rcms_parse_ini_file(CONFIG_PATH."mysql.ini");
 global $alterconf;
 
 //hide mysql passwords
 if ($alterconf['PASSWORDSHIDE']) {
    $mysql_passwd=__('Hidden');
} else {
    $mysql_passwd=$mysql_config['password'];
}
 
 $form='
     <h2>'.__('Database setup'). '</h2>
     <form action="" method="POST">
     <input type="text" name="editserver" value="'.$mysql_config['server'].'" readonly> '.__('MySQL server hostname').' <br>
     <input type="text" name="editport" value="'.$mysql_config['port'].'" readonly> '.__('MySQL server port').' <br>
     <input type="text" name="editusername" value="'.$mysql_config['username'].'" readonly> '.__('MySQL user login').' <br>
     <input type="text" name="editpassword" value="'.$mysql_passwd.'" readonly> '.__('MySQL user password').' <br>
     <input type="text" name="editdb" value="'.$mysql_config['db'].'" readonly> '.__('MySQL database name').' <br>
     <input type="text" name="editcharacter" value="'.$mysql_config['character'].'" readonly> '.__('MySQL encoding').' <br>
     </form>';
        
        return($form);
    }
    
    
  function zb_SettingsSaveMysqlConfig($server,$port,$username,$password,$db,$character) {
      $filename=CONFIG_PATH."mysql.ini";
      $initemplate=';database host
server = "'.$server.'"
;database port
port = "'.$port.'"
;user login
username = "'.$username.'"
;user password
password = "'.$password.'"
;database name to use
db = "'.$db.'" 
character = "'.$character.'"
prefix = "billing"
';
      file_write_contents($filename, $initemplate);
      log_register("MYSQL settings changed");
  }
  
  if (isset($_POST['editserver'])) {
      $server=$_POST['editserver'];
      $port=$_POST['editport'];
      $username=$_POST['editusername'];
      $password=$_POST['editpassword'];
      $db=$_POST['editdb'];
      $character=$_POST['editcharacter'];
      //zb_SettingsSaveMysqlConfig($server, $port, $username, $password, $db, $character);
      //rcms_redirect("?module=sysconf");
      deb('Not implemented yet');
  }
    
$sysconfforms='
    <table width="100%" border="0" class="glamour">
    <tr>
       <td valign="top">'
       .  web_SettingsMysqlForm()
       .web_SettingsBillingForm().'
        </td>
        <td valign="top">
        '.web_SettingsAlterForm().'
        </td>
        </tr>
    </table>
    ';
show_window(__('System settings'),$sysconfforms);  
    
} else {
      show_error(__('You cant control this module'));
}

?>
