<?php

////////////////////////////////////////////////////////////////////////////////
//   Copyright (C) ReloadCMS Development Team                                 //
//   http://reloadcms.sf.net                                                  //
//                                                                            //
//   This program is distributed in the hope that it will be useful,          //
//   but WITHOUT ANY WARRANTY, without even the implied warranty of           //
//   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.                     //
//                                                                            //
//   This product released under GNU General Public License v2                //
////////////////////////////////////////////////////////////////////////////////

/**
 * Misc ReloadCMS packages
 *
 * @author DruidVAV
 * @package ReloadCMS
 */

/**
 * Function recursively check if $needle is present in $haystack
 *
 * @param mixed $needle
 * @param array $haystack
 * @return boolean
 */
function rcms_in_array_recursive($needle, $haystack) {
    foreach ($haystack as $value) {
        if (is_array($value))
            return rcms_in_array_recursive($needle, $value);
        else
            return in_array($needle, $haystack);
    }
}

function in_array_i($needle, $haystack) {
    $needle = strtolower(htmlentities($needle));
    if (!is_array($haystack))
        return false;
    foreach ($haystack as $value) {
        $value = strtolower(htmlentities($value));
        if ($needle == $value)
            return true;
    }
    return false;
}

function rcms_htmlspecialchars_recursive($array) {
    foreach ($array as $key => $value) {
        if (is_array($value))
            $array[$key] = rcms_htmlspecialchars_recursive($value);
        else
            $array[$key] = htmlspecialchars($value);
    }
    return $array;
}

/**
 * Recursively stripslashes array.
 *
 * @param array $array
 * @return boolean
 */
function stripslash_array(&$array) {
    foreach ($array as $key => $value) {
        if (is_array($array[$key]))
            stripslash_array($array[$key]);
        else
            $array[$key] = stripslashes($value);
    }
    return true;
}

/**
 * Shows redirection javascript.
 *
 * @param string $url
 */
function rcms_redirect($url, $header = false) {
    if ($header) {
        @header('Location: ' . $url);
    } else {
        print('<script language="javascript">document.location.href="' . $url . '";</script>');
    }
}

/**
 * Sends e-mail.
 *
 * @param string $to
 * @param string $from
 * @param string $sender
 * @param string $encoding
 * @param string $subj
 * @param string $text
 * @return boolean
 */
function rcms_send_mail($to, $from, $sender, $encoding, $subj, $text) {
    $headers = 'From: ' . $sender . ' <' . $from . ">\n";
    $headers .= "MIME-Version: 1.0\n";
    $headers .= 'Message-ID: <' . md5(uniqid(time())) . "@" . $sender . ">\n";
    $headers .= 'Date: ' . gmdate('D, d M Y H:i:s T', time()) . "\n";
    $headers .= "Content-type: text/plain; charset={$encoding}\n";
    $headers .= "Content-transfer-encoding: 8bit\n";
    $headers .= "X-Mailer: ReloadCMS\n";
    $headers .= "X-MimeOLE: ReloadCMS\n";
    return mail($to, $subj, $text, $headers);
}

/**
 * Returns random string with selected length
 *
 * @param integer $num_chars
 * @return string
 */
function rcms_random_string($num_chars) {
    $chars = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z', '1', '2', '3', '4', '5', '6', '7', '8', '9');

    list($usec, $sec) = explode(' ', microtime());
    mt_srand($sec * $usec);

    $max_chars = sizeof($chars) - 1;
    $rand_str = '';
    for ($i = 0; $i < $num_chars; $i++) {
        $rand_str .= $chars[mt_rand(0, $max_chars)];
    }

    return $rand_str;
}

/**
 * Just returns current time 
 *
 * @return integer
 */
function rcms_get_time() {
    return mktime();
}

/**
 * Function that formats date. Similar to date() function but
 * uses timezone and returns localised string
 *
 * @param string $format
 * @param integer $gmepoch
 * @param integer $tz
 * @return string
 */
function rcms_format_time($format, $gmepoch, $tz = '') {
    global $lang, $system;

    if (empty($tz))
        $tz = $system->user['tz'];

    if ($system->language != 'english') {
        @reset($lang['datetime']);
        while (list($match, $replace) = @each($lang['datetime'])) {
            $translate[$match] = $replace;
        }
    }
    return (!empty($translate) ) ? strtr(@gmdate($format, $gmepoch + (3600 * $tz)), $translate) : @gmdate($format, $gmepoch + (3600 * $tz));
}

