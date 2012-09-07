<?php
// check for right of current admin on this module
if (cfr('CATVSTATS')) {
    
    catv_GlobalControlsShow();
    
    if (wf_CheckGet(array('userid'))) {
           $userid=vf($_GET['userid'],3);
           $userdata=  catv_UserGetData($userid);
           $realname=$userdata['realname'];
           $address=$userdata['street'].' '.$userdata['build'].'/'.$userdata['apt'];
           
        //target year selection
        $yearforminputs=wf_YearSelector('yearselect', 'Year',false);
        $yearforminputs.=' ';
        $yearforminputs.=wf_Submit('Show');
        $yearform=wf_Form('', 'POST', $yearforminputs, 'glamour', '');
        show_window($address.' '.$realname, $yearform.'<div style="clear: both;"></div>');
        
        if (wf_CheckPost(array('yearselect'))) {
            $target_year=$_POST['yearselect'];
        } else {
            $target_year=curyear();
        }
        
        
        catv_UserStatsByYear($userid,$target_year);
        catv_DecoderShowAllChanges($userid);
        catv_ActivityShowAll($userid);
        
        catv_ProfileBack($userid);
        
        
                 
                    
     
    }
    

    
} else {
      show_error(__('You cant control this module'));
}

?>