<?php

//include mysql interraction layer and useful debug functions
include("api.mysql.php");
include("api.compat.php");

//creating object for db layer
$db = new MySQLDB();

//register new non processed transaction
function op_TransactionAdd($hash,$summ,$customerid,$paysys,$note) {
    $date=curdatetime();
    $summ=vf($summ);
    $customerid=mysql_real_escape_string($customerid);
    $paysys=mysql_real_escape_string($paysys);
    $note=mysql_real_escape_string($note);
    $hash=mysql_real_escape_string($hash);
    $query="
        INSERT INTO `op_transactions` (
        `id` ,
        `hash` ,
        `date` ,
        `summ` ,
        `customerid` ,
        `paysys` ,
        `processed` ,
        `note`
        )
        VALUES (
        NULL ,'".$hash."' , '".$date."', '".$summ."', '".$customerid."', '".$paysys."', '0', '".$note."'
        );
        ";
    nr_query($query);
}

//loads openpayz config
function op_LoadConfig() {
    $result=@parse_ini_file(dirname(__FILE__).'/../config/openpayz.ini');
    return ($result);
}

//mark transaction processed
function op_TransactionSetProcessed($transactionid) {
    $transactionid=vf($transactionid);
    $query="UPDATE `op_transactions` SET `processed` = '1' WHERE `id`='".$transactionid."'";
    nr_query($query);
}

//native XML parser function
function xml2array($contents, $get_attributes=1, $priority = 'tag') {
    if(!$contents) return array();

    if(!function_exists('xml_parser_create')) {
        print "'xml_parser_create()' function not found!";
        return array();
    }

    //Get the XML parser of PHP - PHP must have this module for the parser to work
    $parser = xml_parser_create('');
    xml_parser_set_option($parser, XML_OPTION_TARGET_ENCODING, "UTF-8"); # http://minutillo.com/steve/weblog/2004/6/17/php-xml-and-character-encodings-a-tale-of-sadness-rage-and-data-loss
    xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, 0);
    xml_parser_set_option($parser, XML_OPTION_SKIP_WHITE, 1);
    xml_parse_into_struct($parser, trim($contents), $xml_values);
    xml_parser_free($parser);

    if(!$xml_values) return;//Hmm...

    //Initializations
    $xml_array = array();
    $parents = array();
    $opened_tags = array();
    $arr = array();

    $current = &$xml_array; //Refference

    //Go through the tags.
    $repeated_tag_index = array();//Multiple tags with same name will be turned into an array
    foreach($xml_values as $data) {
        unset($attributes,$value);//Remove existing values, or there will be trouble

        //This command will extract these variables into the foreach scope
        // tag(string), type(string), level(int), attributes(array).
        extract($data);//We could use the array by itself, but this cooler.

        $result = array();
        $attributes_data = array();
        
        if(isset($value)) {
            if($priority == 'tag') $result = $value;
            else $result['value'] = $value; //Put the value in a assoc array if we are in the 'Attribute' mode
        }

        //Set the attributes too.
        if(isset($attributes) and $get_attributes) {
            foreach($attributes as $attr => $val) {
                if($priority == 'tag') $attributes_data[$attr] = $val;
                else $result['attr'][$attr] = $val; //Set all the attributes in a array called 'attr'
            }
        }

        //See tag status and do the needed.
        if($type == "open") {//The starting of the tag '<tag>'
            $parent[$level-1] = &$current;
            if(!is_array($current) or (!in_array($tag, array_keys($current)))) { //Insert New tag
                $current[$tag] = $result;
                if($attributes_data) $current[$tag. '_attr'] = $attributes_data;
                $repeated_tag_index[$tag.'_'.$level] = 1;

                $current = &$current[$tag];

            } else { //There was another element with the same tag name

                if(isset($current[$tag][0])) {//If there is a 0th element it is already an array
                    $current[$tag][$repeated_tag_index[$tag.'_'.$level]] = $result;
                    $repeated_tag_index[$tag.'_'.$level]++;
                } else {//This section will make the value an array if multiple tags with the same name appear together
                    $current[$tag] = array($current[$tag],$result);//This will combine the existing item and the new item together to make an array
                    $repeated_tag_index[$tag.'_'.$level] = 2;
                    
                    if(isset($current[$tag.'_attr'])) { //The attribute of the last(0th) tag must be moved as well
                        $current[$tag]['0_attr'] = $current[$tag.'_attr'];
                        unset($current[$tag.'_attr']);
                    }

                }
                $last_item_index = $repeated_tag_index[$tag.'_'.$level]-1;
                $current = &$current[$tag][$last_item_index];
            }

        } elseif($type == "complete") { //Tags that ends in 1 line '<tag />'
            //See if the key is already taken.
            if(!isset($current[$tag])) { //New Key
                $current[$tag] = $result;
                $repeated_tag_index[$tag.'_'.$level] = 1;
                if($priority == 'tag' and $attributes_data) $current[$tag. '_attr'] = $attributes_data;

            } else { //If taken, put all things inside a list(array)
                if(isset($current[$tag][0]) and is_array($current[$tag])) {//If it is already an array...

                    // ...push the new element into that array.
                    $current[$tag][$repeated_tag_index[$tag.'_'.$level]] = $result;
                    
                    if($priority == 'tag' and $get_attributes and $attributes_data) {
                        $current[$tag][$repeated_tag_index[$tag.'_'.$level] . '_attr'] = $attributes_data;
                    }
                    $repeated_tag_index[$tag.'_'.$level]++;

                } else { //If it is not an array...
                    $current[$tag] = array($current[$tag],$result); //...Make it an array using using the existing value and the new value
                    $repeated_tag_index[$tag.'_'.$level] = 1;
                    if($priority == 'tag' and $get_attributes) {
                        if(isset($current[$tag.'_attr'])) { //The attribute of the last(0th) tag must be moved as well
                            
                            $current[$tag]['0_attr'] = $current[$tag.'_attr'];
                            unset($current[$tag.'_attr']);
                        }
                        
                        if($attributes_data) {
                            $current[$tag][$repeated_tag_index[$tag.'_'.$level] . '_attr'] = $attributes_data;
                        }
                    }
                    $repeated_tag_index[$tag.'_'.$level]++; //0 and 1 index is already taken
                }
            }

        } elseif($type == 'close') { //End of tag '</tag>'
            $current = &$parent[$level-1];
        }
    }
    
    return($xml_array);
}  


