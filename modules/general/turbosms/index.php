<?php

if (cfr('TURBOSMS')) {
    set_time_limit(0);
    $altercfg = $ubillingConfig->getAlter();
    if ($altercfg['TSMS_ENABLED']) {
        
        //custom function to preload stargazer users data
        function tsms_UserGetAllStargazerData() {
            $query="SELECT * from `users`";
            $all=  simple_queryall($query);
            $result=array();
            if (!empty($all)) {
                foreach ($all as $io=>$each) {
                    $result[$each['login']]=$each;
                }
            }
            return ($result);
        }
        
        
       //get pay ID
       function tsms_PaymentIDGetAll() {
       global $altercfg;
       $result=array();
       if ($altercfg['OPENPAYZ_REALID']) {
       $query="SELECT `virtualid`,`realid` from `op_customers`;";
       $all=  simple_queryall($query);
           if (!empty($all)) {
               foreach ($all as $io=>$each) {
                   $result[$each['realid']]=$each['virtualid'];
               }
           }
       } else {
           //transform from IPs
           $query="SELECT `login`,`IP` from `users`";
           $all=  simple_queryall($query);
           if (!empty($all)) {
               foreach ($all as $io=>$each) {
                   $result[$each['login']]=  ip2int($each['IP']);
               }
           }

       }
       
       return ($result);
       }
       
        //convert realnames to translit
        function tsms_RealnamesTranslit($realnames) {
            $result=array();
            if (!empty($realnames)) {
                foreach ($realnames as $login=>$realname) {
                    $result[$login]= ucwords(zb_TranslitString($realname));
                }
            }
            return ($result);
        }
        
        //gets all mobile phones from userdatabase
        function tsms_GetAllMobileNumbers() {
            global $tsms_prefix;
            $query="SELECT `login`,`mobile` from `phones`";
            $all=  simple_queryall($query);
            $result=arraY();
            if (!empty($all)) {
                foreach ($all as $io=>$each) {
                    $result[$each['login']]=$tsms_prefix.$each['mobile'];
                }
            }
            return ($result);
        }
        
        //ugly hack to dirty input data filtering in php 5.4 with multiple DB links
        function tsms_SafeEscapeString($string) {
            @$result=preg_replace("#[~@\?\%\/\;=\*\>\<\"\']#Uis",'',$string);;
            return ($result);
        }
        
        /*
         * Connecting to TurboSMS service and loading all of needed data
         */
        //reading config
        $tsms_host = $altercfg['TSMS_GATEWAY'];
        $tsms_db = $altercfg['TSMS_DB'];
        $tsms_login = $altercfg['TSMS_LOGIN'];
        $tsms_password = $altercfg['TSMS_PASSWORD'];
        $tsms_table = $tsms_login;
        $tsms_prefix=$altercfg['TSMS_PHONEPREFIX'];
       //loading ubilling database
        $td_realnames=  zb_UserGetAllRealnames();
        $td_realnamestrans=  tsms_RealnamesTranslit($td_realnames);
        $td_tariffprices=  zb_TariffGetPricesAll();
        $td_tariffperiods= zb_TariffGetPeriodsAll();
        
        $td_curdate=  curdate();
        $td_users=  tsms_UserGetAllStargazerData();
        $td_mobiles=  tsms_GetAllMobileNumbers();
        $td_alladdress= zb_AddressGetFulladdresslistCached();
        $td_allpayids= tsms_PaymentIDGetAll();
     
        
        
        function tsms_query($query) {
            global $tsms_host,$tsms_db,$tsms_login,$tsms_password,$tsms_table;
            $TsmsDB = new DbConnect($tsms_host, $tsms_login, $tsms_password, $tsms_db, $error_reporting = true, $persistent = false);
            $TsmsDB->open() or die($TsmsDB->error());
            $result = array();
            $TsmsDB->query('SET NAMES utf8;');
            $TsmsDB->query($query);
            while ($row = $TsmsDB->fetchassoc()) {
                $result[] = $row;
            }
            $TsmsDB->close();
            return ($result);
        }
        
        function tsms_CheckMobile($num) {
            if (strlen($num)>11) {
                return (true);
            } else {
                return (false);
            }
        }
        
        function tsms_UserFilter($type,$params='') {
            global $td_mobiles,$td_users,$td_tariffprices,$td_tariffperiods;
            $result=array();
            
            //debtors filter
            if ($type=='msenddebtors') {
                if (!empty($td_users)) {
                foreach ($td_users as $login=>$data) {
                    @$userMobile=$td_mobiles[$login];
                    if (tsms_CheckMobile($userMobile)) {
                        if ($data['Cash']<0) {
                            $result[$login]=$userMobile;
                        }
                    }
                }
                }
            }
            
             //money less then 5 days 
             if ($type=='msendless5') {
                if (!empty($td_users)) {
                foreach ($td_users as $login=>$data) {
                    @$userMobile=$td_mobiles[$login];
                    if (tsms_CheckMobile($userMobile)) {
                        $userTariff=$data['Tariff'];
                        $userTariffPrice=$td_tariffprices[$userTariff];
                        $userTariffPeriod=$td_tariffperiods[$userTariff];
                        if ($userTariffPeriod=='month') {
                            $dayprice=$userTariffPrice/30;
                        } else {
                            $dayprice=$userTariffPrice;
                        }
                        $fiveDayPrice=$dayprice*5;
                        if (($data['Cash']<$fiveDayPrice) AND ($data['Cash']>=0)) {
                            $result[$login]=$userMobile;
                        }
                    }
                }
                }
            }
            
            //cash == 0
            if ($type=='msendzero') {
              if (!empty($td_users)) {
                foreach ($td_users as $login=>$data) {
                    @$userMobile=$td_mobiles[$login];
                    if (tsms_CheckMobile($userMobile)) {
                        if ($data['Cash']==0) {
                            $result[$login]=$userMobile;
                        }
                    }
                }
                }
            }
            
            //tariff = params
            if ($type=='msendtariff') {
               if (!empty($td_users)) {
                foreach ($td_users as $login=>$data) {
                    @$userMobile=$td_mobiles[$login];
                    if (tsms_CheckMobile($userMobile)) {
                        if ($data['Tariff']==$params) {
                            $result[$login]=$userMobile;
                        }
                    }
                }
                }
            }
            
            //all users with existing mobile phone
             if ($type=='msendall') {
                if (!empty($td_users)) {
                foreach ($td_users as $login=>$data) {
                    @$userMobile=$td_mobiles[$login];
                    if (tsms_CheckMobile($userMobile)) {
                            $result[$login]=$userMobile;
                    }
                }
                }
            }
            
            return ($result);
        }
        
        function tsms_GetExcludeUsers() {
            $result=array();
            $excludeRaw=  zb_StorageGet('TSMS_EXCLUDE');
            if (!empty($excludeRaw)) {
                //is some exclude users available
                $excludedUsers=  base64_decode($excludeRaw);
                $excludedUsers=  unserialize($excludedUsers);
                $result=$excludedUsers;
                
            } else {
                //first usage
                $newExcludeUsers=  serialize($result);
                $newExcludeUsers=  base64_encode($newExcludeUsers);
                zb_StorageSet('TSMS_EXCLUDE', $newExcludeUsers);
                log_register("TSMS EXCLUDE CREATE");
            }
            return ($result);
        }
        
        function tsms_ExcludeUserAdd($login) {
            $login=trim($login);
            $allExcludedUsers= tsms_GetExcludeUsers();
            $newExcludes=$allExcludedUsers;
            if (!isset($newExcludes[$login])) {
                $newExcludes[$login]='NOP';
                $newExcludes=  serialize($newExcludes);
                $newExcludes= base64_encode($newExcludes);
                zb_StorageSet('TSMS_EXCLUDE', $newExcludes);
                log_register("TSMS EXCLUDE ADD (".$login.")");
            }
        }
        
        function tsms_ExcludeUserDelete($login) {
            $allExcludedUsers= tsms_GetExcludeUsers();
            $newExcludes=$allExcludedUsers;
            if (isset($newExcludes[$login])) {
                unset($newExcludes[$login]);
                $newExcludes=  serialize($newExcludes);
                $newExcludes= base64_encode($newExcludes);
                zb_StorageSet('TSMS_EXCLUDE', $newExcludes);
                log_register("TSMS EXCLUDE DELETE (".$login.")");
            }
        }
        
        function web_TsmsMassendForm() {
            $tariffsRaw=  zb_TariffsGetAll();
            $alltariffs=array();
            if (!empty($tariffsRaw)) {
                foreach ($tariffsRaw as $io=>$each) {
                    $alltariffs[$each['name']]=$each['name'];
                }
            }
            
            $inputs=   wf_RadioInput('msendtype', __('Debtors with balance less then 0'), 'msenddebtors', true, true);
            $inputs.=  wf_RadioInput('msendtype', __('Users who have money left for 5 days'), 'msendless5', true, false);
            $inputs.=  wf_RadioInput('msendtype', __('Users who have zero balance'), 'msendzero', true, false);
            $inputs.=  wf_RadioInput('msendtype', __('All users with mobile'), 'msendall', true, false);
            $inputs.=  wf_RadioInput('msendtype', __('All users with tariff'), 'msendtariff', false, false);
            $inputs.= wf_Selector('msendtariffname', $alltariffs, '', '', true);
            $inputs.=  wf_Submit(__('Search'));
            $result=  wf_Form("", "POST", $inputs, 'glamour');
            return ($result);
        }
        
        
        function tsms_SendSMS($number,$sign,$message,$wappush,$timezone) {
            global $tsms_table;
            $number=   tsms_SafeEscapeString($number);
            $sign=     tsms_SafeEscapeString($sign);
            $message=  tsms_SafeEscapeString($message);
            $wappush=  tsms_SafeEscapeString($wappush);
           
            if ($wappush='NONE') {
                $wappush='';
            }
            
            //commented due fixing timezone issue
            //$date=date("Y-m-d H:i:s");
           
           $tz_offset=(2-$timezone)*3600;
           $date=date("Y-m-d H:i:s",time()+$tz_offset);
          
             $query="
                INSERT INTO `".$tsms_table."`
                    ( `number`, `sign`, `message`, `wappush`,  `send_time`) 
                    VALUES
                    ('".$number."', '".$sign."', '".$message."', '".$wappush."', '".$date."');
                ";
            
            tsms_query($query);
        }
        
        function tsms_GetAllSMS($where='') {
            global $tsms_table;
            $query="SELECT * from `".$tsms_table."`".$where;
            $result=  tsms_query($query);
            return ($result);
        }
        
        
        
        function web_TsmsShowAllSMS ($date='') {
            $date=  mysql_real_escape_string($date);
            if (!empty($date)) {
                $where="WHERE `send_time` LIKE '".$date."%'";
            } else {
                $where='';
            }
            $allSms=  tsms_GetAllSMS($where);
            
            $lighter='onmouseover="this.className = \'row2\';" onmouseout="this.className = \'row3\';" ';
            
            $cells=  wf_TableCell(__('ID'));
            $cells.= wf_TableCell(__('Msg ID'));
            $cells.= wf_TableCell(__('Mobile'));
            $cells.= wf_TableCell(__('Sign'));
            $cells.= wf_TableCell(__('Message'));
            $cells.= wf_TableCell(__('WAP'));
            $cells.= wf_TableCell(__('Cost'));
            $cells.= wf_TableCell(__('Send time'));
            $cells.= wf_TableCell(__('Sended'));
            $cells.= wf_TableCell(__('Status'));
            $rows=wf_TableRow($cells, 'row1');
            
            if (!empty($allSms)) {
                foreach ($allSms as $io=>$each) {
                        $cells=  wf_TableCell($each['id']);
                        $cells.= wf_TableCell($each['msg_id']);
                        $cells.= wf_TableCell($each['number']);
                        $cells.= wf_TableCell($each['sign']);
                        $msg=  wf_modal(__('Show'), __('SMS'), $each['message'], '', '300', '200');
                        $cells.= wf_TableCell($msg);
                        $cells.= wf_TableCell($each['wappush']);
                        $cells.= wf_TableCell($each['cost']);
                        $cells.= wf_TableCell($each['send_time']);
                        $cells.= wf_TableCell($each['sended']);
                        $cells.= wf_TableCell($each['status']);
                        $rows.=wf_tag('tr', false, 'row3', $lighter);
                        $rows.=$cells;
                        $rows.=wf_tag('tr', true);
                }
            }
            
            $result= wf_TableBody($rows, '100%', '0', 'sortable');
            return ($result);
        }
        
        function web_TsmsDateForm() {
            $inputs=__('By date').' '.wf_DatePickerPreset('showqueuedate', curdate());
            $inputs.=wf_Submit(__('Show'));
            $result=  wf_Form("", "POST", $inputs, 'glamour');
            return ($result);
        }
        
        function tsms_GetTemplate() {
            $template= zb_StorageGet('TSMS_TEMPLATE');
            if (empty($template)) {
                //create new one
                $template='Dorogoi abonent! U vas na s4etu ostalos {CASH} deneg!';
                zb_StorageSet('TSMS_TEMPLATE', $template);
                log_register("TSMS TEMPLATE CREATE");
            }
            return ($template);
        }
        
        function tsms_SetTemplate($template) {
            zb_StorageSet('TSMS_TEMPLATE', $template);
            log_register("TSMS TEMPLATE CHANGE");
        }
        
        function web_TsmsTemplateEditForm() {
            $maxsize='140';
            $current_template=tsms_GetTemplate();
            $cursize= strlen($current_template);
            $symbolsRest=($maxsize-$cursize);
            $inputs=wf_TextArea('newsmstemplate', '', $current_template, true, '25x5');
            $inputs.=wf_delimiter();
            $inputs.=__('Symbols rest').': '.$symbolsRest.wf_delimiter();
            $inputs.=wf_Submit(__('Save'));
            $result=  wf_Form("", 'POST', $inputs, 'glamour');
            return ($result);
        }
        
        function tsms_ParseTemplate($login,$template) {
            global $td_curdate,$td_realnames,$td_realnamestrans,$td_tariffprices,$td_users,$td_allpayids;
             $result=$template;
            //known macro
            $result=str_ireplace('{LOGIN}', $login, $result);
            $result=str_ireplace('{REALNAME}', @$td_realnames[$login], $result);
            $result=str_ireplace('{REALNAMETRANS}', @$td_realnamestrans[$login], $result);
            $result=str_ireplace('{CASH}', @$td_users[$login]['Cash'], $result);
            $result=str_ireplace('{ROUNDCASH}', @round($td_users[$login]['Cash'],2), $result);
            $result=str_ireplace('{CREDIT}', @$td_users[$login]['Credit'], $result);
            $result=str_ireplace('{TARIFF}', @$td_users[$login]['Tariff'], $result);
            $result=str_ireplace('{TARIFFPRICE}', @$td_tariffprices[$td_users[$login]['Tariff']], $result);
            $result=str_ireplace('{CURDATE}', @$td_curdate, $result);
            $result=str_ireplace('{PAYID}', @$td_allpayids[$login], $result);
            return ($result);
        }
       
        function tsms_GetSign() {
            $sign= zb_StorageGet('TSMS_SIGN');
            if (empty($sign)) {
                //create new one
                $sign='ISP';
                zb_StorageSet('TSMS_SIGN', $sign);
                log_register("TSMS SIGN CREATE");
            }
            return ($sign);
        }
        
        function tsms_SetSign($sign) {
            zb_StorageSet('TSMS_SIGN', $sign);
            log_register("TSMS SIGN CHANGE");
        }
        
        function tsms_GetWap() {
            $wap= zb_StorageGet('TSMS_WAP');
            if (empty($wap)) {
                //create new one
                $wap='http://isp.ua';
                zb_StorageSet('TSMS_WAP', $wap);
                log_register("TSMS WAP CREATE");
            }
            return ($wap);
        }
        
        function tsms_SetWap($wap) {
            zb_StorageSet('TSMS_WAP', $wap);
            log_register("TSMS WAP CHANGE");
        }
        
        
          function tsms_GetTz() {
            $tz= zb_StorageGet('TSMS_TZ');
            if (empty($tz)) {
                //create new one
                $tz='2';
                zb_StorageSet('TSMS_TZ', $tz);
                log_register("TSMS TIMEZONE CREATE");
            }
            return ($tz);
        }
        
        function tsms_SetTz($tz) {
            zb_StorageSet('TSMS_TZ', $tz);
            log_register("TSMS TIMEZONE CHANGE");
        }
        
       function web_TsmsMiscOpts() {
           $cursign=  tsms_GetSign();
           $curwap=  tsms_GetWap();
           $curtz= tsms_GetTz();
           $inputs=   wf_TextInput('newsign', __('Sign'), $cursign, true, '12');
           $inputs.=  wf_TextInput('newwap', __('WAP'), $curwap, true, '15');
           $inputs.=  user_tz_select($curtz,'newtz').' '.wf_tag('label', false, '', 'for="newtz"').__('Time zone').wf_tag('label',true).wf_tag('br');
           $inputs.= wf_Submit(__('Save'));
           $result=  wf_Form('', 'POST', $inputs, 'glamour');
           return ($result);
       }
       
       function web_TsmsExcludeOpts() {
           $excludedUsers= tsms_GetExcludeUsers();
           $alladdress=  zb_AddressGetFulladdresslist();
           $allrealnames= zb_UserGetAllRealnames();
           $allphones=  tsms_GetAllMobileNumbers();
           
           $cells=  wf_TableCell(__('Login'));
           $cells.= wf_TableCell(__('Full address'));
           $cells.= wf_TableCell(__('Real Name'));
           $cells.= wf_TableCell(__('Phone'));
           $cells.= wf_TableCell(__('Actions'));
           $rows=  wf_TableRow($cells, 'row1');
           
           if (!empty($excludedUsers)) {
               foreach ($excludedUsers as $eachlogin=>$io) {
                   
                   $cells=  wf_TableCell(wf_Link("?module=userprofile&username=".$eachlogin, (web_profile_icon().' '.$eachlogin)));
                   $cells.= wf_TableCell(@$alladdress[$eachlogin]);
                   $cells.= wf_TableCell(@$allrealnames[$eachlogin]);
                   $cells.= wf_TableCell(@$allphones[$eachlogin]);
                   $cells.= wf_TableCell(wf_JSAlert("?module=turbosms&excludedelete=".$eachlogin, web_delete_icon(), __('Are you serious')));
                   $rows.=  wf_TableRow($cells, 'row3');
           
               }
           }
           
           //adding form
           $inputs=   wf_TextInput('newexcludelogin', __('User login to exclude from sending'), '', true, '15');
           $inputs.=  wf_Submit('Save');
                      
           $result=  wf_TableBody($rows, '100%', '0', 'sortable');
           $result.= wf_delimiter();
           $result.= wf_Form("", 'POST', $inputs, 'glamour');
 
           return ($result);
       }
       
       function web_TsmsSingleSendForm() {
            $inputs=  wf_TextInput('sendsinglelogin', __('Login'), '', false, '20');
            $inputs.= wf_Submit(__('Send'));
            $result=  wf_Form("", "POST", $inputs, 'glamour');
            return ($result);
            
       }
       
       function web_TsmsMassendConfirm($userarray) {
           global $td_users,$td_mobiles,$td_realnames,$td_realnamestrans,$td_tariffprices,$td_alladdress;
           global $ubillingConfig;
           $altCfg=$ubillingConfig->getAlter();
           
           $template=  tsms_GetTemplate();
           $excludeUsers=  tsms_GetExcludeUsers();
           $excludeArr=array();
           //ignoring DEAD_TAGID users
           if ($altCfg['CEMETERY_ENABLED']) {
               $cemetery=new Cemetery();
               $excludeCemetery=$cemetery->getAllTagged();
              if (!empty($excludeCemetery)) {
                  foreach ($excludeCemetery as $eecl => $eecld) {
                      $excludeUsers[$eecl]='NOP';
                  }
              }
           }
           
           $cells=   wf_TableCell(__('Login'));
           $cells.=  wf_TableCell(__('Address'));
           $cells.=  wf_TableCell(__('Real Name'));
           $cells.=  wf_TableCell(__('SMS'));
           $cells.=  wf_TableCell(__('Mobile'));
           $cells.=  wf_TableCell(__('Tariff'));
           $cells.=  wf_TableCell(__('Balance'));
           $cells.=  wf_TableCell(__('Credit'));
           $rows=  wf_TableRow($cells, 'row1');
           
           if (!empty($userarray)) {
               
               //excluded users handling
               if (!empty($excludeUsers)) {
                   $excludeResult=wf_tag('h3').__('Next users will be ignored while SMS sending').  wf_tag('h3', true);
                   foreach ($excludeUsers as $excludeLogin=>$nop) {
                       unset($userarray[$excludeLogin]);
                       $excludeArr[$excludeLogin]=$excludeLogin;
                   }
               } else {
                   $excludeResult='';
               }
               
               foreach ($userarray as $login=>$phone) {
                   $message=tsms_ParseTemplate($login, $template);
                   $smsContainer=  wf_modal(__('Show'), __('SMS'), $message, '', '300', '200');
                   $cells=   wf_TableCell(wf_Link("?module=userprofile&username=".$login, web_profile_icon().' '.$login, false));
                   $cells.=  wf_TableCell(@$td_alladdress[$login]);
                   $cells.=  wf_TableCell(@$td_realnames[$login]);
                   $cells.=  wf_TableCell($smsContainer);
                   $cells.=  wf_TableCell($td_mobiles[$login]);
                   $cells.=  wf_TableCell($td_users[$login]['Tariff']);
                   $cells.=  wf_TableCell($td_users[$login]['Cash']);
                   $cells.=  wf_TableCell($td_users[$login]['Credit']);
                   $rows.=  wf_TableRow($cells, 'row3');
               }
           }
           
           //confirmation form
           $packdata=  serialize($userarray);
           $packdata=  base64_encode($packdata);
           $inputs=  wf_HiddenInput('massendConfirm', $packdata);
           $inputs.= wf_Submit(__('Send SMS for all of this users'));
           $confirmForm=  wf_Form("", 'POST', $inputs, 'glamour');
           
           $result=$confirmForm;
           $result.=  wf_TableBody($rows, '100%', '0', 'sortable');
           
           //showing which users will be excluded
           if (!empty($excludeUsers)) {
               $result.=$excludeResult;
               $result.=web_UserArrayShower($excludeArr);
           }
           
           return ($result);
       }
        
  /***************************************
   * Controller section
   ***************************************/   
       
        if (wf_CheckPost(array('newsmstemplate'))) {
            tsms_SetTemplate($_POST['newsmstemplate']);
            rcms_redirect("?module=turbosms");
        }
        
        if (wf_CheckPost(array('newsign','newwap'))) {
            tsms_SetSign($_POST['newsign']);
            tsms_SetWap($_POST['newwap']);
            tsms_SetTz($_POST['newtz']);
            rcms_redirect("?module=turbosms");
        }
        
        if (wf_CheckPost(array('newexcludelogin'))) {
            tsms_ExcludeUserAdd($_POST['newexcludelogin']);
            rcms_redirect("?module=turbosms");
        }
        
        if (wf_CheckGet(array('excludedelete'))) {
            tsms_ExcludeUserDelete($_GET['excludedelete']);
            rcms_redirect("?module=turbosms");
        }
        
        //template & sending
        if (!wf_CheckGet(array('sending'))) {
        $availMacro=wf_tag('h3').__('Available macroses').wf_tag('h3',true);
        $availMacro.='{LOGIN}'.wf_tag('br');
        $availMacro.='{REALNAME}'.wf_tag('br');
        $availMacro.='{REALNAMETRANS}'.wf_tag('br');
        $availMacro.='{CASH}'.wf_tag('br');
        $availMacro.='{ROUNDCASH}'.wf_tag('br');
        $availMacro.='{CREDIT}'.wf_tag('br');
        $availMacro.='{TARIFF}'.wf_tag('br');
        $availMacro.='{TARIFFPRICE}'.wf_tag('br');
        $availMacro.='{CURDATE}'.wf_tag('br');
        $availMacro.='{PAYID}'.wf_tag('br');
        
        $templateEditForm=  wf_TableCell(web_TsmsTemplateEditForm(),'50%','','valign="top"');
        $templateEditForm.= wf_TableCell($availMacro,'50%','','valign="top"');
        $templateEditForm=  wf_TableRow($templateEditForm);
        $templateEditForm= wf_TableBody($templateEditForm, '100%', 0, '');
        
        $controlButtons=  wf_modal(__('Edit template'), __('Edit template'), $templateEditForm, 'ubButton', '600', '400');
        $controlButtons.= wf_modal(__('Misc options'), __('Misc options'), web_TsmsMiscOpts(), 'ubButton', '320', '200');
        $controlButtons.= wf_modal(__('Excluded users'), __('Excluded users'), web_TsmsExcludeOpts(), 'ubButton', '800', '600');
        $controlButtons.=wf_Link('?module=turbosms&sending=true', __('SMS sending'), false, 'ubButton');
        show_window(__('Options and sending'), $controlButtons);
        
        //view SMS queue
        show_window(__('View SMS sending queue'), web_TsmsDateForm());
        if (wf_CheckPost(array('showqueuedate'))) {
            show_window('',  wf_Link("?module=turbosms", __('Back'), false, 'ubButton'));
            show_window(__('SMS sending queue at TurboSMS gateway'),web_TsmsShowAllSMS($_POST['showqueuedate']));
        }
        
        } else {
            //sending features
            show_window('',  wf_Link("?module=turbosms", __('Back'), false, 'ubButton'));
            show_window(__('Send SMS for single user'),  web_TsmsSingleSendForm());
            show_window(__('Send SMS for user group'), web_TsmsMassendForm());
            
            
           //single user send
            if (wf_CheckPost(array('sendsinglelogin'))) {
                $singlelogin=trim($_POST['sendsinglelogin']);
                $singlelogin=  mysql_real_escape_string($singlelogin);
                if (!empty($singlelogin)) {
                $smsTemplate=  tsms_GetTemplate();
                $smsWap=  tsms_GetWap();
                $smsSign=  tsms_GetSign();
                $smsTz= tsms_GetTz();
                $newMessage=  tsms_ParseTemplate($singlelogin, $smsTemplate);
                @$mobile=$td_mobiles[$singlelogin];
                if (!empty($mobile)) {
                  show_window(__('Result'),$newMessage.' => '.$mobile);
                  log_register("TSMS SEND SINGLE `".$mobile."`");
                  tsms_SendSMS($mobile, $smsSign, $newMessage, $smsWap,$smsTz);
                  
                } else {
                    show_window(__('Error'),__('No mobile'));
                }
                }
            }
            
            //group user sending
            if (wf_CheckPost(array('msendtype'))) {
                $filterParams='';
                if (wf_CheckPost(array('msendtariffname'))) {
                    $filterParams=$_POST['msendtariffname'];
                }
                $userFilters=  tsms_UserFilter($_POST['msendtype'],$filterParams);
                show_window(__('Confirmation'),web_TsmsMassendConfirm($userFilters));
            }
            
            //sending subroutine
            if (wf_CheckPost(array('massendConfirm'))) {
                $smsTemplate=  tsms_GetTemplate();
                $smsWap=  tsms_GetWap();
                $smsSign=  tsms_GetSign();
                $smsTz= tsms_GetTz();
                
                $unpackData=  base64_decode($_POST['massendConfirm']);
                $unpackData= unserialize($unpackData);
                if (!empty($_POST['massendConfirm'])) {
                    if (!empty($unpackData)) {
                        log_register("TSMS SEND MASS FOR `".sizeof($unpackData)."` USERS");
                        foreach ($unpackData as $eachLogin=>$eachPhone) {
                            $newMessage=  tsms_ParseTemplate($eachLogin, $smsTemplate);
                            tsms_SendSMS($eachPhone, $smsSign, $newMessage, $smsWap,$smsTz);
                            
                        }
                        $notifyText=sizeof($unpackData).' '.__('SMS queued and waiting to send').  wf_Link('?module=turbosms', __('Click here to view today sending queue'), true, 'ubButton');
                        $doneNotify=  wf_modalOpened(__('Send SMS for user group'), $notifyText, '400', '200');
                        show_window('', $doneNotify);
                    }
                }
            }
            
        }
       
    } else {
        show_error( __('TurboSMS support is disabled'));
    }
} else {
    show_error(__('You cant control this module'));
}
?>
