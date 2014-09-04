<?php
// check for right of current admin on this module
if (cfr('BINDER')) {

 if (isset ($_GET['username'])) {
     //needed login
     $login=vf($_GET['username']);

     //if change 
     if (isset($_POST['changeapt'])) {
         $changeaptdata=zb_AddressGetAptData($login);
         $changeaptid=$changeaptdata['id'];
         $changeaptbuildid=$changeaptdata['buildid'];
         $changeapt=$_POST['changeapt'];
         if (empty($changeapt)) {
             $changeapt=0;
         }
         @$changefloor=$_POST['changefloor'];
         @$changeentrance=$_POST['changeentrance'];
         zb_AddressChangeApartment($changeaptid, $changeaptbuildid, $changeentrance, $changefloor, $changeapt);
         rcms_redirect("?module=binder&username=".$login);
     }
     
     //if delete
     if (isset ($_GET['orphan'])) {
         $deletedata=zb_AddressGetAptData($login);
         $deleteatpid=$deletedata['aptid'];
         zb_AddressOrphanUser($login);
         zb_AddressDeleteApartment($deleteatpid);
         rcms_redirect("?module=binder&username=".$login);
     }
     
     //if create new home to user
     if (isset($_POST['apt'])) {
         $apt=$_POST['apt'];
         if (empty($apt)) {
             $apt=0;
         }
         @$entrance=$_POST['entrance'];
         @$floor=$_POST['floor'];
         $buildid=$_POST['buildsel'];
         zb_AddressCreateApartment($buildid, $entrance, $floor, $apt);
         $newaptid=zb_AddressGetLastid();
         zb_AddressCreateAddress($login, $newaptid);
         rcms_redirect("?module=binder&username=".$login);      
     }
     
  
    
     $addrdata=zb_AddressGetAptData($login);
     if (!empty ($addrdata)) {
         //if just wan to modify entrance/floor/apt
         show_window(__('Change user apartment'),web_AddressAptForm($login));
     } else {
         // if user is orphan and need new home     
         show_window(__('User occupancy'), web_AddressOccupancyForm());
     }
     
     show_window('',web_UserControls($login));
     
     
     
 }

} else {
    show_error(__('Access denied'));
}
?>
