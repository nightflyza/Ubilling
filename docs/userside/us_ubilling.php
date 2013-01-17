<?php
/*
 * Скрипт осуществляет перенос необходимых данных из базы данных биллинга в систему UserSide
 * Настройка 
 */

$zver="us_ubilling v.1.0"; 

// В файле конфигурации удобно хранить параметры доступа к базам данным и некоторые глобальные параметры
include(dirname(__FILE__)."/us_config.tmp"); //(для работы крона необходимо прописать абсолютный путь к файлу конфигурации)
include(dirname(__FILE__)."/us_api.php");

//В файлах логов фиксируется много полезной информации, что помогает в анализе работы скрипта и поиске ошибок
$ps_repalllog=$ps_logpath."rep_alllog.txt"; // Общий лог всех запусков скрипта. Содержит дату запуска, дату завершения работы и версию скрипта
$ps_rep_logupd=$ps_logpath."rep_logupd.txt"; // Лог текущего либо последнего цикла работы
$ps_rep_sql=$ps_logpath."rep_mysql.txt"; // Команды, посылаемые в базу данных UserSide. Здесь не все команды скрипта, а только те, которые явно указаны (см. ниже)
set_time_limit(0);


log_append($ps_repalllog, $zver." Update START at ".date("d.m.Y H:i:s")."\n");

// Блок фиксации отметки о запуске скрипта, для предотвращения запуска второй копии.
$pidfile=$ps_logpath."pid.txt";
if (file_exists($pidfile)) {
        //Если найден - то выходим
        $oldpid=file_get_contents($pidfile);
        log_append($ps_repalllog, $zver." Find previous PID: ".$oldpid." - exit"."\n");
        log_append($ps_repalllog, $zver." Update FINISH at  ".date("d.m.Y H:i:s")."\n");
        log_append($ps_repalllog, "============================================="."\n");
      // DEBUG  die('PID file already exists');
}

//рисуем новый PID на будущее
file_put_contents($pidfile, '0');

// Выведем заголовок типа данных
print ("Sync module: ".$zver."\n");
print ("==================================="."\n");
print ("start module at ".date("d.m.Y H:i:s")."\n");


$ps_file101=$ps_rep_logupd;
log_append($ps_rep_logupd,"==========================================="."\n");
printlog($zver." начало обновления");


//Открываем базу биллинга
$ub_db['server']=$znserver;
$ub_db['username']=$znuser;
$ub_db['password']=$znpass;
$ub_db['db']=$znbase;
$ub_db['character']=$zncp;

 $conn1 = mysql_connect($ub_db['server'], $ub_db['username'], $ub_db['password'],true);
    mysql_select_db($ub_db['db'],$conn1);
    mysql_query ("set character_set_client='".$ub_db['character']."'",$conn1); 
    mysql_query ("set character_set_results='".$ub_db['character']."'",$conn1); 
    mysql_query ("set collation_connection='".$ub_db['character']."_general_ci'",$conn1);

    
//Открываем базу userside
$us_db['server']=$zuserver;
$us_db['username']=$zuuser;
$us_db['password']=$zupass;
$us_db['db']=$zubase;
$us_db['character']=$zucp;

$conn2 = mysql_connect($us_db['server'], $us_db['username'], $us_db['password'],true);
    mysql_select_db($us_db['db'],$conn2);
    mysql_query ("set character_set_client='".$us_db['character']."'",$conn2);
    mysql_query ("set character_set_results='".$us_db['character']."'",$conn2);
    mysql_query ("set collation_connection='".$us_db['character']."_general_ci'",$conn2);

//Ставим метку - что мы обновляем данные
printlog("ставим отметку в базе, что начато обновление");
nq($conn2,"update tbl_conf set VALUEINT=1 where PARAM='ISUPDATEDO'");
    

/*Блок получения параметров из UserSide - какие данные следует получать из биллинга (выставляются в UserSide в разделе "Настройка"-"Спецнастройка"
==================================================================================================================================================
Выясняем - обновлять ли адреса домов у пользователей
 */

$ps_houseupd = sq($conn2,"select VALUEINT from tbl_conf where PARAM='ISHOUSEUPDBILLING'");
$ps_houseupd = $ps_houseupd['VALUEINT'];

//Выясняем - обновлять ли ФИО
$ps_updfio = sq($conn2,"select VALUEINT from tbl_conf where PARAM='ISHOUSEUPDBILLING'");
$ps_updfio = $ps_updfio['VALUEINT'];

//Выясняем - обновлять ли MAC-адреса
$ps_macupd = sq($conn2,"select VALUEINT from tbl_conf where PARAM='MACUPD'");
$ps_macupd = $ps_macupd['VALUEINT'];

