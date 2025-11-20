<?php

/**
 *  Return web form element id
 * 
 *  @return  string
 */
function la_InputId() {
    // I know it looks really funny. 
    // You can also get a truly random values by throwing dice ;)
    $characters = '0123456789abcdefghijklmnopqrstuvwxyz';
    $result = "";
    for ($p = 0; $p < 8; $p++) {
        $result .= $characters[mt_rand(0, (strlen($characters) - 1))];
    }
    return ($result);
}

/**
 * Return web form body
 *
 * @param string $action action URL
 * @param string $method method: POST or GET
 * @param string $inputs inputs string to include
 * @param string $class  class for form
 * @param string $legend form legend
 * @param bool   $cleanStyle clean css style
 * @param string $options some inline form options
 * 
 * @return  string
 */
function la_Form($action, $method, $inputs, $class = '', $legend = '', $cleanStyle = true, $options = '') {
    if ($class != '') {
        $form_class = ' class="' . $class . '" ';
    } else {
        $form_class = '';
    }
    if ($legend != '') {
        $form_legend = '<legend>' . __($legend) . '</legend> <br>';
    } else {
        $form_legend = '';
    }

    if ($cleanStyle) {
        $cleanDiv = '<div style="clear:both;"></div>';
    } else {
        $cleanDiv = '';
    }

    $form = '
        <form action="' . $action . '" method="' . $method . '" ' . $form_class . ' ' . $options . '>
         ' . $form_legend . '
        ' . $inputs . '
        </form>
        ' . $cleanDiv . '
        ';
    return ($form);
}

/**
 * Return text input Web From element 
 *
 * @param  string $name name of element
 * @param  string $label text label for input
 * @param  string $value current value
 * @param  bool   $br append new line
 * @param  string $size input size
 * @param  string $pattern input check pattern. Avaible: geo, mobile, finance, ip, net-cidr, digits, email, alpha, alphanumeric,mac
 * @param  string $class class of the element
 * @param  string $ctrlID id of the element
 * @param  string $options
 *
 * @return string
 *
 */
