<?php
/*
 * Including all needed APIs and Libs
 */
include('api/libs/api.mysql.php');
include('api/libs/api.ubstorage.php');
include('api/api.stargazer.php');
include('api/libs/api.compat.php');
include('api/libs/api.ubconfig.php');
include('api/libs/api.bootstrap.php');
include('api/libs/api.astral.php');
include('api/libs/api.dbconnect.php');
include('api/libs/api.userdata.php');
include('api/libs/api.address.php');
include('api/libs/api.telepathy.php');
include('api/libs/api.teskman.php');
include('api/libs/api.networking.php');
include('api/libs/api.userreg.php');
include('api/libs/api.workicons.php');
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
include('api/libs/api.dbf.php');
include('api/libs/api.ukv.php');
include('api/libs/api.idlelogout.php');
include('api/libs/api.corps.php');
include('api/libs/api.extnets.php');
include('api/libs/api.assignreport.php');
include('api/libs/api.capabdir.php');
include('api/libs/api.sigreq.php');
include('api/libs/api.roskomnadzor.php');
include('api/libs/api.userprofile.php');
include('api/libs/api.stickynotes.php');
include('api/libs/api.fundsflow.php');
include('api/libs/api.adcomments.php');



/*
 * Initial class creation
 */
$billing = new ApiBilling();
$db = new MySQLDB();
$ubillingConfig=new UbillingConfig();

