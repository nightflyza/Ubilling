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

setcookie("zbs_lang", $lang, time() + 2592000);
$langglobal = zbs_LoadLang($lang);

//setting auth cookies subroutine
if ($statsconfig['auth']=='login') { //if enabled login based auth
    if ((isset($_POST['ulogin'])) AND isset($_POST['upassword'])) {
        $ulogin=trim(vf($_POST['ulogin']));
        $upassword=trim(vf($_POST['upassword']));
        $upassword=md5($upassword);
        setcookie("ulogin", $ulogin, time() + 2592000);
        setcookie("upassword", $upassword, time() + 2592000);
        rcms_redirect("index.php");

    }
    
    if (isset($_POST['ulogout'])) {
        setcookie("upassword", 'nopassword', time() + 2592000);
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
   //additional locale
   if (file_exists('languages/'.$language.'/addons.php')) {
     include('languages/'.$language.'/addons.php');
   }
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

 function zbs_rand_string($size=4) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyz';
    $string = "";
    for ($p = 0; $p < $size; $p++) {
        $string.= $characters[mt_rand(0, (strlen($characters)-1))];
    }

    return ($string);
 }
 
 
  function zbs_morph($n, $f1, $f2, $f5) {
     $n = abs($n) % 100;
     $n1= $n % 10;
     if ($n>10 && $n<20) return $f5;
     if ($n1>1 && $n1<5) return $f2;
     if ($n1==1) return $f1;
     return $f5;
 } 
 
 
  // literated summ
   // i`m localize it later
    function zbs_num2str($inn, $stripkop=false) {
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
         $o[] = zbs_morph( 0, $forms[1][ 0],$forms[1][1],$forms[1][2]);
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
             $o[] = zbs_morph($ri, $forms[$offset][ 0],$forms[$offset][1],$forms[$offset][2]);
             $offset--;
         }
     }
     // копійки
     if (!$stripkop) {
         $o[] = $kop;
         $o[] = zbs_morph($kop,$forms[ 0][ 0],$forms[ 0][1],$forms[ 0][2]);
     }
     return preg_replace("/\s{2,}/",' ',implode(' ',$o));
 }
 
  function zbs_DownloadFile($filePath,$contentType='') {
    if (!empty($filePath)) {
    if (file_exists($filePath)) {
    $fileContent=  file_get_contents($filePath);
    log_register("DOWNLOAD FILE `".$filePath."`");
    
    if (($contentType=='') OR ($contentType=='default')) {
        $contentType='application/octet-stream';
    } else {
        //additional content types
        if ($contentType=='docx') {
            $contentType='application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
        }
    } 

    header('Content-Type: '.$contentType);
    header('Content-Type: application/octet-stream');
    header("Content-Transfer-Encoding: Binary"); 
    header("Content-disposition: attachment; filename=\"" . basename($filePath) . "\""); 
    die($fileContent);
    
    } else {
        throw new Exception('DOWNLOAD_FILEPATH_NOT_EXISTS');
    }
    } else {
        throw new Exception('DOWNLOAD_FILEPATH_EMPTY');
        
    }
}

?>
