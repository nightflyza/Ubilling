<?php
if (cfr('AGENTS')) {
    //mb some custom options?
     $alter_conf=rcms_parse_ini_file(CONFIG_PATH."alter.ini");
     
     //if deleting agent
     if (isset($_GET['delete'])) {
         zb_ContrAhentDelete($_GET['delete']);
         rcms_redirect("?module=contrahens");
     }
    
     //if adding new agent
     if (isset ($_POST['newcontrname'])) {
 @$bankacc=$_POST['newbankacc'];
 @$bankname=$_POST['newbankname'];
 @$bankcode=$_POST['newbankcode'];
 @$edrpo=$_POST['newedrpo'];
 @$ipn=$_POST['newipn'];
 @$licensenum=$_POST['newlicensenum'];
 @$juraddr=$_POST['newjuraddr'];
 @$phisaddr=$_POST['newphisaddr'];
 @$phone=$_POST['newphone'];
 $contrname=$_POST['newcontrname'];
         
         zb_ContrAhentAdd($bankacc, $bankname, $bankcode, $edrpo, $ipn, $licensenum, $juraddr, $phisaddr, $phone, $contrname);
         rcms_redirect("?module=contrahens");
     }
     
     if (isset($_GET['edit'])) {
            
         //if someone changing agent
         if (isset ($_POST['changecontrname'])) {
            $ahentid=$_GET['edit'];
            @$bankacc=$_POST['changebankacc'];
            @$bankname=$_POST['changebankname'];
            @$bankcode=$_POST['changebankcode'];
            @$edrpo=$_POST['changeedrpo'];
            @$ipn=$_POST['changeipn'];
            @$licensenum=$_POST['changelicensenum'];
            @$juraddr=$_POST['changejuraddr'];
            @$phisaddr=$_POST['changephisaddr'];
            @$phone=$_POST['changephone'];
            $contrname=$_POST['changecontrname'];
            zb_ContrAhentChange($ahentid, $bankacc, $bankname, $bankcode, $edrpo, $ipn, $licensenum, $juraddr, $phisaddr, $phone, $contrname);
            rcms_redirect("?module=contrahens");
         }
       // show edit form  
        show_window(__('Edit'),zb_ContrAhentEditForm($_GET['edit']));
       
     }
   
     show_window(__('Available contrahens'),zb_ContrAhentShow());
     show_window(__('Add new'), zb_ContrAhentAddForm());

      
     //check agents region assign
     if ($alter_conf['AGENTS_ASSIGN']) {
         
          //if delete assign
         if (isset($_GET['deleteassign'])) {
             zb_AgentAssignDelete($_GET['deleteassign']);
             rcms_redirect("?module=contrahens");
         }
         //if adding assign 
         if (isset($_POST['newassign'])) {
             zb_AgentAssignAdd($_POST['ahentsel'], $_POST['newassign']);
             rcms_redirect("?module=contrahens");
         }
         
         show_window(__('Contrahent assign'), web_AgentAssignForm());
         show_window(__('Available assigns'),web_AgentAssignShow());
     }
     
    
    
} else {
      show_error(__('You cant control this module'));
}

?>
