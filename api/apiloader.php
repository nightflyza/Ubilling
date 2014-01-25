<?php
/*
 * Including all needed APIs and Libs
 */
include('api/libs/api.mysql.php');
include('api/libs/api.ubstorage.php');
include('api/api.stargazer.php');
include('api/libs/api.compat.php');
include('api/libs/api.astral.php');
include('api/libs/api.dbconnect.php');
include('api/libs/api.userdata.php');
include('api/libs/api.address.php');
include('api/libs/api.teskman.php');
include('api/libs/api.networking.php');
include('api/libs/api.userreg.php');
include('api/libs/api.workaround.php');
include('api/libs/api.payments.php');
include('api/libs/api.usertags.php');
include('api/libs/api.cess.php');
include('api/libs/api.cardpay.php');
include('api/libs/api.cf.php');
include('api/libs/api.switches.php');
include('api/libs/api.gravatar.php');
include('api/libs/api.ticketing.php');
include('api/libs/api.catv.php');
include('api/libs/api.corporate.php');
include('api/libs/api.lousytariffs.php');
include('api/libs/api.banksta.php');
include('api/libs/api.templatize.php');
include('api/libs/api.ymaps.php');
include('api/libs/api.deploy.php');
include('api/libs/api.crm.php');
include('api/libs/api.help.php');
include('api/libs/api.ubim.php');
include('api/libs/api.snmp.php');
include('api/libs/api.routeros.php');
include('api/libs/api.watchdog.php');
include('api/libs/api.docx.php');
include('api/libs/api.documents.php');

/*
 * Initial class creation
 */
$billing = new ApiBilling();
$db = new MySQLDB();