<?php

if (cfr('NAS')) {
    
    /*
     * Deletes Mikrotik NAS interface by NAS id
     * 
     * @param $nasid - NAS id for deletion
     * 
     * @return void
     */
    function zb_MtNasIfacesDelete($nasid) {
        $nasid=vf($nasid,3);
        $query="DELETE from `mtnasifaces` WHERE `nasid`='".$nasid."'";
        nr_query($query);
        log_register("MTNASIFACES DELETE [".$nasid."]");
    }
    
    /*
     * Adds some interface name for Mikrotik NAS
     *
     * @param $nasid - NAS id for deletion
     * @param $iface - interface used for graphing traffic
     * 
     * @return void
     */
    function zb_MtNasIfacesAdd($nasid,$iface) {
        $nasid=vf($nasid,3);
        $iface=  mysql_real_escape_string($iface);
        $query="INSERT INTO `mtnasifaces` (`id` ,`nasid` ,`iface`) VALUES (NULL , '".$nasid."', '".$iface."');";
        nr_query($query);
        log_register("MTNASIFACES ADD [".$nasid."] IFACE `".$iface."`");
    }
    
    /*
     * Returns adding form for Mikrotik interfaces for available NAS server
     * 
     * @return string
     */
    function web_MtNasIfacesAddForm() {
        $query="SELECT * from `nas` WHERE `nastype`='mikrotik'";
        $allnases=  simple_queryall($query);
        $allifaces=  zb_MtNasGetAllIfaces();
        
        $naslist=array();
        if (!empty($allnases)) {
            foreach ($allnases as $io=>$each) {
                if (!isset($allifaces[$each['id']])) {
                $naslist[$each['id']]=$each['nasname'];
                }
            }
        }
        
        $inputs= wf_Selector('newnasid', $naslist, __('NAS'), '', true);
        $inputs.=  wf_TextInput('newiface', __('Interface'), '', true, '12');
        $inputs.= wf_Submit(__('Create'));
        $form=  wf_Form("", "POST", $inputs, 'glamour');
        
        return ($form);
    }
    
     /*
     * Returns editing form for Mikrotik interfaces for available NAS server
     * 
     * @return string
     */
    function web_MtNasIfacesEditForm($nasid) {
        $nasid=vf($nasid,3);
        $query="SELECT * from `nas` WHERE `nastype`='mikrotik'";
        $allnases=  simple_queryall($query);
        $allifaces=  zb_MtNasGetAllIfaces();
        
        $naslist=array();
        if (!empty($allnases)) {
            foreach ($allnases as $io=>$each) {
                $naslist[$each['id']]=$each['nasname'];
            }
        }
        
        
        $inputs=  wf_TextInput('editiface', __('Interface'), $allifaces[$nasid], true, '12');
        $inputs.= wf_Submit(__('Create'));
        $form=  wf_Form("", "POST", $inputs, 'glamour');
        $form.= wf_Link("?module=mtnasifaces", __('Back'), true, 'ubButton');
        return ($form);
    }
    
    /*
     * Shows all available Mikrotik NAS interfaces
     * 
     * @return void
     */
    function web_MtNasIfacesShowAll() {
       $allifaces=  zb_MtNasGetAllIfaces();
       $allnases = zb_NasGetAllData();
       $nasnames=array();
       
       if (!empty($allnases)) {
           foreach ($allnases as $ia=>$eachnas) {
               $nasnames[$eachnas['id']]=$eachnas['nasname'];
           }
       }
       
       $cells=  wf_TableCell(__('NAS'));
       $cells.= wf_TableCell(__('Interface'));
       $cells.= wf_TableCell(__('Actions'));
       $rows=   wf_TableRow($cells, 'row1');
      
      if (!empty($allifaces)) {
          foreach ($allifaces as $nasid=>$iface) {
              
               $cells=  wf_TableCell(@$nasnames[$nasid]);
               $cells.= wf_TableCell($iface);
               $actions=  wf_JSAlert("?module=mtnasifaces&delete=".$nasid, web_delete_icon(), 'Removing this may lead to irreparable results');
               $actions.= wf_JSAlert("?module=mtnasifaces&edit=".$nasid, web_edit_icon(), 'Are you serious');
               $cells.= wf_TableCell($actions);
               $rows.=   wf_TableRow($cells, 'row3');

          }
      } else {
          show_window('',__('No MikroTik NAS interfaces assigned yet'));
      }
      $result=  wf_TableBody($rows, '100%', 0, 'sortable');
      show_window(__('Mikrotik NAS interfaces'),$result);  
    }
    
    /*
     * Controller
     */
    
    //deletion
    if (wf_CheckGet(array('delete'))) {
        zb_MtNasIfacesDelete($_GET['delete']);
        rcms_redirect("?module=mtnasifaces");
    }
    
    //adding
    if (wf_CheckPost(array('newnasid','newiface'))) {
        zb_MtNasIfacesAdd($_POST['newnasid'], $_POST['newiface']);
        rcms_redirect("?module=mtnasifaces");
    }
    
    //editing
    if (wf_CheckPost(array('editiface'))) {
        if (wf_CheckGet(array('edit'))) {
            $editnasid=vf($_GET['edit'],3);
            simple_update_field('mtnasifaces', 'iface', $_POST['editiface'], "WHERE `nasid`='".$editnasid."'");
            log_register("MTNASIFACES EDIT [".$editnasid."] IFACE `".$_POST['editiface']."`");
            rcms_redirect("?module=mtnasifaces");
        }
    }
    
    /*
     * Views
     */
    if (!wf_CheckGet(array('edit'))) {
    web_MtNasIfacesShowAll();
    show_window(__('Assign new interface name'),web_MtNasIfacesAddForm());
    } else {
        show_window(__('Edit'), web_MtNasIfacesEditForm($_GET['edit']));
    }
    
} else {
      show_error(__('You cant control this module'));
}

?>
