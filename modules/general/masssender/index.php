<?php
if(cfr('MASSSEND')) {
 
 $alter_conf=  rcms_parse_ini_file(CONFIG_PATH."alter.ini");
 set_time_limit(0);
 
 function ms_SendMessage($login,$message) {
   $globconf=parse_ini_file('config/billing.ini');
   $SGCONF=$globconf['SGCONF'];
   $STG_HOST=$globconf['STG_HOST'];
   $STG_PORT=$globconf['STG_PORT'];
   $STG_LOGIN=$globconf['STG_LOGIN'];
   $STG_PASSWD=$globconf['STG_PASSWD'];
   $configurator='LANG=ru_UA.utf8 '.$SGCONF.' set -s '.$STG_HOST.' -p '.$STG_PORT.' -a'.$STG_LOGIN.' -w'.$STG_PASSWD.' -u '.$login.' -m "'.$message.'"';
   shell_exec($configurator);
}


function ms_TicketCreate($from,$to,$text,$replyto='NULL',$admin='') {
    $from=loginDB_real_escape_string($from);
    $to=loginDB_real_escape_string($to);
    $admin=loginDB_real_escape_string($admin);
    $text=loginDB_real_escape_string(strip_tags($text));
    $date=curdatetime();
    $replyto=vf($replyto);
    $query="
        INSERT INTO `ticketing` (
    `id` ,
    `date` ,
    `replyid` ,
    `status` ,
    `from` ,
    `to` ,
    `text`,
    `admin`
        )
    VALUES (
    NULL ,
    '".$date."',
    ".$replyto.",
    '0',
    '".$from."',
    '".$to."',
    '".$text."',
    '".$admin."'
           );
        ";
    nr_query($query);
    
}

function ms_TicketSetDone($ticketid) {
    $ticketid=vf($ticketid);
    simple_update_field('ticketing', 'status', '1', "WHERE `id`='".$ticketid."'");
}
    
 function ms_MassSendMessage($users_arr,$message) {
     global $alter_conf;
     if (!empty($users_arr)) {
         foreach  ($users_arr as $eachuser) {
             if (!$alter_conf['MASSSEND_SAFE']) {
                 ms_SendMessage($eachuser, $message);
             } else {
                  ms_TicketCreate('NULL', $eachuser, $message,'NULL',whoami());
                  $newid=simple_get_lastid('ticketing');
                  ms_TicketSetDone($newid);
             }
                 
         }
         log_register("MASSEND (".sizeof($users_arr).")");   
     }
 }

 function ms_ShowForm() {
     $inputs=__('Message').'<br>';
     $inputs.=wf_TextArea('message', '', '', true, '60x6');
     $inputs.=wf_TextInput('exactuserlogins', 'Exact users, comma delimiter', '', true, '30');
     $inputs.=wf_RadioInput('sendtype', 'Exact users', 'exactusers', true,true);
     $inputs.=wf_RadioInput('sendtype', 'Debtors', 'debtors', true);
     $inputs.=wf_RadioInput('sendtype', 'All users', 'allusers', true);
     $inputs.=wf_Submit('Send');
     $form= wf_Form('', 'POST', $inputs, 'glamour');
     show_window(__('Masssender'),$form);
 }
 
 function ms_GetDebtors() {
     $query="SELECT `login` from `users` WHERE `Cash`<0";
     $result=array();
     $alldebtors=  simple_queryall($query);
     if (!empty($alldebtors)) {
         foreach ($alldebtors as $io=>$eachdebtor) {
             $result[]=$eachdebtor['login'];
         }
     }
     return($result);
 }
 
 function ms_GetAllusers() {
     $query="SELECT `login` from `users`";
     $result=array();
     $allusers=  simple_queryall($query);
     if (!empty($allusers)) {
         foreach ($allusers as $io=>$eachuser) {
             $result[]=$eachuser['login'];
         }
     }
     return($result);
 }
 
 function ms_GetExactUsers() {
     $result=array();
     if (wf_CheckPost(array('exactuserlogins'))) {
         $usersplit=explode(',',$_POST['exactuserlogins']);
         if (!empty($usersplit)) {
             foreach ($usersplit as $eachlogin) {
             $result[]=trim($eachlogin);
             }
         }
     }
     return($result);
 }
 

 if ($alter_conf['MASSSEND_ENABLED']) {
 //show form
 ms_ShowForm();
 //send messages if need
 if (wf_CheckPost(array('sendtype'))) {
     $sendtype=$_POST['sendtype'];
     if ($sendtype=='debtors') {
         $users_arr=  ms_GetDebtors();
     }
     if ($sendtype=='allusers') {
         $users_arr=  ms_GetAllusers();
     }
     if ($sendtype=='exactusers') {
         $users_arr=  ms_GetExactUsers();
     }
     
     if (wf_CheckPost(array('message'))) {
     ms_MassSendMessage($users_arr, $_POST['message']);
     }
 }
 
 } else {
     show_error(__('Disabled'));
 }
} else {
	show_error(__('Access denied'));
}

?>