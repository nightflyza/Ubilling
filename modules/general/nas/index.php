<?php
if (cfr('NAS')) {
        if (isset ($_GET['delete'])) {
            $deletenas=$_GET['delete'];
            zb_NasDelete($deletenas);
            zb_NasConfigSave();
            rcms_redirect("?module=nas");
        }
        
        if (isset ($_POST['newnasip'])) {
            $newnasip=$_POST['newnasip'];
            $newnetid=$_POST['networkselect'];
            $newnasname=$_POST['newnasname'];
            $newnastype=$_POST['newnastype'];
            $newbandw=$_POST['newbandw'];
            if ((!empty ($newnasip)) AND (!empty ($newnasname))) {
                zb_NasAdd($newnetid, $newnasip, $newnasname, $newnastype, $newbandw);
                zb_NasConfigSave();
                rcms_redirect("?module=nas");
            }
        }
 

 // Show available NASes
    $allnas=zb_NasGetAllData();

    // construct needed editor
    $titles=array(
        'ID',
        'Network',
        'IP',
        'NAS name',
        'NAS type',
        'Bandwidthd URL'
        );
    $keys=array('id',
        'netid',
        'nasip',
        'nasname',
        'nastype',
        'bandw'
        );
    
    if (!wf_CheckGet(array('edit')))  {
    $altCfg=$ubillingConfig->getAlter();
    if ($altCfg['FREERADIUS_ENABLED']) {
        $freeRadiusClientsData=web_FreeRadiusListClients();
        $radiusControls=  wf_modal(web_icon_extended(__('FreeRADIUS NAS parameters')), __('FreeRADIUS NAS parameters'), $freeRadiusClientsData, '', '600', '300');
    } else {
        $radiusControls='';
    }
    show_window(__('Network Access Servers').' '.$radiusControls,web_GridEditorNas($titles, $keys, $allnas,'nas'));
    show_window(__('Add new'),web_NasAddForm());
    //vlangen patch start
    	if($altCfg['VLANGEN_SUPPORT']) {

	        if (isset($_GET['deleteterm'])) {
                $term_id=$_GET['deleteterm'];
                delete_term($term_id);
                rcms_redirect('?module=nas');
        	}

		if(!isset($_GET['editterm'])) {
			if (isset ($_POST['addterm'])) {
                	$terminator_req=array('ip', 'username', 'password');
                		if (wf_CheckPost($terminator_req)) {
                        		$netid=$_POST['networkselect'];
                        		$vlanpoolid=$_POST['vlanpoolselect'];
                        		$terminator_ip=$_POST['ip'];
                        		$terminator_type=$_POST['type'];
                        		$terminator_username=$_POST['username'];
                        		$terminator_password=$_POST['password'];
					$terminator_remoteid=$_POST['remoteid'];
					$terminator_ifname=$_POST['interface'];
					$relay=$_POST['relay'];
                        		term_add($netid,$vlanpoolid,$terminator_ip,$terminator_type,$terminator_username,$terminator_password,$terminator_remoteid,$terminator_ifname,$relay);
                        		rcms_redirect("?module=nas");
                		} else {
                        		show_window(__('Error'), __('No all of required fields is filled'));
                		}
       			}
			show_all_terminators();
			terminators_show_form();
			} else {
				if(isset($_GET['editterm'])) {
					$term_id=$_GET['editterm'];
					if(isset($_POST['termedit'])) {
					$terminator_req=array('editip','editusername','editpassword');
                                                        if (wf_CheckPost($terminator_req)) {
                                                                simple_update_field('vlan_terminators', 'netid', $_POST['networkselect'], "WHERE `id`='".$term_id."'");
                                                                simple_update_field('vlan_terminators', 'vlanpoolid', $_POST['vlanpoolselect'], "WHERE `id`='".$term_id."'");
                                                                simple_update_field('vlan_terminators', 'ip', $_POST['editip'], "WHERE `id`='".$term_id."'");
								simple_update_field('vlan_terminators', 'type', $_POST['edittype'], "WHERE `id`='".$term_id."'");
								simple_update_field('vlan_terminators', 'username', $_POST['editusername'], "WHERE `id`='".$term_id."'");
								simple_update_field('vlan_terminators', 'password', $_POST['editpassword'], "WHERE `id`='".$term_id."'");
								simple_update_field('vlan_terminators', 'remote-id', $_POST['editremoteid'], "WHERE `id`='".$term_id."'");
								simple_update_field('vlan_terminators', 'interface', $_POST['editinterface'], "WHERE `id`='".$term_id."'");
								simple_update_field('vlan_terminators', 'relay', $_POST['editrelay'], "WHERE `id`='".$term_id."'");
								log_register('MODIFY Vlan Terminator ['.$term_id.']');
                                                                rcms_redirect("?module=nas");
                                                        } else {
                                                                show_window(__('Error'), __('No all of required fields is filled'));
                                                        }
					}
							term_show_editform($term_id);
				}
		}
	}
        //vlangen patch end
    } else {
        //show editing form
       $nasid=vf($_GET['edit']);
       
       //if someone editing nas
       if (wf_CheckPost(array('editnastype'))) {
           $targetnas="WHERE `id` = '".$nasid."'";
           
           $nastype=vf($_POST['editnastype']);
           $nasip=mysql_real_escape_string($_POST['editnasip']);
           $nasname=mysql_real_escape_string($_POST['editnasname']);
           $nasbwdurl=mysql_real_escape_string($_POST['editnasbwdurl']);
           $netid=vf($_POST['networkselect']);
           
           simple_update_field('nas', 'nastype', $nastype, $targetnas);
           simple_update_field('nas', 'nasip', $nasip, $targetnas);
           simple_update_field('nas', 'nasname', $nasname, $targetnas);
           simple_update_field('nas', 'bandw', $nasbwdurl, $targetnas);
           simple_update_field('nas', 'netid', $netid, $targetnas);
           zb_NasConfigSave();
           log_register("NAS EDIT ".$nasip);
           rcms_redirect("?module=nas&edit=".$nasid);
       }
       
       
       $nasdata=zb_NasGetData($nasid);
       $currentnetid=$nasdata['netid'];
       $currentnasip=$nasdata['nasip'];
       $currentnasname=$nasdata['nasname'];
       $currentnastype=$nasdata['nastype'];
       $currentbwdurl=$nasdata['bandw'];
       $nastypes=array(
           'rscriptd'=>'rscriptd',
           'mikrotik'=>'MikroTik',
           'radius'=>'Radius',
           'local'=>'Local NAS'
           );
       
       
       $editinputs=multinet_network_selector($currentnetid)."<br>";
       $editinputs.=wf_Selector('editnastype', $nastypes, 'NAS type', $currentnastype, true);
       $editinputs.=wf_TextInput('editnasip', 'IP', $currentnasip, true, '15');
       $editinputs.=wf_TextInput('editnasname', 'NAS name', $currentnasname, true, '15');
       $editinputs.=wf_TextInput('editnasbwdurl', 'Bandwidthd URL', $currentbwdurl, true, '25');
       $editinputs.=wf_Submit('Save');
       $editform=wf_Form('', 'POST', $editinputs, 'glamour');
       show_window(__('Edit').' NAS',$editform);
       show_window('',wf_Link("?module=nas", 'Back', true, 'ubButton'));
    }

} else {
      show_error(__('You cant control this module'));
}

?>