function la_TextInput($name, $label = '', $value = '', $br = false, $size = '', $pattern = '', $class = '', $ctrlID = '', $options = '') {
    $inputid = ( empty($ctrlID) ) ? la_InputId() : $ctrlID;
    $opts = ( empty($options) ) ? '' : $options;

    //set size
    if ($size != '') {
        $input_size = 'size="' . $size . '"';
    } else {
        $input_size = '';
    }
    if ($br) {
        $newline = '<br>';
    } else {
        $newline = '';
    }
      // We will verify that we correctly enter data by input type
      $pattern = ($pattern == 'alpha') ? 'pattern="[a-zA-Z]+" placeholder="aZ" title="' . __('This field can only contain Latin letters') . '"' : $pattern;
      $pattern = ($pattern == 'alphanumeric') ? 'pattern="[a-zA-Z0-9]+" placeholder="aZ09" title="' . __('This field can only contain Latin letters and numbers') . '"' : $pattern;
      $pattern = ($pattern == 'digits') ? 'pattern="^\d+$" placeholder="0" title="' . __('This field can only contain digits') . '"' : $pattern;
      $pattern = ($pattern == 'finance') ? 'pattern="\d+(\.\d+)?" placeholder="0(.00)" title="' . __('The financial input format can be') . ': 1 ; 4.01 ; 2 ; 0.001"' : $pattern;
      $pattern = ($pattern == 'float') ? 'pattern="\d+(\.\d+)?" placeholder="0.00" title="' . __('This field can only contain digits') . ': 1 ; 4.01 ; 2 ; 0.001"' : $pattern;
      $pattern = ($pattern == 'sigint') ? 'pattern="^-?\d+$" placeholder="0" title="' . __('This field can only contain digits') . ' ' . __('and') . ' - "' : $pattern;
      // For this pattern IP adress also can be 0.0.0.0
      $pattern = ($pattern == 'ip') ? 'pattern="^(([0-9]|[1-9][0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5])\.){3}([0-9]|[1-9][0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5])$" placeholder="0.0.0.0" title="' . __('The IP address format can be') . ': 192.1.1.1"' : $pattern;
      // For this pattern exclude cidr /31
      $pattern = ($pattern == 'net-cidr') ? 'pattern="^(([0-9]|[1-9][0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5])\.){3}([0-9]|[1-9][0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5])\/([0-9]|[1-2][0-9]|30|32)$" placeholder="0.0.0.0/0" title="' . __('The format of IP address with mask can be') . ': 192.1.1.1/32 ' . __('and the mask can not be /31') . '"' : $pattern;
      $pattern = ($pattern == 'email') ? 'pattern="[a-zA-Z0-9._%+\-]+@[a-zA-Z0-9.\-]+\.[a-zA-Z]{2,}" placeholder="bobrik@bobrik.com" title="' . __('This field can only contain email address') . '"' : $pattern;
      $pattern = ($pattern == 'login') ? 'pattern="[a-zA-Z0-9_]+" placeholder="aZ09_" title="' . __('This field can only contain Latin letters and numbers') . ' ' . __('and') . ' _' . '"' : $pattern;
      $pattern = ($pattern == 'mac') ? 'pattern="^[a-fA-F0-9]{2}:[a-fA-F0-9]{2}:[a-fA-F0-9]{2}:[a-fA-F0-9]{2}:[a-fA-F0-9]{2}:[a-fA-F0-9]{2}$|^[a-fA-F0-9]{2}-[a-fA-F0-9]{2}-[a-fA-F0-9]{2}-[a-fA-F0-9]{2}-[a-fA-F0-9]{2}-[a-fA-F0-9]{2}$" placeholder="00:02:02:34:72:a5" title="' . __('This MAC have wrong format') . '"' : $pattern;
      $pattern = ($pattern == 'url') ? 'pattern="https?:\/\/[A-Za-z0-9][A-Za-z0-9\.\\-]*\.[A-Za-z]{2,}(:[0-9]+)?(\/.*)?" placeholder="http://ubilling.net.ua/" title="' . __('URL') . ': http://host.domain/ ' . __('or') . ' https://host.domain/ ' . __('or') . ' http://host.domain:port"' : $pattern;
      $pattern = ($pattern == 'geo') ? 'pattern="-?\d{1,2}(\.\d+)\s?,\s?-?\d{1,3}(\.\d+)" placeholder="0.00000,0.00000" title="' . __('The format of geographic data can be') . ': 40.7143528,-74.0059731 ; 41.40338, 2.17403 ; -14.235004 , 51.92528"' : $pattern;
      $pattern = ($pattern == 'mobile') ? 'pattern="\+?(\d{1,3})?\d{2,3}\d{7}" placeholder="(+)(38)0500000000" title="' . __('The mobile number format can be') . ': +380800100102, 0506430501, 375295431122"' : $pattern;
      $pattern = ($pattern == 'filepath') ? 'pattern="^\/?(?:[^\/ ]+\/)*[^\/ ]+$" placeholder="some/dir/file" title="' . __('This field can contain relative or absolute paths') . ': some/dir/file, dir/file.txt, file.txt"' : $pattern;
      $pattern = ($pattern == 'dirpath') ? 'pattern="^\/?(?:[^\/ ]+\/)*[^\/ ]*\/?$" placeholder="some/dir/" title="' . __('This field can contain relative or absolute directories paths') . ': some/dir/, dir/"' : $pattern;
      $pattern = ($pattern == 'fullpath') ? 'pattern="^\/(?:[^\/ ]+\/?)+$" placeholder="/some/dir/file" title="' . __('This field can only contain absolute Unix-style paths starting with /') . ': /some/dir/file ' . __('or') . ' /some/dir/"' : $pattern;
      $pattern = ($pattern == 'pathorurl') ? 'pattern="^(https?://[^ ]+|/[^ ]*|[^ ]+/[^ ]*)$" placeholder="some/path or http://domain.com/path" title="' . __('This field can accept URLs or paths') . ': http://someurl.com/, some/dir/file, /some/dir/"' : $pattern;
    
      $result = '<input type="text" name="' . $name . '" value="' . $value . '" ' . $input_size . ' id="' . $inputid . '" class="' . $class . '" ' . $opts . ' ' . $pattern . '>' . "\n";
    if ($label != '') {
        $result .= ' <label for="' . $inputid . '">' . __($label) . '</label>' . "\n";
    }
    $result .= $newline . "\n";
    return ($result);
}

