<?php

/*
 * Ubilling instant messenger API
 */

/**
 * Creates message for some admin user
 * 
 * @param string $to   admin login
 * @param string $text message text
 * 
 * @return void
 */
function im_CreateMessage($to, $text) {
    $to = mysql_real_escape_string($to);
    $text = mysql_real_escape_string($text);
    $text = strip_tags($text);
    $from = whoami();
    $date = curdatetime();
    $read = 0;

    $query = "INSERT INTO `ub_im` (
                `id` ,
                `date` ,
                `from` ,
                `to` ,
                `text` ,
                `read`
                )
                VALUES (
                NULL , '" . $date . "', '" . $from . "', '" . $to . "', '" . $text . "', '" . $read . "'
                );
                ";
    nr_query($query);
    log_register("UBIM SEND FROM {" . $from . "} TO {" . $to . "}");
}

/**
 * Deletes message by its id
 * 
 * @param int $msgid   message id from `ub_im`
 * 
 * @return void
 */
function im_DeleteMessage($msgid) {
    $msgid = vf($msgid, 3);
    $query = "DELETE from `ub_im` WHERE `id`='" . $msgid . "'";
    nr_query($query);
    log_register("UBIM DELETE [" . $msgid . "]");
}

/**
 * Shows avatar control form
 * 
 * @return string
 */
function im_AvatarControlForm() {
    $me = whoami();
    $mail = gravatar_GetUserEmail($me);

    $cells = wf_TableCell(wf_tag('h1') . $me . wf_tag('h1', true), '', '', 'align="center"');
    $rows = wf_TableRow($cells);
    $cells = wf_TableCell(gravatar_ShowAdminAvatar($me, '256'), '', '', 'align="center"');
    $rows .= wf_TableRow($cells);
    $cells = wf_TableCell(wf_tag('h3') . __('Your email') . ': ' . $mail . wf_tag('h3', true), '', '', 'align="center"');
    $rows .= wf_TableRow($cells);

    $cells = wf_TableCell(wf_Link("http://gravatar.com/emails/", __('Change my avatar at gravatar.com')), '', '', 'align="center"');
    $rows .= wf_TableRow($cells);
    $result = wf_TableBody($rows, '100%', '0', 'glamour');
    $result .= wf_BackLink("?module=ubim&checknew=true", __('Back'), false, 'ubButton');
    return ($result);
}

/**
 * Check is message created by me?
 * 
 * @param int $msgid   message id from `ub_im`
 * 
 * @return bool
 */
