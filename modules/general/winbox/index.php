<?php
if(cfr('WINBOX')) {

    function winbox_UserDataShow() {
        $alladdrs=zb_AddressGetFulladdresslist();
        $query="SELECT * from `users`";
        $allusers=  simple_queryall($query);
        $query_fio="SELECT * from `realname`";
        $allfioz=simple_queryall($query_fio);
        $fioz=array();
                if (!empty ($allfioz)) {
                 foreach ($allfioz as $ia=>$eachfio) {
                       $fioz[$eachfio['login']]=$eachfio['realname'];
                      }
                     }
        $result='';
        if (!empty ($allusers)) {
            foreach ($allusers as $io=>$eachuser) {
                $result.="[".$eachuser['login']."]\n";
                $result.="Password = ".$eachuser['Password']."\n";
                $result.="IP = ".$eachuser['IP']."\n";
                $result.="Tariff = ".$eachuser['Tariff']."\n";
                $result.="Cash = ".$eachuser['Cash']."\n";
                $result.="Credit = ".$eachuser['Credit']."\n";
                $result.="FullAddress = ".@$alladdrs[$eachuser['login']]."\n";
                $result.="Down = ".$eachuser['Down']."\n";
                $result.="Passive = ".$eachuser['Passive']."\n";
                $result.="DisableDetailStat = ".$eachuser['DisabledDetailStat']."\n";
                $result.="AlwaysOnline = ".$eachuser['AlwaysOnline']."\n";
                $result.="CreditExpire = ".$eachuser['CreditExpire']."\n";
                $result.="RealName = ".@$fioz[$eachuser['login']]."\n";
                // additional fields
                $result.='Contract = '.zb_UserGetContract($eachuser['login'])."\n";
                $result.='Email = '.zb_UserGetEmail($eachuser['login'])."\n";
                $result.='Phone = '.zb_UserGetPhone($eachuser['login'])."\n";
                $result.='Mobile = '.zb_UserGetMobile($eachuser['login'])."\n";
                $result.='MAC = '.zb_MultinetGetMAC($eachuser['IP'])."\n\n\n";
                }
        }
        
        print('<pre>'.$result.'</pre>');
        die();
    }
    
    winbox_UserDataShow();
    
}
else {
	show_error(__('Access denied'));
}
?>