/**
 * Return password input Web From element 
 *
 * @param  string $name name of element
 * @param  string $label text label for input
 * @param  string $value current value
 * @param  bool   $br append new line
 * @param  string $size input size
 * 
 * @return string
 */
function la_PasswordInput($name, $label = '', $value = '', $br = false, $size = '') {
    $inputid = la_InputId();
    //set size
    if ($size != '') {
        $input_size = 'size="' . $size . '"';
    } else {
        $input_size = '';
    }
    if ($br) {
        $newline = '<br>';
    } else {
        $newline = '';
    }
    $result = '<input type="password" name="' . $name . '" value="' . $value . '" ' . $input_size . ' id="' . $inputid . '">' . "\n";
    if ($label != '') {
        $result .= ' <label for="' . $inputid . '">' . __($label) . '</label>' . "\n";
    }
    $result .= $newline . "\n";
    return ($result);
}

/**
 * Return link form element
 *
 * @param string $url needed URL
 * @param string $title text title of URL
 * @param bool  $br append new line - bool
 * @param string $class class for link
 * @param string  $options for link
 * 
 * @return  string
 */
function la_Link($url, $title, $br = false, $class = '', $options = '') {
    if ($class != '') {
        $link_class = 'class="' . $class . '"';
    } else {
        $link_class = '';
    }
    if ($br) {
        $newline = '<br>';
    } else {
        $newline = '';
    }

    $opts = ( empty($options) ) ? '' : ' ' . $options;

    $result = '<a href="' . $url . '" ' . $link_class . $opts . '>' . __($title) . '</a>' . "\n";
    $result .= $newline . "\n";
    return ($result);
}

/**
 * Return Radio  box Web From element 
 *
 * @param string   $name name of element
 * @param string   $label text label for input
 * @param string   $value current value
 * @param bool   $br append new line - bool
 * @param bool  $checked is checked? - bool
 * 
 * @return  string
 */
function la_RadioInput($name, $label = '', $value = '', $br = false, $checked = false) {
    $inputid = la_InputId();
    if ($br) {
        $newline = '<br>';
    } else {
        $newline = '';
    }
    if ($checked) {
        $check = 'checked=""';
    } else {
        $check = '';
    }
    $result = '<input type="radio" name="' . $name . '" value="' . $value . '"  id="' . $inputid . '" ' . $check . '>' . "\n";
    if ($label != '') {
        $result .= ' <label for="' . $inputid . '">' . __($label) . '</label>' . "\n";
    }
    $result .= $newline . "\n";
    return ($result);
}

/**
 * Return check box Web From element
 *
 * @param string  $name name of element
 * @param string  $label text label for input
 * @param bool    $br append new line
 * @param bool    $checked is checked?
 * @param string  $CtrlID
 * @param string  $CtrlClass
 *
 * @return  string
 *
 */
function la_CheckInput($name, $label = '', $br = false, $checked = false, $CtrlID = '', $CtrlClass = '', $options = '', $labelOptions = '') {
    $inputid = ((empty($CtrlID)) ? 'ChkBox_' . la_InputId() : $CtrlID);
    $inputClass = ((empty($CtrlClass)) ? '' : ' class="' . $CtrlClass . '" ');

    if ($br) {
        $newline = '<br>';
    } else {
        $newline = '';
    }
    if ($checked) {
        $check = 'checked=""';
    } else {
        $check = '';
    }
    $result = '<input type="checkbox" id="' . $inputid . '" ' . $inputClass . 'name="' . $name . '" ' . $check . ' ' . $options . ' />';
    if ($label != '') {
        $result .= ' <label for="' . $inputid . '" ' . $labelOptions . '>' . __($label) . '</label>' . "\n";
    }
    $result .= $newline . "\n";
    return ($result);
}

/**
 * Return textarea Web From element 
 *
 * @param string $name name of element
 * @param string $label text label for input
 * @param string $value value for element
 * @param bool $br append new line - bool
 * @param string $size size in format "10x20"
 * 
 * @return  string
 */