//Выясняем - ставить ли автообучение MAC-адреса
$ps_updmaccreate = sq($conn2,"select VALUEINT from tbl_conf where PARAM='UPDMACCREATE'");
$ps_updmaccreate = $ps_updmaccreate['VALUEINT'];

/*
#Обработка тарифных планов
#================================================================================================================
# Используется массив $pmas_group для хранения информации о тарифах
# Элементы массива:
# $pmas_group[$pi1][1] - ID группы (по версии биллинга) (текст)
# $pmas_group[$pi1][2] - Название группы (текст)
# $pmas_group[$pi1][3] - Стоимость за переработку трафика (число или ноль)
# $pmas_group[$pi1][4] - Включенный трафик (число или ноль)
# $pmas_group[$pi1][5] - Метод учета трафика (0 - по входящему, 1 - по исходящему, 2 - по сумме, 3 - по максимуму)
# $pmas_group[$pi1][6] - Размер абонплаты (число или ноль)
# $pmas_group[$pi1][7] - Флаг - ежедневная ли абонплата (1 или 0)
# $pmas_group[$pi1][8] - Входящая скорость в kbps (число или ноль)
# $pmas_group[$pi1][9] - Исходящая скорость в kbps (число или ноль)
#================================================================================================================
*/

$ps_file101=$ps_rep_logupd;
printlog("формируем из БД биллинга массив с тарифными планами");
$pi1=0;

//выцепляем скоростя всех тарифов
$allspeeds=zb_TariffGetAllSpeeds($conn1);

$rs=sqa($conn1, "SELECT * from `tariffs`");
if (!empty ($rs)) {
    foreach ($rs as $io=>$eachtariff) {
        $pi1++;
        //бай дефолт скорость не режется - считаем 0
        if (isset($allspeeds[$eachtariff['name']])) {
            $speeddown=$allspeeds[$eachtariff['name']]['down'];
            $speedup=$allspeeds[$eachtariff['name']]['up'];
        } else {
            $speeddown=0;
            $speedup=0;
        }
        
        //выдираем тип подсчета трафика
        if ($eachtariff['TraffType']=='up+down') $acctype=2;
        if ($eachtariff['TraffType']=='up') $acctype=1;
        if ($eachtariff['TraffType']=='down') $acctype=0;
        if ($eachtariff['TraffType']=='max') $acctype=3;
                
        //собираем масивчик
        $pmas_group[$pi1][1]=$eachtariff['name']; // ну раз VARCHAR... =)
        $pmas_group[$pi1][2]=$eachtariff['name'];
        $pmas_group[$pi1][3]=$eachtariff['PriceDayB0'];
        $pmas_group[$pi1][4]=$eachtariff['Free'];
        $pmas_group[$pi1][5]=$acctype;
        $pmas_group[$pi1][6]=$eachtariff['Fee'];
        $pmas_group[$pi1][7]=0; //нормальная модель - помесячное снятие
        $pmas_group[$pi1][8]=$speeddown;
        $pmas_group[$pi1][9]=$speedup;
        
    }
}

printlog("вносим данные о группах в БД UserSide. НАЧАЛО");
//Снимаем метку обновления
nq($conn2,"update tbl_group set ISUPD=0");
// Здесь и далее - 102-файл - это фиксация в лог команд, посылаемых в базу данных UserSide
$ps_file102=$ps_rep_sql;
printlog_sql(date("d.m.Y H:i:s"));

for ($pi1=1; $pi1<=sizeof($pmas_group)-1;$pi1++){
    //Проверяем - есть ли такая запись уже
    $rs = sq($conn2,"select CODE from tbl_group where CODE='".$pmas_group[$pi1][1]."'");
    $pi_code=$rs['CODE'];
    if ($pi_code!='') {
        // есть такой тариф, круто же :)
         $ps_constr="update tbl_group set GROUPNAME='".$pmas_group[$pi1][2]."',PRICE=".$pmas_group[$pi1][3].",TRAFEX=".$pmas_group[$pi1][4].",TRAFBUH=".$pmas_group[$pi1][5].",ABON=".$pmas_group[$pi1][6].",ABONDAY=".$pmas_group[$pi1][7].",SPEEDRX=".$pmas_group[$pi1][8].",SPEEDTX=".$pmas_group[$pi1][9].",ISUPD=1 where CODE='".$pi_code."';";
         printlog_sql($ps_constr);
         nq($conn2,$ps_constr);       
        
    } else {
        // ухты, нету такого тарифа еще, давайте добавим
        $ps_constr="insert into tbl_group (CODE,GROUPNAME,PRICE,TRAFEX,TRAFBUH,ABON,ABONDAY,SPEEDRX,SPEEDTX,ISUPD) values ('".$pmas_group[$pi1][1]."','".$pmas_group[$pi1][2]."',".$pmas_group[$pi1][3].",".$pmas_group[$pi1][4].",".$pmas_group[$pi1][5].",".$pmas_group[$pi1][6].",".$pmas_group[$pi1][7].",".$pmas_group[$pi1][8].",".$pmas_group[$pi1][9].",1);";
        printlog_sql($ps_constr);
	nq($conn2,$ps_constr);
    }
    
}