function im_IsMineMessage($msgid) {
    $msgid = vf($msgid, 3);
    $me = whoami();
    $query = "SELECT `from` FROM `ub_im` WHERE `id`='" . $msgid . "'";
    $data = simple_query($query);
    if (!empty($data)) {
        if ($data['from'] == $me) {
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

/**
 * mark thread as read by sender
 * 
 * @param string $sender   sender login
 * 
 * @return void
 */
function im_ThreadMarkAsRead($sender) {
    $sender = mysql_real_escape_string($sender);
    $me = whoami();
    $query = "UPDATE `ub_im` SET `read` = '1' WHERE `to` = '" . $me . "' AND `from`='" . $sender . "' AND `read`='0'";
    nr_query($query);
}

/**
 * Return unread messages count for each contact
 * 
 * @param string $username framework admin username
 * 
 * @return string
 */
function im_CheckForUnreadMessagesByUser($username) {
    $username = mysql_real_escape_string($username);
    $me = whoami();
    $query = "SELECT COUNT(`id`) from `ub_im` WHERE `to`='" . $me . "' AND `from`='" . $username . "' AND `read`='0'";
    $data = simple_query($query);
    $result = $data['COUNT(`id`)'];
    return ($result);
}

/**
 * Return contact list 
 * 
 * @return string
 */
function im_ContactList() {
    $me = whoami();
    @$employeeNames = unserialize(ts_GetAllEmployeeLoginsCached());
    $alladmins = rcms_scandir(DATA_PATH . "users/");
    $activeAdmins = im_GetActiveAdmins();
    $result = '';
    $rows = '';
    if (!empty($alladmins)) {
        foreach ($alladmins as $eachadmin) {
            if ($eachadmin != $me) {
                //need checks for unread messages for each user
                $contactClass = 'ubimcontact';
                if (wf_CheckGet(array('checknew'))) {
                    $unreadCounter = im_CheckForUnreadMessagesByUser($eachadmin);
                    if ($unreadCounter != 0) {
                        $contactClass = 'ubimcontactincome';
                    }
                }

                if (isset($activeAdmins[$eachadmin])) {
                    $aliveFlag = web_bool_led(true);
                } else {
                    $aliveFlag = web_bool_led(false);
                }

                $conatactAvatar = gravatar_ShowAdminAvatar($eachadmin, '32') . ' ';
                $adminName = (isset($employeeNames[$eachadmin])) ? $employeeNames[$eachadmin] : $eachadmin;
                $threadLink = wf_AjaxLink("?module=ubim&showthread=" . $eachadmin, $adminName, 'threadContainer', false, $contactClass);

                $cells = wf_TableCell($aliveFlag, '', '', 'valign="center" align="center"');
                $cells .= wf_TableCell($conatactAvatar, '35', '', 'valign="center" align="left"');
                $cells .= wf_TableCell($threadLink, '', '', 'valign="center" align="left"');
                $rows .= wf_TableRow($cells, '');
            }
        }
        $result = wf_TableBody($rows, '100%', '0', 'glamour');
        
        $myAva = gravatar_ShowAdminAvatar($me, '16');
        $result.= wf_CleanDiv();
        $result .= wf_delimiter() . wf_Link("?module=ubim&avatarcontrol=true", $myAva . ' ' . __('Avatar control'), false, 'ubButton');
    }
    return ($result);
}

/**
 * Return UB im main window grid
 * 
 * @return string
 */
function im_MainWindow() {
    $contactList = wf_AjaxLoader();
    $contactList .= im_ContactList();

    $gridcells = wf_TableCell($contactList, '25%', '', 'valign="top"');
    $threadContainer = wf_tag('div', false, 'ubimchat', 'id="threadContainer"');
    $threadContainer .= wf_tag('div', true);
    $gridcells .= wf_TableCell($threadContainer, '75%', '', 'valign="top"');
    $gridrows = wf_TableRow($gridcells);
    $result = wf_TableBody($gridrows, '100%', '0');
    return ($result);
}

/**
 * Return conversation form for some thread
 * 
 * @param string $to - thread username 
 * 
 * @return string
 */
function im_ConversationForm($to) {
    $inputs = wf_HiddenInput('im_message_to', $to);
    $inputs .= wf_TextArea('im_message_text', '', '', true, '60x4');
    $inputs .= wf_Submit('Send message');
    $result = wf_Form("", 'POST', $inputs, 'glamour');
    return ($result);
}

/**
 * Shows thread for me with some user
 * 
 * @param string $threadUser  user to show thread
 * 
 * @return string
 */
function im_ThreadShow($threadUser) {
    global $ubillingConfig;
    $altCfg = $ubillingConfig->getAlter();
    $me = whoami();
    @$employeeNames = unserialize(ts_GetAllEmployeeLoginsCached());
    $threadUser = mysql_real_escape_string($threadUser);
    $adminName = (isset($employeeNames[$threadUser])) ? $employeeNames[$threadUser] : $threadUser;
    $result = __('No conversations with') . ' ' . $adminName . ' ' . __('yet') . wf_delimiter();
    $rows = '';
    $query = "SELECT * from `ub_im` WHERE (`to`='" . $me . "' AND `from`='" . $threadUser . "')  OR (`to`='" . $threadUser . "' AND `from`='" . $me . "') ORDER BY `date` DESC";
    $alldata = simple_queryall($query);
    if (!empty($alldata)) {
        foreach ($alldata as $io => $each) {
            //read icon
            $readIcon = ($each['read'] == '0') ? wf_img("skins/icon_inactive.gif", __('Unread message')) : '';
            $fromName = (isset($employeeNames[$each['from']])) ? $employeeNames[$each['from']] : $each['from'];
            $cells = wf_TableCell(wf_tag('b') . $fromName . wf_tag('b', true), '20%', '', 'align="center"');
            $cells .= wf_TableCell($each['date'] . ' ' . $readIcon, '80%');
            $rows .= wf_TableRow($cells, 'row2');

            $messageText = nl2br($each['text']);
            if (!isset($altCfg['UBIM_NO_LINKIFY'])) {
                $messageText = im_linkify($messageText);
            } else {
                if (!$altCfg['UBIM_NO_LINKIFY']) {
                    $messageText = im_linkify($messageText);
                }
            }
            $cells = wf_TableCell(gravatar_ShowAdminAvatar($each['from'], '64'), '', 'row3', 'align="center"');
            $cells .= wf_TableCell($messageText, '', 'row3');
            $rows .= wf_TableRow($cells);
        }
        $result = wf_TableBody($rows, '100%', '0');

        //mark all unread messages as read now
        im_ThreadMarkAsRead($threadUser);
    }
    return ($result);
}

/**
 * Loads some thread after message posted into standard grid
 * @param string $threadUser  thread username
 * 
 * @return string
 */
function im_ThreadLoad($threadUser) {
    $result = wf_tag('script', false, '', 'type="text/javascript"');
    $result .= 'goajax(\'?module=ubim&showthread=' . $threadUser . '\',\'threadContainer\');';
    $result .= wf_tag('script', true);
    return ($result);
}

/**
 * Checks how many unread messages we have?
 * 
 * @return string
 */
function im_CheckForUnreadMessages() {
    $me = whoami();
    $result = 0;
    $query = "SELECT COUNT(`id`) from `ub_im` WHERE `to`='" . $me . "' AND `read`='0'";
    $data = simple_query($query);
    if (!empty($data)) {
        $result = $data['COUNT(`id`)'];
    }
    return ($result);
}

/**
 * Draw update container and refresh into in some code
 * 
 * @return void
 */
function im_RefreshContainer($timeout) {
    //  setInterval(function(){ goajax(\'?module=ubim&timecheckunread=true\',\'refreshcontainer\'); },'.$timeout.');
    $timeout = $timeout * 1000;
    $jstimer = wf_AjaxLoader() . "
        <script type=\"text/javascript\">
          
    $(function() {
    var alertedMessagesCount = 0;

 $(window).everyTime(" . $timeout . ", function() {
  $.ajax({
   url: '?module=ubim&timecheckunread=true',
   dataType: 'json',success: function(data) {
    if(data.messagesCount > 0) {
     if(alertedMessagesCount != data.messagesCount) {
      // You have new message
     // if(!alert.visible) {
     // alert(data.messagesCount+' '+'" . __('new message received') . "');
     // }
     $(document).ready(function() {
        var position = 'top-right'; 
        var settings = {
                'speed' : 'fast',
                'duplicates' : true,
                'autoclose' : 5000 
        };
	 $.sticky(data.messagesCount+' '+'" . __('new message received') . "');
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
    $container = wf_tag('span', false, '', 'id="refreshcontainer"');
    $container .= wf_tag('span', true);
    $container .= $jstimer;

    show_window('', $container);
}

/**
 * Returns array of "active" administrators
 * 
 * @return array
 */
function im_GetActiveAdmins() {
    $result = array();
    $timeout = 10;
    $query = "SELECT DISTINCT `admin` from `weblogs` WHERE `date` > DATE_SUB(NOW(), INTERVAL " . $timeout . " MINUTE);";
    $all = simple_queryall($query);
    if (!empty($all)) {
        foreach ($all as $io => $each) {
            $result[$each['admin']] = $each['admin'];
        }
    }
    return ($result);
}

/**
 * Turn all URLs in clickable links.
 *
 * @param string $value
 * @param array $protocols http/https, ftp, mail, twitter
 * @param array $attributes
 * @param string $mode normal or all
 * @return string
 */
function im_linkify($value, $protocols = array('http', 'mail'), array $attributes = array(), $mode = 'normal') {
    // Link attributes
    $attr = '';
    foreach ($attributes as $key => $val) {
        $attr = ' ' . $key . '="' . htmlentities($val) . '"';
    }

    $links = array();

    // Extract existing links and tags
    $value = preg_replace_callback('~(<a .*?>.*?</a>|<.*?>)~i', function ($match) use (&$links) {
        return '<' . array_push($links, $match[1]) . '>';
    }, $value);

    // Extract text links for each protocol
    foreach ((array) $protocols as $protocol) {
        switch ($protocol) {
            case 'http':
            case 'https': $value = preg_replace_callback($mode != 'all' ? '~(?:(https?)://([^\s<]+)|(www\.[^\s<]+?\.[^\s<]+))(?<![\.,:])~i' : '~([^\s<]+\.[^\s<]+)(?<![\.,:])~i', function ($match) use ($protocol, &$links, $attr) {
                    if ($match[1])
                        $protocol = $match[1];
                    $link = $match[2] ?: $match[3];
                    return '<' . array_push($links, '<a' . $attr . ' href="' . $protocol . '://' . $link . '">' . $link . '</a>') . '>';
                }, $value);
                break;
            case 'mail': $value = preg_replace_callback('~([^\s<]+?@[^\s<]+?\.[^\s<]+)(?<![\.,:])~', function ($match) use (&$links, $attr) {
                    return '<' . array_push($links, '<a' . $attr . ' href="mailto:' . $match[1] . '">' . $match[1] . '</a>') . '>';
                }, $value);
                break;
            case 'twitter': $value = preg_replace_callback('~(?<!\w)[@#](\w++)~', function ($match) use (&$links, $attr) {
                    return '<' . array_push($links, '<a' . $attr . ' href="https://twitter.com/' . ($match[0][0] == '@' ? '' : 'search/%23') . $match[1] . '">' . $match[0] . '</a>') . '>';
                }, $value);
                break;
            default: $value = preg_replace_callback($mode != 'all' ? '~' . preg_quote($protocol, '~') . '://([^\s<]+?)(?<![\.,:])~i' : '~([^\s<]+)(?<![\.,:])~i', function ($match) use ($protocol, &$links, $attr) {
                    return '<' . array_push($links, '<a' . $attr . ' href="' . $protocol . '://' . $match[1] . '">' . $match[1] . '</a>') . '>';
                }, $value);
                break;
        }
    }

    // Insert all link
    return preg_replace_callback('/<(\d+)>/', function ($match) use (&$links) {
        return $links[$match[1] - 1];
    }, $value);
}

?>