function la_TextArea($name, $label = '', $value = '', $br = false, $size = '') {
    $inputid = la_InputId();
    //set columns and rows count
    if ($size != '') {
        $sizexplode = explode('x', $size);
        $input_size = 'cols="' . $sizexplode[0] . '" rows="' . $sizexplode[1] . '" ';
    } else {
        $input_size = '';
    }
    if ($br) {
        $newline = '<br>';
    } else {
        $newline = '';
    }
    $result = '<textarea name="' . $name . '" ' . $input_size . ' id="' . $inputid . '">' . $value . '</textarea>' . "\n";
    if ($label != '') {
        $result .= ' <label for="' . $inputid . '">' . __($label) . '</label>' . "\n";
        ;
    }
    $result .= $newline . "\n";
    return ($result);
}

/**
 * Return hidden input web form element
 *
 * @param string $name name of element
 * @param string s$value value for input
 * 
 * @return  string
 */
function la_HiddenInput($name, $value = '') {
    $result = '<input type="hidden" name="' . $name . '" value="' . $value . '">';
    return ($result);
}

/**
 * Return submit web form element
 *
 * @param string $value text label for button
 * 
 * @return  string
 */
function la_Submit($value, $class = '') {
    $result = '<input type="submit" class= "' . $class . '" value="' . __($value) . '">';
    return ($result);
}

/**
 * Return select Web From element 
 *
 * @param string  $name name of element
 * @param array  $params array of elements $value=>$option
 * @param string  $label text label for input
 * @param string  $selected selected $value for selector
 * @param string  $br append new line - bool
 * 
 * @return  string
 */
function la_Selector($name, $params, $label, $selected = '', $br = false) {
    $inputid = la_InputId();
    if ($br) {
        $newline = '<br>';
    } else {
        $newline = '';
    }
    $result = '<select name="' . $name . '" id="' . $inputid . '">';
    if (!empty($params)) {
        foreach ($params as $value => $eachparam) {
            $sel_flag = '';
            if ($selected != '') {
                if ($selected == $value) {
                    $sel_flag = 'SELECTED';
                }
            }
            $result .= '<option value="' . $value . '" ' . $sel_flag . '>' . $eachparam . '</option>' . "\n";
        }
    }

    $result .= '</select>' . "\n";
    if ($label != '') {
        $result .= '<label for="' . $inputid . '">' . __($label) . '</label>';
    }
    $result .= $newline . "\n";
    return ($result);
}

/**
 * Return Month select Web From element 
 *
 * @param  string  $name name of element
 * @param  string  $label text label for input
 * @param  string  $selected selected $value for selector
 * @param  string  $br append new line - bool
 * 
 * @return  string
 *
 */
function la_MonthSelector($name, $label, $selected = '', $br = false) {
    $allmonth = months_array();
    $params = array();

    //localize months
    foreach ($allmonth as $monthnum => $monthname) {
        $params[$monthnum] = rcms_date_localise($monthname);
    }

    $inputid = la_InputId();
    if ($br) {
        $newline = '<br>';
    } else {
        $newline = '';
    }
    $result = '<select name="' . $name . '" id="' . $inputid . '">';
    if (!empty($params)) {
        foreach ($params as $value => $eachparam) {
            $sel_flag = '';
            if ($selected != '') {
                if ($selected == $value) {
                    $sel_flag = 'SELECTED';
                }
            }
            $result .= '<option value="' . $value . '" ' . $sel_flag . '>' . $eachparam . '</option>' . "\n";
        }
    }

    $result .= '</select>' . "\n";
    if ($label != '') {
        $result .= '<label for="' . $inputid . '">' . __($label) . '</label>';
    }
    $result .= $newline . "\n";
    return ($result);
}

/**
 * Return Year select Web From element 
 *
 * @param string  $name name of element
 * @param string  $label text label for input
 * @param string  $br append new line - bool
 * @return  string
 *
 */
function la_YearSelector($name, $label = '', $br = false) {
    $curyear = curyear();
    $inputid = la_InputId();
    $count = 5;
    if ($br) {
        $newline = '<br>';
    } else {
        $newline = '';
    }
    $selector = '<select name="' . $name . '">';
    for ($i = 0; $i < $count; $i++) {
        $selector .= '<option value="' . ($curyear - $i) . '">' . ($curyear - $i) . '</option>';
    }
    $selector .= '</select>';
    if ($label != '') {
        $selector .= '<label for="' . $inputid . '">' . __($label) . '</label>';
    }
    $selector .= $newline;
    return($selector);
}

