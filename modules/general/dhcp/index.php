<?php
if(cfr('DHCP')) {
    //if someone adds new dhcp network
    if (isset($_POST['adddhcp'])) {
        $netid=$_POST['networkselect'];
        $dhcpconfig=$_POST['dhcpconfig'];
        $dhcpconfname=$_POST['dhcpconfname'];
        if (!empty ($dhcpconfname)) {
        dhcp_add_network($netid, $dhcpconfig, $dhcpconfname);
        multinet_rebuild_all_handlers();
        rcms_redirect('?module=dhcp');
        }
        
    }
    
   function dhcp_show_previews() {
       $query="SELECT * from `dhcp`";
       $allnets=simple_queryall($query);
       if (!empty ($allnets)) {
       $dhcpdconf='#dhcpd.conf <br>'.str_replace("\n",'<br>',file_get_contents('multinet/dhcpd.conf'));
       $previews=web_Overlay('dhcpd.conf', $dhcpdconf,0.90);
       foreach ($allnets as $io=>$eachnet) {
           $subconfname=trim($eachnet['confname']);
           @$subconfdata='#'.$subconfname.' <br>'.str_replace("\n", '<br>', file_get_contents('multinet/'.$subconfname));
           $previews.=web_Overlay($subconfname, $subconfdata,0.90);
       }
       show_window(__('Generated configs preview'),$previews);
       }
   }
    
    if (isset($_GET['edit'])) {
        //if someone changes network
        if (isset($_POST['editdhcpconfname'])) {
            @$editdhcpconfig=$_POST['editdhcpconfig'];
            $dhcpconfname=$_POST['editdhcpconfname'];
            $dhcpid=$_GET['edit'];
            dhcp_update_data($dhcpid, $dhcpconfname, $editdhcpconfig);
            multinet_rebuild_all_handlers();
            rcms_redirect("?module=dhcp");
        }
        // show editing form
        dhcp_show_edit_form($_GET['edit']);
    }
    
    //if someone deleting net
    if (isset($_GET['delete'])) {
        dhcp_delete_net($_GET['delete']);
        multinet_rebuild_all_handlers();
        rcms_redirect("?module=dhcp");
    }

    dhcp_show_available_nets();
    dhcp_show_add_form();
    dhcp_show_previews();

}
else {
	show_error(__('Access denied'));
}

?>