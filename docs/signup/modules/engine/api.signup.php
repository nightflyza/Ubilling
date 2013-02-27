<?php



function sn_LoadConfig() {
    $path="config/signup.ini";
    $result=parse_ini_file($path);
    return ($result);
}



$db = new MySQLDB();
$conf=sn_LoadConfig();

function sn_LoadTemplate($templatename) {
    $path="config/templates/";
    $result=file_get_contents($path.$templatename);
    print($result);
}

function lang($param) {
    global $conf;
    if (isset($conf[$param])) {
        return ($conf[$param]);
    } else {
        return ($param);
    }
}


function sn_ServicesSelect() {
    $services=lang('ISP_SERVICES');
    $expservices=  explode(',', $services);
    
    $result='<select id="service" name="service"  class="input_field">';
    
    if (!empty($expservices)) {
        foreach ($expservices as $eachservice) {
            $result.='<option value="'.$eachservice.'">'.$eachservice.'</option>';
        }
    }
    $result.='</select>';
    
    print($result);
}


function sn_StreetsArray() {
    $hide=lang("HIDE_STREETS");
    $hide=explode(',',$hide);
    $result='';
    $counter=0;
    
    $query="SELECT `streetname` from `street` ORDER BY `streetname` ASC";
    $allstreets=  simple_queryall($query);
    if (!empty($allstreets)) {
        $streetcount=sizeof($allstreets);
        foreach ($allstreets as $io=>$eachstreet) {
            $counter++;
            if ($counter<$streetcount) {
                $ending=',';
            } else {
                $ending='';
            }
            if (!in_array($eachstreet['streetname'], $hide)) {
            $result.='"'.$eachstreet['streetname'].'"'.$ending;
            }
            
        }
    }
    
    print($result);
}

function sn_CheckPost($params) {
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

   

function sn_CheckFields() {
    if (sn_CheckPost(array('street','build','realname','phone','service'))) {
        if (!sn_CheckPost(array('lastname'))) {
           return (true);
        } else {
           return (false); 
        }
    } else {
        return (false);
    }
}


function sn_MailNotify($text) {
global $conf;

$mail=$conf['NOTIFY_MAIL'];
if (!empty($mail)) {
    $to      = $mail;
    $subject = lang('L_REQFORM');
    $message = strip_tags($text);
    $headers = 'From: '.$mail . "\r\n" .
               'Reply-To: '.$mail . "\r\n" .
               'X-Mailer: PHP/' . phpversion();
    mail($to, $subject, $message, $headers);
    }
}


function sn_CreateRequest() {
    $date=date("Y-m-d H:i:s");
    $ip=$_SERVER['REMOTE_ADDR'];
    $state=0;
    
    
    //sanitize data
    $street=trim($_POST['street']);
    $street=strip_tags($street);
    $street=mysql_real_escape_string($street);
    
    $build=trim($_POST['build']);
    $build=strip_tags($build);
    $build=mysql_real_escape_string($build);
    
    $apt=trim($_POST['apt']);
    $apt=strip_tags($apt);
    $apt=mysql_real_escape_string($apt);
    
    $realname=trim($_POST['realname']);
    $realname=strip_tags($realname);
    $realname=mysql_real_escape_string($realname);
    
    $phone=trim($_POST['phone']);
    $phone=strip_tags($phone);
    $phone=mysql_real_escape_string($phone);
    
    $service=trim($_POST['service']);
    $service=strip_tags($service);
    $service=mysql_real_escape_string($service);
    
    $notes=trim($_POST['notes']);
    $notes=strip_tags($notes);
    $notes=mysql_real_escape_string($notes);
    
    //construct query
    $query="INSERT INTO `sigreq` (
                        `id` ,
                        `date` ,
                        `state` ,
                        `ip` ,
                        `street` ,
                        `build` ,
                        `apt` ,
                        `realname` ,
                        `phone` ,
                        `service` ,
                        `notes`
                        )
                        VALUES (
                        NULL ,
                        '".$date."',
                        '".$state."',
                        '".$ip."',
                        '".$street."',
                        '".$build."',
                        '".$apt."',
                        '".$realname."',
                        '".$phone."',
                        '".$service."',
                        '".$notes."'
                        );
";
  //push query
  nr_query($query);
  
  //mail notify
  
  $notifybody='
DATE:      '.$date.'
PHONE:     '.$phone.'
';
  
  sn_MailNotify($notifybody);
  
  print(lang('L_DONE'));
     
}




?>
