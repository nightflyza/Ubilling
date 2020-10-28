<?php
if (cfr('PDATA')) {
    
    if (wf_CheckGet(array('username'))) {
        $login=vf($_GET['username']);
        $passportdata=  zb_UserPassportDataGet($login);
         
         //editing passport data
         if (wf_CheckPost(array('editbirthdate'))) {
             $newbirthdate=$_POST['editbirthdate'];
             $newpassportnum=$_POST['editpassportnum'];
             $newpassportdate=$_POST['editpassportdate'];
             $newpassportwho=$_POST['editpassportwho'];
             $newpinn=$_POST['editpinn'];
             $newpcity=$_POST['editpcity'];
             $newpstreet=$_POST['editpstreet'];
             $newpbuild=$_POST['editpbuild'];
             $newpapt=$_POST['editpapt'];
             
             zb_UserPassportDataChange($login, $newbirthdate, $newpassportnum, $newpassportdate, $newpassportwho, $newpinn, $newpcity, $newpstreet, $newpbuild, $newpapt);
             rcms_redirect("?module=pdataedit&username=".$login);
         }
        
        web_PassportDataEditFormShow($login,$passportdata);
        show_window('',web_UserControls($login));
        
    } else {
        show_window(__('Error'),__('Strange exeption'));
    }


} else {
      show_error(__('You cant control this module'));
}

?>
