<?php

// Send main headers
header('Last-Modified: ' . date('r')); 
header("Cache-Control: no-store, no-cache, must-revalidate"); 
header("Pragma: no-cache");

function rcms_redirect($url, $header = false) {
    if($header){ 
        @header('Location: ' . $url); 
        } else { 
          print('<script language="javascript">document.location.href="' . $url . '";</script>'); 
         }
}

$ContentContainer='';
$statsconfig=zbs_LoadConfig();




 
// set default lang
$lang = $statsconfig['lang'];

if($statsconfig['allowclang']) { // if language change is allowed
	if(isset($_GET['changelang']) ) {
		$lang = $_GET['changelang'];
	} else {
		if(isset($_COOKIE['zbs_lang'])) {
			$lang = $_COOKIE['zbs_lang'];
		}
	}
}

setcookie("zbs_lang", $lang, time() + 3600);
$langglobal = zbs_LoadLang($lang);

//setting auth cookies subroutine
if ($statsconfig['auth']=='login') { //if enabled login based auth
    if ((isset($_POST['ulogin'])) AND isset($_POST['upassword'])) {
        $ulogin=trim(vf($_POST['ulogin']));
        $upassword=trim(vf($_POST['upassword']));
        $upassword=md5($upassword);
        setcookie("ulogin", $ulogin, time() + 3600);
        setcookie("upassword", $upassword, time() + 3600);
        rcms_redirect("index.php");

    }
    
    if (isset($_POST['ulogout'])) {
        setcookie("upassword", 'nopassword', time() + 3600);
        rcms_redirect("index.php");
    }
}

function zbs_LoadConfig() {
    $config=parse_ini_file('config/userstats.ini');
    return($config);
}

function zbs_LoadLang($language) {
   $language=vf($language);
   $language=preg_replace('/\0/s', '', $language);
 if (file_exists('languages/'.$language.'/lang.php')) {
 include('languages/'.$language.'/lang.php');
 } else {
  include('languages/english/lang.php');
 }
 return($lang);
}


function __($str) {
    global $langglobal;
     if ((isset ($langglobal['def'][$str])) AND (!empty($langglobal['def'][$str]))) {
        return ($langglobal['def'][$str]);
        } else {
        return($str);
    }
      
}

function zbs_ShowTemplate() {
    global $ContentContainer;
    include ('template.html');
}

function show_window($title,$data) {
    global $ContentContainer;
    $window_content='
        <table width="100%" border="0">
        <tr>
        <td><h2>'.@$title.'</h2></td>
        </tr>
        <tr>
        <td valign="top">
        '.@$data.'
        </td>
        </tr>
        </table>
        ';
    $ContentContainer=$ContentContainer.$window_content;
}

function deb($data) {
    show_window('DEBUG', $data);
}

function debarr($data) {
    show_window('DEBUG', '<pre>'.print_r($data,true).'</pre>');
}

function rcms_scandir($directory, $exp = '', $type = 'all', $do_not_filter = false) {
	$dir = $ndir = array();
	if(!empty($exp)){
		$exp = '/^' . str_replace('*', '(.*)', str_replace('.', '\\.', $exp)) . '$/';
	}
	if(!empty($type) && $type !== 'all'){
		$func = 'is_' . $type;
	}
	if(is_dir($directory)){
		$fh = opendir($directory);
		while (false !== ($filename = readdir($fh))) {
			if(substr($filename, 0, 1) != '.' || $do_not_filter) {
				if((empty($type) || $type == 'all' || $func($directory . '/' . $filename)) && (empty($exp) || preg_match($exp, $filename))){
					$dir[] = $filename;
				}
			}
		}
		closedir($fh);
		natsort($dir);
	}
	return $dir;
}

function zbs_LoadModule($modulename) {
    $modulename=vf($modulename);
    $modulename=preg_replace('/\0/s', '', $modulename);
    $module_path='modules/general/';
    if (file_exists($module_path.$modulename.'/index.php')) {
        include($module_path.$modulename.'/index.php');
        
    } else {
        die('Wrong module');
    }
}

//returns current date and time in mysql DATETIME view
function curdatetime() {
    $currenttime=date("Y-m-d H:i:s");
    return($currenttime);
}

function ip2int($src){
  $t = explode('.', $src);
  return count($t) != 4 ? 0 : 256 * (256 * ((float)$t[0] * 256 + (float)$t[1]) + (float)$t[2]) + (float)$t[3];
}

function int2ip($src){
  $s1 = (int)($src / 256);
  $i1 = $src - 256 * $s1;
  $src = (int)($s1 / 256);
  $i2 = $s1 - 256 * $src;
  $s1 = (int)($src / 256);
  return sprintf('%d.%d.%d.%d', $s1, $src - 256 * $s1, $i2, $i1);
}

?>