/**
 * Check for POST have needed variables
 *
 * @param array $params array of POST variables to check
 * @return  bool
 *
 */
function la_CheckPost($params) {
    $result = true;
    if (!empty($params)) {
        foreach ($params as $eachparam) {
            if (isset($_POST[$eachparam])) {
                if (empty($_POST[$eachparam])) {
                    $result = false;
                }
            } else {
                $result = false;
            }
        }
    }
    return ($result);
}

/**
 * Check for GET have needed variables
 *
 * @param array $params array of GET variables to check
 * 
 * @return  bool
 *
 */
function la_CheckGet($params) {
    $result = true;
    if (!empty($params)) {
        foreach ($params as $eachparam) {
            if (isset($_GET[$eachparam])) {
                if (empty($_GET[$eachparam])) {
                    $result = false;
                }
            } else {
                $result = false;
            }
        }
    }
    return ($result);
}

/**
 * Construct HTML table row element
 * 
 * @param string $cells table row cells
 * @param string $class table row class
 * 
 * @return string
 *  
 */
function la_TableRow($cells, $class = '') {
    if ($class != '') {
        $rowclass = 'class="' . $class . '"';
    } else {
        $rowclass = '';
    }
    $result = '<tr ' . $rowclass . '>' . $cells . '</tr>' . "\n";
    return ($result);
}

/**
 * Construct HTML table cell element
 * 
 * @param string $data table cell data
 * @param string $width width of cell element
 * @param string $class table cell class
 * @param string $customkey table cell custom param
 * 
 * @return string
 */
function la_TableCell($data, $width = '', $class = '', $customkey = '') {
    if ($width != '') {
        $cellwidth = 'width="' . $width . '"';
    } else {
        $cellwidth = '';
    }
    if ($class != '') {
        $cellclass = 'class="' . $class . '"';
    } else {
        $cellclass = '';
    }
    if ($customkey != '') {
        $customkey = $customkey;
    } else {
        $customkey = '';
    }
    $result = '<td ' . $cellwidth . ' ' . $cellclass . ' ' . $customkey . '>' . $data . '</td>' . "\n";
    return ($result);
}

/**
 * Construct HTML table body
 * 
 * @param string $rows table rows data
 * @param string $width width of cell element
 * @param string $border table border width
 * @param string $class table cell class
 * 
 * @return string
 *  
 */
function la_TableBody($rows, $width = '', $border = '0', $class = '') {
    if ($width != '') {
        $tablewidth = 'width="' . $width . '"';
    } else {
        $tablewidth = '';
    }
    if ($class != '') {
        $tableclass = 'class="' . $class . '"';
    } else {
        $tableclass = '';
    }

    if ($border != '') {
        $tableborder = 'border="' . $border . '"';
    } else {
        $tableborder = '';
    }

    $result = '
        <table ' . $tablewidth . ' ' . $tableborder . ' ' . $tableclass . ' >
            ' . $rows . '
        </table>
        ';
    return ($result);
}

/**
 * Returns image body
 * 
 * @param string $url image url
 * @param string $title image title
 * 
 * @return string
 */
function la_img($url, $title = '') {
    if ($title != '') {
        $imgtitle = 'title="' . $title . '"';
    } else {
        $imgtitle = '';
    }
    $result = '<img src="' . $url . '" ' . $imgtitle . ' border="0">';
    return ($result);
}

/**
 * Returns image body with some dimensions
 * 
 * @param string $url image url
 * @param string $title title attribure for image
 * @param string $width image width
 * @param string $height image height
 * 
 * @return string
 *  
 */
function la_img_sized($url, $title = '', $width = '', $height = '', $style = '') {
    $imgtitle = ($title != '') ? 'title="' . $title . '"' : '';
    $imgwidth = ($width != '') ? 'width="' . $width . '"' : '';
    $imgheight = ($height != '') ? 'height="' . $height . '"' : '';
    $imgstyle = (empty($style)) ? '' : ' style="' . $style . '" ';

    $result = '<img src="' . $url . '" ' . $imgtitle . ' ' . $imgwidth . ' ' . $imgheight . $imgstyle . ' border="0">';
    return ($result);
}

