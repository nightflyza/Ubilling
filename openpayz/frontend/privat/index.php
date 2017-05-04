<?php

/*
 * Фронтенд для получения уведомлений от Приватбанка
 * Для поиска пользователя предполагается использование прямого логина пользователя из users
 */

//////////////////////////////////////// секция настроек

//дебаг режим
$debug=0;

//Биллинг, возможные значения UB или STG
$billing='UB';

//Следующие параметры нужны только для вывода в случае $billing='STG';
$in_charset='utf-8';
$out_charset='utf-8';

//Индивидуальный код предприятия
$company_code=24;
//название компании
$company_name="OurISP";
//сообщение выдаваемое при успешном поиске
$message_ok="OurISP с радостью примет вашу оплату";
//сообщение об ошибке при поиске абонента
$message_fail="Абонент не найден";
//название услуги
$service='Интернет';
//код услуги
$service_code=1;

/////////////////////////////////// все, настройки кончились

// подключаем API OpenPayz
include ("../../libs/api.openpayz.php");


//Выбираем все адреса в формате Ubilling

function pb_AddressGetFulladdresslist() {
$result=array();
$apts=array();
$builds=array();
//наглая заглушка
$alterconf['ZERO_TOLERANCE']=0;
$alterconf['CITY_DISPLAY']=0;
$city_q="SELECT * from `city`";
$adrz_q="SELECT * from `address`";
$apt_q="SELECT * from `apt`";
$build_q="SELECT * from build";
$streets_q="SELECT * from `street`";
$alladdrz=simple_queryall($adrz_q);
$allapt=simple_queryall($apt_q);
$allbuilds=simple_queryall($build_q);
$allstreets=simple_queryall($streets_q);
if (!empty ($alladdrz)) {
   
        foreach ($alladdrz as $io1=>$eachaddress) {
        $address[$eachaddress['id']]=array('login'=>$eachaddress['login'],'aptid'=>$eachaddress['aptid']);
        }
        foreach ($allapt as $io2=>$eachapt) {
        $apts[$eachapt['id']]=array('apt'=>$eachapt['apt'],'buildid'=>$eachapt['buildid']);
        }
        foreach ($allbuilds as $io3=>$eachbuild) {
        $builds[$eachbuild['id']]=array('buildnum'=>$eachbuild['buildnum'],'streetid'=>$eachbuild['streetid']);
        }
        foreach ($allstreets as $io4=>$eachstreet) {
        $streets[$eachstreet['id']]=array('streetname'=>$eachstreet['streetname'],'cityid'=>$eachstreet['cityid']);
        }

    foreach ($address as $io5=>$eachaddress) {
        $apartment=$apts[$eachaddress['aptid']]['apt'];
        $building=$builds[$apts[$eachaddress['aptid']]['buildid']]['buildnum'];
        $streetname=$streets[$builds[$apts[$eachaddress['aptid']]['buildid']]['streetid']]['streetname'];
        $cityid=$streets[$builds[$apts[$eachaddress['aptid']]['buildid']]['streetid']]['cityid'];
        // zero apt handle
        if ($alterconf['ZERO_TOLERANCE']) {
            if ($apartment==0) {
            $apartment_filtered='';
            } else {
            $apartment_filtered='/'.$apartment;
            }
        } else {
        $apartment_filtered='/'.$apartment;    
        }
    
        if (!$alterconf['CITY_DISPLAY']) {
        $result[$eachaddress['login']]=$streetname.' '.$building.$apartment_filtered;
        } else {
        $result[$eachaddress['login']]=$cities[$cityid].' '.$streetname.' '.$building.$apartment_filtered;
        }
    }
}

return($result);
}


// Выбираем все адреса в виде stargazer
function pb_AddressGetFulladdresslistStg() {
    global $in_charset,$out_charset;
    $result=array();
    $query="SELECT `login`,`Address` from `users`";
    $alladdress=simple_queryall($query);
    if (!empty ($alladdress)) {
        foreach ($alladdress as $io=>$eachaddress) {
            if ($in_charset==$out_charset) {
                $useraddress=$eachaddress['Address'];
            } else {
                    $useraddress=iconv($in_charset, $out_charset, $eachaddress['Address']);
                   }
            $result[$eachaddress['login']]=$useraddress;
        }
    }
    return ($result);
}

//Выбираем все ФИО пользователей в формате Ubilling
function pb_UserGetAllRealnames() {
    $query_fio="SELECT * from `realname`";
    $allfioz=simple_queryall($query_fio);
    $fioz=array();
    if (!empty ($allfioz)) {
        foreach ($allfioz as $ia=>$eachfio) {
            $fioz[$eachfio['login']]=$eachfio['realname'];
          }
    }
    return($fioz);
}


//Выбираем все ФИО пользователей в формате stargazer
function pb_UserGetAllRealnamesStg() {
    global $in_charset,$out_charset;
    $query_fio="SELECT `login`,`RealName` from `users`";
    $allfioz=simple_queryall($query_fio);
    $fioz=array();
    if (!empty ($allfioz)) {
        foreach ($allfioz as $ia=>$eachfio) {
              if ($in_charset==$out_charset) {
                  $userrealname=$eachfio['RealName'];
              } else {
                  $userrealname=iconv($in_charset, $out_charset, $eachfio['RealName']);
              }
            $fioz[$eachfio['login']]=$userrealname;
          }
    }
    return($fioz);
}


