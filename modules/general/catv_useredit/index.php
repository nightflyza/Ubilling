<?php
if (cfr('CATV')) {
    
  //show controls
  catv_GlobalControlsShow();
      if (wf_CheckGet(array('userid'))) {
           $userid=vf($_GET['userid'],3);
           $userdata=catv_UserGetData($userid);
           $tariffnames=catv_TariffGetAllNames();
           
           //if someone edits user
           if (wf_CheckPost(array('realyedit'))) {
               catv_UserEdit($userid, $_POST['editcontract'], $_POST['editrealname'], $_POST['editstreet'], $_POST['editbuild'], $_POST['editapt'], $_POST['editphone'], $_POST['editdiscount'], $_POST['editdecoder'], $_POST['editinetlink'], $_POST['editnotes']);
               rcms_redirect("?module=catv_useredit&userid=".$userid);
           }
           
           //show editing form
           $editinputs=wf_HiddenInput('realyedit', 'true');
           $editinputs.=wf_TextInput('editcontract', 'Contract', $userdata['contract'], true,40);
           $editinputs.=wf_TextInput('editrealname', 'Real name', $userdata['realname'], true,40);
           $editinputs.=wf_TextInput('editstreet', 'Street', $userdata['street'], true,40);
           $editinputs.=wf_TextInput('editbuild', 'Build', $userdata['build'], true,40);
           $editinputs.=wf_TextInput('editapt', 'Apartment', $userdata['apt'], true,40);
           $editinputs.=wf_TextInput('editphone', 'Phone', $userdata['phone'], true,40);
           $editinputs.=wf_TextInput('editdiscount', 'Discount', $userdata['discount'], true,40);
           $editinputs.=wf_HiddenInput('editdecoder', $userdata['decoder']);
           $editinputs.=wf_TextInput('editinetlink', 'Internet account', $userdata['inetlink'], true,40);
           $editinputs.=wf_TextInput('editnotes', 'Notes', $userdata['notes'], true,40);
           $editinputs.=wf_Submit('Edit');
           $editform=wf_Form('', 'POST', $editinputs, 'glamour', '');
         
           
        show_window(__('Edit user'), $editform);
        catv_ProfileBack($userid);
      }
  } else {
      show_error(__('You cant control this module'));
}

?>