//Удаляем необновленные (значит такой тариф уже удален в биллинге и нужно удалить его в UserSide)
nq($conn2,"delete from tbl_group where ISUPD=0");
printlog("вносим данные о группах в БД UserSide. ОКОНЧАНИЕ");


// Достаем из Ubilling всякие штуки которые нам понадобятся потом
$allcontracts=zb_UserGetAllContracts($conn1);
$allregs=zb_UserGetAllUserregDates($conn1);
$allrealnames=zb_UserGetAllRealnames($conn1);
$allnotes=zb_UserGetAllNotes($conn1);
$alladdress=us_AddressGetFulladdresslist($conn1);
$allphones=zb_UserGetAllPhones($conn1);
$allnethosts=us_NethostsGetAll($conn1);
$allstreets=us_AddressGetStreetsAll($conn1);
$allbuilds=us_AddressGetBuildAll($conn1);



/*
#================================================================================================================
# Обработка домов (при условии, что биллинг хранит информацию об адресах абонентов
# Логика работы с адресами может значительно отличаться в разных биллингах. Следует просто понять принципы учета адресов в UserSide и 
# подстроить скрипт под свои фактические потребности.
# В UserSide:
# tbl_street - это таблица с улицами
# tbl_house - это таблица с домами, где tbl_house.STREETCODE=tbl_street.CODE
# tbl_base - это таблица с абонентами, где tbl_base.HOUSECODE=tbl_house.CODE, tbl_base.APART - номер квартиры, tbl_base.FLOOR - этаж, tbl_base.PODEZD - номер подъезда
#================================================================================================================
*/
$ps_file101=$ps_rep_logupd;
printlog("Формируем массив об улицах подключения");


if (!empty ($allstreets)) {
    foreach ($allstreets as $io=>$eachstreet) {
        // проверяем нету ли уже этой улицы
        if (!sq($conn2,"SELECT `CODE` from `tbl_street` WHERE `CODE`='".$io."'")) {
        nq($conn2, "INSERT INTO `tbl_street` (`CODE` ,`STREET` ,`ISDEL`)VALUES ('".$io."' , '".enccorr($eachstreet)."', '');"); // фигачим все наши улицы в базу
        }
    }
}

printlog("Формируем массив с домами");

if (!empty ($allbuilds)) {
    foreach ($allbuilds as $io=>$each) {
        // проверяем нету ли такого дома уже
        if (!sq($conn2,"SELECT `CODE` from `tbl_house` WHERE `CODE`='".$each['id']."'")) {
        nq($conn2, "INSERT INTO `tbl_house` (`CODE`,`STREETCODE` ,`HOUSE`) VALUES ('".$each['id']."','".$each['streetid']."','".vf($each['buildnum'],3)."');"); // фигачим все наши дома в базу
        }
    }
}


/*
 * Здесь какая-то туманная фигня с форматом массива адресов, переходим к юзерам
 * 
 */



/*
#================================================================================================================
#Обработка пользователей
# Используется массив $pmas5 для хранения информации об абонентах
# Элементы массива:
# $pmas5[$pi1][1] - ID пользователя (по версии биллинга) (число)
# $pmas5[$pi1][2] - ID тарифа пользователя (по версии биллинга) (текст) - tbl_group.CODE
# $pmas5[$pi1][3] - Учетная запись абонента (текст)
# $pmas5[$pi1][5] - Номер догвовора с абонентом (текст)
# $pmas5[$pi1][6] - Дата договора с абонентом (текст)
# $pmas5[$pi1][7] - ФИО абонента (текст)
# $pmas5[$pi1][8] - Комментарий из биллинга (текст)
# $pmas5[$pi1][9] - Баланс (число или ноль)
# $pmas5[$pi1][10] - Статус работы (0 - не работает, 1 - в паузе, 2 - остановлен)
# $pmas5[$pi1][11] - Дата подключения к сети (дата)
# $pmas5[$pi1][12] - Входящий трафик - байт (число или ноль)
# $pmas5[$pi1][13] - Исходящий трафик - байт (число или ноль)
# $pmas5[$pi1][14] - Дата последней активности в интернете (дата)
# $pmas5[$pi1][18] - Номер квартиры (число или ноль)
# $pmas5[$pi1][20] - Код дома абонента по версии UserSide - tbl_house.CODE (необходимо предварительно обрабатывать адреса)
# $pmas5[$pi1][21] - Этаж (число или ноль)
# $pmas5[$pi1][22] - Подъезд (число или ноль)
# $pmas5[$pi1][23] - Мобильный телефон (текст)
# $pmas5[$pi1][24] - Размер кредита (число или ноль)
# $pmas5[$pi1][27] - Скидка в процентах (число или ноль)
# $pmas5[$pi1][28] - Телефон (текст)

# Используется массив $pmas_ip для хранения информации об IP-адресах абонентов
# Элементы массива:
# $pi_id - ID пользователя (по версии биллинга) (число)
# $pmas_ip[$pi_id][0] - к-во IP-адресов у этого пользователя
# $pmas_ip[$pi_id][n] - IP-адрес номер n....

# Используется массив $pmas_mac для хранения информации об MAC-адресах абонентов
# Элементы массива:
# $pi_id - ID пользователя (по версии биллинга) (число)
# $pmas_mac[$pi_id][n] - MAC-адрес номер n.... для IP-адреса n.... , т.е. $pmas_mac[$pi_id][1] - это MAC-адрес для IP-адреса $pmas_ip[$pi_id][1]
#================================================================================================================
*/