/**
 * Return localised date from string generated by date()
 *
 * @param string $string
 * @return string
 */
function rcms_date_localise($string) {
    global $lang, $system;

    if ($system->language != 'english') {
        @reset($lang['datetime']);
        while (list($match, $replace) = @each($lang['datetime'])) {
            $translate[$match] = $replace;
        }
    }
    return (!empty($translate) ) ? strtr($string, $translate) : $string;
}

function rcms_parse_text_by_mode($str, $mode) {
    switch ($mode) {
        default:
        case 'check':
            return rcms_parse_text($str, false, false, false, false, false, false);
            break;
        case 'text':
            return rcms_parse_text($str, true, false, true, false, true, true);
            break;
        case 'text-safe':
            return rcms_parse_text($str, true, false, true, false, false, false);
            break;
        case 'html':
            return rcms_parse_text($str, false, true, false, false, true, true);
            break;
        case 'htmlbb':
            return rcms_parse_text($str, true, true, false, false, true, true);
            break;
    }
}

/**
 * Just a stub for backward compatibility.
 *
 * @param string $str
 * @param boolean $bbcode
 * @param boolean $html
 * @param boolean $nl2br
 * @param boolean $wordwrap
 * @param boolean $imgbbcode
 * @return string
 */
function rcms_parse_text($str, $bbcode = true, $html = false, $nl2br = true, $wordwrap = false, $imgbbcode = false, $htmlbbcode = false) {
    $level = intval($bbcode);
    if ($imgbbcode && $bbcode && $level < 2)
        $level = 2;
    if ($htmlbbcode && $bbcode && $level < 3)
        $level = 3;

    $message = new message($str, $level, $html, $nl2br);
    $message->init_bbcodes();
    $message->parse();
    if ($wordwrap) {
        return '<div style="overflow: auto;">' . $message->str . '</div>';
    } else {
        return $message->str;
    }
}

/**
 * Validates e-mail
 *
 * @param string $text
 * @return boolean
 */
function rcms_is_valid_email($text) {
    if (preg_match('/^(?!(?:(?:\x22?\x5C[\x00-\x7E]\x22?)|(?:\x22?[^\x5C\x22]\x22?)){255,})(?!(?:(?:\x22?\x5C[\x00-\x7E]\x22?)|(?:\x22?[^\x5C\x22]\x22?)){65,}@)(?:(?:[\x21\x23-\x27\x2A\x2B\x2D\x2F-\x39\x3D\x3F\x5E-\x7E]+)|(?:\x22(?:[\x01-\x08\x0B\x0C\x0E-\x1F\x21\x23-\x5B\x5D-\x7F]|(?:\x5C[\x00-\x7F]))*\x22))(?:\.(?:(?:[\x21\x23-\x27\x2A\x2B\x2D\x2F-\x39\x3D\x3F\x5E-\x7E]+)|(?:\x22(?:[\x01-\x08\x0B\x0C\x0E-\x1F\x21\x23-\x5B\x5D-\x7F]|(?:\x5C[\x00-\x7F]))*\x22)))*@(?:(?:(?!.*[^.]{64,})(?:(?:(?:xn--)?[a-z0-9]+(?:-[a-z0-9]+)*\.){1,126}){1,}(?:(?:[a-z][a-z0-9]*)|(?:(?:xn--)[a-z0-9]+))(?:-[a-z0-9]+)*)|(?:\[(?:(?:IPv6:(?:(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){7})|(?:(?!(?:.*[a-f0-9][:\]]){7,})(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){0,5})?::(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){0,5})?)))|(?:(?:IPv6:(?:(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){5}:)|(?:(?!(?:.*[a-f0-9]:){5,})(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){0,3})?::(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){0,3}:)?)))?(?:(?:25[0-5])|(?:2[0-4][0-9])|(?:1[0-9]{2})|(?:[1-9]?[0-9]))(?:\.(?:(?:25[0-5])|(?:2[0-4][0-9])|(?:1[0-9]{2})|(?:[1-9]?[0-9]))){3}))\]))$/iD', $text))
        return true;
    else
        return false;
}

/**
 * Returns bbcode panel code for selected textarea
 *
 * @param string $textarea
 * @return string
 */
function rcms_show_bbcode_panel($textarea) {
    return rcms_parse_module_template('bbcodes-panel.tpl', array('textarea' => $textarea));
}

