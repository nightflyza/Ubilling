<?php

function rcms_redirect($url, $header = false) {
    if($header){ 
        @header('Location: ' . $url); 
        } else { 
          print('<script language="javascript">document.location.href="' . $url . '";</script>'); 
         }
}

function sn_LoadConfig() {
    $path="config/signup.ini";
    $result=parse_ini_file($path);
    return ($result);
}

function sn_LoadLang($language) {
   $language=vf($language);
   $language=preg_replace('/\0/s', '', $language);
 if (file_exists('languages/'.$language.'/lang.php')) {
 include('languages/'.$language.'/lang.php');
   //additional locale
   if (file_exists('languages/'.$language.'/addons.php')) {
     include('languages/'.$language.'/addons.php');
   }
 } else {
  include('languages/english/lang.php');
 }
 return($lang);
}

$ContentContainer='';
$snConfig=  sn_LoadConfig();

// set default language
$lang = $snConfig['lang'];

$langglobal = sn_LoadLang($lang);
$templateData=array();

function __($str) {
    global $langglobal;
     if ((isset ($langglobal['def'][$str])) AND (!empty($langglobal['def'][$str]))) {
        return ($langglobal['def'][$str]);
        } else {
        return($str);
    }
      
}

function sn_ShowTemplate() {
    global $ContentContainer;
    global $templateData;
    include ('template/template.html');
}

function show_window($title,$data) {
    global $ContentContainer;
    $cells= la_TableCell(la_tag('h2').@$title.la_tag('h2',true));
    $rows=  la_TableRow($cells);
    $cells= la_TableCell(@$data);
    $rows.= la_TableRow($cells);
    $window_content= la_TableBody($rows,'100%',0);
    $ContentContainer=$ContentContainer.$window_content;
}

function deb($data) {
    show_window('DEBUG', $data);
}

function debarr($data) {
    show_window('DEBUG', '<pre>'.print_r($data,true).'</pre>');
}


?>