//Если в биллинге хранится обширная статистика, то тогда не имеет смысла обрабатывать её всю. 
//Достаточно обрабатывать не более 5 суток для получения актуальных данных
//Если скрипт работает постоянно - то этой информации избыточно



printlog("формируем из БД биллинга массив с пользователями");

$ps_constr="SELECT * from `users`";
printlog_sql($ps_constr);
$rs=sqa($conn1, $ps_constr);
$pi1=0;
if (!empty ($rs)) {
    foreach ($rs as $io=>$eachuser) {
        $pi1++;
        $userid=crc16($eachuser['login']);
        $pmas5[$pi1][1]=$userid;
        $pmas5[$pi1][2]=$eachuser['Tariff'];
        $pmas5[$pi1][3]=$eachuser['login'];
        $pmas5[$pi1][4]=1;
        @$pmas5[$pi1][5]=$allcontracts[$eachuser['login']];
        @$pmas5[$pi1][6]=$allregs[$eachuser['login']];
        @$pmas5[$pi1][7]=enccorr($allrealnames[$eachuser['login']]);
        @$pmas5[$pi1][8]=enccorr($allnotes[$eachuser['login']]);
        $pmas5[$pi1][9]=$eachuser['Cash'];
        $pmas5[$pi1][10]=$eachuser['Down'];
        @$pmas5[$pi1][11]=$allregs[$eachuser['login']];
        $pmas5[$pi1][12]=$eachuser['D0'];
        $pmas5[$pi1][13]=$eachuser['U0'];
        $pmas5[$pi1][14]=date("Y-m-d H:i:s",$eachuser['LastActivityTime']);
        @$pmas5[$pi1][18]=enccorr($alladdress[$eachuser['login']]['apt']);
        @$pmas5[$pi1][20]=$alladdress[$eachuser['login']]['buildid'];
        @$pmas5[$pi1][21]=0; //этажи и подъезды пока что не выдергиваем
        @$pmas5[$pi1][22]=0; // всем вечно в падляну их заполнять
        @$pmas5[$pi1][23]=$allphones[$eachuser['login']]['mobile'];
        $pmas5[$pi1][24]=$eachuser['Credit'];
        $pmas5[$pi1][27]=0; // нету блин скидок, все по чесному :)
        @$pmas5[$pi1][28]=$allphones[$eachuser['login']]['phone'];
        
        // собираем попутно айпишки..
        $pmas_ip[$userid][0]=1; // один юзер - одно айпишко
        $pmas_ip[$userid][1]=ip2int($eachuser['IP']);
        //.. и маки пользователей
        $pmas_mac[$userid][1]=prepare_mac($allnethosts[$eachuser['IP']]);
        

    }
}





printlog("вносим данные в БД UserSide. НАЧАЛО");

//Снимаем метку обновления
nq($conn2,"update tbl_base set ISUPD=0");
printlog_sql(date("d.m.Y H:i:s"));


