<?php

//just dummy module for testing purposes
error_reporting(E_ALL);

$start = '172.16.0.1';
$end = '172.16.0.6';
$type = 'long';

$tmp = array();
$c = 0;
switch ($type) {
    case 'long':
        $start = ip2long($start);
        $end = ip2long($end);
        break;
    case 'int':
        $start = ip2int($start);
        $end = ip2int($end);
        break;
}


for ($i = $start; $i < $end; $i++) {

    switch ($type) {
        case 'long':
            $tmp[$c]['ip'] = long2ip($i);
            $tmp[$c]['value'] = $i;
            break;
        case 'int':
            $tmp[$c]['ip'] = int2ip($i);
            $tmp[$c]['value'] = $i;
            break;
    }

    $c++;
}

debarr($tmp);
?>
