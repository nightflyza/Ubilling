<?php

if($system->checkForRight('PAYFIND')) {
    
function pf_showform() {
    $form='
        <form action="" method="POST">
        <input type="text" name="searchid" size="20"> ID <br>
        <input type="checkbox" name="enc"> IDENC?
        <br>
        <input type="submit" value="'.__('Search').'">
        </form>
        ';
    show_window(__('Payment search'),$form);
}
    
pf_showform();

function pf_search($searchid,$enc=true) {
    $searchid=vf($searchid);
    if ($enc) {
        $searchid=zb_NumUnEncode($searchid);
    }
    $query="SELECT * from `payments` WHERE `id`='".$searchid."'"; 
    $payment=simple_query($query);
    if (!empty ($payment)) {
        $alladdress=zb_AddressGetFulladdresslist();
        $allnames=zb_UserGetAllRealnames();
        $result='
            <table width="100%" border="0">
            <tr class="row1">
            <td>'.__('ID').'</td>
            <td>'.__('IDENC').'</td>
            <td>'.__('Date').'</td>
            <td>'.__('Cash').'</td>
            <td>'.__('Login').'</td>
            <td>'.__('Real Name').'</td>
            <td>'.__('Full address').'</td>
            <td>'.__('Notes').'</td>
            </tr>
            <tr class="row3">
            <td>'.$payment['id'].'</td>
            <td>'.zb_NumEncode($payment['id']).'</td>
            <td>'.$payment['date'].'</td>
            <td>'.$payment['summ'].'</td>
            <td><a href="?module=userprofile&username='.$payment['login'].'">'.  web_profile_icon().'</a> '.$payment['login'].'</td>
            <td>'.$allnames[$payment['login']].'</td>
            <td>'.$alladdress[$payment['login']].'</td>
            <td>'.$payment['note'].'</td>
            </tr>
            </table>
            ';
    } else {
        $result=__('Nothing found');
    }
    show_window(__('Result'), $result);
}


if (isset ($_POST['searchid'])) {
     $enc=false;
    if (isset($_POST['enc'])) {
        $enc=true;
    } 
    pf_search($_POST['searchid'],$enc);
}
    
    
} else {
	show_error(__('Access denied'));
}

?>