for ($pi1=1; $pi1<=sizeof($pmas5)-1;$pi1++){
    //Обновляем данные в логе - для удобства анализа производительности скрипта
    if (($pi1 % 100) == 0){
        printlog("вносим данные в БД UserSide - ".$pi1.'/'.(sizeof($pmas5)-1));
    }
    
    //Проверяем - есть ли такая запись уже
    $rs=sq($conn2,"select CODETI,LASTPING from tbl_base where CODETI=".$pmas5[$pi1][1]."");
    $pi_code=$rs['CODETI'];
    $ps_lasping=$rs['LASTPING'];

    if ($pi_code != ''){
       //Есть
        $ps_constr="update tbl_base set GROUPN='".$pmas5[$pi1][2]."',LOGNAME='".$pmas5[$pi1][3]."',DOP='".$pmas5[$pi1][8]."',BALANS=".$pmas5[$pi1][9].",SKIDKA=".$pmas5[$pi1][27].",KREDIT=".$pmas5[$pi1][24].",WORKSTATUS=".$pmas5[$pi1][10].",RXTRAF=".$pmas5[$pi1][12].",TXTRAF=".$pmas5[$pi1][13].",ISUPD=1";
	if ($pmas5[$pi1][14] != '1970-01-01'){
	 $ps_constr=$ps_constr.",LASTACT='".$pmas5[$pi1][14]."'";
	}
        //ФИО из биллинга
	if ($ps_updfio == 1){
         $ps_constr=$ps_constr.",FIO='".$pmas5[$pi1][7]."'";
	}
        //Дом из биллинга
		if ($ps_houseupd == 1){
			if ($pmas5[$pi1][20] == ''){
				$pmas5[$pi1][20]=0;
			}
			$ps_constr=$ps_constr.",HOUSEC=".$pmas5[$pi1][20].",PODEZD=".$pmas5[$pi1][22].",FLOOR=".$pmas5[$pi1][21].",APART=".$pmas5[$pi1][18].",TELMOB='".$pmas5[$pi1][23]."'";
			if (($pmas5[$pi1][28] != '') && ($pmas5[$pi1][23] != $pmas5[$pi1][28])){
				$ps_constr=$ps_constr.",TEL='".$pmas5[$pi1][28]."'";
			}
		} else{
			$pmas5[$pi1][20]=0;
		}
                
        $ps_constr=$ps_constr." where CODE=".$pi_code;
        printlog_sql($ps_constr);
        nq($conn2,$ps_constr);
        //Вносим данные по IP-адресам абонента
        $pi_id=$pmas5[$pi1][1];
        $ps_constr="update tbl_ip set ISUPD=0 where TYPER=1 and USERCODE=".$pi_id;
        printlog_sql($ps_constr);
        nq($conn2,$ps_constr);
        
        if ($pmas_ip[$pi_id][0]>0){
			for ($pi2=1; $pi2<=$pmas_ip[$pi_id][0];$pi2++){		
				//Проверяем - есть ли такой ИП уже в базе
				$rs = sq($conn2,"select USERCODE from tbl_ip where TYPER=1 and USERCODE=".$pi_code." and USERIP=".$pmas_ip[$pi_id][$pi2]);
				$pi_usercode=$rs['USERCODE'];
				if ($pi_usercode == $pi_code){
					if ($ps_macupd == 1){
						$ps_constr="update tbl_ip set ISUPD=1,MAC='".$pmas_mac[$pi_id][$pi2]."',UPDMAC=0 where TYPER=1 and USERCODE=".$pi_code." and USERIP=".$pmas_ip[$pi_id][$pi2];
					}else{
						$ps_constr="update tbl_ip set ISUPD=1 where TYPER=1 and USERCODE=".$pi_code." and USERIP=".$pmas_ip[$pi_id][$pi2];
					}
					printlog_sql($ps_constr);
                                        nq($conn2,$ps_constr);
				} else {
					if ($ps_macupd == 1){
						$ps_constr="insert into tbl_ip (TYPER,USERCODE,USERIP,MAC,ISUPD,UPDMAC) values (1,".$pi_code.",".$pmas_ip[$pi_id][$pi2].",'".$pmas_mac[$pi_id][$pi2]."',1,0)";
					}else{
						$ps_constr="insert into tbl_ip (TYPER,USERCODE,USERIP,ISUPD,UPDMAC) values (1,".$pi_code.",".$pmas_ip[$pi_id][$pi2].",1,".$ps_updmaccreate.")";
					}
					printlog_sql($ps_constr);
                                        nq($conn2,$ps_constr);
				}
				nq($conn2,$ps_constr);
				//Удаляем из таблицы неизвестных МАКов
				if ($ps_macupd == 1){
					$ps_constr="delete from tbl_unkmac where MAC='".$pmas_mac[$pi_id][$pi2]."'";
					printlog_sql($ps_constr);
					nq($conn2,$ps_constr);	
				//Указываем в таблице неизвестных маков пеленгатора, что мак известен
					$ps_constr="update tbl_peleng_mac set INBASE=1 where MAC='".$pmas_mac[$pi_id][$pi2]."'";
					printlog_sql($ps_constr);
					nq($conn2,$ps_constr);
				}
			}
		}	
                //Удаляем все IP, которые необновлены
		$ps_constr="delete from tbl_ip where TYPER=1 and USERCODE=".$pi_code." and ISUPD=0";
		printlog_sql($ps_constr);
		nq($conn2,$ps_constr);
		
    } else {
        //Нет
        $ps_constr="insert into tbl_base (
            CODETI,
            GROUPN,
            LOGNAME,
            ISREG,
            LOGPASS,
            DOGNUMBER,
            DATEDOG2,
            FIO,
            NICK,
            PASS,
            HOUSEC,
            PODEZD,
            FLOOR,
            APART,
            APART_B,
            TEL,
            TELMOB,
            EMAIL,
            DATEADD,
            BROWSER,
            DOP,
            DOP2,
            BALANS,
            KREDIT,
            LASTACT,
            LASTPING,
            INKREDIT,
            WORKSTATUS,
            RXTRAF,
            TXTRAF,
            DATEINNET,
            ISUPD,
            SKIDKA) 
            values ('
            ".$pmas5[$pi1][1]."',  -- userid
            '".$pmas5[$pi1][2]."', -- tariff
            '".$pmas5[$pi1][3]."', -- login
             1,  -- isreg
            ".$pmas5[$pi1][4].", -- pass avail
            '".$pmas5[$pi1][5]."', -- contract
            '".$pmas5[$pi1][6]."', -- contractdate
            '".$pmas5[$pi1][7]."', -- realname
            '', -- nickname
            '', -- US password 
            '".$pmas5[$pi1][20]."', -- buildid
            '".$pmas5[$pi1][22]."', -- entrance
            '".$pmas5[$pi1][21]."', -- floor
            '".$pmas5[$pi1][18]."', -- apt
            '',
            '".$pmas5[$pi1][28]."', -- phone
            '".$pmas5[$pi1][23]."', -- mobile
            '',
             NOW(), -- dateadd
            '',
            '".$pmas5[$pi1][8]."', -- note
            '',
            ".$pmas5[$pi1][9].", -- balance
            ".$pmas5[$pi1][24].", -- credit
            '".$pmas5[$pi1][14]."', -- LAT
            '".$pmas5[$pi1][14]."', -- LAT
            0,
            ".$pmas5[$pi1][10].", -- userdown
            ".$pmas5[$pi1][12].", -- downloaded
            ".$pmas5[$pi1][13].", -- uploaded
            '".$pmas5[$pi1][11]."', -- regdate
            1, -- updateflag
            ".$pmas5[$pi1][27]." );";
        printlog_sql($ps_constr);
	nq($conn2,$ps_constr);
        $rs = sq($conn2,"select CODE from tbl_base where CODETI='".$pmas5[$pi1][1]."'");
		$pi_code=$rs['CODE'];
		//USERIP
		$pi_id=$pmas5[$pi1][1];
		if ($pmas_ip[$pi_id][0]>0){
			if ($pi_code != ''){
				for ($pi2=1; $pi2<=$pmas_ip[$pi_id][0];$pi2++){		
					if ($ps_macupd == 1){
						$ps_constr="insert into tbl_ip (TYPER,USERCODE,USERIP,MAC,ISUPD,UPDMAC) values (1,".$pi_code.",".$pmas_ip[$pi_id][$pi2].",'".$pmas_mac[$pi_id][$pi2]."',1,0);";
					}else{
						$ps_constr="insert into tbl_ip (TYPER,USERCODE,USERIP,ISUPD,UPDMAC) values (1,".$pi_code.",".$pmas_ip[$pi_id][$pi2].",1,".$ps_updmaccreate.");";
					}
					printlog_sql($ps_constr);
                                        nq($conn2,$ps_constr);
					//Удаляем из таблицы неизвестных МАКов
					if ($ps_macupd == 1){
						$ps_constr="delete from tbl_unkmac where MAC='".$pmas_mac[$pi_id][$pi2]."'";
						printlog_sql($ps_constr);
                                                nq($conn2,$ps_constr);		
						//Указываем в таблице неизвестных маков пеленгатора, что мак известен
						$ps_constr="update tbl_peleng_mac set INBASE=1 where MAC='".$pmas_mac[$pi_id][$pi2]."'";
						printlog_sql($ps_constr);
                                                nq($conn2,$ps_constr);
					}					
				}
			}
		}	
        
    }
}

