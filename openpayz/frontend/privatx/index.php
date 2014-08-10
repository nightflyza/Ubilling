<?php
/*
 * Фронтенд для получения уведомлений о платежах от Приватбанка
 * Протокол: https://docs.google.com/document/d/1JrH84x2p4FOjm89q3xArvnEfsFXRnbIoa6qJFNq2VYw/edit#
 * 
 * Возможно получение запросов как в виде отдельной POST переменной, так и в виде HTTP_RAW_POST_DATA
 * Идентификация абонента по лицевому счету в виде paymentID материализующемуся из вьюшки вида:
 * CREATE VIEW op_customers (realid,virtualid) AS SELECT users.login, CRC32(users.login) FROM `users`;
 */


/////////// Секция настроек
// Имя POST переменной в которой должны приходить запросы, либо raw в случае получения 
// запросов в виде HTTP_RAW_POST_DATA.
define('PBX_REQUEST_MODE', 'xml');

//Текст уведомлений и екзепшнов
define('ISP_NAME','НашПровайдер'); //Информация о поставщике услуг
define('ISP_CODE','1'); // Id в ПС
define('ISP_SERVICE_NAME','Интернет'); // Наименование услуги
define('ISP_SERVICE_CODE','101'); //Код услуги


define('PBX_EX_NOT_FOUND', 'Абонент не найден');

// подключаем API OpenPayz
include ("../../libs/api.openpayz.php");

error_reporting(E_ALL);

// Send main headers
header('Last-Modified: ' . gmdate('r')); 
header('Content-Type: text/html; charset=utf-8');
header("Cache-Control: no-store, no-cache, must-revalidate"); // HTTP/1.1 
header("Pragma: no-cache");


/**
 *
 * Check for POST have needed variables
 *
 * @param   $params array of POST variables to check
 * @return  bool
 *
 */
function pbx_CheckPost($params) {
    $result = true;
    if (!empty($params)) {
        foreach ($params as $eachparam) {
            if (isset($_POST[$eachparam])) {
                if (empty($_POST[$eachparam])) {
                    $result = false;
                }
            } else {
                $result = false;
            }
        }
    }
    return ($result);
}

/*
 * Returns request data
 * 
 * @return string
 */

function pbx_RequestGet() {
    $result = '';
    if (PBX_REQUEST_MODE != 'raw') {
        if (pbx_CheckPost(array(PBX_REQUEST_MODE))) {
            $result = $_POST[PBX_REQUEST_MODE];
        }
    } else {
        $result = $HTTP_RAW_POST_DATA;
    }
    return ($result);
}

/*
 * String entity search
 * 
 * @param $string - string variable to compare
 * @param $search - searched substring
 * @return bool
 */

function pbx_ispos($string, $search) {
    if (strpos($string, $search) === false) {
        return(false);
    } else {
        return(true);
    }
}

/*
 * Returns all user RealNames
 * 
 * @return array
 */

function pbx_UserGetAllRealnames() {
    $query = "SELECT * from `realname`";
    $all = simple_queryall($query);
    $result = array();
    if (!empty($all)) {
        foreach ($all as $io => $each) {
            $result[$each['login']] = $each['realname'];
        }
    }
    return($result);
}

/*
 * Returns user stargazer data by login
 * 
 * @param string $login existing stargazer login
 * 
 * @return array
 */
function pbx_UserGetStargazerData($login) {
    $login=  mysql_real_escape_string($login);
    $query="SELECT * from `users` WHERE `login`='".$login."';";
    $result=  simple_query($query);
    return ($result);
}

/*
 * Returns all user mobile phones
 * 
 * @return array
 */

function pbx_UserGetAllMobiles() {
    $query = "SELECT * from `phones`";
    $all = simple_queryall($query);
    $result = array();
    if (!empty($all)) {
        foreach ($all as $io => $each) {
            $result[$each['login']] = $each['mobile'];
        }
    }
    return($result);
}

/*
 * Returns all tariff prices array
 * 
 * @return array
 */