/**
 * Returns some count of delimiters
 * 
 * @param int $count count of delimited rows
 * 
 * @return string
 */
function la_delimiter($count = 1) {
    $result = '';
    for ($i = 0; $i <= $count; $i++) {
        $result .= '<br />';
    }
    return ($result);
}

/**
 * Returns some count of non-breaking space symbols
 *
 * @param int $count
 *
 * @return string
 */
function la_nbsp($count = 1) {
    $result = '';
    for ($i = 0; $i < $count; $i++) {
        $result .= '&nbsp;';
    }
    return ($result);
}

/**
 * Returns some html styled tag
 * 
 * @param string $tag HTML tag entity
 * @param string $closed tag is closing?
 * @param string $class tag styling class
 * @param string $options tag extra options
 * 
 * @return string
 */
function la_tag($tag, $closed = false, $class = '', $options = '') {
    if (!empty($class)) {
        $tagclass = ' class="' . $class . '"';
    } else {
        $tagclass = '';
    }

    if ($closed) {
        $tagclose = '/';
    } else {
        $tagclose = '';
    }

    if ($options != '') {
        $tagoptions = $options;
    } else {
        $tagoptions = '';
    }

    $result = '<' . $tagclose . $tag . $tagclass . ' ' . $tagoptions . '>';
    return ($result);
}

/**
 * Returns calendar widget with preset date
 * 
 * @param string $field field name to insert calendar
 * 
 * @return string
 *  
 */