printlog("вносим данные в БД UserSide. ОКОНЧАНИЕ");

/*
#================================================================================================================
#Обработка заявок на смену тарифов
# Используется массив $pmas_change для хранения информации о заявках
# Элементы массива:
# $pmas_change[$pi1][1] - ID пользователя (по версии биллинга) (число)
# $pmas_change[$pi1][2] - Следующий тариф - tbl_group.CODE
# $pmas_change[$pi1][3] - Дата подачи заявки (дата)
#================================================================================================================
*/

// смены тарифов есть, способа узнать точное время планирования - нету
// пока что не конвертим


/*
# Нижеуказанные операции являются обязательными при проведении обновления и в общем случае в корректировках не нуждаются
#=================================================================================================================
*/


printlog("системные операции. НАЧАЛО");

//Выясняем время активности
$rs = sq($conn2,"select VALUESTR from tbl_conf where PARAM='ACTIVETIME'");
$ps_activetime=$rs['VALUESTR'];
//Считаем время, с которого пользователь считается активным
$pi1=time()-$ps_activetime*60;
$ps_activetime2=date("Y-m-d H:i:s", $pi1);

printlog("обнуляем трафик у ненайденных пользователей");
nq($conn2,"update tbl_base set RXTRAF=0,TXTRAF=0 where ISUPD=0");