function get_animated_to_array() {
    $arr = rcms_scandir(SMILES_PATH);
    $arr2 = array();
    foreach ($arr as $key) {
        if (file_exists(SMILES_PATH . basename($key, ".gif") . ".gif")) {
            $arr2['#\[' . basename($key, ".gif") . '\]#is'] = '<img src="' . SMILES_PATH . $key . '" alt = "' . basename($key, ".gif") . '">';
        }
    }
    return $arr2;
}

function return_hidden_bb_text() {
    if (LOGGED_IN) {
        return '<div class="hidden">\\1</div>';
    } else {
        return '<div class="hidden">' . __('This block only for registered users') . '</div>';
    }
}

/**
 * Message parser class
 *
 * @package ReloadCMS
 */
class message {

    /**
     * Message container
     *
     * @var string
     */
    var $str = '';

    /**
     * Level of bbcode security.
     * 
     * @var integer
     */
    var $bbcode_level = 0; // 0 - no bbcode, 1 - save bbcodes, 2 - all bbcodes
    /**
     * Allow HTML in message
     *
     * @var boolean
     */
    var $html = false;

    /**
     * Perform nl2br in message
     *
     * @var boolean
     */
    var $nl2br = false;

    /**
     * Array of regexps for bbcodes
     *
     * @var array
     */
    var $regexp = array();
    var $sr_temp = array();

    /**
     * Class constructor
     *
     * @param string $message
     * @param integer $bbcode_level
     * @param boolean $html
     * @param boolean $nl2br
     * @return message
     */
    public function __construct($message, $bbcode_level = 0, $html = false, $nl2br = false) {
        $this->str = $message;
        $this->nl2br = $nl2br;
        $this->bbcode_level = $bbcode_level;
        $this->html = $html;
    }

    /**
     * BBCodes initialisation. Filling in message::regexp array
     *
     */
    function init_bbcodes() {
        $this->regexp[0] = array();
        $this->regexp[1] = array(
            "#\[b\](.*?)\[/b\]#is" => '<span style="font-weight: bold">\\1</span>',
            "#\[i\](.*?)\[/i\]#is" => '<span style="font-style: italic">\\1</span>',
            "#\[u\](.*?)\[/u\]#is" => '<span style="text-decoration: underline">\\1</span>',
            "#\[del\](.*?)\[/del\]#is" => '<span style="text-decoration: line-through">\\1</span>',
            "#\[url\][\s\n\r]*(((https?|ftp|ed2k|irc)://)[^ \"\n\r\t\<]*)[\s\n\r]*\[/url\]#is" => '<a href="\\1" target="_blank">\\1</a>',
            "#\[url\][\s\n\r]*(www\.[^ \"\n\r\t\<]*?)[\s\n\r]*\[/url\]#is" => '<a href="http://\\1" target="_blank">\\1</a>',
            "#\[url\][\s\n\r]*((ftp)\.[^ \"\n\r\t\<]*?)[\s\n\r]*\[/url\]#is" => '<a href="\\2://\\1" target="_blank">\\1</a>',
            "#\[url=(\"|&quot;|)(((https?|ftp|ed2k|irc)://)[^ \"\n\r\t\<]*?)(\"|&quot;|)\](.*?)\[/url\]#is" => '<a href="\\2" target="_blank">\\6</a>',
            "#\[url=(\"|&quot;|)(www\.[^ \"\n\r\t\<]*?)(\"|&quot;|)\](.*?)\[/url\]#is" => '<a href="http://\\2" target="_blank">\\4</a>',
            "#\[url=(\"|&quot;|)((ftp)\.[^ \"\n\r\t\<]*?)(\"|&quot;|)\](.*?)\[/url\]#is" => '<a href="\\3://\\2" target="_blank">\\5</a>',
            "#\[mailto\][\s\n\r]*([a-z0-9&\-_.]+?@[\w\-]+\.([\w\-\.]+\.)?[\w]+)[\s\n\r]*\[/mailto\]#is" => '<a href="mailto:\\1">\\1</a>',
            "#\[mailto=(\"|&quot;|)([a-z0-9&\-_.]+?@[\w\-]+\.([\w\-\.]+\.)?[\w]+)(\"|&quot;|)\](.*?)\[/mailto\]#is" => '<a href="mailto:\\2">\\5</a>',
            "#\[color=(\"|&quot;|)([\#\w]*)(\"|&quot;|)\](.*?)\[/color(.*?)\]#is" => '<span style="color:\\2">\\4</span>',
            "#\[size=(\"|&quot;|)([0-9]*)(\"|&quot;|)\](.*?)\[/size(.*?)\]#is" => '<span style="font-size: \\2pt">\\4</span>',
            "#\[align=(\"|&quot;|)(left|right|center|justify)(\"|&quot;|)\](.*?)\[/align(.*?)\]#is" => '<div style="text-align: \\2">\\4</span>',
            "#\[user\]([\d\w]*?)\[/user\]#is" => ' [ <a href="' . RCMS_ROOT_PATH . '?module=user.list&amp;user=\\1">\\1</a> ] ',
            "#\[user=([\d\w]*?)\](.*?)\[/user\]#is" => ' [ <a href="' . RCMS_ROOT_PATH . '?module=user.list&amp;user=\\1">\\2</a> ] ',
            "#\[hidden\](.*?)\[/hidden\]#is" => return_hidden_bb_text()
        );

        $this->regexp[1] = array_merge(get_animated_to_array(), $this->regexp[1]);
        $this->regexp[2] = array(
            "#[\s\n\r]*\[img\][\s\n\r]*([\w]+?://[^ \"\n\r\t<]*?)\.(gif|png|jpe?g)[\s\n\r]*\[/img\][\s\n\r]*#is" => '<br /><img src="\\1.\\2" alt="" /><br />',
            "#[\s\n\r]*\[img=(\"|&quot;|)(left|right)(\"|&quot;|)\][\s\n\r]*([\w]+?://[^ \"\n\r\t<]*?)\.(gif|png|jpe?g)[\s\n\r]*\[/img\][\s\n\r]*#is" => '<img src="\\4.\\5" alt="" align="\\2" />',
        );
    }

