<?php
if (cfr('CASHTYPES')) {
    
    function web_CashCashtypeDefaultForm() {
        $defCashType=  zb_StorageGet('DEF_CT');
        if (empty($defCashType)) {
            $defCashType='NOP';
        }
        
        $allCashTypes=  zb_CashGetAllCashTypes();
     
        $inputs= wf_Selector('setdefaultcashtype', $allCashTypes, __('Current default cashtype for manual input'), $defCashType, true);
        $inputs.= wf_Submit(__('Set as default cash type'));
        $result=  wf_Form('', 'POST', $inputs, 'glamour');
        return ($result);
    }
    
    if (isset($_GET['action'])) {
        // delete cash type
        if ($_GET['action']=='delete') {
            $cashtypeid=vf($_GET['id'],3);
            //check for default cash type
            if ($cashtypeid!=1) {
            zb_CashDeleteCashtype($cashtypeid);
            rcms_redirect("?module=cashtypes");
            } else {
                show_error(__('You know, we really would like to let you perform this action, but our conscience does not allow us to do'));
            }
        }
        
        //cash type editing
        if ($_GET['action']=='edit') {
            $cashtypeid=vf($_GET['id'],3);
            
            if (wf_CheckPost(array('editcashtype'))) {
                simple_update_field('cashtype', 'cashtype', $_POST['editcashtype'], "WHERE `id`='".$cashtypeid."'");
                log_register('EDIT CASHTYPE '.$cashtypeid);
                rcms_redirect("?module=cashtypes");
            }
            
            $cashtypename=zb_CashGetTypeName($cashtypeid);
            $editinputs=wf_TextInput('editcashtype', 'Cash type', $cashtypename, true, '10');
            $editinputs.=wf_Submit('Save');
            $editform=wf_Form('', 'POST', $editinputs, 'glamour');
            $editform.=wf_BackLink('?module=cashtypes', 'Back', true);
            show_window(__('Edit').' '.__('Cash type'), $editform);
           }
        
      }
    
     //creating new cash type
    if (isset($_POST['newcashtype'])) {
        $newcashtype=mysql_real_escape_string($_POST['newcashtype']);
        if (!empty($newcashtype)) {
        zb_CashCreateCashType($newcashtype);
        rcms_redirect("?module=cashtypes");
        } else {
            show_error(__('No all of required fields is filled'));
        }
    }
    
    //setting default cashtype
    if (wf_CheckPost(array('setdefaultcashtype'))) {
        zb_StorageSet('DEF_CT', $_POST['setdefaultcashtype']);
        log_register("CASHTYPE SET DEFAULT [".$_POST['setdefaultcashtype']."]");
        rcms_redirect("?module=cashtypes");
    }
    
    

    
// Edit form construct
$fieldname=__('Cash type');
$fieldkey='cashtype';
$formurl='?module=cashtypes';
$olddata=zb_CashGetAlltypes();

$form=web_EditorTableDataFormOneField($fieldname, $fieldkey, $formurl, $olddata);

show_window(__('Edit payment types'), $form);
show_window(__('Default cash type'),  web_CashCashtypeDefaultForm());

} else {
      show_error(__('You cant control this module'));
}

?>
