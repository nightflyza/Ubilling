<?php

    function zb_ContrAhentAdd($bankacc,$bankname,$bankcode,$edrpo,$ipn,$licensenum,$juraddr,$phisaddr,$phone,$contrname) {
    $bankacc=mysql_real_escape_string($bankacc);
    $bankname=mysql_real_escape_string($bankname);
    $bankcode=mysql_real_escape_string($bankcode);
    $edrpo=mysql_real_escape_string($edrpo);
    $ipn=mysql_real_escape_string($ipn);
    $licensenum=mysql_real_escape_string($licensenum);
    $juraddr=mysql_real_escape_string($juraddr);
    $phisaddr=mysql_real_escape_string($phisaddr);
    $phone=mysql_real_escape_string($phone);
    $contrname=mysql_real_escape_string($contrname);
    $query="INSERT INTO `contrahens` (
        `id` ,
        `bankacc` ,
        `bankname` ,
        `bankcode` ,
        `edrpo` ,
        `ipn` ,
        `licensenum` ,
        `juraddr` ,
        `phisaddr` ,
        `phone` ,
        `contrname`
               )
        VALUES (
                NULL ,
                '".$bankacc."',
                '".$bankname."',
                '".$bankcode."',
                '".$edrpo."',
                '".$ipn."',
                '".$licensenum."',
                '".$juraddr."',
                '".$phisaddr."',
                '".$phone."',
                '".$contrname."'
                );";
    nr_query($query);
    log_register("ADD AGENT ".$contrname);
    }
    
    function zb_ContrAhentChange($ahentid,$bankacc,$bankname,$bankcode,$edrpo,$ipn,$licensenum,$juraddr,$phisaddr,$phone,$contrname) {
    $ahentid=vf($ahentid);
    $bankacc=mysql_real_escape_string($bankacc);
    $bankname=mysql_real_escape_string($bankname);
    $bankcode=mysql_real_escape_string($bankcode);
    $edrpo=mysql_real_escape_string($edrpo);
    $ipn=mysql_real_escape_string($ipn);
    $licensenum=mysql_real_escape_string($licensenum);
    $juraddr=mysql_real_escape_string($juraddr);
    $phisaddr=mysql_real_escape_string($phisaddr);
    $phone=mysql_real_escape_string($phone);
    $contrname=mysql_real_escape_string($contrname);
    $query="UPDATE `contrahens` SET 
        `bankacc` = '".$bankacc."',
        `bankname` = '".$bankname."',
        `bankcode` = '".$bankcode."',
        `edrpo` = '".$edrpo."',
        `ipn` = '".$ipn."',
        `licensenum` = '".$licensenum."',
        `juraddr` = '".$juraddr."',
        `phisaddr` = '".$phisaddr."',
        `phone` = '".$phone."',
        `contrname` = '".$contrname."'
          WHERE `contrahens`.`id` =".$ahentid." LIMIT 1;";
    nr_query($query);
    log_register("CHANGE AGENT ".$contrname);
    }
    
    function zb_ContrAhentDelete($id) {
        $id=vf($id);
        $query="DELETE from `contrahens` where `id`='".$id."'";
        nr_query($query);
        log_register("DELETE AGENT ".$id);
    } 
    
    function zb_ContrAhentGetData($id) {
        $id=vf($id);
        $query="SELECT * from `contrahens` WHERE `id`='".$id."'";
        $result=simple_query($query);
        return($result);
     }
     
     function zb_ContrAhentGetAllData() {
         $query="SELECT * from `contrahens`";
         $result=simple_queryall($query);
         return ($result);
     }
     
     function zb_ContrAhentShow() {
       $allcontr=zb_ContrAhentGetAllData();
    
       // construct needed editor
      $titles=array(
        'ID',
        'Bank account',
        'Bank name',
        'Bank code',
        'EDRPOU',
        'IPN',
        'License number',
        'Juridical address',
        'Phisical address',
        'Phone',
        'Contrahent name',
        );
    $keys=array(
        'id',
        'bankacc',
        'bankname',
        'bankcode',
        'edrpo',
        'ipn',
        'licensenum',
        'juraddr',
        'phisaddr',
        'phone',
        'contrname'
        );
       $result=web_GridEditor($titles, $keys, $allcontr,'contrahens',true,true);
       return($result);
     }
     
     function zb_ContrAhentAddForm() {
         $form='
             <form action="" method="POST"> 
             <input type="text" name="newbankacc" > '.__('Bank account').' <br>
             <input type="text" name="newbankname" > '.__('Bank name').' <br>
             <input type="text" name="newbankcode" > '.__('Bank code').' <br>
             <input type="text" name="newedrpo" > '.__('EDRPOU').' <br>
             <input type="text" name="newipn" > '.__('IPN').' <br>
             <input type="text" name="newlicensenum" > '.__('License number').' <br>
             <input type="text" name="newjuraddr" > '.__('Juridical address').' <br>
             <input type="text" name="newphisaddr" > '.__('Phisical address').' <br>
             <input type="text" name="newphone" > '.__('Phone').' <br>
             <input type="text" name="newcontrname" > '.__('Contrahent name').' <br>
             <input type="submit" value="'.__('Save').'">
             </form>
             ';
         return($form);
     }
     
         function zb_ContrAhentEditForm($ahentid) {
         $ahentid=vf($ahentid);
         $cdata=zb_ContrAhentGetData($ahentid);
         $form='
             <form action="" method="POST"> 
             <input type="text" name="changebankacc" value="'.$cdata['bankacc'].'"> '.__('Bank account').' <br>
             <input type="text" name="changebankname" value="'.$cdata['bankname'].'"> '.__('Bank name').' <br>
             <input type="text" name="changebankcode" value="'.$cdata['bankcode'].'"> '.__('Bank code').' <br>
             <input type="text" name="changeedrpo" value="'.$cdata['edrpo'].'"> '.__('EDRPOU').' <br>
             <input type="text" name="changeipn" value="'.$cdata['ipn'].'"> '.__('IPN').' <br>
             <input type="text" name="changelicensenum" value="'.$cdata['licensenum'].'"> '.__('License number').' <br>
             <input type="text" name="changejuraddr" value="'.$cdata['juraddr'].'"> '.__('Juridical address').' <br>
             <input type="text" name="changephisaddr" value="'.$cdata['phisaddr'].'"> '.__('Phisical address').' <br>
             <input type="text" name="changephone" value="'.$cdata['phone'].'"> '.__('Phone').' <br>
             <input type="text" name="changecontrname" value="'.$cdata['contrname'].'"> '.__('Contrahent name').' <br>
             <input type="submit" value="'.__('Save').'">
             </form>
             ';
         return($form);
     }
     
     function zb_ContrAhentSelect() {
         $allagents=zb_ContrAhentGetAllData();
         $select='<select name="ahentsel">';
         if (!empty ($allagents)) {
             foreach ($allagents as $io=>$eachagent) {
                 $select.='<option value="'.$eachagent['id'].'">'.$eachagent['contrname'].'</option>';
             }
             
         }
         $select.='</select>';
         return($select);
     }
     
          
     function zb_AgentAssignGetAllData() {
          $query="SELECT * from `ahenassign`";
          $allassigns=simple_queryall($query);
          return($allassigns);
     }
     
     function zb_AgentAssignDelete($id) {
         $id=vf($id);
         $query="DELETE from `ahenassign` where `id`='".$id."'";
         nr_query($query);
         log_register("DELETE AGENTASSIGN ".$id);
     }
     
     function zb_AgentAssignAdd($ahenid,$streetname) {
         $ahenid=vf($ahenid);
         $streetname=mysql_real_escape_string($streetname);
         $query="INSERT INTO `ahenassign` (
                `id` ,
                `ahenid` ,
                `streetname`
                )
                VALUES (
                NULL , '". $ahenid."', '".$streetname."'
                );";
         nr_query($query);
         log_register("ADD AGENTASSIGN ".$ahenid.' '.$streetname);
     }
     
     function web_AgentAssignForm() {
             $form='
                 <form action="" method="POST">
                 '.  zb_ContrAhentSelect().' '.__('Contrahent name').' <br>
                 <input type="text" name="newassign"> '.__('Street name').' <br>
                 <input type="submit" value="'.__('Save').'">
                 </form>
                 ';
             
             return($form);
       }
       
       function web_AgentAssignShow() {
           $allassigns=zb_AgentAssignGetAllData();
           $allahens=zb_ContrAhentGetAllData();
           $agentnames=array();
           if (!empty ($allahens)) {
               foreach ($allahens as $io=>$eachahen) {
                   $agentnames[$eachahen['id']]=$eachahen['contrname'];
               }
           }
           $result='<table width="100%" border="0" class="sortable">';
              $result.='
                   <tr class="row1">
                         <td>'.__('ID').'</td>
                         <td>'.__('Contrahent name').'</td>
                         <td>'.__('Street name').'</td>
                         <td>'.__('Actions').'</td>
                   </tr>
                   ';
           if (!empty ($allassigns)) {
               foreach ($allassigns as $io2=>$eachassign) {
               $result.='
                   <tr class="row3">
                         <td>'.$eachassign['id'].'</td>
                         <td>'.@$agentnames[$eachassign['ahenid']].'</td>
                         <td>'.$eachassign['streetname'].'</td>
                          <td>
                          <a href="?module=contrahens&deleteassign='.$eachassign['id'].'">'.  web_delete_icon().'</a>
                          </td>
                   </tr>
                   ';
               }
           }
           $result.='</table>';
           return($result);
       }
         
    function zb_AgentAssignCheckLogin($login,$allassigns,$alladdress) {
        $alter_cfg=rcms_parse_ini_file(CONFIG_PATH."alter.ini");
        $result=false;
           // если пользователь куда-то заселен
          if (isset($alladdress[$login])) {
              // возвращаем дефолтного агента если присваиваний нет вообще
             if (empty ($allassigns)) {
                 $result=$alter_cfg['DEFAULT_ASSIGN_AGENT'];
           } else {
               //если какие-то присваивалки есть
            $useraddress=$alladdress[$login];
            // проверяем для каждой присваивалки попадает ли она под нашего абонента
            foreach ($allassigns as $io=>$eachassign) {
                if (strpos($useraddress,$eachassign['streetname'])!==false) {
                    $result=$eachassign['ahenid'];
                    } else {
                        // и если не нашли - возвращаем  умолчательного
                        $result=$alter_cfg['DEFAULT_ASSIGN_AGENT'];
                    }
           }
           }
        }
        // если присваивание выключено возвращаем умолчального
        if (!$alter_cfg['AGENTS_ASSIGN']) {
            $result=$alter_cfg['DEFAULT_ASSIGN_AGENT'];
        }
        return($result);
    }
    
       function zb_ExportLoadTemplate($filename) {
       $template=file_get_contents($filename);
       return($template);
   }
   
   function zb_ExportTariffsLoadAll() {
      $allstgdata=zb_UserGetAllStargazerData();
      $result=array();
      if (!empty ($allstgdata)) {
          foreach ($allstgdata as $io=>$eachuser) {
              $result[$eachuser['login']]=$eachuser['Tariff'];
          }
      }
      return($result);
   }
   
   function zb_ExportContractsLoadAll() {
       $query="SELECT `login`,`contract` from `contracts`";
       $allcontracts=simple_queryall($query);
       $queryDates="SELECT `contract`,`date` from `contractdates`";
       $alldates=  simple_queryall($queryDates);
       $result=array();
       $dates=array();
       if (!empty($alldates)) {
           foreach ($alldates as $ia=>$eachdate) {
               $dates[$eachdate['contract']]=$eachdate['date'];
           }
       }
       
       if (!empty ($allcontracts)) {
        foreach ($allcontracts as $io=>$eachcontract) {
              $result[$eachcontract['login']]['contractnum']=$eachcontract['contract'];
              if (isset($dates[$eachcontract['contract']])) {
                $rawdate=$dates[$eachcontract['contract']];
                $timestamp=  strtotime($rawdate);
                $newDate=date("Y-m-d\T00:00:00",$timestamp);
                $result[$eachcontract['login']]['contractdate']=$newDate;
              } else {
                $result[$eachcontract['login']]['contractdate']='1970-01-01T00:00:00';  
              }
        }
       
       }

       return($result);
   }
   
   function zb_ExportParseTemplate($templatebody,$templatedata) {
       foreach ($templatedata as $field=>$data) {
           $templatebody=str_ireplace($field, $data, $templatebody);
       }
       return($templatebody);
   }
   
   function zb_ExportAgentsLoadAll() {
       $allagents=zb_ContrAhentGetAllData();
       $result=array();
       if (!empty ($allagents)) {
           foreach ($allagents as $io=>$eachagent) {
               $result[$eachagent['id']]['contrname']=$eachagent['contrname'];
               @$result[$eachagent['id']]['edrpo']=$eachagent['edrpo'];
               @$result[$eachagent['id']]['bankacc']=$eachagent['bankacc'];
           }
           return($result);
       }
   }
   
   
   function zb_ExportForm() {
       $curdate=curdate();
       $yesterday=date("Y-m-d",time()-86400);
       
       $inputs=__('From');
       $inputs.= wf_DatePickerPreset('fromdate',$yesterday);
       $inputs.=__('To');
       $inputs.=wf_DatePickerPreset('todate',$curdate);
       $inputs.=wf_Submit('Export');
       $form=  wf_Form("", 'POST', $inputs, 'glamour');
       return($form);
   }
       
   function zb_ExportPayments($from_date,$to_date) {
       // reading export options
       $alter_conf=rcms_parse_ini_file(CONFIG_PATH."alter.ini");
       $default_assign_agent=$alter_conf['DEFAULT_ASSIGN_AGENT'];
       $export_template=$alter_conf['EXPORT_TEMPLATE'];
       $export_template_head=$alter_conf['EXPORT_TEMPLATE_HEAD'];
       $export_template_end=$alter_conf['EXPORT_TEMPLATE_END'];
       $export_only_positive=$alter_conf['EXPORT_ONLY_POSITIVE'];
       $export_format=$alter_conf['EXPORT_FORMAT'];
       $export_encoding=$alter_conf['EXPORT_ENCODING'];
       $import_encoding=$alter_conf['IMPORT_ENCODING'];
       $export_from_time=$alter_conf['EXPORT_FROM_TIME'];
       $export_to_time=$alter_conf['EXPORT_TO_TIME'];
       $citydisplay=$alter_conf['CITY_DISPLAY'];
       if ($citydisplay) {
           $address_offset=1;
       } else {
           $address_offset=0;
       }
       
       // loading templates
       $template_head=zb_ExportLoadTemplate($export_template_head);
       $template=zb_ExportLoadTemplate($export_template);
       $template_end=zb_ExportLoadTemplate($export_template_end);
       
       // load all needed data
       $allassigns=zb_AgentAssignGetAllData();
       $alladdress=zb_AddressGetFulladdresslist();
       $allrealnames=zb_UserGetAllRealnames();
       $allagentsdata=zb_ExportAgentsLoadAll();
       $allcontracts=zb_ExportContractsLoadAll();
       $alltariffs=zb_ExportTariffsLoadAll();
       //main code
       $qfrom_date=$from_date.' '.$export_from_time;
       $qto_date=$to_date.' '.$export_to_time;
       $query="SELECT * from `payments` WHERE `date` >= '".$qfrom_date."' AND `date`<= '".$qto_date."'";
       $allpayments=simple_queryall($query);
       $parse_data=array();
       $parse_data['{FROMDATE}']=$from_date;
       $parse_data['{FROMTIME}']=$export_from_time;
       $parse_data['{TODATE}']=$to_date;
       $parse_data['{TOTIME}']=$export_to_time;
       $export_result=zb_ExportParseTemplate($template_head, $parse_data);
       if (!empty ($allpayments)) {
           foreach ($allpayments as $io=>$eachpayment) {
               // forming export data
               $paylogin=$eachpayment['login'];
               @$payrealname=$allrealnames[$eachpayment['login']];
               $payid=$eachpayment['id'];
               $paytariff=$alltariffs[$paylogin];
               $paycontractdata=$allcontracts[$paylogin];
               $paycontract=$paycontractdata['contractnum'];
               $paycontractdate=$paycontractdata['contractdate'];
               $paycity='debug city';
               $payregion='debug region';
               $paydrfo='';
               $payjurface='false';
               $paydatetime=$eachpayment['date'];
               $paysumm=$eachpayment['summ'];
               $paynote=$eachpayment['note'];
               $paytimesplit=explode(' ',$paydatetime);
               $paydate=$paytimesplit[0];
               $paytime=$paytimesplit[1];
               @$payaddr=$alladdress[$paylogin];
               @$splitaddr=explode(' ',$payaddr);
               @$paystreet=$splitaddr[0+$address_offset];
               @$splitbuild=explode('/',$splitaddr[1+$address_offset]);
               @$paybuild=$splitbuild[0];
               @$payapt=$splitbuild[1];
               $agent_assigned=zb_AgentAssignCheckLogin($paylogin, $allassigns, $alladdress);
               @$agent_bankacc=$allagentsdata[$agent_assigned]['bankacc'];
               @$agent_edrpo=$allagentsdata[$agent_assigned]['edrpo'];
               @$agent_name=$allagentsdata[$agent_assigned]['contrname'];
               // construct template data
               
               $parse_data['{PAYID}']=md5($payid);
               $parse_data['{AGENTNAME}']=$agent_name;
               $parse_data['{AGENTEDRPO}']=$agent_edrpo;
               $parse_data['{PAYDATE}']=$paydate;
               $parse_data['{PAYTIME}']=$paytime;
               $parse_data['{PAYSUMM}']=$paysumm;
               $parse_data['{CONTRACT}']=$paycontract;
               $parse_data['{CONTRACTDATE}']=$paycontractdate;
               $parse_data['{REALNAME}']=$payrealname;
               $parse_data['{DRFO}']=$paydrfo;
               $parse_data['{JURFACE}']=$payjurface;
               $parse_data['{STREET}']=$paystreet;
               $parse_data['{BUILD}']=$paybuild;
               $parse_data['{APT}']=$payapt;
               $parse_data['{NOTE}']=$paynote;
               $parse_data['{CITY}']=$paycity;
               $parse_data['{REGION}']=$payregion;
               $parse_data['{TARIFF}']=$paytariff;
               
               // custom positive payments export
               if ($export_only_positive) {
                   // check is that pos payment
               if ($paysumm>0) {
               $export_result.=zb_ExportParseTemplate($template, $parse_data);
               }
               } else {
                   //or anyway export it
                   $export_result.=zb_ExportParseTemplate($template, $parse_data);
               }
           }
       }
       $export_result.=zb_ExportParseTemplate($template_end, $parse_data);
       
       if ($import_encoding!=$export_encoding) {
           $export_result=iconv($import_encoding, $export_encoding, $export_result);
       }
         return($export_result);
   }
   
   function zb_AgentAssignedGetData($login) {
       $login=vf($login);
       $allassigns=zb_AgentAssignGetAllData();
       $alladdress=zb_AddressGetFulladdresslist();
       $assigned_agent=zb_AgentAssignCheckLogin($login, $allassigns, $alladdress);
       $result=zb_ContrAhentGetData($assigned_agent);
       return($result);
   }
   
   
   // literated summ
   // i`m localize it later
    function num2str($inn, $stripkop=false) {
     $nol = 'нуль';
     $str[100]= array('','сто','двісті','триста','чотириста','п`ятсот','шістсот', 'сімсот', 'вісімсот','дев`ятсот');
     $str[11] = array('','десять','одинадцять','дванадцять','тринадцять', 'чотирнадцять','п`ятнадцять','шістнадцять','сімнадцять', 'вісімнадцять','дев`ятнадцять','двадцять');
     $str[10] = array('','десять','двадцять','тридцять','сорок','п`ятдесят', 'шістдесят','сімдесят','вісімдесят','дев`яносто');
     $sex = array(
         array('','один','два','три','чотири','п`ять','шість','сім', 'вісім','дев`ять'),// m
         array('','одна','дві','три','чотири','п`ять','шість','сім', 'вісім','дев`ять') // f
     );
     $forms = array(
         array('копійка', 'копійки', 'копійок', 1), // 10^-2
         array('гривня', 'гривні', 'гривень',  0), // 10^ 0
         array('тисяча', 'тисячі', 'тисяч', 1), // 10^ 3
         array('мільйон', 'мільйона', 'мільйонів',  0), // 10^ 6
         array('мільярд', 'мільярда', 'мільярдів',  0), // 10^ 9
         array('трильйон', 'трильйона', 'трильйонів',  0), // 10^12
     );
     $out = $tmp = array();
     // Поехали!
     $tmp = explode('.', str_replace(',','.', $inn));
     $rub = number_format($tmp[ 0], 0,'','-');
     if ($rub== 0) $out[] = $nol;
     // нормализация копеек
     $kop = isset($tmp[1]) ? substr(str_pad($tmp[1], 2, '0', STR_PAD_RIGHT), 0,2) : '00';
     $segments = explode('-', $rub);
     $offset = sizeof($segments);
     if ((int)$rub== 0) { // если 0 рублей
         $o[] = $nol;
         $o[] = morph( 0, $forms[1][ 0],$forms[1][1],$forms[1][2]);
     }
     else {
         foreach ($segments as $k=>$lev) {
             $sexi= (int) $forms[$offset][3]; // определяем род
             $ri = (int) $lev; // текущий сегмент
             if ($ri== 0 && $offset>1) {// если сегмент==0 & не последний уровень(там Units)
                 $offset--;
                 continue;
             }
             // нормализация
             $ri = str_pad($ri, 3, '0', STR_PAD_LEFT);
             // получаем циферки для анализа
             $r1 = (int)substr($ri, 0,1); //первая цифра
             $r2 = (int)substr($ri,1,1); //вторая
             $r3 = (int)substr($ri,2,1); //третья
             $r22= (int)$r2.$r3; //вторая и третья
             // розгрібаємо порядки
             if ($ri>99) $o[] = $str[100][$r1]; // Сотни
             if ($r22>20) {// >20
                 $o[] = $str[10][$r2];
                 $o[] = $sex[ $sexi ][$r3];
             }
             else { // <=20
                 if ($r22>9) $o[] = $str[11][$r22-9]; // 10-20
                 elseif($r22> 0) $o[] = $sex[ $sexi ][$r3]; // 1-9
             }
             // гривні
             $o[] = morph($ri, $forms[$offset][ 0],$forms[$offset][1],$forms[$offset][2]);
             $offset--;
         }
     }
     // копійки
     if (!$stripkop) {
         $o[] = $kop;
         $o[] = morph($kop,$forms[ 0][ 0],$forms[ 0][1],$forms[ 0][2]);
     }
     return preg_replace("/\s{2,}/",' ',implode(' ',$o));
 }
  

 function morph($n, $f1, $f2, $f5) {
     $n = abs($n) % 100;
     $n1= $n % 10;
     if ($n>10 && $n<20) return $f5;
     if ($n1>1 && $n1<5) return $f2;
     if ($n1==1) return $f1;
     return $f5;
 } 
    
    function zb_PaymentGetData($paymentid) {
        $paymentid=vf($paymentid);
        $result=array();
        $query="SELECT * from `payments` WHERE `id`='".$paymentid."'";
        $result=simple_query($query);
        return($result);
    }
    
    function zb_PrintCheckLoadTemplate() {
        $template=file_get_contents(CONFIG_PATH.'printcheck.tpl');
        return($template);
    } 
    
    function zb_PrintCheckLoadCassNames() {
        $names=rcms_parse_ini_file(CONFIG_PATH.'cass.ini');
        return($names);
    }
    
   function zb_PrintCheckGetDayNum($payid,$paymentdate) {
       $payid=vf($payid,3);
       
       $result='EXEPTION';
           $onlyday=$paymentdate;
           $onlyday=  strtotime($onlyday);
           $onlyday=date("Y-m-d",$onlyday);
           $date_q="SELECT `id` from `payments` where `date` LIKE '".$onlyday."%' ORDER BY `id` ASC LIMIT 1;";
           $firstbyday=  simple_query($date_q);

        if (!empty($firstbyday)) {
           $firstbyday=$firstbyday['id'];
           $currentnumber=$payid-$firstbyday;
           $currentnumber=$currentnumber+1;
           $result=$currentnumber;
        }
       return ($result);
   }
    
   function zb_PrintCheck($paymentid) {
        $paymentdata=zb_PaymentGetData($paymentid);
        $login=$paymentdata['login'];
        $templatebody=zb_PrintCheckLoadTemplate();
        $allfioz=zb_UserGetAllRealnames();
        $alladdress=zb_AddressGetFulladdresslist();
        $agent_data=zb_AgentAssignedGetData($login);
        $cassnames=zb_PrintCheckLoadCassNames();
        $cday=date("d");
        $cmonth=date("m");
        $month_array=months_array();
        $cmonth_name=$month_array[$cmonth];
        $cyear=curyear();
        //forming template data
        @$templatedata['{PAYID}']=$paymentdata['id'];
        @$templatedata['{PAYIDENC}']=zb_NumEncode($paymentdata['id']);;
        @$templatedata['{AGENTEDRPO}']=$agent_data['edrpo'];
        @$templatedata['{AGENTNAME}']=$agent_data['contrname'];
        @$templatedata['{PAYDATE}']=$paymentdata['date'];
        @$templatedata['{PAYSUMM}']=$paymentdata['summ'];
        @$templatedata['{PAYSUMM_LIT}']=num2str($paymentdata['summ']); // omg omg omg 
        @$templatedata['{REALNAME}']=$allfioz[$login];
        @$templatedata['{BUHNAME}']='а відки я знаю?';
        @$templatedata['{CASNAME}']=  $cassnames[whoami()];
        @$templatedata['{PAYTARGET}']='Оплата за послуги / '.$paymentdata['date'];
        @$templatedata['{FULLADDRESS}']=$alladdress[$login];
        @$templatedata['{CDAY}']=$cday;
        @$templatedata['{CMONTH}']=rcms_date_localise($cmonth_name);
        @$templatedata['{CYEAR}']=$cyear;
        @$templatedata['{DAYPAYID}']=zb_PrintCheckGetDayNum($paymentdata['id'],$paymentdata['date']);
     
        //parsing result
        $result=zb_ExportParseTemplate($templatebody,$templatedata);
        return($result);
        
   }

   
    function zb_NdsGetAllUsers() {
        $alterconf=rcms_parse_ini_file(CONFIG_PATH."alter.ini");
        $nds_tag=$alterconf['NDS_TAGID'];
        $query="SELECT `login`,`id` from `tags` WHERE `tagid`='".$nds_tag."'";
        $allusers=  simple_queryall($query);
        $result=array();
        if (!empty($allusers)) {
            foreach ($allusers as $io=>$eachuser) {
                $result[$eachuser['login']]=$eachuser['id'];
            }
        }
        return ($result);
    }
    
    function zb_NdsCheckUser($login,$allndsusers) {
           if (isset($allndsusers[$login])) {
               return (true);
            } else {
               return (false);
            }
    }
    
    function zb_NdsGetPercent() {
        $alterconf=rcms_parse_ini_file(CONFIG_PATH."alter.ini");
        $nds_rate=$alterconf['NDS_TAX_PERCENT'];
        return ($nds_rate);
    }
    
    function zb_NdsCalc($summ,$ndspercent) {
        $result=($summ/100)*$ndspercent;
        return ($result);
    }
    
      function web_NdsPaymentsShow($query) {
    $alter_conf=rcms_parse_ini_file(CONFIG_PATH.'alter.ini');
    $alladrs=zb_AddressGetFulladdresslist();
    $alltypes=zb_CashGetAllCashTypes();
    $allapayments=simple_queryall($query);
    $ndstax=$alter_conf['NDS_TAX_PERCENT'];
    $allndsusers=  zb_NdsGetAllUsers();
    $ndspercent=  zb_NdsGetPercent();
    $allservicenames=zb_VservicesGetAllNamesLabeled();
    $total=0;
    $ndstotal=0;
  
     $tablecells= wf_TableCell(__('ID'));
     $tablecells.= wf_TableCell(__('IDENC'));
     $tablecells.= wf_TableCell(__('Date'));
     $tablecells.= wf_TableCell(__('Cash'));
     $tablecells.= wf_TableCell(__('NDS'));
     $tablecells.= wf_TableCell(__('Without NDS'));
     $tablecells.= wf_TableCell(__('Login'));
     $tablecells.= wf_TableCell(__('Full address'));
     $tablecells.= wf_TableCell(__('Cash type'));

     $tablecells.= wf_TableCell(__('Notes'));
     $tablecells.= wf_TableCell(__('Admin'));
     $tablerows= wf_TableRow($tablecells, 'row1');
      
    if (!empty ($allapayments)) {
        foreach ($allapayments as $io=>$eachpayment) {
            if (zb_NdsCheckUser($eachpayment['login'], $allndsusers)) {
            if ($alter_conf['TRANSLATE_PAYMENTS_NOTES']) {
                if ($eachpayment['note']=='') {
                    $eachpayment['note']=__('Internet');
                }
                
                if (isset ($allservicenames[$eachpayment['note']])) {
                    $eachpayment['note']=$allservicenames[$eachpayment['note']];
                }
                
                 if (ispos($eachpayment['note'], 'CARD:')) {
                    $cardnum=explode(':', $eachpayment['note']);
                    $eachpayment['note']=__('Card')." ".$cardnum[1];
                 }
                 
                 if (ispos($eachpayment['note'], 'SCFEE')) {
                    $eachpayment['note']=__('Credit fee');
                 }
                 
                 if (ispos($eachpayment['note'], 'TCHANGE:')) {
                    $tariff=explode(':', $eachpayment['note']);
                    $eachpayment['note']=__('Tariff change')." ".$tariff[1];
                 }
                 
                 if (ispos($eachpayment['note'], 'BANKSTA:')) {
                    $banksta=explode(':', $eachpayment['note']);
                    $eachpayment['note']=__('Bank statement')." ".$banksta[1];
                 }
                 
            }
      
            
     $tablecells= wf_TableCell($eachpayment['id']);
     $tablecells.= wf_TableCell(zb_NumEncode($eachpayment['id']));
     $tablecells.= wf_TableCell($eachpayment['date']);
     $tablecells.= wf_TableCell($eachpayment['summ']);
     $paynds=zb_NdsCalc($eachpayment['summ'], $ndspercent);
     $tablecells.= wf_TableCell($paynds);
     $tablecells.= wf_TableCell($eachpayment['summ']-$paynds);
     $profilelink=  wf_Link('?module=userprofile&username='.$eachpayment['login'], web_profile_icon().' '.$eachpayment['login'], false);
     $tablecells.= wf_TableCell($profilelink);
     $tablecells.= wf_TableCell(@$alladrs[$eachpayment['login']]);
     $tablecells.= wf_TableCell(@__($alltypes[$eachpayment['cashtypeid']]));
     $tablecells.= wf_TableCell($eachpayment['note']);
     $tablecells.= wf_TableCell($eachpayment['admin']);
     $tablerows.= wf_TableRow($tablecells, 'row3');
            
            
            if ($eachpayment['summ']>0) {
            $total=$total+$eachpayment['summ'];
            $ndstotal=$ndstotal+$paynds;
            }
            }
        }
    }
    
    $tablecells= wf_TableCell('');
     $tablecells.= wf_TableCell('');
     $tablecells.= wf_TableCell('');
     $tablecells.= wf_TableCell($total);
     $tablecells.= wf_TableCell($ndstotal);
     $tablecells.= wf_TableCell($total-$ndstotal);
     $tablecells.= wf_TableCell('');
     $tablecells.= wf_TableCell('');
     $tablecells.= wf_TableCell('');
     $tablecells.= wf_TableCell('');
     $tablecells.= wf_TableCell('');
     $tablerows.= wf_TableRow($tablecells, 'row2');
     
    $result=  wf_TableBody($tablerows, '100%', '0', 'sortable');
    $result.=''.__('Total').': <strong>'.$total.'</strong> '.__('ELVs for all payments of').': <strong>'.$ndstotal.'</strong>';
    return($result);
}


