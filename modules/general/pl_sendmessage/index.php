<?php
if (cfr('PLSENDMESSAGE')) {
    
    function web_MessageSendForm() {
        $form='
            <form action="" method="POST">
             <textarea name="messagetext" cols="60" rows="10"></textarea> <br>
             <input type="submit" value="'.__('Send').'">
            </form>
            ';
        show_window(__('Send message'), $form);
    }
    
    
    function web_MessagesShowPrevious($login) {
        $login=loginDB_real_escape_string($login);
        $query="SELECT * from `ticketing` WHERE `to`='".$login."' AND `from`='NULL' AND `status`='1' ORDER BY `date` DESC";
        $allmessages=simple_queryall($query);
        $result='<table width="100%" class="sortable">';
          $result.='
                    <tr class="row1">
                    <td>'.__('Date').'</td>
                    <td>'.__('Text').'</td>
                    </tr>
                    ';
        if (!empty ($allmessages)) {
            foreach ($allmessages as $io=>$eachmessage) {
                $result.='
                    <tr class="row3">
                    <td>'.$eachmessage['date'].'</td>
                    <td>'.$eachmessage['text'].'</td>
                    </tr>
                    ';
            }
        }
        $result.='</table>';
        
        show_window(__('Previous messages'),$result);
    }
  
    if (isset($_GET['username'])) {
        $login=$_GET['username'];
        web_MessagesShowPrevious($login);
        
        if (isset ($_POST['messagetext'])) {
            zb_TicketCreate('NULL', $login, $_POST['messagetext'],'NULL',whoami());
            $newid=simple_get_lastid('ticketing');
            zb_TicketSetDone($newid);
            rcms_redirect("?module=pl_sendmessage&username=".$login);
            
        }
        web_MessageSendForm();
        show_window('', web_UserControls($login));
        
    }

} else {
      show_error(__('You cant control this module'));
}

?>