    /**
     * Main parse method. Parses message::str
     *
     */
    function parse() {
        $old = $this->str;
        if (!$this->html)
            $this->str = htmlspecialchars($this->str);
        if (!empty($this->bbcode_level)) {
            $this->str = preg_replace(array_keys($this->regexp[0]), array_values($this->regexp[0]), ' ' . $this->str . ' ');
            if ($this->bbcode_level > 0) {
                $this->parseCodeTag();
                $this->parseQuoteTag();
                $this->str = preg_replace_callback("#\[spoiler(=(\"|&quot;|)(.*?)(\"|&quot;|)|)\](.*?)\[/spoiler\]#is", 'rcms_spoiler_tag', $this->str);
                $this->str = preg_replace(array_keys($this->regexp[1]), array_values($this->regexp[1]), ' ' . $this->str . ' ');
            }
            if ($this->bbcode_level > 1) {
                $this->str = preg_replace(array_keys($this->regexp[2]), array_values($this->regexp[2]), ' ' . $this->str . ' ');
            }
            if ($this->bbcode_level > 2) {
                $this->str = preg_replace_callback("#\[html\](.*?)\[/html\]#is", 'rcms_html_tag', $this->str);
            }
            if ($this->nl2br) {
                $this->str = nl2br($this->str);
            }
            $this->parseUrls();
        }
        $this->str = str_replace(array_keys($this->sr_temp), array_values($this->sr_temp), $this->str);
        $this->result = $this->str;
        $this->str = $old;
    }

    /**
     * Parses message::str [qoute|quote="Who"]..[/qoute] bbtag
     *
     */
    function parseQuoteTag() {
        $this->str = preg_replace("#[\s\n\r]*\[quote\][\s\n\r]*(.*?)[\s\n\r]*\[/quote\][\s\n\r]*#is", '<div class="codetitle"><b>' . __('Quote') . ':</b></div><div class="codetext">\\1</div>', $this->str);
        $this->str = preg_replace("#[\s\n\r]*\[quote=(\"|&quot;|)(.*?)(\"|&quot;|)\][\s\n\r]*(.*?)[\s\n\r]*\[/quote\][\s\n\r]*#is", '<div class="codetitle"><b>\\2 ' . __('wrote') . ':</b></div><div class="codetext">\\4</div>', $this->str);
    }

