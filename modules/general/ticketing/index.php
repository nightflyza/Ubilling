<?php
if (cfr('TICKETING')) {
    
  // close ticket
  if (isset($_GET['closeticket'])) {
      zb_TicketSetDone($_GET['closeticket']);
      //update notification area
      $darkVoid=new DarkVoid();
      $darkVoid->flushCache();
      rcms_redirect("?module=ticketing");
  } 
  
  //open ticket
  if (isset($_GET['openticket'])) {
      zb_TicketSetUnDone($_GET['openticket']);
      rcms_redirect("?module=ticketing&showticket=".$_GET['openticket']);
  }  
  
  if (!wf_CheckGet(array('settings'))) {
  //view tickets list or calendar
  if (!isset($_GET['showticket'])) {
      $configControl=  wf_Link('?module=ticketing&settings=true', wf_img('skins/settings.png', __('Typical answers presets'))).' ';
      if (!wf_CheckGet(array('calendarview'))) {
          $viewControl=  wf_Link('?module=ticketing&calendarview=true', wf_img('skins/icon_calendar.gif', __('As calendar')), false, '');
          show_window($configControl.__('Available user tickets').' '.$viewControl, web_TicketsShow());
      } else {
          $viewControl=  wf_Link('?module=ticketing', wf_img('skins/icon_table.png', __('Grid view')), false, '');
          show_window($configControl.__('Available user tickets').' '.$viewControl, web_TicketsCalendar());
      }
      
  } else {
      //or view ticket data and replies
      $ticketid=vf($_GET['showticket']);
      show_window(__('Ticket').':'.$ticketid,web_TicketDialogue($ticketid));
      // maybe someone want to post reply
      if (isset($_POST['postreply'])) {
          $originaladdress=zb_TicketGetData($_POST['postreply']);
          $originaladdress=$originaladdress['from'];
          $admin=whoami();
          zb_TicketCreate('NULL', $originaladdress, $_POST['replytext'], $_POST['postreply'],$admin);
          rcms_redirect("?module=ticketing&showticket=".$_POST['postreply']);
      }
      
      //maybe someone deleting reply
      if (isset($_GET['deletereply'])) {
          zb_TicketDeleteReply($_GET['deletereply']);
          rcms_redirect("?module=ticketing&showticket=".$ticketid);
      }
      
      //reply editing sub 
      if (isset($_POST['editreply'])) {
          zb_TicketUpdateReply($_POST['editreply'], $_POST['editreplytext']);
          rcms_redirect("?module=ticketing&showticket=".$ticketid);
      }
  }
  
  } else {
      //Typical Answers Presets (TAP) configuration
      
      //create new one
      if (wf_CheckPost(array('createnewtap','newtaptext'))) {
          zb_TicketsTAPCreate($_POST['newtaptext']);
          rcms_redirect('?module=ticketing&settings=true');
      }
      
      //deleting tap
      if (wf_CheckGet(array('deletetap'))) {
          zb_TicketsTAPDelete($_GET['deletetap']);
          rcms_redirect('?module=ticketing&settings=true');
      }
      
      //editing tap
      if (wf_CheckPost(array('edittapkey','edittaptext'))) {
          zb_TicketsTAPEdit($_POST['edittapkey'], $_POST['edittaptext']);
          rcms_redirect('?module=ticketing&settings=true');
      }
      
      //list available
      show_window(__('Available typical answers presets'), web_TicketsTapShowAvailable());
      
      //add form
      show_window(__('Create new preset'),web_TicketsTAPAddForm());
      
      show_window('', wf_BackLink('?module=ticketing'));
  }   
    
} else {
       show_error(__('You cant control this module'));
}

?>