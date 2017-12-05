<?php

/*
 * Фронтенд для получения уведомлений от PAYMASTER
 * 
 */
 
$conf_ipay=parse_ini_file("../config/paymaster.ini");

if(!empty($_REQUEST['LMI_PREREQUEST'])){
	echo 'YES';
	exit;
}elseif(!empty($_REQUEST['LMI_HASH']) && $_REQUEST['LMI_PAID_AMOUNT'] && $_REQUEST['LMI_PAYMENT_NO'] && $_REQUEST['LMI_PAYMENT_DESC']){
	// подключаем API OpenPayz
	include ("../../libs/api.openpayz.php");

			$hash=$_REQUEST['LMI_HASH'];
			$summ=$_REQUEST['LMI_PAID_AMOUNT'];
			$customerid=$_REQUEST['LMI_PAYMENT_NO'];
			$paysys='PAYMASTER';
			$note=$_REQUEST['LMI_PAYMENT_DESC'];
			//регистрируем новую транзакцию
			op_TransactionAdd($hash, $summ, $customerid, $paysys, $note);
			//вызываем обработчики необработанных транзакций
			op_ProcessHandlers();
}else{
    echo "some gone wrong\n";
}
