<?php
if (cfr('USEREDIT')) {
    if (isset($_GET['username'])) {
        $login=vf($_GET['username']);

        function web_UserEditShowForm($login) {
        $stgdata=zb_UserGetStargazerData($login);
        $address=zb_UserGetFullAddress($login);
        $realname=zb_UserGetRealName($login);
        $phone=zb_UserGetPhone($login);
        $contract=zb_UserGetContract($login);
        $mobile=zb_UserGetMobile($login);
	$mail=zb_UserGetEmail($login);
        $notes=zb_UserGetNotes($login); 
	$mac=zb_MultinetGetMAC($stgdata['IP']);
        $speedoverride=zb_UserGetSpeedOverride($login);
        $alter_conf=rcms_parse_ini_file(CONFIG_PATH.'alter.ini');
        //////////////////////////////
        $tariff=$stgdata['Tariff'];
	$credit=$stgdata['Credit'];
        $cash=$stgdata['Cash'];
	$password=$stgdata['Password'];
        if ($alter_conf['PASSWORDSHIDE']) {
         $password=__('Hidden');   
        }
	$aonline=$stgdata['AlwaysOnline'];
	$dstatdisable=$stgdata['DisabledDetailStat'];
        $passive=$stgdata['Passive'];
        $down=$stgdata['Down'];
        $creditexpire=$stgdata['CreditExpire'];
        if ($creditexpire>0) {
            $creditexpire=date("Y-m-d",$creditexpire);
        }

      $form='<table width="100%" border="0">';
	$form.='<tr class="row2"><td>'.__('Parameter').'</td><td>'.__('Current value').'</td><td>'.__('Actions').'</td></tr>';
	$form.='<tr class="row3"><td>'.__('Full address').'</td><td>'.$address.'</td><td><a href="?module=binder&username='.$login.'"><img src="skins/icon_build.gif" border="0"> '.__('Occupancy').'</a></td></tr>';
        $form.='<tr class="row3"><td>'.__('Password').'</td><td>'.$password.'</td><td><img src="skins/icon_key.gif" border="0"> <a href="?module=passwordedit&username='.$login.'"> '.__('Change').' '.__('Password').'</a></td></tr>';
	$form.='<tr class="row3"><td>'.__('Real Name').'</td><td>'.$realname.'</td><td><img src="skins/icon_user.gif" border="0"> <a href="?module=realnameedit&username='.$login.'"> '.__('Change').' '.__('Real Name').'</a></td></tr>';
	$form.='<tr class="row3"><td>'.__('Phone').'</td><td>'.$phone.'</td><td><a href="?module=phoneedit&username='.$login.'"><img src="skins/icon_phone.gif"  border="0"> '.__('Change').' '.__('phone').'</a></td></tr>';
        $form.='<tr class="row3"><td>'.__('Mobile').'</td><td>'.$mobile.'</td><td><a href="?module=mobileedit&username='.$login.'"><img src="skins/icon_mobile.gif"  border="0"> '.__('Change').' '.__('mobile').'</a></td></tr>';
        $form.='<tr class="row3"><td>'.__('Contract').'</td><td>'.$contract.'</td><td><a href="?module=contractedit&username='.$login.'"><img src="skins/icon_link.gif"  border="0"> '.__('Change').' '.__('contract').'</a></td></tr>';
        $form.='<tr class="row3"><td>'.__('Email').'</td><td>'.$mail.'</td><td><a href="?module=mailedit&username='.$login.'"><img src="skins/icon_mail.gif"  border="0"> '.__('Change').' '.__('email').'</a></td></tr>';
	$form.='<tr class="row3"><td>'.__('Tariff').'</td><td>'.$tariff.'</td><td><a href="?module=tariffedit&username='.$login.'"><img src="skins/icon_tariff.gif" border="0"> '.__('Change').' '.__('Tariff').'</a></td></tr>';
	$form.='<tr class="row3"><td>'.__('Speed override').'</td><td>'.$speedoverride.'</td><td><a href="?module=speededit&username='.$login.'"><img src="skins/icon_speed.gif" border="0"> '.__('Change').' '.__('Speed override').'</a></td></tr>';
	$form.='<tr class="row3"><td>'.__('Credit').'</td><td>'.$credit.'</td><td><a href="?module=creditedit&username='.$login.'"><img src="skins/icon_credit.gif" border="0"> '.__('Change').' '.__('Credit').'</a></td></tr>';
        $form.='<tr class="row3"><td>'.__('Credit expire').'</td><td>'.$creditexpire.'</td><td><a href="?module=creditexpireedit&username='.$login.'"><img src="skins/icon_calendar.gif" border="0"> '.__('Change').' '.__('Credit expire').'</a></td></tr>';
	$form.='<tr class="row3"><td>'.__('Balance').'</td><td>'.$cash.'</td><td><a href="?module=addcash&username='.$login.'#profileending"><img src="skins/icon_dollar.gif"  border="0"> '.__('Finance operations').'</a></td></tr>';
	$form.='<tr class="row3"><td>'.__('MAC').'</td><td>'.$mac.'</td><td><a href="?module=macedit&username='.$login.'"><img src="skins/icon_ether.gif" border="0"> '.__('Change').' MAC</a></td></tr>';
	$form.='<tr class="row3"><td>'.__('AlwaysOnline').'</td><td>'.web_trigger($aonline).'</td><td><a href="?module=aoedit&username='.$login.'"><img src="skins/icon_online.gif" border="0"> '.__('AlwaysOnline').'</a></td></tr>';
	$form.='<tr class="row3"><td>'.__('Disable detailed stats').'</td><td>'.web_trigger($dstatdisable).'</td><td><a href="?module=dstatedit&username='.$login.'"><img src="skins/icon_stats.gif" border="0"> '.__('Detailed stats').'</a></td></tr>';
        $form.='<tr class="row3"><td>'.__('User passive').'</td><td>'.web_trigger($passive).'</td><td><a href="?module=passiveedit&username='.$login.'"><img src="skins/icon_passive.gif" border="0"> '.__('User passive').'</a></td></tr>';
        $form.='<tr class="row3"><td>'.__('User down').'</td><td>'.web_trigger($down).'</td><td><a href="?module=downedit&username='.$login.'"><img src="skins/icon_down.gif" border="0"> '.__('User down').'</a></td></tr>';
        $form.='<tr class="row3"><td>'.__('Notes').'</td><td>'.$notes.'</td><td><a href="?module=notesedit&username='.$login.'"><img src="skins/icon_note.gif"  border="0"> '.__('Change').' '.__('Notes').'</a></td></tr>';
	$form.='</table>';
         show_window(__('Edit user').' '.$address, $form);
         cf_FieldEditor($login);
         show_window('',web_UserControls($login));
        }

        web_UserEditShowForm($login);

    }
} else {
    show_error(__('You cant control this module'));
}
?>