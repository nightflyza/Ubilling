<?php
// check for right of current admin on this module
if (cfr('CATVDECODEREDIT')) {
    
      catv_GlobalControlsShow();
      
  if (wf_CheckGet(array('userid'))) {
      $userid=$_GET['userid'];
  
      $userdata=catv_UserGetData($userid);
      $currentdecoder=$currenttariff=$userdata['decoder'];
       
      
      //if someone changing decoder
      if (wf_CheckPost(array('newuserdecoder'))) {
          
          catv_DecoderChange($userid, $_POST['newuserdecoder']);
          rcms_redirect("?module=catv_decoderedit&userid=".$userid);
      }
      
      
      //form construct
      $editinputs=wf_TextInput('newuserdecoder', 'Decoder', $currentdecoder, false, '20');
      $editinputs.=wf_Submit('Change');
      $editform=wf_Form('', 'POST',  $editinputs, 'glamour', '');
      show_window(__('Edit decoder'), $editform);
      
      catv_DecoderShowAllChanges($userid);
      
      catv_ProfileBack($userid);
      
  }
    
}

?>