//ip2int function

function ip2int($src){
  $t = explode('.', $src);
  return count($t) != 4 ? 0 : 256 * (256 * ((float)$t[0] * 256 + (float)$t[1]) + (float)$t[2]) + (float)$t[3];
}

//int2ip function
function int2ip($src){
  $s1 = (int)($src / 256);
  $i1 = $src - 256 * $s1;
  $src = (int)($s1 / 256);
  $i2 = $s1 - 256 * $src;
  $s1 = (int)($src / 256);
  return sprintf('%d.%d.%d.%d', $s1, $src - 256 * $s1, $i2, $i1);
}

function op_TransactionGetNotProcessed() {
    $query="SELECT * from `op_transactions` WHERE `processed`='0';";
    $result=simple_queryall($query);
    return ($result);
}

function op_CustomersGetAll() {
    $query="SELECT * from `op_customers`";
    $allcustomers=simple_queryall($query);
    $result=array();
    if (!empty ($allcustomers)) {
        foreach ($allcustomers as $io=>$eachcustomer) {
            $result[$eachcustomer['virtualid']]=$eachcustomer['realid'];
        }
    }
    return ($result);
}


//stg direct handler
function op_HandleStg($virtualid,$cash) {
    $opconfig=op_LoadConfig();
    $allcustomers=op_CustomersGetAll();
    $sgconf=$opconfig['SGCONF'];
    $stg_host=$opconfig['STG_HOST'];
    $stg_port=$opconfig['STG_PORT'];
    $stg_login=$opconfig['STG_LOGIN'];
    $stg_passwd=$opconfig['STG_PASSWD'];
    if (isset ($allcustomers[$virtualid])) {
        $login=$allcustomers[$virtualid];
    //adding cash if login exists
    $addcash_cmd=$sgconf.' set -s '.$stg_host.' -p '.$stg_port.' -a'.$stg_login.' -w'.$stg_passwd.' -u'.$login.' -c '.$cash;
    shell_exec($addcash_cmd);
    }
    
}

//send mail handler
function op_HandleMail($body) {
    $opconfig=op_LoadConfig();
    $allcustomers=op_CustomersGetAll();
    $notify_mail=$opconfig['NOTIFY_MAIL'];
    $subject="New OpenPayz transaction";
    mail($notify_mail, $subject, $body);
}


//process all needed handlers
function op_ProcessHandlers() {
$opconfig=op_LoadConfig();
    $unprocessed=op_TransactionGetNotProcessed();
      if (!empty ($unprocessed)) {
          $mailbody='';
        foreach ($unprocessed as $io=>$eachtransaction) {
           // mail notify 
            if($opconfig['SEND_MAIL']) {
$mailbody.="
===\n
id: ".$eachtransaction['id']." \n    
date: ".$eachtransaction['date']." \n    
customerid: ".$eachtransaction['customerid']." \n
summ: ".$eachtransaction['summ']." \n    
paysys: ".$eachtransaction['paysys']." \n    
hash: ".$eachtransaction['hash']." \n    
===\n\n
";
            }
           // stargazer direct payments
           if ($opconfig['STG_DIRECT']) {
               //$customerid=vf($eachtransaction['customerid'],3);
               $customerid=$eachtransaction['customerid'];
               op_HandleStg($customerid, $eachtransaction['summ']);
               op_TransactionSetProcessed($eachtransaction['id']);
           }
         }
         //send mail notify
            if($opconfig['SEND_MAIL']) {
                op_HandleMail($mailbody);
            }
         
    }

    
}


?>
