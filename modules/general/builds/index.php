<?php
// check for right of current admin on this module
if (cfr('BUILDS')) {

  if (!isset ($_GET['action'])) {
  show_window(__('Builds editor'),  web_StreetListerBuildsEdit());
  } else {
   if (isset($_GET['streetid'])) {
       $streetid=vf($_GET['streetid']);
       if ($_GET['action']=='edit') {
           if (isset($_POST['newbuildnum'])) {
               if (!empty($_POST['newbuildnum'])) {
                   //check for exist of same build at this street
                   $existingBuilds_raw=  zb_AddressGetBuildAllDataByStreet($streetid);
                   $existingBuilds=array();
                   if (!empty($existingBuilds_raw)) {
                       foreach ($existingBuilds_raw as $ix=>$eachbuilddata) {
                           $existingBuilds[]=  strtolower_utf8($eachbuilddata['buildnum']);
                       }
                   }
                   if (!in_array(strtolower_utf8($_POST['newbuildnum']), $existingBuilds)) {
                       zb_AddressCreateBuild($streetid, trim($_POST['newbuildnum']));
                   } else {
                       show_error(__('The same build already exists'));
                   }
                   
               } else {
                   show_error(__('Empty building number'));
               }
               
           }
           $streetname=zb_AddressGetStreetData($streetid);
           $streetname=$streetname['streetname'];
           show_window(__('Add build'),web_BuildAddForm());
           show_window(__('Available buildings on street').' '.$streetname,web_BuildLister($streetid));
       }
       if ($_GET['action']=='delete') {
           if (!zb_AddressBuildProtected($_GET['buildid'])) {
           zb_AddressDeleteBuild($_GET['buildid']);
           rcms_redirect("?module=builds&action=edit&streetid=".$streetid);
           } else {
               show_window('', wf_BackLink("?module=builds&action=edit&streetid=".$streetid));               
               show_error(__('You can not delete a building if there are users of the apartment'));
            }

       }
       
       if ($_GET['action']=='editbuild') {
           $buildid=vf($_GET['buildid']);
           $streetid=vf($_GET['streetid']);
           
           //build edit subroutine
           if (isset($_POST['editbuildnum'])) {
               if (!empty($_POST['editbuildnum'])) {
               simple_update_field('build', 'buildnum', trim($_POST['editbuildnum']), "WHERE `id`='".$buildid."'");
               simple_update_field('build', 'geo', preg_replace('/[^-?0-9\.,]/i', '', $_POST['editbuildgeo']), "WHERE `id`='".$buildid."'");
               }
               log_register("CHANGE AddressBuild [".$buildid."] ".  mysql_real_escape_string(trim($_POST['editbuildnum'])));
               rcms_redirect("?module=builds&action=edit&streetid=".$streetid);
           }
               
          
           //construct edit form
           $builddata=zb_AddressGetBuildData($buildid);
           $streetname=zb_AddressGetStreetData($streetid);
           $streetname=$streetname['streetname'];
           $editinputs=$streetname." ".$builddata['buildnum'].  wf_tag('hr');
           $editinputs.=wf_TextInput('editbuildnum', 'Building number', $builddata['buildnum'], true, '10');
           $editinputs.=wf_TextInput('editbuildgeo', 'Geo location', $builddata['geo'], true, '20', 'geo');
           $editinputs.=wf_Submit('Save');
           $editform=wf_Form('', 'POST', $editinputs, 'glamour');
           show_window(__('Edit').' '.__('Build'), $editform);
           show_window('', wf_BackLink("?module=builds&action=edit&streetid=".$streetid));
       }
   }
  }

    
    
}
?>
