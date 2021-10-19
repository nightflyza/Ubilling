<?php

/*
 * Including all needed APIs and Libs
 */
require_once('api/libs/api.mysql.php');
require_once('api/libs/api.ubstorage.php');
require_once('api/api.stargazer.php');
require_once('api/libs/api.compat.php');
require_once('api/libs/api.morph.php');
require_once('api/libs/api.ubconfig.php');
require_once('api/libs/api.ubcache.php');
require_once('api/libs/api.astral.php');
require_once('api/libs/api.barcodeqr.php');
require_once('api/libs/api.dbconnect.php');
require_once('api/libs/api.userdata.php');
require_once('api/libs/api.usersearch.php');
require_once('api/libs/api.address.php');
require_once('api/libs/api.telepathy.php');
require_once('api/libs/api.taskman.php');
require_once('api/libs/api.networking.php');
require_once('api/libs/api.dhcp.php');
require_once('api/libs/api.userreg.php');
require_once('api/libs/api.workicons.php');
require_once('api/libs/api.workaround.php');
require_once('api/libs/api.usms.php');
require_once('api/libs/api.payments.php');
require_once('api/libs/api.usertags.php');
require_once('api/libs/api.cess.php');
require_once('api/libs/api.cardpay.php');
require_once('api/libs/api.cf.php');
require_once('api/libs/api.switches.php');
require_once('api/libs/api.gravatar.php');
require_once('api/libs/api.ticketing.php');
require_once('api/libs/api.catv.php');
require_once('api/libs/api.corporate.php');
require_once('api/libs/api.lousytariffs.php');
require_once('api/libs/api.banksta.php');
require_once('api/libs/api.templatize.php');
require_once('api/libs/api.custmaps.php');
require_once('api/libs/api.deploy.php');
require_once('api/libs/api.crm.php');
require_once('api/libs/api.help.php');
require_once('api/libs/api.ubim.php');
require_once('api/libs/api.snmp.php');
require_once('api/libs/api.swpoll.php');
require_once('api/libs/api.routeros.php');
require_once('api/libs/api.watchdog.php');
require_once('api/libs/api.docx.php');
require_once('api/libs/api.documents.php');
require_once('api/libs/api.dbf.php');
require_once('api/libs/api.idlelogout.php');
require_once('api/libs/api.corps.php');
require_once('api/libs/api.extnets.php');
require_once('api/libs/api.assignreport.php');
require_once('api/libs/api.capabdir.php');
require_once('api/libs/api.sigreq.php');
require_once('api/libs/api.condet.php');
require_once('api/libs/api.userprofile.php');
require_once('api/libs/api.stickynotes.php');
require_once('api/libs/api.fundsflow.php');
require_once('api/libs/api.adcomments.php');
require_once('api/libs/api.vlan.php');
require_once('api/libs/api.globalsearch.php');
require_once('api/libs/api.darkvoid.php');
require_once('api/libs/api.globalmenu.php');
require_once('api/libs/api.loginform.php');
require_once('api/libs/api.photostorage.php');
require_once('api/libs/api.dshaper.php');
require_once('api/libs/api.uhw.php');
require_once('api/libs/api.cudiscounts.php');
require_once('api/libs/api.cap.php');
require_once('api/libs/api.opayz.php');
require_once('api/libs/api.cemetery.php');
require_once('api/libs/api.reminder.php');
require_once('api/libs/api.friendship.php');
require_once('api/libs/api.migration.php');
require_once('api/libs/api.percity.php');
require_once('api/libs/api.dealwithit.php');
require_once('api/libs/api.megogo.php');
require_once('api/libs/api.userside.php');
require_once('api/libs/api.whois.php');
require_once('api/libs/api.exhorse.php');
require_once('api/libs/api.telegram.php');
require_once('api/libs/api.senddog.php');
require_once('api/libs/api.tsupport.php');
require_once('api/libs/api.asterisk.php');
require_once('api/libs/api.policedog.php');
require_once('api/libs/api.branches.php');
require_once('api/libs/api.selling.php');
require_once('api/libs/api.printcard.php');
require_once('api/libs/api.generatecard.php');
require_once('api/vendor/fpdf/fpdf.php');
require_once('api/libs/api.updates.php');
require_once('api/libs/api.wdyc.php');
require_once('api/libs/api.mapscommon.php');
require_once('api/libs/api.mapscompat.php');
require_once('api/libs/api.announcements.php');
require_once('api/libs/api.nasmon.php');
require_once('api/libs/api.ipchange.php');
require_once('api/libs/api.messagesqueue.php');
require_once('api/libs/api.taskbar.php');
require_once('api/libs/api.onubase.php');
require_once('api/libs/api.onudelete.php');
require_once('api/libs/api.onuregister.php');
require_once('api/libs/api.onureboot.php');
require_once('api/libs/api.onuderegister.php');
require_once('api/libs/api.onudescribe.php');
require_once('api/libs/api.onumaster.php');
require_once('api/libs/api.ldap.php');
require_once('api/libs/api.districts.php');
require_once('api/libs/api.onepunch.php');
require_once('api/libs/api.fwtbt.php');
require_once('api/libs/api.ddt.php');
require_once('api/libs/api.sphinxsearch.php');
require_once('api/libs/api.switchlogin.php');
require_once('api/libs/api.ubrouting.php');
require_once('api/libs/api.nyanorm.php');
require_once('api/libs/api.zabbix.php');

/**
 * Initial class creation
 */
$billing = new ApiBilling();
$ubillingConfig = new UbillingConfig();

require_once('api/api.autolader.php');

/**
 * Branches access control 
 */
$globalAlter = $ubillingConfig->getAlter();
if (@$globalAlter['BRANCHES_ENABLED']) {
    $branchControl = new UbillingBranches();
    $branchControl->accessControl();
}

