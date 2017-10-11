<?php

//Register and load file classes 
spl_autoload_register(function ($className) {

    $api_directory = 'api' . DIRECTORY_SEPARATOR;
    $libs_directory = 'libs' . DIRECTORY_SEPARATOR;
    $venor_directory = 'vendor' . DIRECTORY_SEPARATOR;

    // Defined path
    $classFileName = strtolower($className);

    // For old Class owervide path
    $classFileName = ($className == 'UbillingWhois') ? 'whois' : $classFileName;
    $classFileName = ($className == 'UbillingBranches') ? 'branches' : $classFileName;
    $classFileName = ($className == 'PONizer') ? 'pon' : $classFileName;
    $classFileName = ($className == 'UkvSystem') ? 'ukv' : $classFileName;
    $classFileName = ($className == 'dbf_class') ? 'dbf' : $classFileName;
    $classFileName = ($className == 'CrimeAndPunishment') ? 'cap' : $classFileName;
    $classFileName = ($className == 'OpenPayz') ? 'opayz' : $classFileName;
    $classFileName = ($className == 'DOCXTemplate') ? 'docx' : $classFileName;
    //$classFileName = ($className == 'AutoLogout') ? 'idlelogout' : $classFileName;
    $classFileName = ($className == 'ProfileDocuments') ? 'documents' : $classFileName;
    $classFileName = ($className == 'WhyDoYouCall') ? 'wdyc' : $classFileName;
    $classFileName = ($className == 'UbillingUpdateManager') ? 'updates' : $classFileName;
    $classFileName = ($className == 'ConnectionDetails') ? 'condet' : $classFileName;
    $classFileName = ($className == 'UbillingCache') ? 'ubcache' : $classFileName;
    $classFileName = ($className == 'UbillingDHCP') ? 'dhcp' : $classFileName;
    $classFileName = ($className == 'UbillingSMS') ? 'usms' : $classFileName;
    $classFileName = ($className == 'CustomMaps') ? 'custmaps' : $classFileName;
    $classFileName = ($className == 'SNMPHelper') ? 'snmp' : $classFileName;
    $classFileName = ($className == 'WatchDogInterface') ? 'watchdog' : $classFileName;
    $classFileName = ($className == 'CapabilitiesDirectory') ? 'capabdir' : $classFileName;
    $classFileName = ($className == 'SignupRequests') ? 'sigreq' : $classFileName;
    $classFileName = ($className == 'SignupConfig') ? 'sigreq' : $classFileName;
    $classFileName = ($className == 'DynamicShaper') ? 'dshaper' : $classFileName;
    $classFileName = ($className == 'CumulativeDiscounts') ? 'cudiscounts' : $classFileName;
    $classFileName = ($className == 'FriendshipIsMagic') ? 'friendship' : $classFileName;
    $classFileName = ($className == 'mikbill') ? 'migration' : $classFileName;
    $classFileName = ($className == 'MegogoApi') ? 'megogo' : $classFileName;
    $classFileName = ($className == 'MegogoInterface') ? 'megogo' : $classFileName;
    $classFileName = ($className == 'UserSideApi') ? 'userside' : $classFileName;
    $classFileName = ($className == 'UbillingMail') ? 'email' : $classFileName;
    $classFileName = ($className == 'ExistentialHorse') ? 'exhorse' : $classFileName;
    $classFileName = ($className == 'UbillingTelegram') ? 'telegram' : $classFileName;
    $classFileName = ($className == 'TSupportApi') ? 'tsupport' : $classFileName;
    $classFileName = ($className == 'WifiCPE') ? 'wcpe' : $classFileName;
    $classFileName = ($className == 'UbillingTaskbar') ? 'taskbar' : $classFileName;
    $classFileName = ($className == 'TaskbarWidget') ? 'taskbar' : $classFileName;

    $apiClassFileName = $api_directory . $libs_directory . 'api.' . $classFileName . '.php';
    $vendorClassFileName = $api_directory . $venor_directory . strtolower($className) . DIRECTORY_SEPARATOR . $classFileName . '.php';

    if (file_exists($apiClassFileName)) {
        include $apiClassFileName;
    } elseif (file_exists($vendorClassFileName)) {
        include $vendorClassFileName;
    }
});

?>
