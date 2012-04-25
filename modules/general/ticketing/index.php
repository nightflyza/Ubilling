<?php
if (cfr('TICKETING')) {
  function web_TicketsShow() {
      $alltickets=zb_TicketsGetAll();
    
      $tablecells=wf_TableCell(__('ID'));
      $tablecells.=wf_TableCell(__('Date'));
      $tablecells.=wf_TableCell(__('From'));
      $tablecells.=wf_TableCell(__('Real Name'));
      $tablecells.=wf_TableCell(__('Full address'));
      $tablecells.=wf_TableCell(__('Tariff'));
      $tablecells.=wf_TableCell(__('Balance'));
      $tablecells.=wf_TableCell(__('Credit'));
      $tablecells.=wf_TableCell(__('Processed'));
      $tablecells.=wf_TableCell(__('Actions'));
      $tablerows=wf_TableRow($tablecells, 'row1');
      
      
      if (!empty ($alltickets)) {
          $allrealnames=zb_UserGetAllRealnames();
          $alladdress=zb_AddressGetFulladdresslist();
          $alltariffs=zb_TariffsGetAllUsers();
          $allcash=zb_CashGetAllUsers();
          $allcredits=zb_CreditGetAllUsers();
          
      foreach ($alltickets as $io=>$eachticket) {
  
      $tablecells=wf_TableCell($eachticket['id']);
      $tablecells.=wf_TableCell($eachticket['date']);
      $fromlink=wf_Link('?module=userprofile&username='.$eachticket['from'], web_profile_icon().' '.$eachticket['from']);
      $tablecells.=wf_TableCell($fromlink);
      $tablecells.=wf_TableCell(@$allrealnames[$eachticket['from']]);
      $tablecells.=wf_TableCell(@$alladdress[$eachticket['from']]);
      $tablecells.=wf_TableCell(@$alltariffs[$eachticket['from']]);
      $tablecells.=wf_TableCell(@$allcash[$eachticket['from']]);
      $tablecells.=wf_TableCell(@$allcredits[$eachticket['from']]);
      $tablecells.=wf_TableCell(web_bool_led($eachticket['status']), '', '', 'sorttable_customkey="'.$eachticket['status'].'"');
      $actionlink=wf_Link('?module=ticketing&showticket='.$eachticket['id'], 'Show', false, 'ubButton');
      $tablecells.=wf_TableCell($actionlink);
      $tablerows.=wf_TableRow($tablecells, 'row3');
      
          }
      }
      $result=wf_TableBody($tablerows, '100%', '0', 'sortable');
      return ($result);
  }
  
  function web_TicketReplyForm($ticketid) {
      $ticketid=vf($ticketid);
      $ticketdata=zb_TicketGetData($ticketid);
      $ticketstate=$ticketdata['status'];
      if (!$ticketstate) {
      $replyinputs=wf_HiddenInput('postreply', $ticketid);
      $replyinputs.=wf_TextArea('replytext', '', '', true, '60x10');
      $replyinputs.=wf_Submit('Reply');
      $replyform=wf_Form('', 'POST', $replyinputs, 'glamour');
      } else {
          $replyform=__('Ticket is closed');
      }
      return ($replyform);
  }
  
  function web_TicketDialogue($ticketid) {
      $ticketid=vf($ticketid);
      $ticketdata=zb_TicketGetData($ticketid);
      $ticketreplies=zb_TicketGetReplies($ticketid);
      $result='<p align="right">'. wf_Link('?module=ticketing', 'Back to tickets list', true, 'ubButton').'</p>';
      if (!empty ($ticketdata)) {
          $alladdress=zb_AddressGetFulladdresslist();
          $allrealnames=zb_UserGetAllRealnames();
          $alltariffs=zb_TariffsGetAllUsers();
          $allcash=zb_CashGetAllUsers();
          $allcredits=zb_CreditGetAllUsers();
          
          if ($ticketdata['status']) {
              $actionlink=wf_Link('?module=ticketing&openticket='.$ticketdata['id'], 'Open', false, 'ubButton');
          } else {
              $actionlink=wf_Link('?module=ticketing&closeticket='.$ticketdata['id'], 'Close', false, 'ubButton');
          }
          
            
          $tablecells=wf_TableCell(__('ID'));
          $tablecells.=wf_TableCell(__('Date'));
          $tablecells.=wf_TableCell(__('Login'));
          $tablecells.=wf_TableCell(__('Real Name'));
          $tablecells.=wf_TableCell(__('Full address'));
          $tablecells.=wf_TableCell(__('Tariff'));
          $tablecells.=wf_TableCell(__('Balance'));
          $tablecells.=wf_TableCell(__('Credit'));
          $tablecells.=wf_TableCell(__('Processed'));
          $tablerows=wf_TableRow($tablecells, 'row1');
          
          $tablecells=wf_TableCell($ticketdata['id']);
          $tablecells.=wf_TableCell($ticketdata['date']);
          $profilelink=wf_Link('?module=userprofile&username='.$ticketdata['from'], web_profile_icon().' '.$ticketdata['from']);
          $tablecells.=wf_TableCell($profilelink);
          $tablecells.=wf_TableCell(@$allrealnames[$ticketdata['from']]);
          $tablecells.=wf_TableCell(@$alladdress[$ticketdata['from']]);
          $tablecells.=wf_TableCell(@$alltariffs[$ticketdata['from']]);
          $tablecells.=wf_TableCell(@$allcash[$ticketdata['from']]);
          $tablecells.=wf_TableCell(@$allcredits[$ticketdata['from']]);
          $tablecells.=wf_TableCell(web_bool_led($ticketdata['status']));
          $tablerows.=wf_TableRow($tablecells, 'row3');
          $result.=wf_TableBody($tablerows, '100%', '0');
          
          //ticket body
          $tickettext=strip_tags($ticketdata['text']);
          $tablecells=wf_TableCell('', '20%');
          $tablecells.=wf_TableCell($ticketdata['date']);
          $tablerows=wf_TableRow($tablecells, 'row2');
          
           $ticketauthor='<center><b>'.@$allrealnames[$ticketdata['from']].'</b></center>';    
           $ticketavatar='<center><img src="skins/userava.png"></center>';
           $ticketpanel=$ticketauthor.'<br>'.$ticketavatar;
          
          $tablecells=wf_TableCell($ticketpanel);
          $tablecells.=wf_TableCell($tickettext);
          $tablerows.=wf_TableRow($tablecells, 'row3');
          
          $result.=wf_TableBody($tablerows, '100%', '0','glamour');
          $result.=$actionlink;
      }
      
      
      if (!empty ($ticketreplies)) {
          $result.='<h2>'.__('Replies').'</h2>';
          foreach ($ticketreplies as $io=>$eachreply) {
              
              $resultx='
              <tr class="row2">
              <td>'.__('Date').'</td>
              <td>'.$eachreply['date'].'</td>
              <td>'.$eachreply['admin'].'</td>
              </tr>
              
              <tr class="row3">
              <td>'.__('Ticket').'</td>
              <td>'.strip_tags($eachreply['text']).'</td>
              <td></td>
              </tr>
              ';
              
           //reply
          if ($eachreply['admin']) {
              $replyauthor='<center><b>'.$eachreply['admin'].'</b></center>';    
              $replyavatar='<center><img src="skins/admava.png"></center>';
          } else {
              $replyauthor='<center><b>'.@$allrealnames[$eachreply['from']].'</b></center>';    
              $replyavatar='<center><img src="skins/userava.png"></center>';
          }
          
          $replytext=strip_tags($eachreply['text']);
          $replypanel=$replyauthor.'<br>'.$replyavatar;
          
          $tablecells=wf_TableCell('', '20%');
          $tablecells.=wf_TableCell($eachreply['date']);
          $tablerows=wf_TableRow($tablecells, 'row2');
          
          $tablecells=wf_TableCell($replypanel);
          $tablecells.=wf_TableCell($replytext);
          $tablerows.=wf_TableRow($tablecells, 'row3');
          
          $result.=wf_TableBody($tablerows, '100%', '0','glamour');
                
          }
          
      }
      
      
      
      //reply form and previous tickets
      $allprevious=zb_TicketsGetAllByUser($ticketdata['from']);
      $previoustickets='';
      if (!empty ($allprevious)) {
          $previoustickets='<h2>'.__('All tickets by this user').'</h2>';
          foreach ($allprevious as $io=>$eachprevious) {
              $tablecells=wf_TableCell($eachprevious['date']);
              $tablecells.=wf_TableCell(web_bool_led($eachprevious['status']));
              $prevaction=wf_Link('?module=ticketing&showticket='.$eachprevious['id'], 'Show', false, 'ubButton');
              $tablecells.=wf_TableCell($prevaction);
              $tablerows=wf_TableRow($tablecells, 'row3');
              $previoustickets.=wf_TableBody($tablerows, '100%','0');
          }
          
      }
      
      $tablecells=wf_TableCell(web_TicketReplyForm($ticketid),'50%','','valign="top"');
      $tablecells.=wf_TableCell($previoustickets,'50%','','valign="top"');
      $tablerows=wf_TableRow($tablecells);
      
      $result.=wf_TableBody($tablerows, '100%', '0','glamour');
      return ($result);
  }
  
  
  // close ticket
  if (isset($_GET['closeticket'])) {
      zb_TicketSetDone($_GET['closeticket']);
      rcms_redirect("?module=ticketing");
  } 
  
  //open ticket
  if (isset($_GET['openticket'])) {
      zb_TicketSetUnDone($_GET['openticket']);
      rcms_redirect("?module=ticketing&showticket=".$_GET['openticket']);
  }  
  
  
  //view tickets list
  if (!isset($_GET['showticket'])) {
      show_window(__('Available user tickets'), web_TicketsShow());
      
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
      
  }
  
    
    
} else {
       show_error(__('You cant control this module'));
}

?>