//=================================================================================================================
printlog("обновляем количество пользователей в группах");
$pc_rxtraf2=0;
$pc_txtraf2=0;

$rs = sqa($conn2,"select CODE from tbl_group");
if (!empty ($rs)) {
    foreach ($rs as $io=>$each) {
        	$pc_rxtraf=0;
		$pc_txtraf=0;
		$pi2=0;
                $rs2 = sq($conn2,"select count(CODE), sum(RXTRAF), sum(TXTRAF) from tbl_base where GROUPN='".$each['CODE']."'");
                $pi2=$rs2['count(CODE)'];
                $pc_rxtraf=$rs2['sum(RXTRAF)'];
                $pc_txtraf=$rs2['sum(TXTRAF)'];
                if ($pc_rxtraf == ''){$pc_rxtraf=0;}
		if ($pc_txtraf == ''){$pc_txtraf=0;}
                if ($pi2 == ''){$pi2=0;}
                $pc_rxtraf2=$pc_rxtraf2+$pc_rxtraf;
		$pc_txtraf2=$pc_txtraf2+$pc_txtraf;
          nq($conn2,"update tbl_group set USERS=".$pi2.",TRAFRX2=TRAFRX1,TRAFTX2=TRAFTX1,TRAFRX1=".$pc_rxtraf.",TRAFTX1=".$pc_txtraf." where CODE='".$each['CODE']."'");
    }
    
}

//Фиксируем - трафик итого по базе
$rs=sq($conn2,"select VALUESTR from tbl_conf where PARAM='TRAFRX1'");
$ps1 = $rs['VALUESTR'];
if ($ps1 == '') {$ps1=0;}
nq($conn2,"update tbl_conf set VALUESTR=".$ps1." where PARAM='TRAFRX2'");
nq($conn2,"update tbl_conf set VALUESTR=".$pc_rxtraf2." where PARAM='TRAFRX1'");
$rs = sq($conn2,"select VALUESTR from tbl_conf where PARAM='TRAFTX1'");
$ps1= $rs['VALUESTR'];
if ($ps1 == '') { $ps1=0; }
nq($conn2,"update tbl_conf set VALUESTR=".$ps1." where PARAM='TRAFTX2'");
nq($conn2,"update tbl_conf set VALUESTR=".$pc_txtraf2." where PARAM='TRAFTX1'");

//===========================================================================================================
printlog("обновляем даты по максимальной активности");
//Выясняем - нужно ли обновлять?
$pi1=time()-3600;
$pi2=date("Y-m-d H:i:s", $pi1);
$rs = sqa($conn2,"select DATESTAT from tbl_activtable where DATESTAT>'".$pi2."'");
$pi_count=sizeof($rs);

//Если нужно - обновляем
if ($pi_count<1){
	// Вычисляем сколько активных сейчас
	$rs = sqa($conn2,"select CODE from tbl_base where LASTACT>='".$ps_activetime2."'");
		$pi_count2=sizeof($rs);
	
	//Фиксируем данные
	nq($conn2,"insert into tbl_activtable (DATESTAT,COUNT) values (NOW(),".$pi_count2.")");
}

//=================================================================================================================
printlog("меняем дату активности в сети, если она меньше даты в интернете");
nq($conn2,"update tbl_base set LASTPING=LASTACT where LASTPING<LASTACT or LASTPING is null");

//=================================================================================================================
printlog("обновляем когда был последний положительный остаток на счету");
nq($conn2,"update tbl_base set DATEPLUS=NOW() where BALANS>=0");

