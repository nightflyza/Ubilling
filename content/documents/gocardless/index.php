<?php
$gcssConf = parse_ini_file('config/gcss.ini');

// подключаем API OpenPayz
include ("../../libs/api.openpayz.php");

require ("lib/loader.php");
require ("guzzle/autoloader.php");

/**
 * Returns all user RealNames
 *
 * @return array
 */
function gcss_GetAllUserData($login) {
    $query = "
             SELECT `users`.`login`, `realname`.`realname`, `Passive`, `Down`, `Password`,`AlwaysOnline`, `Tariff`, `TariffChange`, `Credit`, `Cash`,
                    `ip`, `mac`, `cityname`, `streetname`, `buildnum`, `entrance`, `floor`, `apt`, `geo`,";

    $query.= "concat(`streetname`, ' ', `buildnum`, IF(`apt`, concat('/',`apt`), '')) AS `fulladress`,";

    $query.= "
                `phones`.`phone`,`mobile`,`contract`,`emails`.`email`
                FROM `users` LEFT JOIN `nethosts` USING (`ip`)
                LEFT JOIN `realname` ON (`users`.`login`=`realname`.`login`)
                LEFT JOIN `address` ON (`users`.`login`=`address`.`login`)
                LEFT JOIN `apt` ON (`address`.`aptid`=`apt`.`id`)
                LEFT JOIN `build` ON (`apt`.`buildid`=`build`.`id`)
                LEFT JOIN `street` ON (`build`.`streetid`=`street`.`id`)
                LEFT JOIN `city` ON (`street`.`cityid`=`city`.`id`)
                LEFT JOIN `phones` ON (`users`.`login`=`phones`.`login`)
                LEFT JOIN `contracts` ON (`users`.`login`=`contracts`.`login`)
                LEFT JOIN `emails` ON (`users`.`login`=`emails`.`login`)
                WHERE `users`.`login` = '" . vf($login) .
             "'";

    $alldata = simple_query($query);
    $result[$login] = $alldata;

    return($result);
}

function gcss_GetUserLoginByPaymID($paymentID) {
    $query = "SELECT realid FROM `op_customers` WHERE `op_customers`.`virtualid` = '" . $paymentID . "'";
    $alldata = simple_query($query);
    $result = $alldata['realid'];

    return($result);
}

function gcss_CancelMandates($login, $excludeMandate = '') {
    $excludeWHERE = (empty($excludeMandate)) ? '' : " AND `mandate_id` != '" . $excludeMandate . "'";

    $query = "UPDATE `gcss_mandates` SET 
                     `canceled` = 1
               WHERE `login` = '" . $login . "'" . $excludeWHERE;
    nr_query($query);
}

function gcss_GetMandateByLogin($login) {
    $query = "SELECT * FROM `gcss_mandates` WHERE `login` = '" . $login . "'";
    $alldata = simple_query($query);
    $result = $alldata['mandate_id'];

    return($result);
}

$client = new \GoCardlessPro\Client(array(
        'access_token' => $gcssConf['GCSS_API_TOKEN'],
        'environment' => \GoCardlessPro\Environment::SANDBOX
    )
);

if (empty($_GET['redirect_flow_id'])) {
    $customer_id = $_GET['customer_id'];
    $customer_login = gcss_GetUserLoginByPaymID($customer_id);
    $customer_mandate = gcss_GetActiveMandateByLogin($customer_login);

    if (empty($customer_mandate)) {
        $customer_data = strp_GetAllUserData($customer_login);

        $customer_names = explode(' ', $customer_data[$customer_login]['realname']);
        $customer_1stname = (empty($customer_names[0])) ? '' : $customer_names[0];
        $customer_2ndname = (empty($customer_names[1])) ? '' : $customer_names[1];

        $datetime = curdatetime();
        $sessiontoken = sha1('GCSS_SESSION_' . $customer_login . $datetime);

        $redirectFlow = $client->redirectFlows()->create(array(
            "params" => array(
                // This will be shown on the payment pages
                "description" => "Internet service fee",
                // Not the access token
                "session_token" => $sessiontoken,
                "success_redirect_url" => $gcssConf['GCSS_SUCCES_REDIR_URL'],
                // Optionally, prefill customer details on the payment page
                "prefilled_customer" => array(
                    "given_name" => $customer_1stname,
                    "family_name" => $customer_2ndname,
                    "email" => $customer_data[$customer_login]['email'],
                    "address_line1" => $customer_data[$customer_login]['fulladress'],
                    "city" => $customer_data[$customer_login]['cityname'],
                    "postal_code" => "EC1V 7LQ",
                    "country_code" => $gcssConf['GCSS_COUNTRY_CODE']
                )
            )
        ));


        $redirFlowID = $redirectFlow->id;
        $redirFlowURL = $redirectFlow->redirect_url;
        $redirFlowDT = $redirectFlow->created_at;

        $query = "INSERT INTO `gcss_events` (`login`, `event`, `event_date`, `session_token`, `redir_flow_id`) 
                             VALUES ('" . $customer_login . "', 'MANDATE CREATE', '" . $datetime . "', '" .
            $sessiontoken . "', '" . $redirFlowID . "')";
        nr_query($query);

        rcms_redirect($redirFlowURL);
    } else {
        rcms_redirect('/gocardless/mandate_already_set.html');
    }
} else {
    $redirFlowID = $_GET['redirect_flow_id'];

    $query = "SELECT * FROM `gcss_events` WHERE `redir_flow_id` = '" . $redirFlowID . "'";
    $alldata = simple_query($query);

    $sessiontoken = $alldata['session_token'];
    $customer_login = $alldata['login'];
    //$datetime = $alldata['event_date'];
    $datetime = curdatetime();

    $redirectFlow = $client->redirectFlows()->complete($redirFlowID, //The redirect flow ID from above.
        array("params" => array("session_token" => $sessiontoken))
    );

    print("Mandate: " . $redirectFlow->links->mandate . "<br />");
// Save this mandate ID for the next section.
    print("Customer: " . $redirectFlow->links->customer . "<br />");

    $query = "INSERT INTO `gcss_mandates` (`login`, `mandate_id`, `creation_date`) 
                            VALUES ('" . $customer_login . "', '" . $redirectFlow->links->mandate . "', '" . $datetime . "')";
    nr_query($query);

    gcss_CancelMandates($customer_login, $redirectFlow->links->mandate);

// Display a confirmation page to the customer, telling them their Direct Debit has been
// set up. You could build your own, or use ours, which shows all the relevant
// information and is translated into all the languages we support.
    print("Confirmation URL: " . $redirectFlow->confirmation_url . "<br />");

    $customers = $client->customers()->list()->records;
    echo print_r($customers);

    rcms_redirect('/gocardless/dd_set_success.html?login=' . $customer_login);
}