function la_DatePickerPreset($field, $date) {
    $inputid = la_InputId();
    $us_config = zbs_LoadConfig();
    $curlang = $us_config['lang'];
    $skinPath = zbs_GetCurrentSkinPath($us_config);
    $iconsPath = $skinPath . 'iconz/';
    $result = '<script>
	$(function() {
		$( "#' . $inputid . '" ).datepicker({
			showOn: "both",
                        buttonImage: "' . $iconsPath . 'icon_calendar.gif",
			buttonImageOnly: true,
                        dateFormat:  "yy-mm-dd",
                        showAnim: "slideDown"
		});
               
                    
                $.datepicker.regional[\'english\'] = {
		closeText: \'Done\',
		prevText: \'Prev\',
		nextText: \'Next\',
		currentText: \'Today\',
		monthNames: [\'January\',\'February\',\'March\',\'April\',\'May\',\'June\',
		\'July\',\'August\',\'September\',\'October\',\'November\',\'December\'],
		monthNamesShort: [\'Jan\', \'Feb\', \'Mar\', \'Apr\', \'May\', \'Jun\',
		\'Jul\', \'Aug\', \'Sep\', \'Oct\', \'Nov\', \'Dec\'],
		dayNames: [\'Sunday\', \'Monday\', \'Tuesday\', \'Wednesday\', \'Thursday\', \'Friday\', \'Saturday\'],
		dayNamesShort: [\'Sun\', \'Mon\', \'Tue\', \'Wed\', \'Thu\', \'Fri\', \'Sat\'],
		dayNamesMin: [\'Su\',\'Mo\',\'Tu\',\'We\',\'Th\',\'Fr\',\'Sa\'],
		weekHeader: \'Wk\',
		dateFormat: \'dd/mm/yy\',
		firstDay: 1,
		isRTL: false,
		showMonthAfterYear: false,
		yearSuffix: \'\'};
                    
                $.datepicker.regional[\'russian\'] = {
		closeText: \'Закрыть\',
		prevText: \'&#x3c;Пред\',
		nextText: \'След&#x3e;\',
		currentText: \'Сегодня\',
		monthNames: [\'Январь\',\'Февраль\',\'Март\',\'Апрель\',\'Май\',\'Июнь\',
		\'Июль\',\'Август\',\'Сентябрь\',\'Октябрь\',\'Ноябрь\',\'Декабрь\'],
		monthNamesShort: [\'Янв\',\'Фев\',\'Мар\',\'Апр\',\'Май\',\'Июн\',
		\'Июл\',\'Авг\',\'Сен\',\'Окт\',\'Ноя\',\'Дек\'],
		dayNames: [\'воскресенье\',\'понедельник\',\'вторник\',\'среда\',\'четверг\',\'пятница\',\'суббота\'],
		dayNamesShort: [\'вск\',\'пнд\',\'втр\',\'срд\',\'чтв\',\'птн\',\'сбт\'],
		dayNamesMin: [\'Вс\',\'Пн\',\'Вт\',\'Ср\',\'Чт\',\'Пт\',\'Сб\'],
		weekHeader: \'Нед\',
		dateFormat: \'dd.mm.yy\',
		firstDay: 1,
		isRTL: false,
		showMonthAfterYear: false,
		yearSuffix: \'\'};
                    
                $.datepicker.regional[\'ukrainian\'] = {
		closeText: \'Закрити\',
		prevText: \'&#x3c;\',
		nextText: \'&#x3e;\',
		currentText: \'Сьогодні\',
		monthNames: [\'Січень\',\'Лютий\',\'Березень\',\'Квітень\',\'Травень\',\'Червень\',
		\'Липень\',\'Серпень\',\'Вересень\',\'Жовтень\',\'Листопад\',\'Грудень\'],
		monthNamesShort: [\'Січ\',\'Лют\',\'Бер\',\'Кві\',\'Тра\',\'Чер\',
		\'Лип\',\'Сер\',\'Вер\',\'Жов\',\'Лис\',\'Гру\'],
		dayNames: [\'неділя\',\'понеділок\',\'вівторок\',\'середа\',\'четвер\',\'п’ятниця\',\'субота\'],
		dayNamesShort: [\'нед\',\'пнд\',\'вів\',\'срд\',\'чтв\',\'птн\',\'сбт\'],
		dayNamesMin: [\'Нд\',\'Пн\',\'Вт\',\'Ср\',\'Чт\',\'Пт\',\'Сб\'],
		weekHeader: \'Тиж\',
		dateFormat: \'dd/mm/yy\',
		firstDay: 1,
		isRTL: false,
		showMonthAfterYear: false,
		yearSuffix: \'\'};
                
	$.datepicker.setDefaults($.datepicker.regional[\'' . $curlang . '\']);
      

	});
	</script>
        
        <input type="text" id="' . $inputid . '" name="' . $field . '" value="' . $date . '" size="10">
        ';
    return($result);
}

/**
 * Returns modal window JS code
 * 
 * @param string $link
 * @param string $title
 * @param string $content
 * @param string $linkclass
 * @param string $width
 * @param string $height
 * 
 * @return string
 */
function la_modal($link, $title, $content, $linkclass = '', $width = '', $height = '') {
    $wid = la_inputid();
//setting link class
    if ($linkclass != '') {
        $link_class = 'class="' . $linkclass . '"';
    } else {
        $link_class = '';
    }

//setting auto width if not specified
    if ($width == '') {
        $width = '600';
    }

//setting auto width if not specified
    if ($height == '') {
        $height = '400';
    }

    $dialog = '
<script type="text/javascript">
$(function() {
		$( "#dialog-modal_' . $wid . '" ).dialog({
			autoOpen: false,
			width: ' . $width . ',
                        height: ' . $height . ',
			modal: true,
			show: "drop",
			hide: "fold"
		});

		$( "#opener_' . $wid . '" ).click(function() {
			$( "#dialog-modal_' . $wid . '" ).dialog( "open" );
                      	return false;
		});
	});
</script>

<div id="dialog-modal_' . $wid . '" title="' . $title . '" style="display:none; width:1px; height:1px;">
	<p>
        ' . $content . '
        </p>
</div>

<a href="#" id="opener_' . $wid . '" ' . $link_class . '>' . $link . '</a>
';

    return($dialog);
}

/**
 * Returns link that calls new modal window with automatic dimensions by inside content
 * 
 * @param string $link link text
 * @param string $title modal window title
 * @param string $content modal window content
 * @param string $linkclass link class
 *
 * @return string
 *  
 */
function la_modalAuto($link, $title, $content, $linkclass = '') {
    $wid = la_inputid();

//setting link class
    if ($linkclass != '') {
        $link_class = 'class="' . $linkclass . '"';
    } else {
        $link_class = '';
    }

    $width = "'auto'";
    $height = "'auto'";

    $dialog = '
<script type="text/javascript">
$(function() {
		$( "#dialog-modal_' . $wid . '" ).dialog({
			autoOpen: false,
			width: \'auto\',
            height: \'auto\',
			modal: true,
			show: "drop",
			hide: "fold"
		});

		$( "#opener_' . $wid . '" ).click(function() {
			$( "#dialog-modal_' . $wid . '" ).dialog( "open" );
            return false;
		});
	});
</script>

<div id="dialog-modal_' . $wid . '" title="' . $title . '" style="display:none; width:1px; height:1px;">
	<p>
    ' . $content . '
    </p>
</div>

<a href="#" id="opener_' . $wid . '" ' . $link_class . '>' . $link . '</a>
';

    return($dialog);
}

/**
 * Returns new opened modal window with some content
 * 
 * @param string $title modal window title
 * @param string $content modal window content
 * @param string $width modal window width 
 * @param string $height modal window height
 * @return string
 *  
 */
function la_modalOpened($title, $content, $width = '', $height = '') {

    $wid = la_inputid();

//setting auto width if not specified
    if ($width == '') {
        $width = "'auto'";
    }

//setting auto width if not specified
    if ($height == '') {
        $height = "'auto'";
    }

    $dialog = '
<script type="text/javascript">
$(function() {
		$( "#dialog-modal_' . $wid . '" ).dialog({
			autoOpen: true,
			width: ' . $width . ',
                        height: ' . $height . ',
			modal: true,
                        show: "drop",
			hide: "fold",
                        create: function( event, ui ) {
                            $(this).css("maxWidth", "800px");
                        }
		});

		$( "#opener_' . $wid . '" ).click(function() {
			$( "#dialog-modal_' . $wid . '" ).dialog( "open" );
                      	return false;
		});
	});
</script>

<div id="dialog-modal_' . $wid . '" title="' . $title . '" style="display:none; width:1px; height:1px;">
	<p>
        ' . $content . '
        </p>
</div>
';

    return($dialog);
}

/**
 * Returns JS confirmation url 
 * 
 * @param string $url URL if confirmed
 * @param string $title link title
 * @param string $alerttext alert text
 * @return string
 *  
 */
function la_JSAlert($url, $title, $alerttext) {
    $result = '<a  onclick="if(!confirm(\'' . __($alerttext) . '\')) { return false;}" href="' . $url . '">' . $title . '</a>';
    return ($result);
}

/**
 * Returns JS confirmation url with some applied class
 * 
 * @param string $url URL if confirmed
 * @param string $title link title
 * @param string $alerttext alert text
 * @param string $functiontorun function name with parameters which must exist on a page
 *
 * @return string
 */
function la_JSAlertStyled($url, $title, $alerttext, $class = '', $functiontorun = '') {
    $class = (!empty($class)) ? 'class="' . $class . '"' : '';

    if (empty($functiontorun)) {
        $result = '<a onclick="if(!confirm(\'' . __($alerttext) . '\')) { return false;}" href="' . $url . '" ' . $class . '>' . $title . '</a>';
    } else {
        $result = '<a onclick="if(!confirm(\'' . __($alerttext) . '\')) { return false;} else { ' . $functiontorun . '; }" href="' . $url . '" ' . $class . '>' . $title . '</a>';
    }

    return ($result);
}

/**
 * Returns confirmation dialog to navigate to some URL
 * 
 * @param string $url
 * @param string $title
 * @param string $alerttext
 * @param string $class
 * @param string $cancelUrl
 * 
 * @return string
 */
function la_ConfirmDialog($url, $title, $alerttext, $class = '', $cancelUrl = '') {
    $result = '';
    $dialog = __($alerttext);
    $dialog .= la_tag('br');
    $dialog .= la_tag('center', false);
    $dialog .= la_Link($url, __('Agree'), false, 'anreadbutton');
    if ($cancelUrl) {
        $dialog .= la_Link($cancelUrl, __('Cancel'), false, 'anunreadbutton');
    }
    $dialog .= la_tag('center', true);

    $result .= la_modalAuto($title, __($title), $dialog, $class);
    return($result);
}

?>