//=================================================================================================================
printlog("высчитываем скорость");
//Нужно получить количество секунд, прошедщих с последнего обновления
$rs =sq($conn2,"select VALUEDATE from tbl_conf where PARAM='LASTUPDSCET'");
$pd1= $rs['VALUEDATE'];
$pd2=date('U',strtotime($pd1)); //UNX-дата предыдущего обновления
$pd3=time(); //дата текущего обновления
$pi_interval=$pd3-$pd2; //разница между датами (секунд)
//пересчитываем скорость у групп
$rs = sqa($conn2,"select TRAFRX1,TRAFRX2,TRAFTX1,TRAFTX2,CODE from tbl_group");
if (!empty($rs)) {
    $ln=sizeof($rs);
    foreach ($rs as $io=>$each) {
        //получаем сколько в группе активных
	$rs2 = sq($conn2,"select CODE from tbl_base where GROUPN='".$ln[4]."' and LASTACT>='".$ps_activetime2."'"); //CODE
		$pi_count=sizeof($rs2);
		//вычисляем скорость
		$pi1=$ln[0]-$ln[1]; //TRAFRX1 #TRAFRX2
		$pi2=$ln[2]-$ln[3]; //TRAFTX1 #TRAFTX2
		$pi_rxkbps=0;
		$pi_txkbps=0;
		if ($pi1>0){$pi_rxkbps=$pi1/$pi_interval/1024 * 8;}
		if ($pi2>0){$pi_txkbps=$pi2/$pi_interval/1024 * 8;}
		nq($conn2,"update tbl_group set RXKBPS='".$pi_rxkbps."',TXKBPS='".$pi_txkbps."' where CODE='".$ln[4]."'");  //CODE
    }
}

//Фиксируем скорость итого по базе
$rs = sq($conn2,"select VALUESTR from tbl_conf where PARAM='TRAFRX2'");
$ln = $rs['VALUESTR'];
	$rs2 = sq($conn2,"select VALUESTR from tbl_conf where PARAM='TRAFRX1'");
		$ln2 = $rs2['VALUESTR'];
		$pi1=$ln2[0]-$ln[0]; //VALUESTR #VALUESTR
	$pi_rxkbps=0;
	if ($pi1>0){$pi_rxkbps=$pi1/$pi_interval/1024 * 8;}
	nq($conn2,"update tbl_conf set VALUESTR=".$pi_rxkbps." where PARAM='RXKBPS'");
        
$rs = sq($conn2,"select VALUESTR from tbl_conf where PARAM='TRAFTX2'");
	$ln = $rs['VALUESTR'];
	$rs2 =sq($conn2,"select VALUESTR from tbl_conf where PARAM='TRAFTX1'");
	$ln2 = $rs2['VALUESTR'];
            $pi1=$ln2[0]-$ln[0]; //VALUESTR #VALUESTR
	$pi_rxkbps=0;
	if ($pi1>0){$pi_rxkbps=$pi1/$pi_interval/1024 * 8;}
	nq($conn2,"update tbl_conf set VALUESTR=".$pi_rxkbps." where PARAM='TXKBPS'");

        
//=================================================================================================================
printlog("обновляем даты по максимальному пингу");
//Выясняем - нужно ли обновлять?
$pi1=time()-3600;
$pi2=date("Y-m-d H:i:s", $pi1);
$rs =sqa($conn2,"select DATESTAT from tbl_pingtable where DATESTAT>'".$pi2."'");
	$pi_count=sizeof($rs);
//Если нужно - обновляем
if ($pi_count<1){
	//Вычисляем сколько активных сейчас
	$rs = sq($conn2,"select CODE from tbl_base where LASTPING>='".$ps_activetime2."'");
		$pi_count2=sizeof($rs);
	//Фиксируем данные
	nq($conn2,"insert into tbl_pingtable (DATESTAT,COUNT) values (NOW(),".$pi_count2.")");
}


//=================================================================================================================
printlog("записываем дату обновления");
nq($conn2,"update tbl_conf set VALUEDATE=NOW() where PARAM='LASTUPDSCET'");
//=================================================================================================================


//=================================================================================================================
printlog("делаем запись в истории про обновление");
nq($conn2,"insert into tbl_operhist (OPERCODE,DATEOPER,TYPER,PAR1,PAR2,VAL_OLD,VAL_NEW) values (0,NOW(),3,'','','','')");
//=================================================================================================================
printlog("системные операции. ОКОНЧАНИЕ");

printlog("ставим отметку в базе, что обновление завершено");
nq($conn2,"update tbl_conf set VALUEINT=0 where PARAM='ISUPDATEDO'");
printlog("обновление завершено");
printlog('===========================================');
$ps_file101=$ps_repalllog;
printlog($zver." Update FINISH at ".date("d.m.Y H:i:s", time()));
printlog('===========================================');



        
//клоузим БД
mysql_disconnect($conn1);
mysql_disconnect($conn2);

//грохаем PID на выходе
unlink($pidfile);


?>
