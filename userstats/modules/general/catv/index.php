<?php

$user_ip=zbs_UserDetectIp('debug');
$user_login=zbs_UserGetLoginByIp($user_ip);
$us_config=zbs_LoadConfig();

// if CaTV enabled
if ($us_config['TV_ENABLED']) {
    
    function zbs_CatvGetAssociatedUser($login) {
        $query="SELECT * from `catv_users` WHERE `inetlink`='".$login."'";
        $userdata=  simple_query($query);
        if (!empty($userdata)) {
            return ($userdata);
        } else {
            return (false);
        }
    }
    
    function zbs_CatvGetAllTariffs() {
        $query="SELECT * from `catv_tariffs`";
        $alltariffs=  simple_queryall($query);
        $result=array();
        if (!empty($alltariffs)) {
            foreach ($alltariffs as $io=>$each) {
                $result[$each['id']]=$each['name'];
            }
        }
        
        return ($result);
    }
    
     function zbs_CatvGetAllTariffPrices() {
        $query="SELECT * from `catv_tariffs`";
        $alltariffs=  simple_queryall($query);
        $result=array();
        if (!empty($alltariffs)) {
            foreach ($alltariffs as $io=>$each) {
                $result[$each['id']]=$each['price'];
            }
        }
        
        return ($result);
    }
    
    function zbs_CatvShowProfile($catv_user,$alltariffs,$allprices) {
        
        if (($catv_user['apt']!='') OR ($catv_user['apt']!='0')) {
            $apt='/'.$catv_user['apt'];
        } else {
            $apt='';
        }
        
        $cells=la_TableCell(__('Contract'),'','row1');
        $cells.=la_TableCell($catv_user['contract']);
        $rows= la_TableRow($cells, 'row3');
        
        $cells=la_TableCell(__('Real name'),'','row1');
        $cells.=la_TableCell($catv_user['realname']);
        $rows.= la_TableRow($cells, 'row3');
        
        $cells=la_TableCell(__('Address'),'','row1');
        $cells.=la_TableCell($catv_user['street'].' '.$catv_user['build'].$apt);
        $rows.= la_TableRow($cells, 'row3');
        
        $cells=la_TableCell(__('Phone'),'','row1');
        $cells.=la_TableCell($catv_user['phone']);
        $rows.= la_TableRow($cells, 'row3');
        
        $cells=la_TableCell(__('Tariff'),'','row1');
        $cells.=la_TableCell(@$alltariffs[$catv_user['tariff']]);
        $rows.= la_TableRow($cells, 'row3');
        
        $cells=la_TableCell(__('Tariff price'),'','row1');
        $cells.=la_TableCell(@$allprices[$catv_user['tariff']]);
        $rows.= la_TableRow($cells, 'row3');
        
        $cells=la_TableCell(__('Balance'),'','row1');
        $cells.=la_TableCell($catv_user['cash']);
        $rows.= la_TableRow($cells, 'row3');
               
        
        $result=  la_TableBody($rows, '100%', '0', '');
        
        show_window(__('CaTV user profile'),$result);
    }
    
    function zbs_CatvGetUserPayments($catv_userid) {
        $catv_userid=vf($catv_userid,3);
        $query="SELECT * from `catv_payments` WHERE `userid`='".$catv_userid."'";
        $allpayments=  simple_queryall($query);
        if (!empty($allpayments)) {
            return ($allpayments);
        } else {
            return (false);
        }
    }
    
    function zbs_CatvShowPayments($catv_payments) {
        $monthnames=  zbs_months_array_wz();
        
        if (!empty($catv_payments)) {
            $cells=la_TableCell(__('Date'));
            $cells.=la_TableCell(__('Cash'));
            $cells.=la_TableCell(__('Month'));
            $cells.=la_TableCell(__('Year'));
            $rows=la_TableRow($cells, 'row1');
            
            foreach ($catv_payments as $io=>$each) {
                $cells=la_TableCell($each['date']);
                $cells.=la_TableCell($each['summ']);
                $cells.=la_TableCell(__($monthnames[$each['from_month']]));
                $cells.=la_TableCell($each['from_year']);
                $rows.=la_TableRow($cells, 'row3');
            }
            
            $result=  la_TableBody($rows, '100%', '0', '');
            show_window(__('CaTV payments'), $result);
            
        } else {
            show_window(__('Sorry'), __('No payments to display'));
        }
    }
    
    $catv_user=  zbs_CatvGetAssociatedUser($user_login);
    if ($catv_user) {
        $catv_userid=$catv_user['id'];
        $catv_payments=  zbs_CatvGetUserPayments($catv_userid);
        $alltariffs=  zbs_CatvGetAllTariffs();
        $allprices=  zbs_CatvGetAllTariffPrices();
        //show catv profile 
        zbs_CatvShowProfile($catv_user,$alltariffs,$allprices);
        zbs_CatvShowPayments($catv_payments);
        
    } else {
        show_window(__('Sorry'),__('No CaTV account associated with your Internet service')); 
    }
    
    
} else {
     show_window(__('Sorry'),__('Unfortunately CaTV is disabled'));
}
?>