    /**
     * Parses message::str [code]..[/code] bbtag
     *
     */
    function parseCodeTag() {
        preg_match_all("#[\s\n\r]*\[code\][\s\n\r]*(.*?)[\s\n\r]*\[/code\][\s\n\r]*#is", $this->str, $matches);
        foreach ($matches[1] as $oldpart) {
            $newpart = preg_replace("#[\n\r]+#", '', highlight_string(strtr($oldpart, array_flip(get_html_translation_table(HTML_SPECIALCHARS))), true));
            $newpart = preg_replace(array('#\[#', '#\]#'), array('&#91;', '&#93;'), $newpart);
            $tmp = '{SR:' . rcms_random_string(6) . '}';
            $this->sr_temp[$tmp] = $newpart;
            $this->str = str_replace($oldpart, $tmp, $this->str);
        }
        $this->str = preg_replace("#[\s\n\r]*\[code\][\s\n\r]*(.*?)[\s\n\r]*\[/code\][\s\n\r]*#is", '<div class="codetitle"><b>' . __('Code') . ':</b></div><div class="codetext" style="overflow: auto; white-space: nowrap;">\\1</div>', $this->str);
    }

    function parseUrls() {
        $this->str = $this->highlightUrls($this->str);
        return true;
    }

    function highlightUrls($string) {
        $string = ' ' . $string;
        $string = preg_replace_callback("#(^|[\n\s\r])((https?|ftp|ed2k|irc)://[^ \"\n\r\t<]*)#is", 'rcms_prc_link', $string);
        $string = preg_replace_callback("#(^|[\n\s\r])((www|ftp)\.[^ \"\t\n\r<]*)#is", 'rcms_prc_link_short', $string);
        $string = preg_replace_callback("#(^|[\n\s\r])([a-z0-9&\-_.]+?)@([\w\-]+\.([\w\-\.]+\.)*[\w]+)#i", 'rcms_prc_mail', $string);
        $string = substr($string, 1);
        return $string;
    }

}

/**
 * Callback for link replacement
 *
 * @param array $matches
 * @return string
 */
function rcms_prc_link($matches) {
    if (strlen($matches[2]) > 25) {
        return ' <a href="' . $matches[2] . '" target="_blank">' . substr($matches[2], 0, 11) . '...' . substr($matches[2], -11) . '</a>';
    } else
        return ' <a href="' . $matches[2] . '" target="_blank">' . $matches[2] . '</a>';
}

/**
 * Callback for short link replacement
 *
 * @param array $matches
 * @return string
 */
function rcms_prc_link_short($matches) {
    if (strlen($matches[2]) > 25) {
        return ' <a href="http://' . $matches[2] . '" target="_blank">' . substr($matches[2], 0, 11) . '...' . substr($matches[2], -11) . '</a>';
    } else
        return ' <a href="http://' . $matches[2] . '" target="_blank">' . $matches[2] . '</a>';
}

/**
 * Callback for e-mail replacement
 *
 * @param array $matches
 * @return string
 */
function rcms_prc_mail($matches) {
    if (strlen($matches[2]) > 25) {
        return ' <a href="mailto:' . $matches[2] . '@' . $matches[3] . '" target="_blank">' . substr($matches[2], 0, 11) . '...' . substr($matches[2], -11) . '</a>';
    } else
        return ' <a href="mailto:' . $matches[2] . '@' . $matches[3] . '" target="_blank">' . $matches[2] . '</a>';
}

function rcms_spoiler_tag($matches) {
    $id1 = rcms_random_string('6');
    $id2 = rcms_random_string('6');
    if (!empty($matches[3]))
        $title = __('Spoiler') . ': ' . $matches[3];
    else
        $title = __('Spoiler') . ' (' . __('click to view') . ')';
    return '<div id="' . $id1 . '" class="codetitle"><a onClick="javascript:document.getElementById(\'' . $id2 . '\').style.display=\'block\';">' . $title . '</a></div><div id="' . $id2 . '" style="display: none;" class="codetext">' . $matches[5] . '</div>';
}

function rcms_html_tag($matches) {
    return str_replace(array('[', ']'), array('&#91', '&#93'), strtr($matches[1], array_flip(get_html_translation_table(HTML_SPECIALCHARS))));
}

function rcms_remove_index($key, &$array, $preserve_keys = false) {
    $temp_array = $array;
    $array = array();
    foreach ($temp_array as $ckey => $value) {
        if ($key != $ckey) {
            if ($preserve_keys) {
                $array[$ckey] = $value;
            } else {
                $array[] = $value;
            }
        }
    }
}

?>