<?php

/*
 * Фронтенд для получения уведомлений о платежах от Приватбанка
 * Протокол: https://docs.google.com/document/d/1JrH84x2p4FOjm89q3xArvnEfsFXRnbIoa6qJFNq2VYw/edit#
 * 
 * Возможно получение запросов как в виде отдельной POST переменной, так и в виде HTTP_RAW_POST_DATA
 */

/////////// Секция настроек

// Имя POST переменной в которой должны приходить запросы, либо raw в случае получения 
// запросов в виде HTTP_RAW_POST_DATA.
define(PBX_REQUEST_MODE,'xml'); 


// подключаем API OpenPayz
include ("../../libs/api.openpayz.php");


/**
 *
 * Check for POST have needed variables
 *
 * @param   $params array of POST variables to check
 * @return  bool
 *
 */
function pbx_CheckPost($params) {
    $result=true;
    if (!empty ($params)) {
        foreach ($params as $eachparam) {
            if (isset($_POST[$eachparam])) {
                if (empty ($_POST[$eachparam])) {
                $result=false;                    
                }
            } else {
                $result=false;
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
    $result='';
    if (PBX_REQUEST_MODE!='raw') {
        if (pbx_CheckPost(array(PBX_REQUEST_MODE))) {
          $result=$_POST[PBX_REQUEST_MODE];
        }
    } else {
        $result=$HTTP_RAW_POST_DATA;
    }
    return ($result);
}

/*
 * Checks array values recursively
 * 
 * @return bool
 */


$xmlRequest=  pbx_RequestGet();

//debug
$xmlRequest='<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Transfer xmlns="http://debt.privatbank.ua/Transfer" interface="Debt" action="Presearch">
    <Data xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:type="Payer">
        <Unit name="ls" value="710005007302" />
    </Data>
</Transfer>';


//raw xml data received
if (!empty($xmlRequest)) {
    $xmlParse=  xml2array($xmlRequest);
   // debarr($xmlParse);
    
    // Presearch action handling
    if (isset($xmlParse['Transfer']['Data']['Unit_attr']['name'])) {
          if ($xmlParse['Transfer']['Data']['Unit_attr']['name']=='ls') {
                                    deb('PRESEARCH MAZAFAKA!!!!!1111'); 
                                }
    }
    
    
    
}

?>