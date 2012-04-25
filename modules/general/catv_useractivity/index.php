<?php

if (cfr('CATVACT')) {
       catv_GlobalControlsShow();
       
    if (wf_CheckGet(array('userid'))) {
        $userid=vf($_GET['userid']);
        
        //collect all data
        $alluseractivity=catv_ActivityGetAllByUser($userid);
        $lasuseractivity=catv_ActivityGetLastByUser($userid);
        $lasuseractivitytime=catv_ActivityGetTimeLastByUser($userid);
        $userdata=catv_UserGetData($userid);
        $curyear=curyear();
        $curmonth=date("m");
        $curday=date("d");
        $curtime=curtime();
        
        //if creating new activity
        if (wf_CheckPost(array('newacttime','newactday'))) {
            $customdate=$_POST['newactyear'].'-'.$_POST['newactmonth'].'-'.$_POST['newactday'].' '.$_POST['newacttime'];
            deb($customdate);
            catv_ActivityCreateCustomDate($userid, $_POST['newactivity'],$customdate);
            rcms_redirect('?module=catv_useractivity&userid='.$userid);
        }
        
        //edit form construct
        $actinputs='';
        $actinputs.=wf_Trigger('newactivity', 'Connected', $lasuseractivity, true).'<hr>';
        $actinputs.=wf_TextInput('newactday', 'Day', $curday, false, '2');
        $actinputs.=wf_MonthSelector('newactmonth', 'Month', $curmonth, false);
        $actinputs.=wf_YearSelector('newactyear', 'Year', true).'<br>';
        $actinputs.=wf_HiddenInput('newacttime', $curtime);
        $actinputs.=wf_Submit('Change');
        
        $actform=wf_Form('', 'POST', $actinputs, 'glamour');
        
        show_window(__('User activity'),$actform);
        catv_ActivityShowAll($userid);
        
        catv_ProfileBack($userid);
        
        
        
        
        
        
    }
    
    
} else {
      show_error(__('You cant control this module'));
}
 
?>
