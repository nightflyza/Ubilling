<?php

//just dummy module for testing purposes
error_reporting(E_ALL);

if (cfr('ROOT')) {

    /**
     * Renders area with some users payment IDs
     * 
     * @param array $userArray user array as login=>login
     * @param array $allPaymentIds payment ids array as login=>op_data
     * 
     */
    function renderPayids($userArray, $allPaymentIds) {
        $result = '';
        $content = '';
        if (!empty($userArray)) {
            foreach ($userArray as $io => $each) {
                $content .= $allPaymentIds[$each]['virtualid'] . PHP_EOL;
            }
        }
        $result .= wf_delimiter();
        $result .= wf_TextArea('thatsswhy', '', $content, false, '50x15');
        return($result);
    }

    $tagsDb = new NyanORM('tags');
    $tagsDb->where('tagid', '=', '2');
    $tagsDb->orWhere('tagid', '=', '6');
    $tagsDb->orWhere('tagid', '=', '10');
    $tagsDb->orWhere('tagid', '=', '11');
    $tagsDb->orWhere('tagid', '=', '10');
    $tagsDb->orWhere('tagid', '=', '14');
    $tagsDb->orWhere('tagid', '=', '56');
    $tagsDb->orWhere('tagid', '=', '27');
    $tagsDb->orWhere('tagid', '=', '52');
    $tagsDb->orWhere('tagid', '=', '58');
    $tagsDb->orWhere('tagid', '=', '36');
    $tagsDb->orWhere('tagid', '=', '3');
    $tagsDb->orWhere('tagid', '=', '23');

    $badUsers = $tagsDb->getAll('login');


    $usersDb = new NyanORM('users');
    $usersDb->where('Passive', '=', 0);
    $usersDb->where('Down', '=', 0);
    $usersDb->where('AlwaysOnline', '=', 1);
    $usersDb->where('Cash', '>=', 0);
    $usersDb->where('U0', '>', 0);
    $usersDb->where('Tariff', 'NOT LIKE', 'Special');
    $usersDb->where('Tariff', 'NOT LIKE', 'Traffic-100');
    $usersDb->where('Tariff', 'NOT LIKE', 'Employee');
    $usersDb->where('Tariff', 'NOT LIKE', '%ISP%');
    $usersDb->where('Tariff', 'NOT LIKE', '%Business%');
    $usersDb->where('Tariff', 'NOT LIKE', '%Education%');
    $usersDb->where('Tariff', 'NOT LIKE', '%Ultra%');
    $usersDb->where('Tariff', 'NOT LIKE', '%Mega%');
    $usersDb->where('Tariff', 'NOT LIKE', '%Cams%');
    $usersDb->where('Tariff', 'NOT LIKE', '%School%');

    $allUsers = $usersDb->getAll('login');

    $normalUsers = array();
    $regsDb = new NyanORM('userreg');
    $allRegs = $regsDb->getAll('login');

    $opCustomersDb = new NyanORM('op_customers');
    $allPaymentIds = $opCustomersDb->getall('realid');

    foreach ($allUsers as $eachUser => $eachUserData) {
        if (!isset($badUsers[$eachUser])) {
            if (isset($allPaymentIds[$eachUser])) {
                $normalUsers[$eachUser] = $eachUser;
            }
        }
    }

    show_window('Просто нормальні користувачі', renderPayids($normalUsers, $allPaymentIds));


    $dinoUsers = array();
    foreach ($allUsers as $eachLogin => $eachUserData) {
        if (!isset($allRegs[$eachLogin])) {
            $dinoUsers[$eachLogin] = $eachLogin;
        }
    }

    show_window('Найдревніші користувачі', renderPayids($dinoUsers, $allPaymentIds));

    //deb(web_UserArrayShower($dinoUsers));
}