function web_NdsPaymentsShowYear($year) {
    $months=months_array();
   
    $year_summ=zb_PaymentsGetYearSumm($year);
  
    $cells=  wf_TableCell(__('Month'));
    $rows=  wf_TableRow($cells, 'row1');
    
    foreach ($months as $eachmonth=>$monthname) {
        $month_summ=zb_PaymentsGetMonthSumm($year, $eachmonth);
        $paycount=zb_PaymentsGetMonthCount($year, $eachmonth);
        $cells=  wf_TableCell(wf_Link('?module=nds&month='.$year.'-'.$eachmonth, rcms_date_localise($monthname), false));
        $rows.=  wf_TableRow($cells, 'row3');
    }
    $result= wf_TableBody($rows,'30%','0');
    
    show_window(__('Payments by').' '.$year, $result);
}

 function zb_RegContrAhentSelect($name,$selected='') {
         $allagents=zb_ContrAhentGetAllData();
         $agentArr=array();
         
         $select='<select name="regagent">';
         if (!empty ($allagents)) {
             foreach ($allagents as $io=>$eachagent) {
                 $agentArr[$eachagent['id']]=$eachagent['contrname'];
             }
             
         }
         $select=  wf_Selector($name, $agentArr, '', $selected, false);
         return($select);
     }

?>