<?php
/*
 * Ubilling instant messenger API
 */

    /*
     * Creates message for some admin user
     * 
     * @param $to   admin login
     * @param $text message text
     * 
     * @return void
     */
    function im_CreateMessage($to,$text) {
        $to=    mysql_real_escape_string($to);
        $text=  mysql_real_escape_string($text);
        $text=  strip_tags($text);
        $from=  whoami();
        $date=  curdatetime();
        $read=  0;
        
        $query="INSERT INTO `ub_im` (
                `id` ,
                `date` ,
                `from` ,
                `to` ,
                `text` ,
                `read`
                )
                VALUES (
                NULL , '".$date."', '".$from."', '".$to."', '".$text."', '".$read."'
                );
                ";
        nr_query($query);
        log_register("UBIM SEND FROM {".$from."} TO {".$to."}");
    }
    
    /*
     * Deletes message by its id
     * 
     * @param $msgid   message id from `ub_im`
     * 
     * @return void
     */
    function im_DeleteMessage($msgid) {
        $msgid= vf($msgid,3);
        $query="DELETE from `ub_im` WHERE `id`='".$msgid."'";
        nr_query($query);
        log_register("UBIM DELETE [".$msgid."]");
    }
    
    /*
     * Shows avatar control form
     * 
     * @return string
     */
    
    function im_AvatarControlForm() {
        $me=   whoami();
        $mail= gravatar_GetUserEmail($me); 
        
        $cells=  wf_TableCell(wf_tag('h1').$me.wf_tag('h1',true),'','','align="center"');
        $rows=  wf_TableRow($cells);
        $cells=  wf_TableCell(gravatar_ShowAdminAvatar($me,'256'),'','','align="center"');
        $rows.=  wf_TableRow($cells); 
        $cells=  wf_TableCell(wf_tag('h3').__('Your email').': '.$mail.wf_tag('h3',true),'','','align="center"');
        $rows.=  wf_TableRow($cells); 
        
        $cells=  wf_TableCell(wf_Link("http://gravatar.com/emails/", __('Change my avatar at gravatar.com')),'','','align="center"');
        $rows.=  wf_TableRow($cells); 
        $result=  wf_TableBody($rows, '100%', '0', 'glamour');
        $result.=wf_Link("?module=ubim&checknew=true", __('Back'), false, 'ubButton');
        return ($result);
    } 
    
    /*
     * Check is message created by me?
     * 
     * @param $msgid   message id from `ub_im`
     * 
     * @return bool
     */
    function im_IsMineMessage($msgid) {
        $msgid= vf($msgid,3);
        $me=  whoami();
        $query="SELECT `from` FROM `ub_im` WHERE `id`='".$msgid."'";
        $data=  simple_query($query);
        if (!empty($data)) {
          if ($data['from']==$me) {
              //message created by me
                return (true);
            } else {
              //or not
                return (false);
            }
        } else {
            //message not exists
            return (false);
        }
    }
    
    
    /*
     * mark thread as read by sender
     * 
     * @param $sender   sender login
     * 
     * @return void
     */
    function im_ThreadMarkAsRead($sender) {
        $sender=  mysql_real_escape_string($sender);
        $me=whoami();
        $query="UPDATE `ub_im` SET `read` = '1' WHERE `to` = '".$me."' AND `from`='".$sender."' AND `read`='0'";
        nr_query($query);
    }
    
    /*
     * Return unread messages count for each contact
     * 
     * @param $username framework admin username
     * 
     * @return string
     */
    
    function im_CheckForUnreadMessagesByUser($username){
      $username=  mysql_real_escape_string($username);
      $me=whoami();
      $query="SELECT COUNT(`id`) from `ub_im` WHERE `to`='".$me."' AND `from`='".$username."' AND `read`='0'";
      $data=  simple_query($query);
      $result=$data['COUNT(`id`)'];
      return ($result);
    }   
    
    /*
     * Return contact list 
     * 
     * @return string
     */
    function im_ContactList() {
        $me=  whoami();
        $alladmins=  rcms_scandir(DATA_PATH."users/");
        $result='';
        $rows='';
        if (!empty($alladmins)) {
            foreach ($alladmins as $eachadmin) {
                if ($eachadmin!=$me) {
                    //need checks for unread messages for each user
                    if (wf_CheckGet(array('checknew'))) {
                        $unreadCounter=  im_CheckForUnreadMessagesByUser($eachadmin);
                        if ($unreadCounter!=0) {
                          $blinker=  wf_img('skins/ticketnotify.gif', __('Unread message'));
                        } else {
                          $blinker='';
                        }
                    } else {
                        $blinker='';
                    }
                    $conatactAvatar=gravatar_ShowAdminAvatar($eachadmin,'32').' ';
                    $threadLink=  wf_AjaxLink("?module=ubim&showthread=".$eachadmin, $eachadmin, 'threadContainer', false, 'ubButton');
                    $threadLink.=$blinker;
                    $cells=  wf_TableCell($conatactAvatar,'35','','valign="center" align="left"');
                    $cells.=wf_TableCell($threadLink,'','','valign="center" align="left"');
                    $rows.=wf_TableRow($cells, '');
                }
            }
            $result=  wf_TableBody($rows, '100%', '0', 'glamour');
            $result.=wf_delimiter().wf_Link("?module=ubim&avatarcontrol=true", __('Avatar control'), false, 'ubButton');
        }
        return ($result);
    }
    
    /*
     * Return UB im main window grid
     * 
     * @return string
     */
    
    function im_MainWindow() {
        $contactList=  wf_AjaxLoader();
        $contactList.=  im_ContactList();
        
        $gridcells= wf_TableCell($contactList, '25%', '','valign="top"');
        $threadContainer=  wf_tag('div', false, 'ubimchat', 'id="threadContainer"');
        $threadContainer.=wf_tag('div', true);
        $gridcells.=wf_TableCell($threadContainer, '75%', '','valign="top"');
        $gridrows=  wf_TableRow($gridcells);
        $result=  wf_TableBody($gridrows, '100%', '0');
        return ($result);
    }
    
    
    /*
     * Return conversation form for some thread
     * 
     * @param $to - thread username 
     */
    function im_ConversationForm($to) {
        $inputs=  wf_HiddenInput('im_message_to', $to);
        $inputs.=wf_TextArea('im_message_text', '', '', true, '60x4');
        $inputs.=wf_Submit('Send message');
        $result=  wf_Form("", 'POST', $inputs, 'glamour');
        //scroll down the conversation area - now not work
        //in all browsers - than disabled
//        $result.='
//             <script type="text/javascript">
//                var objDiv = document.getElementById("threadContainer");
//                objDiv.scrollTop = objDiv.scrollHeight;
//            </script>
//            ';
        return ($result);
    }
    
    
    /*
     * Shows thread for me with some user
     * 
     * @param $threadUser  user to show thread
     */
    function im_ThreadShow($threadUser) {
        $me=whoami();
        $threadUser=  mysql_real_escape_string($threadUser);
        $result=__('No conversations with').' '.$threadUser.' '.__('yet').  wf_delimiter();
        $rows='';
        $query="SELECT * from `ub_im` WHERE (`to`='".$me."' AND `from`='".$threadUser."')  OR (`to`='".$threadUser."' AND `from`='".$me."') ORDER BY `date` DESC";
        $alldata=  simple_queryall($query);
        if (!empty($alldata)) {
            foreach ($alldata as $io=>$each) {
                //read icon
                $readIcon=($each['read']=='0') ? wf_img("skins/icon_inactive.gif",__('Unread message')) : '';
                
                $cells=   wf_TableCell(wf_tag('b').$each['from'].  wf_tag('b',true), '20%', '','align="center"');
                $cells.=  wf_TableCell($each['date'].' '.$readIcon, '80%');
                $rows.=   wf_TableRow($cells,'row2');
                //$controls=  wf_delimiter().'controls here';
                $messageText= nl2br($each['text']);
                $cells=   wf_TableCell(gravatar_ShowAdminAvatar($each['from'], '64'), '', 'row3','align="center"');
                $cells.=  wf_TableCell($messageText, '', 'row3');
                $rows.=   wf_TableRow($cells);
            }
           $result=  wf_TableBody($rows, '100%', '0');
        
           //mark all unread messages as read now
           im_ThreadMarkAsRead($threadUser);
        }
        return ($result);
        
    }