function pbx_TariffGetPricesAll() {
    $query = "SELECT `name`,`Fee` from `tariffs`";
    $allprices = simple_queryall($query);
    $result = array();

    if (!empty($allprices)) {
        foreach ($allprices as $io => $eachtariff) {
            $result[$eachtariff['name']] = $eachtariff['Fee'];
        }
    }

    return ($result);
}

/*
 * Returns full address list
 * 
 * @return array
 */
function pbx_AddressGetFulladdresslist() {
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


/*
 * Returns presearch reply
 * 
 * @return string
 */

function pbx_ReplyPresearch($customerid) {
    $allcustomers=  op_CustomersGetAll();
    
    if (isset($allcustomers[$customerid])) {
        $customerLogin = $allcustomers[$customerid];
        $allrealnames= pbx_UserGetAllRealnames();

        //normal search reply
        $templateOk = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
                <Transfer xmlns="http://debt.privatbank.ua/Transfer" interface="Debt" action="Presearch">
                <Data xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:type="PayersTable">
                <Headers>
                <Header name="fio"/>
                <Header name="ls"/>
                </Headers>
                <Columns>
                <Column>
                 <Element>' . @$allrealnames[$customerLogin] . '</Element>
                </Column>
                <Column>
                 <Element>' . $customerid . '</Element>
                </Column>
                </Columns>
                </Data>
                </Transfer>';
        $result = $templateOk;
    } else {
        //search fail reply template
        $templateFail = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
                    <Transfer xmlns="http://debt.privatbank.ua/Transfer" interface="Debt" action="Presearch">
                    <Data xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:type="ErrorInfo" code="99">
                     <Message>' . PBX_EX_NOT_FOUND . '</Message>
                    </Data>
                    </Transfer>';
        $result = $templateFail;
    }
    $result = trim($result);
    return ($result);
}

/*
 * Returns search reply
 * 
 * @return string
 */

function pbx_ReplySearch($customerid) {
    $allcustomers=  op_CustomersGetAll();
    if (isset($allcustomers[$customerid])) {
        $customerLogin=$allcustomers[$customerid];
        $allrealnames= pbx_UserGetAllRealnames();
        $alladdress=  pbx_AddressGetFulladdresslist();
        $allmobiles= pbx_UserGetAllMobiles();
        $userdata=  pbx_UserGetStargazerData($customerLogin);
        $userBalance=$userdata['Cash'];
        
        //normal reply
        $templateOk='<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
                    <Transfer xmlns="http://debt.privatbank.ua/Transfer" interface="Debt" action="Search">
                    <Data xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:type="DebtPack" billPeriod="'.date("Ym").'">
                    <PayerInfo billIdentifier="'.$customerid.'">
                     <Fio>'.@$allrealnames[$customerLogin].'</Fio>
                     <Phone>'.@$allmobiles[$customerLogin].'</Phone>
                     <Address>'.@$alladdress[$customerLogin].'</Address>
                    </PayerInfo>
                    <ServiceGroup>
                     <DebtService  serviceCode="'.ISP_SERVICE_CODE.'" >
                        <CompanyInfo>
                         <CompanyCode>'.ISP_CODE.'</CompanyCode>
                         <CompanyName>'.ISP_NAME.'</CompanyName>
                        </CompanyInfo>
                        <DebtInfo>
                         <Balance>'.$userBalance.'</Balance>
                        </DebtInfo>
                       <ServiceName>'.ISP_SERVICE_NAME.'</ServiceName>
                    </DebtService>
                    </ServiceGroup>
                    </Data>
                    </Transfer>
                    ';
        $result = $templateOk;
    } else {
        //reply fail
        $templateFail='<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
                        <Transfer xmlns="http://debt.privatbank.ua/Transfer" interface="Debt" action="Search">
                        <Data xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:type="ErrorInfo" code="2">
                        <Message>'.PBX_EX_NOT_FOUND.'</Message>
                        </Data>
                        </Transfer>';
        $result=$templateFail;
    }
    $result=trim($result);
    return ($result);
}

/*
 * Returns payment possibility reply
 * 
 * @return string
 */

function pbx_ReplyCheck($customerid) {
    $allcustomers=  op_CustomersGetAll();
     if (isset($allcustomers[$customerid])) {
        $customerLogin=$allcustomers[$customerid];
        $reference=rand(10000000,90000000);
        $templateOk='<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
                    <Transfer xmlns="http://debt.privatbank.ua/Transfer" interface="Debt" action="Check">
                    <Data xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:type="Gateway" reference="'.$reference.'" />
                    </Transfer>
                    ';
        $result=$templateOk;
        
     } else {
         $templateFail='<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
                        <Transfer xmlns="http://debt.privatbank.ua/Transfer" interface="Debt" action="Check">
                        <Data xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:type="ErrorInfo" code="2">
                        <Message>'.PBX_EX_NOT_FOUND.'</Message>
                        </Data>
                        </Transfer>
                        ';
         $result=$templateFail;
     }
     $result=trim($result);
    return ($result);
}

/*
 *  Controller part
 */

$xmlRequest = pbx_RequestGet();

//debug 2887647521


$xmlRequest = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Transfer xmlns="http://debt.privatbank.ua/Transfer" interface="Debt" action="Check">
<Data xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:type="Payment">
<CompanyInfo companyId="412341234">
<CompanyCode>1</CompanyCode>
<CompanyName>КП Воддоканал</CompanyName>
<DopData>
<Dop name="доп информация" value="значение" />
</DopData>
</CompanyInfo>
<PayerInfo billIdentifier="1210236" ls="2887647521">
<Fio>Иванов Иван Иванович</Fio>
<Phone>+321234214</Phone>
<Address>пр.Ленина 10 кв 5</Address>
</PayerInfo>
<TotalSum>0.01</TotalSum>
<CreateTime>2012-01-01T08:00:00.001+03:00</CreateTime>
<ServiceGroup>
<Service sum="0.01" serviceCode="102">
<CompanyInfo>
<CompanyCode>1</CompanyCode>
<CompanyName>КП Воддоканал</CompanyName>
</CompanyInfo>
<ServiceName>Холоднaя вода</ServiceName>
<Destination>Оплата за услугу "Холодная вода"</Destination>
<MeterData>
<Meter previosValue="213" currentValue="214" tarif="0.01" delta="1" name="Холодная вода кухня"/>
 ...
</MeterData>
<DopData>
<Dop name="city_code" value="3" />
</DopData>
</Service>

</ServiceGroup>
</Data>
</Transfer>';


//raw xml data received
if (!empty($xmlRequest)) {
    $xmlParse = xml2array($xmlRequest);
    if (!empty($xmlParse)) {
        debarr($xmlParse);
       
        // Presearch action handling
        if (isset($xmlParse['Transfer']['Data']['Unit_attr']['name'])) {
            if ($xmlParse['Transfer']['Data']['Unit_attr']['name'] == 'ls') {
                if (isset($xmlParse['Transfer']['Data']['Unit_attr']['value'])) {
                    $customerid = vf($xmlParse['Transfer']['Data']['Unit_attr']['value'], 3);
                    die(pbx_ReplyPresearch($customerid));
                }
            }
        }
        
        // Main search
        if (isset($xmlParse['Transfer']['Data']['Unit_attr']['name'])) {
           if ($xmlParse['Transfer']['Data']['Unit_attr']['name']=='bill_identifier') {
               if (isset($xmlParse['Transfer']['Data']['Unit_attr']['value'])) {
                   if ($xmlParse['Transfer_attr']['action']=='Search') {
                    $customerid=vf($xmlParse['Transfer']['Data']['Unit_attr']['value'],3);
                    die(pbx_ReplySearch($customerid));
                   }
                   
               }
           }
        }
        
        // Check payment possibility
        if (isset($xmlParse['Transfer_attr']['action'])) {
            if ($xmlParse['Transfer_attr']['action']=='Check') {
                if (isset($xmlParse['Transfer']['Data']['PayerInfo_attr']['ls'])) {
                    $customerid=vf($xmlParse['Transfer']['Data']['PayerInfo_attr']['ls'],3);
                    die(pbx_ReplyCheck($customerid));
                }
            }
        }
        
    } else {
        die('XML_PARSER_FAIL');
    }
}
?>