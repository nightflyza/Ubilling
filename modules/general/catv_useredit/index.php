<?php
if (cfr('CATV')) {
    
  //show controls
  catv_GlobalControlsShow();
      if (wf_CheckGet(array('userid'))) {
           $userid=vf($_GET['userid'],3);
           $userdata=catv_UserGetData($userid);
           $tariffnames=catv_TariffGetAllNames();
           if ($userdata['apt']=='') {
           $address=$userdata['street'].' '.$userdata['build'];    
           } else {
           $address=$userdata['street'].' '.$userdata['build'].'/'.$userdata['apt'];
           }
           
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
         
           
        show_window(__('Edit user').' '.$address, $editform);
        catv_ProfileBack($userid);
      }
      
      //user deletion subroutine
       if (wf_CheckGet(array('deleteuserid'))) {
           $userid=vf($_GET['deleteuserid'],3);
           
           if (wf_CheckPost(array('deleteconfirmation'))) {
               if ($_POST['deleteconfirmation']=='confirm') {
                   catv_UserDelete($userid);
                   rcms_redirect("?module=catv&action=showusers");
               } else {
                   show_error(__('You are not mentally prepared for this'));
               }
           }
           
           $deleteinputs=__('Be careful, this module permanently deletes user and all data associated with it. Opportunities to raise from the dead no longer.').'<br>';
           $deleteinputs.=__('To ensure that we have seen the seriousness of your intentions to enter the word сonfirm the field below.').'<br>';
           $deleteinputs.=wf_TextInput('deleteconfirmation', '', '', false, '10');
           $deleteinputs.=wf_Submit('I really want to stop suffering User');
           $deleteform=  wf_Form('', 'POST', $deleteinputs, 'glamour');
          show_window('',$deleteform);
          catv_ProfileBack($userid);
      }
      
  } else {
      show_error(__('You cant control this module'));
}

?>