//Выбираем все телефоны в формате Ubilling
function pb_UserGetAllPhones() {
    $query="SELECT * from `phones`";
    $allphones=simple_queryall($query);
    $result=array();
    if (!empty ($allphones)) {
        foreach ($allphones as $io=>$eachphone) {
            $result[$eachphone['login']]=$eachphone['phone'];
          }
    }
    return($result);
}

//Выбираем телефоны в формате stargazer
function pb_UserGetAllPhonesStg() {
    $query="SELECT `login`,`Phone` from `users`";
    $allphones=simple_queryall($query);
    $result=array();
    if (!empty ($allphones)) {
        foreach ($allphones as $io=>$eachphone) {
            $result[$eachphone['login']]=$eachphone['Phone'];
          }
    }
    return($result);
}


//Получаем Тарифы всех пользователей
function pb_StgGetAllTariffs() {
    $result=array();
    $query="SELECT `login`,`Tariff` from `users`";
    $alltariffs=simple_queryall($query);
    if (!empty ($alltariffs)) {
        foreach ($alltariffs as $io=>$eachtariff) {
            $result[$eachtariff['login']]=$eachtariff['Tariff'];
        }
    }
    return ($result);
}

//Получаем стоимость всех тарифов
function pb_StgGetAllTariffPrices() {
    $result=array();
    $query="SELECT `name`,`Fee` from `tariffs`";
    $allprices=simple_queryall($query);
    if (!empty ($allprices)) {
        foreach ($allprices as $io=>$eachprice) {
            $result[$eachprice['name']]=$eachprice['Fee'];
        }
    }
    return ($result);
}

///////////////////////////////////////// Таки сама механика ///////

if ($debug) {
    print_r($_GET);
}

if ($billing=='UB') {
    $alladdress=pb_AddressGetFulladdresslist();
    $allrealnames=pb_UserGetAllRealnames();
    $allphones=pb_UserGetAllPhones();
}

if ($billing=='STG') {
    $alladdress=pb_AddressGetFulladdresslistStg();
    $allrealnames=pb_UserGetAllRealnamesStg();
    $allphones=pb_UserGetAllPhonesStg();
}

$alltariffs=pb_StgGetAllTariffs();
$alltariffprices=  pb_StgGetAllTariffPrices();

//если мы поймали попытку поиска по логину в виде:
// ?action=bill_search&bill_identifier=
if(isset($_GET['action'])) {
    if ($_GET['action']=='bill_search') {
        $search_pattern=$_GET['bill_identifier'];
    }
}


// в общем если поймали паттерн для поиска проверяем есть ли такой юзер вообще 
// для упрощения ориентируемся по паре логин+тариф
if (!empty ($search_pattern)) {
    if (!empty ($alltariffs[$search_pattern])) {
        //показываем ответ с потрохами пользователя
        $needsumm=$alltariffprices[$alltariffs[$search_pattern]];
        
        $search_ok_reply='
    <?xml version="1.0" encoding="UTF-8" standalone="yes"?>
    <ResponseDebt>
    <debtPayPack phone="'.$allphones[$search_pattern].'" fio="'.$allrealnames[$search_pattern].'" bill_period="'.date("Ym").'" bill_identifier="'.$search_pattern.'"  address="'.$alladdress[$search_pattern].'">
       <service>
           <ks company_code="'.$company_code.'" service_code="'.$service_code.'" service="'.$service.'"/>
           <debt amount_to_pay="'.$needsumm.'"/>
           <payer ls="'.$search_pattern.'"/>
       </service>
       <message>'.$message_ok.'</message>
        </debtPayPack>
    </ResponseDebt>
    ';
        print($search_ok_reply);
    } else {
        //ну и если нету такой жертвы - показываем фейл
        $search_fail='
            <?xml version="1.0" encoding="UTF-8" standalone="yes"?>
            <ResponseDebt>
                <errorResponse>
                  <code>2</code>
                  <message>'.$message_fail.'</message>
                </errorResponse>
            </ResponseDebt>
            ';
        print($search_fail);
        
    }
}
// Конец поиска




// Ловим сообщения о совершенных платежах в виде:
// ?action=bill_input&bill_identifier=ifvya13ap22_iejd&summ=150&pkey=aaa444455656&date=2011-12-02T18:20:15
if(isset($_GET['action'])) {
    if ($_GET['action']=='bill_input') {
        $customerid=$_GET['bill_identifier'];
        //если все нормально регистрируем новую транзакцию
        if (!empty ($customerid)) {
           $summ=$_GET['summ'];
           $hash=$_GET['pkey'];
           $date=$_GET['date'];
           op_TransactionAdd($hash, $summ, $customerid, 'PBANK', $date);
           op_ProcessHandlers();
           $transaction_ok='
               <?xml version="1.0" encoding="UTF-8" standalone="yes"?>
                <ResponseExtInputPay>
                  <extInputPay>
                   <response_code>0<response_code>
                   <inner_ref>'.$hash.'</inner_ref>
                </extInputPay>
              </ResponseExtInputPay>
               ';
           print($transaction_ok);
        }
    }
}


?>
