<?php

// подключаем API OpenPayz
include ("../../libs/api.openpayz.php");

// Проверка на допустимось IP
function CheckIpClient() {
        $result=true;

        // Список разрешенных IP
        // loop, ips_server, ips_server, ips_tintoff
        $servers=array('127.0.0.0/8','91.194.226.0/23');
        // Получаем ip клиента
        $client=$_SERVER['REMOTE_ADDR'];

        // Проверка разрешенности удаленного адреса
        $client_long = ip2long($client);
        foreach ($servers as $server) {
                $ip_arr = explode('/' , $server);
                $network_long = ip2long($ip_arr[0]);
                $mask_long = ip2long($ip_arr[1]);
                $mask = long2ip($mask_long) == $ip_arr[1] ? $mask_long : 0xffffffff << ( 32 - $ip_arr[1] );
                if (( $client_long & $mask ) == ( $network_long & $mask )) return true;
        }
        return false;
}


function CheckParams() {
        // Список обязательных параметров
        $params=array('TerminalKey','OrderId','PaymentId','Amount','Token','Success','Status');
        // Проверка их наличия
        foreach ($params as $eachparam) {
                if (empty($_POST[$eachparam])) {
                        return( false );
                }
        }

        if ( $_POST['Success'] != 'true' ) return( false );
        if ( $_POST['ErrorCode'] != '0' ) return( false );
        if ( $_POST['TerminalKey'] != '1480927487288' ) return( false );
        if ( $_POST['Status'] != 'AUTHORIZED' && $_POST['Status'] != 'CONFIRMED' ) return( false );

        return( true );
}

function CheckTransaction($hash) {
        $hash=  loginDB_real_escape_string($hash);
        $query = "SELECT `id` from `op_transactions` WHERE `paysys`='TINKOFF' AND `hash`='".$hash."'";
        $data = simple_query($query);
        if (!empty($data)) {
                return (false);
        } else {
                return (true);
        }
}


//если нас пнули объязательными параметрами
if( CheckIpClient() AND CheckParams() ) {
        $allcustomers=op_CustomersGetAll();

        $hash=$_POST['PaymentId'];
        $sum=$_POST['Amount'] / 100;
        $customerid=explode('_' ,trim($_POST['OrderId']))[0];
        $paysys='TINKOFF';
        $hashStore=$paysys.'_'.$hash;
        $status=$_POST['Status'];
        //$note='some debug info:'.implode(",", $_POST).', ';
        $note="some debug info: "; foreach ($_POST as $key => $value) { $note.="{$key} => {$value} "; }

        //нашелся братиша!
        if (isset($allcustomers[$customerid])) {
                //проверяем транзакцию на уникальность и проводим
                if ( CheckTransaction($hashStore) &&  $status != 'CONFIRMED' ) {
                        //регистрируем новую транзакцию
                        op_TransactionAdd($hashStore, $sum, $customerid, $paysys, $note);
                        //вызываем обработчики необработанных транзакций
                        op_ProcessHandlers();
                }
                //в любом случае отвечаем, что у нас все хорошо в этой жизни
                die('OK');
        }
}else{
        error_log("Tinkoff fail transaction:".implode(",", $_POST), 0);
        die('OK');
}

?>
