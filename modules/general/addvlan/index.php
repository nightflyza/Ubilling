<?php

if(cfr('ADDVLAN')) {
	$altcfg=  rcms_parse_ini_file(CONFIG_PATH.'alter.ini');
	if ($altcfg['VLANGEN_SUPPORT']) {

		//adding new network
		if (isset($_POST['addvlan'])) {
		$vlanadd_req=array('firstvlan','lastvlan','desc');
			if (wf_CheckPost($vlanadd_req)) {
				$desc=$_POST['desc'];
				$firstvlan=$_POST['firstvlan'];
				$lastvlan=$_POST['lastvlan'];
				$qinq=$_POST['use_qinq'];
				$svlan=$_POST['svlan'];
					if($qinq) {  vlan_add_pool($desc, $firstvlan, $lastvlan, $qinq, $svlan); }
					else {
						vlan_add_pool($desc, $firstvlan, $lastvlan, $qinq, 'NULL'); }
              			rcms_redirect('?module=addvlan');
				} else {
				show_window(__('Error'), __('No all of required fields is filled'));
				}
			}

//deleting pool
	if (isset($_GET['deletevlanpool'])) {
		$vlanpool_id=$_GET['deletevlanpool'];
		vlan_delete_pool($vlanpool_id);
		rcms_redirect('?module=addvlan');
	}

	if (!isset($_GET['editvlanpool'])) {
		vlan_show_available_pools();
		vlan_show_pools_form();
		} else {
          // editing network
			if (isset($_GET['editvlanpool'])) {
				$vlanpooledit=vf($_GET['editvlanpool']);
					if (isset($_POST['vlanpooledit'])) {
						$vlanpooled_req=array('editfirstvlan','editendvlan','editdesc');
							if (wf_CheckPost($vlanpooled_req)) {
								simple_update_field('vlan_pools', 'firstvlan', $_POST['editfirstvlan'], "WHERE `id`='".$vlanpooledit."'");
								simple_update_field('vlan_pools', 'endvlan', $_POST['editendvlan'], "WHERE `id`='".$vlanpooledit."'");
								simple_update_field('vlan_pools', 'desc', $_POST['editdesc'], "WHERE `id`='".$vlanpooledit."'");
								simple_update_field('vlan_pools', 'qinq', $_POST['edituse_qinq'], "WHERE `id`='".$vlanpooledit."'");
								simple_update_field('vlan_pools', 'svlan', $_POST['editsvlan'], "WHERE `id`='".$vlanpooledit."'");
								log_register('MODIFY VlanPool ['.$vlanpooledit.']');
								rcms_redirect("?module=addvlan"); 
							} else {
								show_window(__('Error'), __('No all of required fields is filled'));
							}
					}	
					vlan_show_pooleditform($vlanpooledit);
			}
	}
}
}

?>
