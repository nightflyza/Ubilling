<?php

if($system->checkForRight('PAYFIND')) {
    
function pf_showform() {
    $inputs=  wf_TextInput('searchid', __('ID'), '', true, '20');
    $inputs.=wf_CheckInput('enc', __('IDENC').'?', true, false);
    $inputs.=wf_Submit(__('Search'));
    $form=  wf_Form('', 'POST', $inputs, 'glamour');
    show_window(__('Payment search'),$form);
}
    


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
        
        $cells=  wf_TableCell(__('ID'));
        $cells.= wf_TableCell(__('IDENC'));
        $cells.= wf_TableCell(__('Date'));
        $cells.= wf_TableCell(__('Cash'));
        $cells.= wf_TableCell(__('Login'));
        $cells.= wf_TableCell(__('Real Name'));
        $cells.= wf_TableCell(__('Full address'));
        $cells.= wf_TableCell(__('Notes'));
        $cells.= wf_TableCell(__('Admin'));
        $rows=  wf_TableRow($cells, 'row1');
        
        $cells=  wf_TableCell($payment['id']);
        $cells.= wf_TableCell(zb_NumEncode($payment['id']));
        $cells.= wf_TableCell($payment['date']);
        $cells.= wf_TableCell($payment['summ']);
        $cells.= wf_TableCell(wf_Link("?module=userprofile&username=".$payment['login'], web_profile_icon(), false));
        $cells.= wf_TableCell(@$allnames[$payment['login']]);
        $cells.= wf_TableCell(@$alladdress[$payment['login']]);
        $cells.= wf_TableCell($payment['note']);
        $cells.= wf_TableCell($payment['admin']);
        $rows.=  wf_TableRow($cells, 'row3');
        
        $result=  wf_TableBody($rows, '100%', 0, 'sortable');
    
    } else {
        $result=__('Nothing found');
    }
    show_window(__('Result'), $result);
}


pf_showform();
show_window('',  wf_Link("?module=report_finance", __('Back'), true, 'ubButton'));

if (wf_CheckPost(array('searchid'))) {
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