/*
 * Loads some thread after message posted into standard grid
 * @param $threadUser  thread username
 * 
 * @return string
 */
    
function im_ThreadLoad($threadUser) {
    $result='
        <script type="text/javascript">
        goajax(\'?module=ubim&showthread='.$threadUser.'\',\'threadContainer\');
        </script>
        ';
    return ($result);
}


/*
 * Checks how many unread messages we have?
 * 
 * @return string
 */
function im_CheckForUnreadMessages() {
    $me=  whoami();
    $result=0;
    $query="SELECT COUNT(`id`) from `ub_im` WHERE `to`='".$me."' AND `read`='0'";
    $data=  simple_query($query);
    if (!empty($data)) {
        $result=$data['COUNT(`id`)'];
    }
    return ($result);
}

/*
 * Draw update container and refresh into in some code
 * 
 * @return void
 */
function im_RefreshContainer($timeout) {
    //  setInterval(function(){ goajax(\'?module=ubim&timecheckunread=true\',\'refreshcontainer\'); },'.$timeout.');
    $timeout=$timeout*1000; 
    $jstimer=  wf_AjaxLoader()."
        <script type=\"text/javascript\">
          
    $(function() {
    var alertedMessagesCount = 0;

 $(window).everyTime(".$timeout.", function() {
  $.ajax({
   url: '?module=ubim&timecheckunread=true',
   dataType: 'json',success: function(data) {
    if(data.messagesCount > 0) {
     if(alertedMessagesCount != data.messagesCount) {
      // You have new message
     // if(!alert.visible) {
     // alert(data.messagesCount+' '+'".__('new message received')."');
     // }
     $(document).ready(function() {
        var position = 'top-right'; 
        var settings = {
                'speed' : 'fast',
                'duplicates' : true,
                'autoclose' : 5000 
        };
	 $.sticky(data.messagesCount+' '+'".__('new message received')."');
     });
      alertedMessagesCount = data.messagesCount;
     }
    }
   }
  });
 });
});

        </script>
        ";
    $container=  wf_tag('span', false, '','id="refreshcontainer"');
    $container.=wf_tag('span',true);
    $container.=$jstimer;
    
    show_window('',$container);
}

?>