<?php
require ("content/documents/gocardless/lib/loader.php");
require ("content/documents/gocardless/guzzle/autoloader.php");

function getAllChargeParams() {
    $allChargeParams = array();

    $query = "SELECT * FROM `gcss_charges` WHERE `mandate_canceled` = 0";
    $qResult = simple_queryall($query);

    if (!empty($qResult)) {
        foreach ($qResult as $eachRec) {
            $allChargeParams[$eachRec['login']] = $eachRec;
        }
    }

    return ($allChargeParams);
}

function getUserInvoiceFromDB($login, $monthNum) {
    $invoice = '';

    $query = "SELECT * FROM `invoices` WHERE `login` = '" . $login . "' and MONTH(`invoice_date`) = '" . $monthNum . "'";
    $qResult = simple_query($query);

    if (!empty($qResult)) {
        $invoice = $qResult;
    }

    return ($invoice);
}

$PAYSYS = 'GOCARDLESS';
$login = '';

$query = "SELECT `users`.`login`, `users`.`Cash`, `users`.`Credit`, FROM_UNIXTIME(`CreditExpire`, '%Y-%m-%d') AS `CreditExpireDate`,  `users`.`Tariff`, 
                 `tariffs`.`Fee`, `gcss_mandates`.`mandate_id`, `contracts`.`contract` 
            FROM `users` 
                INNER JOIN `gcss_mandates` ON `gcss_mandates`.`login` = `users`.`login` AND `gcss_mandates`.`canceled` <= 0
                LEFT JOIN `tariffs` ON `tariffs`.`name` = `users`.`Tariff`
                LEFT JOIN `contracts` ON `contracts`.`login` = `users`.`login` 
            WHERE `users`.`login` = '" . $login . "' and `users`.`Passive` = '0' AND (`users`.`Cash` < -`users`.`Credit` OR `users`.`CreditExpire` > 0)";
$allUsersTM = simple_query($query);

if (empty($allUsersTM)) {
    print('No users found');
    return (false);
}

print_r($allUsersTM);

$usrLogin       = $allUsersTM['login'];
$usrMandate     = $allUsersTM['mandate_id'];
$usrContract    = $allUsersTM['contract'];
$usrCash        = $allUsersTM['Cash'];
$usrTariff      = $allUsersTM['Tariff'];
$usrTariffPrice = $allUsersTM['Fee'];

$curMonthNum     = date('n');     // month without leading 0
$allChargeParams = getAllChargeParams();
$usrChargeParams = (empty($allChargeParams[$usrLogin])) ? array() : $allChargeParams[$usrLogin];

if (empty($usrChargeParams)) {
    $query = "INSERT INTO `gcss_charges` (`login`, `mandate_id`) 
                                          VALUES ('" . $usrLogin . "', '" . $usrMandate . "')";
    nr_query($query);

    $allChargeParams  = getAllChargeParams();
    $usrChargeParams  = $allChargeParams[$usrLogin];
    $usrNotChargedYet = true;

    if (GCSS_CHARGE_DEBUG_ON) {
        log_register(PAYSYS . ' no charge record for login (' . $usrLogin . '). Added one with mandate [' . $usrMandate . '].');
    }
}

$usrInvoiceFromDB = getUserInvoiceFromDB($usrLogin, $curMonthNum);

if (empty($usrInvoiceFromDB)) {
    log_register(PAYSYS . ' no invoices fround for login (' . $usrLogin . ') for month [' . $curMonthNum . ']');
}

// get invoice number. it changes only for the first charge in a billing period
if (empty($usrChargeParams['last_payment_invoice'])) {
    if (empty($usrInvoiceFromDB)) {
        $usrInvoice = 'INV-' . $usrContract . '-' . date('Ymd');
    } else {
        $usrInvoice = 'INV-' . $usrContract . '-' . $usrInvoiceFromDB['invoice_num'];
    }
} else {
    $usrInvoice = $usrChargeParams['last_payment_invoice'];
}

// getting fee charge amount
if (empty($usrInvoiceFromDB)) {
    $usrAllVServicesCost = zb_VservicesGetUserPrice($usrLogin);
    $usrFullFee = $usrTariffPrice + $usrAllVServicesCost + abs($usrCash);
} else {
    $usrFullFee = $usrInvoiceFromDB['invoice_sum'];
}

$datetime            = curdatetime();
$idempotencyKey      = 'GCSS_' . md5($usrInvoice . $datetime);

$client = new \GoCardlessPro\Client(array(
        'access_token' => 'live_R9FzG88ihjmBkIlYjBC5uZZcQLcnJLOO8PwyuaTw',
        'environment' => \GoCardlessPro\Environment::LIVE
    )
);
$payment = $client->payments()->create(array(
        "params" => array(
            "amount" => $usrFullFee * 100, // sum in GBP in pence
            "currency" => "GBP",
            "description" => 'BeaconsTelecom Internet service access fee charge',
            "links" => array(
                // The mandate ID
                "mandate" => $usrMandate
            ),
            // Almost all resources in the API let you store custom metadata,
            // which you can retrieve later
            "metadata" => array(
                "invoice_number" => $usrInvoice
            )
        ),
        "headers" => array(
            "Idempotency-Key" => $idempotencyKey
        )
    )
);

$query = "INSERT INTO `op_transactions` (`id`,`hash`, `date` , `summ` , `customerid` ,`paysys` , `processed` ,`invoice`, `payment_id`)
                                         VALUES (NULL ,'" . $idempotencyKey . "' , '" . $datetime . "', '" . $usrFullFee . "', '" . $usrContract . "', '" . $PAYSYS . "', '0', '" . $usrInvoice . "', '" . $payment->id .  "');";
nr_query($query);