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
        $terminator = new VlanTerminator;
	if (isset($_GET['DeleteTerminator'])) {
            $TermID=$_GET['DeleteTerminator'];
            $terminator->delete($TermID);
            rcms_redirect(VlanTerminator::MODULE_URL);
        }

	if(!isset($_GET['EditTerminator'])) {
            if (isset ($_POST['AddTerminator'])) {
                $terminator_req=array('IP', 'Username', 'Password');
                if (wf_CheckPost($terminator_req)) {                        		
                    $terminator->add($_POST['NetworkSelected'], $_POST['VlanPoolSelected'], $_POST['IP'], $_POST['Type'], $_POST['Username'], $_POST['Password'], $_POST['RemoteID'], $_POST['Interface'], $_POST['Relay']);
                    rcms_redirect(VlanTerminator::MODULE_URL);
                } else {
                    show_window(__('Error'), __('No all of required fields is filled'));
                }
            }
            $terminator->RenderTerminators();
            $terminator->AddForm();
        } else {
            if(isset($_GET['EditTerminator'])) {
                $term_id=$_GET['EditTerminator'];
		if(isset($_POST['TerminatorEdit'])) {
                    $terminator_req=array('IP','Username','Password');
                    if (wf_CheckPost($terminator_req)) {
                        $terminator->edit($_POST['NetworkSelected'], $_POST['VlanPoolSelected'], $_POST['IP'], $_POST['Type'], $_POST['Username'], $_POST['Password'], $_POST['RemoteID'], $_POST['Interface'], $_POST['Relay'], $term_id);
                        rcms_redirect(VlanTerminator::MODULE_URL);
                    } else {
                        show_window(__('Error'), __('No all of required fields is filled'));
                    }
                }
		$terminator->EditForm($term_id);
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
