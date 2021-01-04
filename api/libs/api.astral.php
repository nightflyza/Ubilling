<?php

/**
 *  Returns web form element id
 * 
 *  @return  string
 */
function wf_InputId() {
    // I know it looks really funny. 
    // You can also get a truly random values​by throwing dice ;)
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
 * @param  string $action action URL
 * @param  string $method method: POST or GET
 * @param  string $inputs inputs string to include
 * @param  string $class  class for form
 * @param  string $legend form legend
 * @param  string $CtrlID
 *
 * @return  string
 *
 */
function wf_Form($action, $method, $inputs, $class = '', $legend = '', $CtrlID = '', $target = '') {
    $FrmID = ( (empty($CtrlID)) ? 'Form_' . wf_InputId() : $CtrlID );

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

    if ($target != '') {
        $target = ' target="' . $target . '" ';
    } else {
        $target = '';
    }

    $form = '
        <form action="' . $action . '" method="' . $method . '" ' . $form_class . 'id="' . $FrmID . '"' . $target . '>
        ' . $form_legend . '
        ' . $inputs . '
        </form>
        <div style="clear:both;"></div>
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
function wf_TextInput($name, $label = '', $value = '', $br = false, $size = '', $pattern = '', $class = '', $ctrlID = '', $options = '') {
    $inputid = ( empty($ctrlID) ) ? wf_InputId() : $ctrlID;
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
    $pattern = ($pattern == 'geo') ? 'pattern="-?\d{1,2}(\.\d+)\s?,\s?-?\d{1,3}(\.\d+)" placeholder="0.00000,0.00000" title="' . __('The format of geographic data can be') . ': 40.7143528,-74.0059731 ; 41.40338, 2.17403 ; -14.235004 , 51.92528"' : $pattern;
    $pattern = ($pattern == 'mobile') ? 'pattern="\+?(\d{1,3})?\d{2,3}\d{7}" placeholder="(+)(38)0500000000" title="' . __('The mobile number format can be') . ': +78126121104, 0506430501, 375295431122"' : $pattern;
    $pattern = ($pattern == 'finance') ? 'pattern="\d+(\.\d+)?" placeholder="0(.00)" title="' . __('The financial input format can be') . ': 1 ; 4.01 ; 2 ; 0.001"' : $pattern;
    // For this pattern IP adress also can be 0.0.0.0
    $pattern = ($pattern == 'ip') ? 'pattern="^(([0-9]|[1-9][0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5])\.){3}([0-9]|[1-9][0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5])$" placeholder="0.0.0.0" title="' . __('The IP address format can be') . ': 192.1.1.1"' : $pattern;
    // For this pattern exclude cidr /31
    $pattern = ($pattern == 'net-cidr') ? 'pattern="^(([0-9]|[1-9][0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5])\.){3}([0-9]|[1-9][0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5])\/([0-9]|[1-2][0-9]|30|32)$" placeholder="0.0.0.0/0" title="' . __('The format of IP address with mask can be') . ': 192.1.1.1/32 ' . __('and the mask can not be /31') . '"' : $pattern;
    $pattern = ($pattern == 'digits') ? 'pattern="^\d+$" placeholder="0" title="' . __('This field can only contain digits') . '"' : $pattern;
    $pattern = ($pattern == 'email') ? 'pattern="^([\w\._-]+)@([\w\._-]+)\.([a-z]{2,6}\.?)$" placeholder="bobrik@bobrik.com" title="' . __('This field can only contain email address') . '"' : $pattern;
    $pattern = ($pattern == 'alpha') ? 'pattern="[a-zA-Z]+" placeholder="aZ" title="' . __('This field can only contain Latin letters') . '"' : $pattern;
    $pattern = ($pattern == 'alphanumeric') ? 'pattern="[a-zA-Z0-9]+" placeholder="aZ09" title="' . __('This field can only contain Latin letters and numbers') . '"' : $pattern;
    $pattern = ($pattern == 'mac') ? 'pattern="^[a-fA-F0-9]{2}:[a-fA-F0-9]{2}:[a-fA-F0-9]{2}:[a-fA-F0-9]{2}:[a-fA-F0-9]{2}:[a-fA-F0-9]{2}$|^[a-fA-F0-9]{2}-[a-fA-F0-9]{2}-[a-fA-F0-9]{2}-[a-fA-F0-9]{2}-[a-fA-F0-9]{2}-[a-fA-F0-9]{2}$" placeholder="00:02:02:34:72:a5" title="' . __('This MAC have wrong format') . '"' : $pattern;

    $result = '<input type="text" name="' . $name . '" value="' . $value . '" ' . $input_size . ' id="' . $inputid . '" class="' . $class . '" ' . $opts . ' ' . $pattern . '>' . "\n";
    if ($label != '') {
        $result .= ' <label for="' . $inputid . '">' . __($label) . '</label>' . "\n";
        ;
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
 * @return string
 *
 */
function wf_PasswordInput($name, $label = '', $value = '', $br = false, $size = '') {
    $inputid = wf_InputId();
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
        ;
    }
    $result .= $newline . "\n";
    return ($result);
}

/**
 * Return link form element
 *
 * @param string  $url needed URL
 * @param string  $title text title of URL
 * @param bool    $br append new line
 * @param string  $class class for link
 * @param string  $options for link
 * @return  string
 *
 */
function wf_Link($url, $title, $br = false, $class = '', $options = '') {
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
 * Return ajax loader compatible link
 *  
 * @param string  $url needed URL
 * @param string  $title text title of URL
 * @param string  $container output container for ajax content
 * @param bool    $br append new line
 * @param string  $class class for link
 * @return  string
 */
function wf_AjaxLink($url, $title, $container, $br = false, $class = '') {
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
    $result = '<a href="#" onclick="goajax(\'' . $url . '\',\'' . $container . '\');" ' . $link_class . '>' . $title . '</a>' . "\n";
    $result .= $newline . "\n";
    return ($result);
}

/**
 * Return Radio  box Web From element 
 *
 * @param string  $name name of element
 * @param string  $label text label for input
 * @param string  $value current value
 * @param bool    $br append new line
 * @param bool    $checked is checked?
 * @param  string $ctrlID id of the element
 *
 * @return string
 *
 */
function wf_RadioInput($name, $label = '', $value = '', $br = false, $checked = false, $ctrlID = '') {
    $inputid = ( empty($ctrlID) ) ? wf_InputId() : $ctrlID;

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
        ;
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
function wf_CheckInput($name, $label = '', $br = false, $checked = false, $CtrlID = '', $CtrlClass = '') {
    $inputid = ( (empty($CtrlID)) ? 'ChkBox_' . wf_InputId() : $CtrlID );
    $inputClass = ( (empty($CtrlClass)) ? '' : ' class="' . $CtrlClass . '" ');

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
    $result = '<input type="checkbox" id="' . $inputid . '" ' . $inputClass . 'name="' . $name . '" ' . $check . ' />';
    if ($label != '') {
        $result .= ' <label for="' . $inputid . '">' . __($label) . '</label>' . "\n";
        ;
    }
    $result .= $newline . "\n";
    return ($result);
}

/**
 * Return textarea Web From element 
 *
 * @param string  $name name of element
 * @param string  $label text label for input
 * @param string  $value value for element
 * @param bool    $br append new line - bool
 * @param string  $size size in format "10x20"
 * @return  string
 *
 */
function wf_TextArea($name, $label = '', $value = '', $br = false, $size = '') {
    $inputid = wf_InputId();
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
 * @param string  $name name of element
 * @param string  $value value for input
 * @param string  $CtrlID
 * @param string  $CtrlClass
 *
 * @return  string
 *
 */
function wf_HiddenInput($name, $value = '', $CtrlID = '', $CtrlClass = '') {
    $HiddenID = ( (empty($CtrlID)) ? 'Hidden_' . wf_InputId() : $CtrlID );
    $Hiddenclass = ( (empty($CtrlClass)) ? '' : ' class="' . $CtrlClass . '" ');
    /**
     * Call me by my astral name
     * Breeding fear through wordless tounge
     * Heavenly thirst - unspeakable pain
     * Emptied from all human motion
     * Confront the faceless wrath
     */
    $result = '<input type="hidden" name="' . $name . '" value="' . $value . '" id="' . $HiddenID . '"' . $Hiddenclass . '>';
    return ($result);
}

/**
 * Return submit web form element
 *
 * @param string  $value text label for button
 * @param string $CtrlID
 * @param string $options
 *
 * @return string
 *
 */
function wf_Submit($value, $CtrlID = '', $options = '') {
    $SubmitID = ( (empty($CtrlID)) ? 'Submit_' . wf_InputId() : $CtrlID );
    $result = '<input type="submit" value="' . __($value) . '" id="' . $SubmitID . '" ' . $options . '>';
    return ($result);
}

/**
 * Return submit web form element for which you can specify class and other options
 *
 * @param $value
 * @param string $class
 * @param string $name
 * @param string $caption
 * @param string $CtrlID
 *
 * @return string
 */
function wf_SubmitClassed($value, $class = '', $name = '', $caption = '', $CtrlID = '') {
    $SubmitID = ( (empty($CtrlID)) ? 'Submit_' . wf_InputId() : $CtrlID );
    $result = '<button type="submit" value="' . $value . '" name="' . $name . '" class= "' . $class . '" id="' . $SubmitID . '">';
    $result .= $caption;
    $result .= '</button>';
    return ($result);
}

/**
 * Return Trigger select web form input
 *
 * @param string  $name name of element
 * @param string  $label text label for input
 * @param string  $state selected $value for trigger
 * @param bool    $br append new line
 * @return  string
 *
 */
function wf_Trigger($name, $label = '', $state = '', $br = false) {
    $inputid = wf_InputId();
    if (!$state) {
        $noflag = 'SELECTED';
    } else {
        $noflag = '';
    }
    if ($br) {
        $newline = '<br>';
    } else {
        $newline = '';
    }
    $result = '
           <select name="' . $name . '" id="' . $inputid . '">
                       <option value="1">' . __('Yes') . '</option>
                       <option value="0" ' . $noflag . '>' . __('No') . '</option>
           </select>
        ' . "\n";
    if ($label != '') {
        $result .= ' <label for="' . $inputid . '">' . __($label) . '</label>' . "\n";
        ;
    }
    $result .= $newline . "\n";
    return ($result);
}

/**
 * Return select Web From element 
 *
 * @param string  $name name of element
 * @param array   $params array of elements $value=>$option
 * @param string  $label text label for input
 * @param string  $selected selected $value for selector
 * @param bool    $br append new line
 * @param bool    $sort alphabetical sorting of params array by value
 * @param string  $CtrlID id of the element
 * 
 * @return  string
 *
 */
function wf_Selector($name, $params, $label, $selected = '', $br = false, $sort = false, $CtrlID = '', $CtrlClass = '', $options = '') {

    $inputid = ( empty($CtrlID) ) ? wf_InputId() : $CtrlID;
    $inputclass = ( empty($CtrlClass) ) ? '' : ' class="' . $CtrlClass . '"';
    $opts = ( empty($options)) ? '' : ' ' . $options . ' ';

    if ($br) {
        $newline = '<br>';
    } else {
        $newline = '';
    }
    $result = '<select name="' . $name . '" id="' . $inputid . '"' . $inputclass . $options . '>';
    if (!empty($params)) {
        ($sort) ? asort($params) : $params;
        foreach ($params as $value => $eachparam) {
            $flag_selected = (($selected == $value) AND ( $selected != '')) ? 'SELECTED' : ''; // !='' because 0 values possible
            $result .= '<option value="' . $value . '" ' . $flag_selected . '>' . $eachparam . '</option>' . "\n";
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
 * Return select Web From element 
 * 
 * @param string $name
 * @param string $params
 * @param string $label
 * @param string $selected
 * @param bool $br
 * @param string $class
 * @return string
 */
function wf_SelectorClassed($name, $params, $label, $selected = '', $br = false, $class = '') {
    $inputid = wf_InputId();
    if ($br) {
        $newline = '<br>';
    } else {
        $newline = '';
    }
    $result = '<select name="' . $name . '" id="' . $inputid . '" class="' . $class . '">';
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
 * Return select Web From element with auto click option
 *
 * @param string  $name name of element
 * @param array   $params array of elements $value=>$option
 * @param string  $label text label for input
 * @param string  $selected selected $value for selector
 * @param bool    $br append new line
 * @return  string
 *
 */
function wf_SelectorAC($name, $params, $label, $selected = '', $br = false) {
    $inputid = wf_InputId();
    if ($br) {
        $newline = '<br>';
    } else {
        $newline = '';
    }
    $result = '<select name="' . $name . '" id="' . $inputid . '" onChange="this.form.submit();">';
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
 * Return select Web From element with auto click option into ajax container
 *
 * @param string  $container name of container element
 * @param array   $params array of elements $url=>$option
 * @param string  $label text label for input
 * @param string  $selected selected $value for selector
 * @param bool    $br append new line
 * @return  string
 *
 */
function wf_AjaxSelectorAC($container, $params, $label, $selected = '', $br = false) {
    $inputid = wf_InputId();
    if ($br) {
        $newline = '<br>';
    } else {
        $newline = '';
    }
    $result = '<select name="' . $inputid . '" id="' . $inputid . '" onChange="this.options[this.selectedIndex].onclick();">';
    if (!empty($params)) {
        foreach ($params as $value => $eachparam) {
            $sel_flag = '';
            if ($selected != '') {
                if ($selected == $value) {
                    $sel_flag = 'SELECTED';
                }
            }
            $result .= '<option value="' . $value . '" ' . $sel_flag . ' onclick="goajax(\'' . $value . '\',\'' . $container . '\');">' . $eachparam . '</option>' . "\n";
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
 * @param string  $name name of element
 * @param string  $label text label for input
 * @param string  $selected selected $value for selector
 * @param bool    $br append new line
 * @param bool    $allTime appends month '1488' to the end of selector
 * 
 * @return  string
 */
function wf_MonthSelector($name, $label, $selected = '', $br = false, $allTime = false) {
    $allmonth = months_array();
    $params = array();

    //localize months
    foreach ($allmonth as $monthnum => $monthname) {
        $params[$monthnum] = rcms_date_localise($monthname);
    }

    $inputid = wf_InputId();
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

    if ($allTime) {
        $selectedM = ($selected == '1488') ? 'SELECTED' : ''; // yep, this required to passing vf() checks and empty() checks.
        $result .= '<option value="1488"  ' . $selectedM . '>' . __('All time') . '</option>';
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
 * @param bool    $br append new line
 * @return  string
 *
 */
function wf_YearSelector($name, $label = '', $br = false) {
    $curyear = curyear();
    $inputid = wf_InputId();
    $count = (date("Y") - 2007);
    if ($br) {
        $newline = '<br>';
    } else {
        $newline = '';
    }
    $selector = '<select name="' . $name . '" id="' . $inputid . '">';
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
 * Return Year select Web From element 
 *
 * @param string  $name name of element
 * @param string  $label text label for input
 * @param bool    $br append new line
 * @param int     $year selected year
 * @param int     $allTime as last year equal 1488
 * 
 * @return  string
 *
 */
function wf_YearSelectorPreset($name, $label = '', $br = false, $year = '', $allTime = false) {
    $curyear = curyear();
    $inputid = wf_InputId();
    $count = (date("Y") - 2007);
    $selected = '';

    if ($br) {
        $newline = '<br>';
    } else {
        $newline = '';
    }
    $selector = '<select name="' . $name . '" id="' . $inputid . '">';
    for ($i = 0; $i < $count; $i++) {
        $selected = (($curyear - $i) == $year) ? 'SELECTED' : '';
        $selector .= '<option value="' . ($curyear - $i) . '" ' . $selected . '>' . ($curyear - $i) . '</option>';
    }
    if ($allTime) {
        $selected = ($year == '1488') ? 'SELECTED' : ''; // yep, this required to passing vf() checks and empty() checks.
        $selector .= '<option value="1488"  ' . $selected . '>' . __('All time') . '</option>';
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
 * @param array  $params array of POST variables to check
 * @return  bool
 *
 */
function wf_CheckPost($params) {
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
 * @param array  $params array of GET variables to check
 * @return  bool
 *
 */
function wf_CheckGet($params) {
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
 * Returns boolean representation of variable like boolval() in PHP 5.5+
 * but also can check if variable contains strings 'true' and 'false'
 * and return appropriate value
 *
 * @param mixed $Variable
 * @param bool $CheckAsTrueFalseStr
 *
 * @return bool
 */
function wf_getBoolFromVar($Variable, $CheckAsTrueFalseStr = false) {
    if (isset($Variable)) {
        if (empty($Variable)) {
            return false;
        }
    } else {
        return false;
    }

    if ($CheckAsTrueFalseStr) {
        if (strtolower($Variable) === 'true' || strtolower($Variable) === '1') {
            return true;
        }

        if (strtolower($Variable) === 'false' || strtolower($Variable) === '0') {
            return false;
        }
    } else {
        return !!$Variable;
    }
}

/**
 * Returns true if $value is empty() or null but not equals to 0 or '0'
 *
 * @param string $value
 *
 * @return bool
 */
function wf_emptyNonZero($value = '') {
    return ( (empty($value) and $value !== 0 and $value !== '0') ? true : false );
}

/**
 * Construct HTML table row element
 * 
 * @param string $cells table row cells
 * @param string $class table row class
 * @return string
 *  
 */
function wf_TableRow($cells, $class = '') {
    if ($class != '') {
        $rowclass = 'class="' . $class . '"';
    } else {
        $rowclass = '';
    }
    $result = '<tr ' . $rowclass . '>' . $cells . '</tr>' . "\n";
    return ($result);
}

/**
 * Construct HTML table row element with style inside
 * 
 * @param string $cells table row cells
 * @param string $class table row class
 * @return string
 *  
 */
function wf_TableRowStyled($cells, $class = '', $style = '') {
    if ($class != '') {
        $rowclass = 'class="' . $class . '"';
    } else {
        $rowclass = '';
    }
    $result = '<tr style="' . $style . '" ' . $rowclass . '>' . $cells . '</tr>' . "\n";
    return ($result);
}

/**
 * Construct HTML table cell element
 * 
 * @param string $data table cell data
 * @param string $width width of cell element
 * @param string $class table cell class
 * @param string $customkey table cell custom param
 * @return string
 *  
 */
function wf_TableCell($data, $width = '', $class = '', $customkey = '', $colspan = '', $rowspan = '') {
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

    $colspan = (empty($colspan)) ? '' : 'colspan="' . $colspan . '"';
    $rowspan = (empty($rowspan)) ? '' : 'rowspan="' . $rowspan . '"';

    $result = '<td ' . $cellwidth . ' ' . $cellclass . ' ' . $customkey . ' ' . $colspan . ' ' . $rowspan . '>' . $data . '</td>' . "\n";
    return ($result);
}

/**
 * Construct HTML table body
 * 
 * @param string $rows table rows data
 * @param string $width width of cell element
 * @param string $border table border width
 * @param string $class table cell class
 * @param string $options table additional options
 * @return string
 *  
 */
function wf_TableBody($rows, $width = '', $border = '0', $class = '', $options = '') {
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
        <table ' . $tablewidth . ' ' . $tableborder . ' ' . $tableclass . ' ' . $options . ' >
            ' . $rows . '
        </table>
        ';
    return ($result);
}

/**
 * Returns JS confirmation url 
 * 
 * @param string $url URL if confirmed
 * @param string $title link title
 * @param string $alerttext alert text
 * @param string $functiontorun function name with parameters which must exist on a page
 * @param string $class link class
 *
 * @return string
 *  
 */
function wf_JSAlert($url, $title, $alerttext, $functiontorun = '', $class = '') {
    $class = (empty($class)) ? '' : 'class="' . $class . '"';

    if (empty($functiontorun)) {
        $result = '<a ' . $class . ' onclick="if(!confirm(\'' . __($alerttext) . '\')) { return false;}" href="' . $url . '">' . $title . '</a>';
    } else {
        $result = '<a ' . $class . ' onclick="if(!confirm(\'' . __($alerttext) . '\')) { return false;} else { ' . $functiontorun . '; return false; }" href="' . $url . '">' . $title . '</a>';
    }
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
 *  
 */
function wf_JSAlertStyled($url, $title, $alerttext, $class = '', $functiontorun = '') {
    $class = (!empty($class)) ? 'class="' . $class . '"' : '';

    if (empty($functiontorun)) {
        $result = '<a onclick="if(!confirm(\'' . __($alerttext) . '\')) { return false;}" href="' . $url . '" ' . $class . '>' . $title . '</a>';
    } else {
        $result = '<a onclick="if(!confirm(\'' . __($alerttext) . '\')) { return false;} else { ' . $functiontorun . '; }" href="' . $url . '" ' . $class . '>' . $title . '</a>';
    }

    return ($result);
}

/**
 * Returns filled paginator
 * 
 * @param int $total Total items count
 * @param int $perpage Per page items count
 * @param int $current current page
 * @param string $link module link which use paginator
 * @param string $class page links class
 * @return string
 *  
 */
function wf_pagination($total, $perpage, $current, $link, $class = '') {
    if ($class != '') {
        $pageclass = 'class="' . $class . '"';
    } else {
        $pageclass = '';
    }

    $return = '';
    $link = preg_replace("/((&amp;|&)page=(\d*))/", '', $link);
    if (!empty($perpage)) {
        $pages = ceil($total / $perpage);
        if ($pages != 1) {
            $c = 1;
            while ($c <= $pages) {
                if ($c != $current)
                    $return .= ' ' . '<a href="' . $link . '&amp;page=' . $c . '" ' . $pageclass . '>' . $c . '</a> ';
                else
                    $return .= ' ' . '<a href="#" ' . $pageclass . ' style="color: #ff0000;">' . $c . '</a> ';
                $c++;
            }
        }
    }
    return $return;
}

/**
 * Returns image body
 * 
 * @param string $url image url
 * @param string $title image title
 * 
 * @return string
 *  
 */
function wf_img($url, $title = '', $style = '') {
    if ($title != '') {
        $imgtitle = 'title="' . $title . '"';
    } else {
        $imgtitle = '';
    }

    $imgstyle = (empty($style)) ? '' : ' style="' . $style . '" ';

    $result = '<img src="' . $url . '" ' . $imgtitle . $imgstyle . ' border="0">';
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
function wf_img_sized($url, $title = '', $width = '', $height = '', $style = '') {
    $imgtitle = ($title != '') ? 'title="' . $title . '"' : '';
    $imgwidth = ($width != '') ? 'width="' . $width . '"' : '';
    $imgheight = ($height != '') ? 'height="' . $height . '"' : '';
    $imgstyle = (empty($style)) ? '' : ' style="' . $style . '" ';

    $result = '<img src="' . $url . '" ' . $imgtitle . ' ' . $imgwidth . ' ' . $imgheight . $imgstyle . ' border="0">';
    return ($result);
}

/**
 * Returns link that calls new modal window
 * 
 * @param string $link link text
 * @param string $title modal window title
 * @param string $content modal window content
 * @param string $linkclass link class
 * @param string $width modal window width 
 * @param string $height modal window height
 * @return string
 *  
 */
function wf_modal($link, $title, $content, $linkclass = '', $width = '', $height = '') {
    $wid = wf_inputid();

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

//setting auto height if not specified
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
function wf_modalAuto($link, $title, $content, $linkclass = '') {
    $wid = wf_inputid();

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
 * Returns link that calls new modal window with automatic dimensions by inner content and without "opener" object
 *
 * @param string $Title
 * @param string $Content
 * @param string $WindowID
 * @param string $WindowBodyID
 * @param bool $DestroyOnClose
 * @param string $AutoOpen
 * @param string $Width
 * @param string $Height
 *
 * @return string
 */
function wf_modalAutoForm($Title, $Content, $WindowID = '', $WindowBodyID = '', $DestroyOnClose = false, $AutoOpen = 'false', $Width = '', $Height = '') {
    $WID = (empty($WindowID)) ? 'dialog-modal_' . wf_inputid() : $WindowID;
    $WBID = (empty($WindowBodyID)) ? 'body_dialog-modal_' . wf_inputid() : $WindowBodyID;

    if (empty($Width)) {
        $Width = "'auto'";
    }

    if (empty($Height)) {
        $Height = "'auto'";
    }

    $DestroyParams = '';
    if ($DestroyOnClose) {
        $DestroyParams = ', 
                            close: function(event, ui) { 
                                $(\'#' . $WID . '\').dialog("destroy");
                                $(\'#' . $WID . '\').remove();
                                $(\'#script_' . $WID . '\').remove();
                          }
                         ';
    }

    $Dialog = wf_tag('script', false, '', 'type="text/javascript" id="script_' . $WID . '"');
    $Dialog .= ' 
                $(function() {   
                    $(\'#' . $WID . '\').dialog({
                        autoOpen: ' . $AutoOpen . ',
                        width: ' . $Width . ',
                        height: ' . $Height . ',
                        modal: true,
                        show: "drop",
                        hide: "fold"' . $DestroyParams . '
                    });
                });
                ';
    $Dialog .= wf_tag('script', true);
    $Dialog .= '
                <div id="' . $WID . '" title="' . $Title . '" style="display:none; width:1px; height:1px;">
	                <p id="' . $WBID . '">' . $Content . '</p>                
                </div>
                ';

    return $Dialog;
}

/**
 * Returns calendar widget
 * 
 * @param string $field field name to insert calendar
 * @param bool $extControls extended year and month controls
 * 
 * @return string
 *  
 */
function wf_DatePicker($field, $extControls = false) {
    $inputid = wf_InputId();
    $curlang = curlang();
    if ($extControls) {
        $extControls = ',
                        changeMonth: true,
                        yearRange: "-100:+100",
                        changeYear: true';
    } else {
        $extControls = '';
    }
    $result = '<script>
	$(function() {
		$( "#' . $inputid . '" ).datepicker({
			showOn: "both",
			buttonImage: "skins/icon_calendar.gif",
			buttonImageOnly: true,
                        dateFormat:  "yy-mm-dd",
                        showAnim: "slideDown"' . $extControls . '
		});
               
                    
                $.datepicker.regional[\'en\'] = {
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
                    
                $.datepicker.regional[\'ru\'] = {
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
                    
                $.datepicker.regional[\'uk\'] = {
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
        
        <input type="text" id="' . $inputid . '" name="' . $field . '" size="10">
        ';
    return($result);
}

/**
 * Returns calendar widget with preset date
 * 
 * @param string $field field name to insert calendar
 * @param string $date to set the calendar's value to
 * @param bool $extControls extended year and month controls
 * @param string $CtrlID
 *
 * @return string
 *  
 */
function wf_DatePickerPreset($field, $date, $extControls = false, $CtrlID = '', $ctrlClass = '') {
    $inputid = ( empty($CtrlID) ) ? wf_InputId() : $CtrlID;
    $class = ( empty($ctrlClass) ) ? '' : ' class="' . $ctrlClass . '" ';
    $curlang = curlang();
    if ($extControls) {
        $extControls = ',
                        changeMonth: true,
                        yearRange: "-100:+100",
                        changeYear: true';
    } else {
        $extControls = '';
    }
    $result = '<script>
	$(function() {
		$( "#' . $inputid . '" ).datepicker({
			showOn: "both",
			buttonImage: "skins/icon_calendar.gif",
			buttonImageOnly: true,
                        dateFormat:  "yy-mm-dd",
                        showAnim: "slideDown"' . $extControls . '
		});
               
                    
                $.datepicker.regional[\'en\'] = {
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
                    
                $.datepicker.regional[\'ru\'] = {
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
                    
                $.datepicker.regional[\'uk\'] = {
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
        
        <input type="text" id="' . $inputid . '" name="' . $field . '" value="' . $date . '" size="10"' . $class . '>
        ';
    return($result);
}

/**
 * Returns FullCalendar widget
 * 
 * @param string $data prepeared data to show
 * @return string
 *  
 */
function wf_FullCalendar($data, $options = '', $useHTMLInTitle = false, $useHTMLListViewOnly = false) {
    $elementid = wf_InputId();

    if ($useHTMLInTitle) {
        if ($useHTMLListViewOnly) {
            $htmlInTitle = " eventRender: function(event, element, view) {
                                if (view.type.indexOf('list') >= 0) {
                                    var link = element.find('[class*=-title] a');
                                    var title = element.find('[class*=-title]');
                                    link.html(title.text());
                                    title.html( link );
                                } else {                                    
                                    var title = element.find('[class*=-title]');
                                    // some hack to remove HTML from text
                                    var doc = new DOMParser().parseFromString(title.text(), 'text/html');
                                    var titleText = (doc.body.textContent || \"\");
                                    title.html( titleText );
                                }
                            }, ";
        } else {
            $htmlInTitle = " eventRender: function(event, element, view) {
                                if (view.type.indexOf('list') >= 0) {
                                    var link = element.find('[class*=-title] a');
                                    var title = element.find('[class*=-title]');
                                    link.html(title.text());
                                    title.html( link );
                                } else {
                                    var title = element.find('[class*=-title]');
                                    title.html( title.text() );
                                }
                            }, ";
        }
    } else {
        $htmlInTitle = '';
    }

    $calendar = "<script type='text/javascript'>

	$(document).ready(function() {
	
		var date = new Date();
		var d = date.getDate();
		var m = date.getMonth();
		var y = date.getFullYear();
         
		$('#" . $elementid . "').fullCalendar({
                     header: {
				left: 'prev,next today',
				center: 'title',
				right: 'month,basicWeek,basicDay,listMonth'
			},
                        
			editable: false,
                        " . $htmlInTitle . "                         
                        theme: true,
                        weekends: true,
                        timeFormat: 'H(:mm)',
                        displayEventTime: false,
                        height: 'auto',
                        contentHeight: 'auto',
                        " . $options . "
                        monthNamesShort: [
                        '" . rcms_date_localise('Jan') . "',
                        '" . rcms_date_localise('Feb') . "',
                        '" . rcms_date_localise('Mar') . "',
                        '" . rcms_date_localise('Apr') . "',
                        '" . rcms_date_localise('May') . "',
                        '" . rcms_date_localise('Jun') . "',
                        '" . rcms_date_localise('Jul') . "',
                        '" . rcms_date_localise('Aug') . "',
                        '" . rcms_date_localise('Sep') . "',
                        '" . rcms_date_localise('Oct') . "',
                        '" . rcms_date_localise('Nov') . "',
                        '" . rcms_date_localise('Dec') . "'
                        ],

                        monthNames: [
                        '" . rcms_date_localise('January') . "',
                        '" . rcms_date_localise('February') . "',
                        '" . rcms_date_localise('March') . "',
                        '" . rcms_date_localise('April') . "',
                        '" . rcms_date_localise('May') . "',
                        '" . rcms_date_localise('June') . "',
                        '" . rcms_date_localise('July') . "',
                        '" . rcms_date_localise('August') . "',
                        '" . rcms_date_localise('September') . "',
                        '" . rcms_date_localise('October') . "',
                        '" . rcms_date_localise('November') . "',
                        '" . rcms_date_localise('December') . "'
                        ],
                        
                        dayNamesShort: [
                        '" . rcms_date_localise('Sun') . "',
                        '" . rcms_date_localise('Mon') . "',
                        '" . rcms_date_localise('Tue') . "',
                        '" . rcms_date_localise('Wed') . "',
                        '" . rcms_date_localise('Thu') . "',
                        '" . rcms_date_localise('Fri') . "',
                        '" . rcms_date_localise('Sat') . "'
                        ],
                        
                        dayNames: [
                        '" . rcms_date_localise('Sunday') . "',
                        '" . rcms_date_localise('Monday') . "',
                        '" . rcms_date_localise('Tuesday') . "',
                        '" . rcms_date_localise('Wednesday') . "',
                        '" . rcms_date_localise('Thursday') . "',
                        '" . rcms_date_localise('Friday') . "',
                        '" . rcms_date_localise('Saturday') . "'
                        ],
                        
                        buttonText: {
                            today:    '" . __('Today') . "',
                            month:    '" . __('Month') . "',
                            week:     '" . __('Week') . "',
                            day:      '" . __('Day') . "',
                            list:      '" . __('List') . "'
                        },
                        
                   
			events: [
				" . $data . "
			
			]
                        
		});
		
	});

</script>
<div id='" . $elementid . "'></div>
";

    return($calendar);
}

/**
 * Returns div plate with some content
 * 
 * @param string $content Data to include into plate widget
 * @param string $width   Widget width
 * @param string $height  Widget height
 * @param string $class   Widget class to assign
 * @param string $opts    Widget style options. Do not include style="..."
 *
 * @return string
 */
function wf_Plate($content, $width = '', $height = '', $class = '', $opts = '') {
    if ($width != '') {
        $width = 'width: ' . $width . ';';
    }

    if ($height != '') {
        $height = 'height: ' . $height . ';';
    }


    if ($class != '') {
        $class = 'class="' . $class . '"';
    }

    $result = '
        <div style="' . $width . ' ' . $height . ' float: left; ' . $opts . ' " ' . $class . '>
		' . $content . '
        </div>
        ';
    return ($result);
}

/**
 * Returns some count of delimiters
 * 
 * @param int $count count of delimited rows
 * @return string
 *  
 */
function wf_delimiter($count = 1) {
    $result = '';
    for ($i = 0; $i <= $count; $i++) {
        $result .= '<br />';
    }
    return ($result);
}

/**
 * Returns some html styled tag
 * 
 * @param int    $tag HTML tag entity
 * @param bool   $closed tag is closing?
 * @param string $class tag styling class
 * @param string $options tag extra options
 * @return string
 *  
 */
function wf_tag($tag, $closed = false, $class = '', $options = '') {
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
 * Constructs ajax loader. Animation image have id: ubajaxloaderanim
 * 
 * @return string
 */
function wf_AjaxLoader() {
    $result = '
          <script type="text/javascript">
           


        function getXmlHttp()
        {
            var xmlhttp;
            try
        {
            xmlhttp = new ActiveXObject("Msxml2.XMLHTTP");
        }
        catch (e)
        {
            try
            {
                xmlhttp = new ActiveXObject("Microsoft.XMLHTTP");
            }
            catch (E)
            {
                xmlhttp = false;
            }
        }
 
        if(!xmlhttp && typeof XMLHttpRequest!=\'undefined\')
        {
            xmlhttp = new XMLHttpRequest();
        }
        return xmlhttp;
    }
 
    function goajax(link,container)
    {
 
        var myrequest = getXmlHttp()
        var docum = link;
        var contentElem = document.getElementById(container);
        myrequest.open(\'POST\', docum, true);
        myrequest.setRequestHeader(\'Content-Type\', \'application/x-www-form-urlencoded\');
       contentElem.innerHTML = \'<img src=skins/ajaxloader.gif id=ubajaxloaderanim>\';
        myrequest.onreadystatechange = function()
        {
            if (myrequest.readyState == 4)
            {
                if(myrequest.status == 200)
                {
                    var resText = myrequest.responseText;
 
 
                    var ua = navigator.userAgent.toLowerCase();
 
                    if (ua.indexOf(\'gecko\') != -1)
                    {
                        var range = contentElem.ownerDocument.createRange();
                        range.selectNodeContents(contentElem);
                        range.deleteContents();
                        var fragment = range.createContextualFragment(resText);
                        contentElem.appendChild(fragment);
                    }
                    else  
                    {
                        contentElem.innerHTML = resText;

                    }
                }
                else
                {
                    contentElem.innerHTML = \'' . __('Error') . '\';
                }
            }
 
        }
        myrequest.send();
    }
    </script>
          ';
    return ($result);
}

/**
 * Returns default ajax container div element
 * 
 * @param string $containerName container name aka ID
 * @param string $options misc options like size/display if required
 * @param string $content default container content
 * 
 * @return string
 */
function wf_AjaxContainer($containerName, $options = '', $content = '') {
    $result = wf_tag('div', false, '', 'id="' . $containerName . '" ' . $options . ' ') . $content . wf_tag('div', true);
    return ($result);
}

/**
 * Returns default ajax container span element
 * 
 * @param string $containerName container name aka ID
 * @param string $options misc options like size/display if required
 * @param srring $content default container content
 * 
 * @return string
 */
function wf_AjaxContainerSpan($containerName, $options = '', $content = '') {
    $result = wf_tag('span', false, '', 'id="' . $containerName . '" ' . $options . ' ') . $content . wf_tag('span', true);
    return ($result);
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
function wf_modalOpened($title, $content, $width = '', $height = '') {

    $wid = wf_inputid();

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
			autoOpen: true,
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
';

    return($dialog);
}

/**
 * Returns new opened modal window with some content and automatic sizes
 * 
 * @param string $title modal window title
 * @param string $content modal window content
 * 
 * @return string
 */
function wf_modalOpenedAuto($title, $content) {

    $wid = wf_inputid();

    $width = "'auto'";
    $height = "'auto'";


    $dialog = '
<script type="text/javascript">
$(function() {
		$( "#dialog-modal_' . $wid . '" ).dialog({
			autoOpen: true,
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
';

    return($dialog);
}

/**
 * Returns Chart source
 * 
 * @param string $data      - CSV formatted data
 * @param string $widht     - graph width in pixels
 * @param string $height    - graph height in pixels
 * @param bool   $errorbars - display error bars around data series
 * 
 * @return string
 */
function wf_Graph($data, $width = '500', $height = '300', $errorbars = false, $GraphTitle = '', $XLabel = '', $YLabel = '', $RangeSelector = false) {
    $randomId = wf_InputId();
    $objectId = 'graph_' . $randomId;
    $data = trim($data);
    $data = explodeRows($data);
    $cleandata = '';
    if ($errorbars) {
        $errorbars = 'true';
    } else {
        $errorbars = 'false';
    }
    if (!empty($data)) {
        foreach ($data as $eachrow) {
            $cleandata .= '"' . trim($eachrow) . '\n" +' . "\n";
        }
        $cleandata = mb_substr($cleandata, 0, -2, 'utf-8');
    }
    //style="width: 98%; "
    $result = wf_tag('div', false, '', 'id="' . $randomId . '" style="width:' . $width . 'px; height:' . $height . 'px;"') . wf_tag('div', true);
    $result .= wf_tag('script', false, '', 'type="text/javascript"');
    $result .= $objectId . ' = new Dygraph(';
    $result .= 'document.getElementById("' . $randomId . '"),' . "\n";
    $result .= $cleandata;

    $result .= ', {  errorBars: ' . $errorbars;
    $result .= (!empty($GraphTitle)) ? ', title: \'' . $GraphTitle . '\'' : '';
    $result .= (!empty($XLabel)) ? ', xlabel: \'' . $XLabel . '\'' : '';
    $result .= (!empty($YLabel)) ? ', ylabel: \'' . $YLabel . '\'' : '';
    $result .= (!empty($RangeSelector)) ? ', showRangeSelector: true' : '';
    $result .= ' }' . "\n";

    $result .= ');';
    $result .= wf_tag('script', true);

    return ($result);
}

/**
 * Returns Chart source by data loaded from the file - acceptable for huge data sets
 * 
 * @param string $datafile  - existing CSV file path
 * @param string $widht     - graph width in pixels
 * @param string $height    - graph height in pixels
 * @param bool   $errorbars - display error bars around data series
 * 
 * @return string
 */
function wf_GraphCSV($datafile, $width = '500', $height = '300', $errorbars = false, $GraphTitle = '', $XLabel = '', $YLabel = '', $RangeSelector = false) {
    $randomId = wf_InputId();
    $objectId = 'graph_' . $randomId;

    if ($errorbars) {
        $errorbars = 'true';
    } else {
        $errorbars = 'false';
    }

    $result = wf_tag('div', false, '', 'id="' . $randomId . '" style="width:' . $width . 'px; height:' . $height . 'px;"') . wf_tag('div', true);
    $result .= wf_tag('script', false, '', 'type="text/javascript"');
    $result .= $objectId . ' = new Dygraph(';
    $result .= 'document.getElementById("' . $randomId . '"), "' . $datafile . '" ' . "\n";


    $result .= ', {  errorBars: ' . $errorbars;
    $result .= (!empty($GraphTitle)) ? ', title: \'' . $GraphTitle . '\'' : '';
    $result .= (!empty($XLabel)) ? ', xlabel: \'' . $XLabel . '\'' : '';
    $result .= (!empty($YLabel)) ? ', ylabel: \'' . $YLabel . '\'' : '';
    $result .= (!empty($RangeSelector)) ? ', showRangeSelector: true' : '';
    $result .= ' }' . "\n";

    $result .= ');';
    $result .= wf_tag('script', true);

    return ($result);
}

/**
 * Returns color picker dialog
 * 
 * @param string $name   input name
 * @param string $label input text label
 * @param string $value input pre setted data
 * @param bool   $br add line break after input?
 * @param string $size size of element
 * 
 * @return string
 */
function wf_ColPicker($name, $label = '', $value = '', $br = false, $size = '') {
    $id = wf_InputId();
    $css = '
            <link rel="stylesheet" href="modules/jsc/colpick/colpick.css" type="text/css"/>';
    $js = '
            <script src="modules/jsc/colpick/colpick.js" type="text/javascript"></script>
            <script type="text/javascript">
            $(document).ready(function() {
                $("#' . $id . '").colpick({
                    colorScheme: "light",
                    layout: "hex",
                    submit: true,
                    color:  "' . (!empty($value) ? $value : "#f57601" ) . '",
                    onSubmit: function(hsb,hex,rgb,el) {
                        var hex_str = $("div.colpick_hex_field > input").val();
                        $(el).val("#" + hex_str);
                        $(el).colpickHide();
                    }
                });
            });
            </script>
        ';
    $size = (!empty($size) ) ? 'size="' . $size . '"' : null;
    $result = '<input type="text" name="' . $name . '" value="' . $value . '" id="' . $id . '" ' . $size . '>' . "\n";
    $result .= (!empty($label) ) ? '<label for="' . $id . '">' . __($label) . '</label>' : null;
    $result .= (!empty($br) ) ? '<br>' : null;
    $result .= "\n";
    return $css . $js . $result;
}

/**
 * Return Jquery UI selectable combobox
 *
 * @param string  $name name of element
 * @param array   $params array of elements $value=>$option
 * @param string  $label text label for input
 * @param string  $selected selected $value for selector (now ignored)
 * @param bool    $br append new line
 * @return  string
 *
 */
function wf_JuiComboBox($name, $params, $label, $selected = '', $br = false) {
    $id = wf_InputId();
    $select = '';

    if (!empty($params)) {
        foreach ($params as $io => $each) {
            $flag_selected = (!empty($selected) and $selected == $io) ? 'SELECTED' : '';
            $select .= '<option value="' . $io . '" ' . $flag_selected . '>' . $each . '</option>' . "\n";
        }
    }

    $result = '

 <style>
.custom-combobox_' . $id . ' {
position: relative;
display: inline-block;
}
.custom-combobox-toggle_' . $id . ' {
position: absolute;
top: 0;
bottom: 0;
margin-left: -1px;
padding: 0;
}
.custom-combobox-input_' . $id . ' {
margin: 0;
padding: 5px 10px;
}

.ui-autocomplete {
    max-height: 400px;
    overflow-y: auto;   /* prevent horizontal scrollbar */
    overflow-x: hidden; /* add padding to account for vertical scrollbar */
    z-index:1000 !important;
}
</style>
<script>
(function( $ ) {
$.widget( "custom.combobox_' . $id . '", {
_create: function() {
this.wrapper = $( "<span>" )
.addClass( "custom-combobox_' . $id . '" )
.insertAfter( this.element );
this.element.hide();
this._createAutocomplete();
this._createShowAllButton();
},
_createAutocomplete: function() {
var selected = this.element.children( ":selected" ),
value = selected.val() ? selected.text() : "";
this.input = $( "<input>" )
.appendTo( this.wrapper )
.val( value )
.attr( "title", "" )
.addClass( "custom-combobox-input_' . $id . ' ui-widget_' . $id . ' ui-widget-content ui-state-default ui-corner-left" )
.autocomplete({
delay: 0,
minLength: 0,
source: $.proxy( this, "_source" )
})
.tooltip({
tooltipClass: "ui-state-highlight"
});
this._on( this.input, {
autocompleteselect: function( event, ui ) {
ui.item.option.selected = true;
this._trigger( "select", event, {
item: ui.item.option
});
},
autocompletechange: "_removeIfInvalid"
});
},
_createShowAllButton: function() {
var input = this.input,
wasOpen = false;
$( "<a>" )
.attr( "tabIndex", -1 )
.attr( "title", "' . __('Show all') . '" )
.tooltip()
.appendTo( this.wrapper )
.button({
icons: {
primary: "ui-icon-triangle-1-s"
},
text: false
})
.removeClass( "ui-corner-all" )
.addClass( "custom-combobox-toggle_' . $id . ' ui-corner-right" )
.mousedown(function() {
wasOpen = input.autocomplete( "widget" ).is( ":visible" );
})
.click(function() {
input.focus();
// Close if already visible
if ( wasOpen ) {
return;
}
// Pass empty string as value to search for, displaying all results
input.autocomplete( "search", "" );
});
},
_source: function( request, response ) {
var matcher = new RegExp( $.ui.autocomplete.escapeRegex(request.term), "i" );
response( this.element.children( "option" ).map(function() {
var text = $( this ).text();
if ( this.value && ( !request.term || matcher.test(text) ) )
return {
label: text,
value: text,
option: this
};
}) );
},
_removeIfInvalid: function( event, ui ) {
// Selected an item, nothing to do
if ( ui.item ) {
return;
}
// Search for a match (case-insensitive)
var value = this.input.val(),
valueLowerCase = value.toLowerCase(),
valid = false;
this.element.children( "option" ).each(function() {
if ( $( this ).text().toLowerCase() === valueLowerCase ) {
this.selected = valid = true;
return false;
}
});
// Found a match, nothing to do
if ( valid ) {
return;
}

this.input.autocomplete( "instance" ).term = "";
},
_destroy: function() {
this.wrapper.remove();
this.element.show();
}
});
})( jQuery );

$(function() {
$( "#combobox_' . $id . '" ).combobox_' . $id . '();
});
</script>


<div class="ui-widget_' . $id . '">
<label for="combobox_' . $id . '">' . $label . '</label>
<select id="combobox_' . $id . '" name=' . $name . '>
' . $select . '
</select>
</div>
';
    if ($br) {
        $result .= wf_tag('br');
    }

    return ($result);
}

/**
 * Returns auto complete text input element
 * 
 * @param string $name name of element
 * @param array  $data data array for autocomplete box
 * @param string $label text label for input
 * @param string $value current value
 * @param bool   $br append new line - bool
 * @param string $size input size
 * @return  string
 *
 */
function wf_AutocompleteTextInput($name, $data = array(), $label = '', $value = '', $br = false, $size = '') {
    $inputid = wf_InputId();
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
    $acData = '';
    $autocomplete = '<script>
                    $(function() {
                    var availableOpts_' . $inputid . ' = [
                  ';
    if (!empty($data)) {
        foreach ($data as $io => $each) {
            $each = str_replace('"', '`', $each);
            $acData .= '"' . $each . '",';
        }
    }
    //removing ending coma
    $acData = mb_substr($acData, 0, -1, 'UTF-8');


    $autocomplete .= $acData;

    $autocomplete .= '
                                      ];
                    $( "#' . $name . '_autocomplete" ).autocomplete({
                    source: availableOpts_' . $inputid . '
                    });
                    });
                    </script>';
    $result = $autocomplete;
    $result .= '<input type="text" id="' . $name . '_autocomplete" name="' . $name . '" value="' . $value . '" ' . $input_size . ' id="' . $inputid . '">' . "\n";
    if ($label != '') {
        $result .= ' <label for="' . $inputid . '">' . __($label) . '</label>' . "\n";
        ;
    }

    $result .= $newline . "\n";
    return ($result);
}

/**
 * Returns calendar widget with preset time
 * Based on Jon Thornton's jquery timepicker:   http://jonthornton.github.io/jquery-timepicker
 *
 * @param string $field field name to insert time select widget
 * @param string $time default value time for widget
 * @param string $DisabledTimeRanges string which represents time ranges unavailable to pick up, like: "['11:00', '14:05'], ['20:30', '21:00']" and so on
 * @param string $label label of widget
 * @param bool $br add break after the widget body?
 * @return string
 */
function wf_TimePickerPreset($field, $time = '', $label = '', $br = false, $DisabledTimeRanges = '') {
    $inputId = wf_InputId();
    if (isset($DisabledTimeRanges)) {
        $DisabledTimeRanges = ',\'disableTimeRanges\': [ ' . $DisabledTimeRanges . ']';
    }
    $result = wf_tag('input', false, '', 'type="text" value="' . $time . '" name="' . $field . '" size="5" id="' . $inputId . '"');
    $result .= wf_tag('script');
    $result .= '$(\'#' . $inputId . '\').timepicker({\'scrollDefault\': \'' . $time . '\', \'timeFormat\': \'H:i\'' . $DisabledTimeRanges . ' });';
    $result .= wf_tag('script', true);
    //clickable icon and label
    if (!empty($label)) {
        $label = ' ' . __($label);
    }
    $result .= wf_tag('label', false, '', 'for="' . $inputId . '"') . wf_img('skins/icon_time_small.png', __('Time')) . $label . wf_tag('label', true);
    //break at end
    if ($br) {
        $result .= wf_tag('br');
    }
    return ($result);
}

/**
 * Returns calendar widget with preset time
 * Based on Jon Thornton's jquery timepicker:   http://jonthornton.github.io/jquery-timepicker
 *
 * @param string $field field name to insert time select widget
 * @param string $time default value time for widget
 * @param string $DisabledTimeRanges string which represents time ranges unavailable to pick up, like: "['11:00', '14:05'], ['20:30', '21:00']" and so on
 * @param string $label label of widget
 * @param bool $br add break after the widget body?
 * @return string
 */
function wf_TimePickerPresetSeconds($field, $time = '', $label = '', $br = false, $DisabledTimeRanges = '') {
    $inputId = wf_InputId();
    if (isset($DisabledTimeRanges)) {
        $DisabledTimeRanges = ',\'disableTimeRanges\': [ ' . $DisabledTimeRanges . ']';
    }
    $result = wf_tag('input', false, '', 'type="text" value="' . $time . '" name="' . $field . '" size="8" id="' . $inputId . '"');
    $result .= wf_tag('script');
    $result .= '$(\'#' . $inputId . '\').timepicker({\'scrollDefault\': \'' . $time . '\', \'timeFormat\': \'H:i:s\'' . $DisabledTimeRanges . ' });';
    $result .= wf_tag('script', true);
    //clickable icon and label
    if (!empty($label)) {
        $label = ' ' . __($label);
    }
    $result .= wf_tag('label', false, '', 'for="' . $inputId . '"') . wf_img('skins/icon_time_small.png', __('Time')) . $label . wf_tag('label', true);
    //break at end
    if ($br) {
        $result .= wf_tag('br');
    }
    return ($result);
}

/**
 * Returns div with styles cleanup
 * 
 * @return string
 */
function wf_CleanDiv() {
    $result = wf_tag('div', false, '', 'style="clear:both;"') . wf_tag('div', true);
    return ($result);
}

/**
 * Renders JQuery Data Tables container
 * 
 * @param array $columns columns names array
 * @param string $ajaxUrl URL to fetch JSON data
 * @param bool $saveState grid state saving - conflicts with default sort order
 * @param string $objects object names
 * @param int $rowsCount rows count to default display
 * @param string $opts additional options like:
 *                                       "order": [[ 0, "desc" ]]
 *                                       or 
 *                                       dom: \'Bfrtipsl\',  buttons: [\'copy\', \'csv\', \'excel\', \'pdf\', \'print\']
 *
 * @return string
 */
function wf_JqDtLoader($columns, $ajaxUrl, $saveState = false, $objects = 'users', $rowsCount = 100, $opts = '') {
    $tableId = 'jqdt_' . md5($ajaxUrl);
    $result = '';
    $saveState = ($saveState) ? 'true' : 'false';
    $opts = (!empty($opts)) ? $opts . ',' : '';

    $buttonsTest = '
        dom: \'frtipslB\',  buttons: [\'copy\', \'csv\', \'excel\', \'pdf\', \'print\'],
        ';

    $jq_dt = wf_tag('script', false, '', ' type="text/javascript" charset="utf-8"');
    $jq_dt .= '
 		$(document).ready(function() {                 
            
            $(\'#' . $tableId . '\').dataTable( {
                "oLanguage": {
                        "sLengthMenu": "' . __('Show') . ' _MENU_",
                        "sZeroRecords": "' . __('Nothing found') . '",
                        "sInfo": "' . __('Showing') . ' _START_ ' . __('to') . ' _END_ ' . __('of') . ' _TOTAL_ ' . __($objects) . '",
                        "sInfoEmpty": "' . __('Showing') . ' 0 ' . __('to') . ' 0 ' . __('of') . ' 0 ' . __($objects) . '",
                        "sInfoFiltered": "(' . __('Filtered') . ' ' . __('from') . ' _MAX_ ' . __('Total') . ')",
                        "sSearch":       "' . __('Search') . '",
                        "sProcessing":   "' . __('Processing') . '...",
                        "oPaginate": {
                            "sFirst": "' . __('First') . '",
                            "sPrevious": "' . __('Previous') . '",
                            "sNext": "' . __('Next') . '",
                            "sLast": "' . __('Last') . '"
                        },
                },
            
                "bPaginate": true,
                "bLengthChange": true,
                "bFilter": true,
                "bSort": true,
                "bInfo": true,
                "bAutoWidth": false,
                "bProcessing": true,
                "bStateSave": ' . $saveState . ',
                "iDisplayLength": ' . $rowsCount . ',
                "sAjaxSource": \'' . $ajaxUrl . '\',
                "bDeferRender": true,
                "lengthMenu": [[10, 25, 50, 100, 200, -1], [10, 25, 50, 100, 200, "' . __('All') . '"]],
                ' . $opts . '
                "bJQueryUI": true
            } );
              
		} );
                
          ';
    $jq_dt .= wf_tag('script', true);

    $result = $jq_dt;
    $result .= wf_tag('table', false, 'display compact', 'id="' . $tableId . '"');
    $result .= wf_tag('thead', false);

    $tablecells = '';
    foreach ($columns as $io => $eachColumn) {
        $tablecells .= wf_TableCell(__($eachColumn));
    }


    $result .= wf_TableRow($tablecells);

    $result .= wf_tag('thead', true);
    $result .= wf_tag('table', true);


    return ($result);
}

/**
 * Returns a JS snippet to control the visibility of JQDT column
 *
 * @param string $CallerObjID
 * @param string $CallerObjEvent
 * @param string $JQDTID
 * @param int $ColIndex
 *
 * @return string
 */
function wf_JQDTColumnHideShow($CallerObjID, $CallerObjEvent, $JQDTID, $ColIndex) {
    $JSCode = '$(\'#' . $CallerObjID . '\').on("' . $CallerObjEvent . '", function() {
                    // Get the column API object
                    var column = $(\'#' . $JQDTID . '\').DataTable().column(' . $ColIndex . '); 
                    // Toggle the visibility
                    column.visible( !column.visible() );
                 }); 
                ';

    return $JSCode;
}

/**
 * Outputs a hex color based text string without # at begin, like an ac1c09
 *
 * @param $text String of text
 * @param $palette Integer between 0 and 100
 * 
 * @return string
 */
function wf_genColorCodeFromText($text, $palette = '') {
    $hash = md5($palette . $text); // modify input to get a different palette
    $result = '';
    $result = substr($hash, 0, 2) . substr($hash, 2, 2) . substr($hash, 4, 2);
    return($result);
}

/**
 * Renders Google 3d pie chart
 * 
 * @param array $params data in format like string=>count
 * @param string $title chart title
 * @param string $width chart width in px or %, 500px default
 * @param string $height chart height in px or %, 500px default
 * @param string $options google charts options, useful examples see below<br>
 * pieSliceText: percentage/value/label/none OR  pieSliceText: 'value-and-percentage'<br>
 * is3D: true/false <br>
 * backgroundColor: '#666', <br>
 * legend : {position: 'bottom', textStyle: {color: 'red', fontSize: 12 }}, <br>
 * chartArea: {  width: '90%', height: '90%' }, <br>
 * @param string $fixedColors use fixed auto-generated colors based on text labels with pallette<br>
 * 
 * @return string
 */
function wf_gcharts3DPie($params, $title = '', $width = '', $height = '', $options = '', $fixedColors = '') {
    global $ubillingConfig;
    $altCfg = $ubillingConfig->getAlter();

    $containerId = wf_InputId();
    $width = ($width) ? $width : '500px';
    $height = ($height) ? $height : '500px';
    $result = '';
    $chartData = '';
    $enableFlag = true;
    if (!isset($altCfg['GCHARTS_ENABLED'])) {
        $enableFlag = true;
    } else {
        if ($altCfg['GCHARTS_ENABLED']) {
            $enableFlag = true;
        } else {
            $enableFlag = false;
        }
    }

    if ($enableFlag) {
        $colors = '';
        if ($fixedColors) {
            $palette = (is_bool($fixedColors)) ? '' : $fixedColors; //use string parameter as palette
            $colors .= 'var colors = { ';
        }

        if (!empty($params)) {
            foreach ($params as $io => $each) {
                $chartData .= '[\'' . $io . '\',' . $each . '],';
                if ($fixedColors) {
                    $colors .= " '" . $io . "': '" . wf_genColorCodeFromText($io, $palette) . "',";
                }
            }
            $chartData = substr($chartData, 0, -1);
        }

        if ($fixedColors) {
            $colors = rtrim($colors, ',');
            $colors .= '};';
        }

        if ($fixedColors) {
            $colors .= ' var slices = [];
                for (var i = 0; i < data.getNumberOfRows(); i++) {
                  slices.push({
                    color: colors[data.getValue(i, 0)]
                  });
                }';
            $slicesInject = 'slices: slices,';
        } else {
            $slicesInject = '';
        }

//legend.scrollArrows.activeColor
        $result = wf_tag('script', false, '', 'type="text/javascript" src="https://www.google.com/jsapi"') . wf_tag('script', true);
        $result .= wf_tag('script', false, '', 'type="text/javascript"');
        $result .= '
      google.load("visualization", "1", {packages:["corechart"]});
      google.setOnLoadCallback(drawChart);
      
      function drawChart() {

        var data = google.visualization.arrayToDataTable([
          [\'X\', \'Y\'],
           ' . $chartData . '
         ]);
         
        ' . $colors . '
        
        var options = {
          title: \'' . $title . '\',
          ' . $slicesInject . '
          is3D: true,
          
          ' . $options . '
          
          \'tooltip\' : {
             trigger: \'none\'
            }
        };

        var chart = new google.visualization.PieChart(document.getElementById(\'' . $containerId . '\'));
          
        chart.draw(data, options);
      }
';

        $result .= wf_tag('script', true);
        $result .= wf_tag('div', false, '', 'id="' . $containerId . '" style="width: ' . $width . '; height: ' . $height . ';"') . wf_tag('div', true);
    }
    return ($result);
}

/**
 * Renders Google line chart
 * 
 * @param array $params data in format like 
 *      $params=array(
 *       0=>array('month','total','active','inactive'),
 *       1=>array('Февраль',200,150,50),
 *       2=>array('Сентябрь',200,160,40)
 *       );
 * @param string $title chart title
 * @param string $width chart width in px or %, 500px default
 * @param string $height chart height in px or %, 500px default
 * @param string $options google charts options
 * 
 * @return string
 */
function wf_gchartsLine($params, $title = '', $width = '', $height = '', $options = '') {
    global $ubillingConfig;
    $altCfg = $ubillingConfig->getAlter();

    $containerId = wf_InputId();
    $width = ($width) ? $width : '500px';
    $height = ($height) ? $height : '500px';
    $result = '';
    $chartData = '';
    $enableFlag = true;
    if (!isset($altCfg['GCHARTS_ENABLED'])) {
        $enableFlag = true;
    } else {
        if ($altCfg['GCHARTS_ENABLED']) {
            $enableFlag = true;
        } else {
            $enableFlag = false;
        }
    }

    if ($enableFlag) {
        if (!empty($params)) {
            $chartData = json_encode($params, JSON_NUMERIC_CHECK);
        }

        $result = wf_tag('script', false, '', 'type="text/javascript" src="https://www.gstatic.com/charts/loader.js"') . wf_tag('script', true);
        $result .= wf_tag('script', false, '', 'type="text/javascript"');
        $result .= 'google.charts.load(\'current\', {\'packages\':[\'corechart\']});
      google.charts.setOnLoadCallback(drawChart);

      function drawChart() {
        var data = google.visualization.arrayToDataTable(
          ' . $chartData . '
        );

        var options = {
          title: \'' . $title . '\',
          curveType: \'function\',
           ' . $options . '
          legend: { position: \'bottom\' }
        };

        var chart = new google.visualization.LineChart(document.getElementById(\'' . $containerId . '\'));

        chart.draw(data, options);
      }
        ';
        $result .= wf_tag('script', true);
        $result .= wf_tag('div', false, '', 'id="' . $containerId . '" style="width: ' . $width . '; height: ' . $height . ';"') . wf_tag('div', true);
    }

    return ($result);
}

/**
 * Renders Google line chart
 * 
 * @param array $params data in format like 
 *      $params=array(
 *       0=>array('month','total','active','inactive'),
 *       1=>array('Февраль',200,150,50),
 *       2=>array('Сентябрь',200,160,40)
 *       );
 * @param string $title chart title
 * @param string $width chart width in px or %, 500px default
 * @param string $height chart height in px or %, 500px default
 * @param string $options google charts options
 * 
 * @return string
 */
function wf_gchartsLineZeroIsBad($params, $title = '', $width = '', $height = '', $options = '') {
    global $ubillingConfig;
    $altCfg = $ubillingConfig->getAlter();

    $containerId = wf_InputId();
    $width = ($width) ? $width : '500px';
    $height = ($height) ? $height : '500px';
    $result = '';
    $chartData = '';
    $enableFlag = true;
    if (!isset($altCfg['GCHARTS_ENABLED'])) {
        $enableFlag = true;
    } else {
        if ($altCfg['GCHARTS_ENABLED']) {
            $enableFlag = true;
        } else {
            $enableFlag = false;
        }
    }

    if ($enableFlag) {
        if (!empty($params)) {
            $chartData = json_encode($params, JSON_NUMERIC_CHECK);
        }

        $result = wf_tag('script', false, '', 'type="text/javascript" src="https://www.gstatic.com/charts/loader.js"') . wf_tag('script', true);
        $result .= wf_tag('script', false, '', 'type="text/javascript"');
        $result .= 'google.charts.load(\'current\', {\'packages\':[\'corechart\']});
      google.charts.setOnLoadCallback(drawChart);

      function drawChart() {
        var data = google.visualization.arrayToDataTable(
          ' . $chartData . '
        );

        var options = {
          title: \'' . $title . '\',
          curveType: \'function\',
           ' . $options . '
          legend: { position: \'bottom\' }
        };
        
var dataView = new google.visualization.DataView(data);
  dataView.setColumns([
    // reference existing columns by index
    0, 1,
    // add function for line color
    {
      calc: function(data, row) {
        var colorDown = "#FF0000";
        var colorUp = "#0d8a00";

        if ((data.getValue(row, 1) < 0)) {
          return colorDown;
        } else {
          //return colorUp;
        }
      },
      type: "string",
      role: "style"
    }
  ]);

        var chart = new google.visualization.LineChart(document.getElementById(\'' . $containerId . '\'));

        chart.draw(dataView, options);
      }
        ';
        $result .= wf_tag('script', true);
        $result .= wf_tag('div', false, '', 'id="' . $containerId . '" style="width: ' . $width . '; height: ' . $height . ';"') . wf_tag('div', true);
    }

    return ($result);
}

/**
 * Returns default back control
 * 
 * @param string $url Link URL
 * @param string $title Link title
 * @param bool $br Line break line after link
 * @param string $class Link class name
 * @param string $opts Link style or attributes
 * 
 * @return string
 */
function wf_BackLink($url, $title = '', $br = false, $class = 'ubButton', $opts = '') {
    $title = (empty($title)) ? __('Back') : __($title);
    $result = wf_Link($url, wf_img('skins/back.png') . ' ' . $title, $br, $class, $opts);
    return ($result);
}

/**
 * Returns form disabler JS code, for preventing duplicating POST requests
 * 
 * @return string
 */
function wf_FormDisabler() {
    $result = wf_tag('script', false, '', 'type="text/javascript" language="javascript" src="modules/jsc/form-disabler.js"') . wf_tag('script', true);
    return ($result);
}

/**
 * Returns spoiler control with specified options
 *
 * @param string $Content
 * @param string $Title
 * @param bool $Closed
 * @param string $SpoilerID
 * @param string $OuterDivClass
 * @param string $OuterDivOptions
 * @param string $InnerDivClass
 * @param string $InnerDivOptions
 *
 * @return string
 */
function wf_Spoiler($Content, $Title = '', $Closed = false, $SpoilerID = '', $OuterDivClass = '', $OuterDivOptions = '', $InnerDivClass = '', $InnerDivOptions = '') {
    if (empty($SpoilerID)) {
        $SpoilerID = 'spoiler_' . wf_InputId();
    }
    $SpoilerLnkID = 'lnk_' . wf_InputId();
    $SpoilerBodyID = 'spbody_' . wf_InputId();
    $SpoilerStateID = 'spstate_' . wf_InputId();
    $SpoilerState = ($Closed) ? '▼' : '▲';

    //$ubngStrPos = strpos(CUR_SKIN_PATH, 'ubng');

    $OuterDivClass = 'spoiler clearfix ' . $OuterDivClass;
    $OuterDivOptions = ' id="' . $SpoilerID . '" ' . $OuterDivOptions;

    $InnerDivClass = 'spoiler_body ' . $InnerDivClass;
    $InnerDivOptions = ' id="' . $SpoilerBodyID . '" ' . $InnerDivOptions;

    $Result = wf_tag('div', false, $OuterDivClass, $OuterDivOptions);
    $Result .= wf_tag('div', false, 'spoiler_title clearfix');
    //$Result .= '<a id="' . $SpoilerLnkID . '" class="spoiler_link" href="#">';
    $Result .= '<span id="' . $SpoilerLnkID . '" class="spoiler_link">';
    $Result .= wf_tag('h3', false, '', '');
    $Result .= $Title;
    $Result .= wf_tag('h3', true);
    //$Result .= $SpoilerState . '</a>' . "\n";
    $Result .= '<span id="' . $SpoilerStateID . '">' . $SpoilerState . '</span>';
    $Result .= '</span>' . "\n";
    $Result .= wf_tag('div', true);
    $Result .= wf_tag('div', false, $InnerDivClass, $InnerDivOptions);
    $Result .= $Content;
    $Result .= wf_tag('div', true);
    $Result .= wf_tag('div', true);

    $Result .= wf_tag('script', false, '', 'type="text/javascript"');
    $Result .= '$(\'#' . $SpoilerLnkID . '\').click(function() {
                    $(\'#' . $SpoilerBodyID . '\').toggleClass("spoiler_closed");
                    
                    if ( $(\'#' . $SpoilerBodyID . '\').hasClass("spoiler_closed") ) {
                        $(\'#' . $SpoilerBodyID . '\').slideUp(\'50\');
                        $(\'#' . $SpoilerStateID . '\').html(\'▼\');                        
                    } else {
                        $(\'#' . $SpoilerBodyID . '\').slideDown(\'50\');
                        $(\'#' . $SpoilerStateID . '\').html(\'▲\');
                    }
                    
                    return false;
                });';

    //$Result .= ($Closed) ? '$(\'#' . $SpoilerBodyID . '\').css("display", "none").toggleClass("spoiler_closed");' : '';
    $Result .= ($Closed) ? '$(\'#' . $SpoilerBodyID . '\').slideUp(\'50\').toggleClass("spoiler_closed");' : '';
    $Result .= wf_tag('script', true);

    return $Result;
}

/**
 * Returns JS for a control which will be responsible for opening dynamic modal windows via ajax call to a specific URL
 *
 * @param $ajaxURL
 * @param $dataArray
 * @param string $queryType
 * @param string $controlId
 * @param string $jsEvent
 * @param bool $noPreventDefault
 * @param bool $noReturnFalse
 * @param bool $wrapWithJSScriptTag
 *
 * @return string
 */
function wf_JSAjaxModalOpener($ajaxURL, $dataArray, $controlId = '', $wrapWithJSScriptTag = false, $queryType = 'GET', $jsEvent = 'click', $noPreventDefault = false, $noReturnFalse = false, $jqdtToRefreshID = '') {

    $inputId = (empty($controlId)) ? wf_InputId() : $controlId;
    $jqdtRefreshCode = (empty($jqdtToRefreshID)) ? '' : '$(\'#' . $jqdtToRefreshID . '\').DataTable().ajax.reload();';
    $modalWindowId = 'modalWindowId:"dialog-modal_' . $inputId . '", ';
    $modalWindowBodyId = 'modalWindowBodyId:"body_dialog-modal_' . $inputId . '"';
    $preventDefault = ($noPreventDefault) ? "" : "\nevt.preventDefault();";
    $returnFalse = ($noReturnFalse) ? "" : "\nreturn false;";

    $ajaxData = '';
    foreach ($dataArray as $io => $each) {
        $ajaxData .= $io . ':"' . $each . '", ';
    }

    $result = '$(\'#' . $inputId . '\').' . $jsEvent . '(function(evt) {
                  $.ajax({
                      type: "' . $queryType . '",
                      url: "' . $ajaxURL . '",
                      data: {' . $ajaxData
            . $modalWindowId
            . $modalWindowBodyId
            . '},
                      success: function(ajaxresult) {
                                  $(document.body).append(ajaxresult);
                                  $(\'#dialog-modal_' . $inputId . '\').dialog("open");
                                  ' . $jqdtRefreshCode . '
                               }
                  });'
            . $preventDefault
            . $returnFalse
            . '});
              ';

    if ($wrapWithJSScriptTag) {
        $result = wf_tag('script', false, '', 'type="text/javascript"')
                . $result
                . wf_tag('script', true);
    }

    return ($result);
}

/**
 * Generates tabbed UI for almost any data.
 *
 * @param $tabsDivID - ID of the main tab div
 * @param $tabsList - array of: tab ID => array('tab_options' => 'options',
 *                                              'tab_caption' => 'caption,
 *                                              'additional_data' => 'anything')
 *                    which represents the tabs itself.
 *                    Additional data can be anything, like some JS script or comments or whatever.
 * @param $tabsBody - array of: div ID => array('div_options' => 'options',
 *                                              'tab_body_data' => 'data'
 *                                              'additional_data' => 'anything')
 *                    which represents the divs with tabs data.
 *                    Additional data can be anything, like some JS script or comments or whatever.
 * @param string $mainDivOpts
 * @param string $ulOpts
 * @param bool $tabsCarouselOn
 *
 * @return string
 */
function wf_TabsGen($tabsDivID, $tabsList, $tabsBody, $mainDivOpts = '', $ulOpts = '', $tabsCarouselOn = false) {
    $result = '';

    if (!empty($tabsDivID) and ! empty($tabsList) and ! empty($tabsBody)) {
        $divOps = 'id="' . $tabsDivID . '" ' . $mainDivOpts;
        $initTabsJSStr = '$( "#' . $tabsDivID . '" ).tabs();';

        if ($tabsCarouselOn) {
            $initTabsJSStr = '$("#' . $tabsDivID . '").scrollTabs({        
                                scrollOptions: {
                                    showFirstLastArrows: false,                                    
                        	        closable: false
                                }
                             });
                             
                             // dirty hack for scrollTabsPlugin to select the very first tab
                             $( "#' . $tabsDivID . '" ).scrollTabs("option", "active", 0);
                             // another hack for Firefox
                             $(".ui-scroll-tabs-view").css("margin-bottom", "0");
                             ';
        }

        $result .= wf_tag('script', false, '', 'type="text/javascript"');
        $result .= ' $( function() { ' .
                $initTabsJSStr .
                ' } );
                  ';
        $result .= wf_tag('script', true);

        $result .= wf_tag('div', false, '', $divOps);
        $result .= wf_tag('ul', false, '', $ulOpts);

        foreach ($tabsList as $tabhref => $tabData) {
            $result .= wf_tag('li') .
                    wf_tag('a', false, '', 'href="#' . $tabhref . '" ' . $tabData['options']) .
                    $tabData['caption'] .
                    wf_tag('a', true) .
                    wf_tag('li', true) .
                    $tabData['additional_data'];
        }

        $result .= wf_tag('ul', true);

        foreach ($tabsBody as $bodyID => $bodyData) {
            $result .= wf_tag('div', false, '', 'id="' . $bodyID . '" ' . $bodyData['options']) .
                    $bodyData['body'] .
                    wf_tag('div', true) .
                    $bodyData['additional_data'];
        }

        $result .= wf_tag('div', true);
    }

    return ($result);
}

/**
 * Returns scripts and CSS links for tabs carousel plugin.
 * Be sure to add this once to a page if you planning to use wf_TabsGen function with tabsCarouselOn
 */
function wf_TabsCarouselInitLinking() {
    $result = '<link rel="stylesheet" href="modules/jsc/JQUI_ScrollTabs/style.css" type="text/css">';
    $result .= '<script type="text/javascript" src="modules/jsc/JQUI_ScrollTabs/jquery.ba-throttle-debounce.min.js"></script>';
    $result .= '<script type="text/javascript" src="modules/jsc/JQUI_ScrollTabs/jquery.mousewheel.min.js"></script>';
    $result .= '<script type="text/javascript" src="modules/jsc/JQUI_ScrollTabs/jquery.touchSwipe.min.js"></script>';
    $result .= '<script type="text/javascript" src="modules/jsc/JQUI_ScrollTabs/jquery.ui.scrolltabs.js"></script>';

    return ($result);
}

/**
 * Returns plain JS-code of 'empty' function to use for checking an empty value in JS code
 *
 * @return string
 */
function wf_JSEmptyFunc() {
    $Result = '
                function empty (mixed_var) {
                    // version: 909.322
                    // discuss at: http://phpjs.org/functions/empty
                    
                    var key;
                    if (mixed_var === "" || mixed_var === 0 || mixed_var === "0" || mixed_var === null || mixed_var === false || mixed_var === undefined ) {
                        return true;
                    }
                    
                    if (typeof mixed_var == \'object\') {
                        for (key in mixed_var) {
                            return false;
                        }                        
                        return true;
                    }                    
                    return false;
                }
              ';

    return ($Result);
}

/**
 * Returns some count of non-breaking space symbols
 * 
 * @param int $count
 * 
 * @return string
 */
function wf_nbsp($count = 1) {
    $result = '';
    for ($i = 0; $i < $count; $i++) {
        $result .= '&nbsp;';
    }
    return ($result);
}

/**
 * Returns JS onElementInserted() func which allow to make any actions for
 * dynamically created objects right after the moment of it's creation
 * elementSelector MUST be a class selector, like '.SomeMyClass'
 *
 * Source code: https://stackoverflow.com/a/38517525
 *
 * @return string
 */
function wf_JSElemInsertedCatcherFunc() {
    $Result = '
                function onElementInserted(containerSelector, elementSelector, callback) {
                    var onMutationsObserved = function(mutations) {
                        mutations.forEach(function(mutation) {                            
                            if (mutation.addedNodes.length) {
                                var elements = $(mutation.addedNodes).find(elementSelector);
                                
                                if (elements.length <= 0) {
                                    elements = $(mutation.addedNodes).closest(elementSelector);
                                }
                            
                                for (var i = 0, len = elements.length; i < len; i++) {
                                    callback(elements[i]);
                                }
                            }
                            
                            if (mutation.type == \'attributes\' && (\'.\' + $(mutation.target).attr(\'class\') == elementSelector)) {
                                callback(mutation.target);
                            }
                        });
                    };
                
                    var target = $(containerSelector)[0];
                    var config = { childList: true, subtree: true, attributes: true};
                    var MutationObserver = window.MutationObserver || window.WebKitMutationObserver;
                    var observer = new MutationObserver(onMutationsObserved);    
                    observer.observe(target, config);                    
                }
             ';

    return $Result;
}

/**
 * Renders default steps-meter progressbar
 * 
 * @param array $params as stepname=>decription
 * @param int $current
 * 
 * @return type
 */
function wf_StepsMeter($params, $current) {
    $style = wf_tag('style');
    $style .= " 
   .steps{
    min-height:90px;
    padding:30px 30px 0 30px;
    position:relative
    } 
    
    .steps .steps-container{
    background:#DDD;
    height:10px;
    width:95%;
    
    border-radius:10px ;
    -moz-border-radius:10px ;
    -webkit-border-radius:10px ;
    -ms-border-radius:10px ;
    margin:0;
    list-style:none
    }
    
    .steps .steps-container li{
    text-align:center;
    list-style:none;
    float:left
    }
    
    .steps .steps-container li .step{
    padding:0 50px
    }
    
    .steps .steps-container li .step .step-image{
    margin:-14px 0 0 0
    }
    
    .steps .steps-container li .step .step-image span{
    background-color:#DDD;
    display:block;
    width:37px;
    height:37px;
    margin:0 auto;
    border-radius:37px ;
    -moz-border-radius:37px ;
    -webkit-border-radius:37px ;
    -ms-border-radius:37px 
    }
    
    .steps .steps-container li .step .step-current{
    font-size:11px;
    font-style:italic;
    color:#999;
    margin:8px 0 0 0
    }
    
    .steps .steps-container li .step .step-description{
    font-size:13px;
    font-style:italic;
    color:#538897
    }
    
    .steps .steps-container li.activated .step .step-image span{
    background-color:#5DC177
    }
    
    .steps .steps-container li.activated .step .step-image span:after{
    background-color:#FFF;
    display:block;
    content:'';
    position:absolute;
    z-index:1;
    width:27px;
    height:27px;
    margin:5px;
    border-radius:27px ;
    -moz-border-radius:27px ;
    -webkit-border-radius:27px ;
    -ms-border-radius:27px ;
    box-shadow: 4px 4px 0px 0px rgba(0,0,0,0.15) ;
    -moz-box-shadow: 4px 4px 0px 0px rgba(0,0,0,0.15) ;
    -webkit-box-shadow: 4px 4px 0px 0px rgba(0,0,0,0.15) 
    }
    
    .steps .step-bar{
    background-color:#5DC177;
    height:10px;
    position:absolute;
    top:30px;
    border-radius:10px 0 0 10px;
    -moz-border-radius:10px 0 0 10px;
    -webkit-border-radius:10px 0 0 10px;
    -ms-border-radius:10px 0 0 10px
    }
    
    .steps .step-bar.last{
    border-radius:10px ;
    -moz-border-radius:10px ;
    -webkit-border-radius:10px ;
    -ms-border-radius:10px 
    }

  ";

    $style .= wf_tag('style', true);
    $count = 1;
    $paramsCount = sizeof($params);
    if (!empty($params)) {
        $width = round(100 / $paramsCount) - 1;
        $code = wf_tag('div', false, 'steps');
        $code .= wf_tag('ul', false, 'steps-container');
        foreach ($params as $io => $each) {
            $currentClass = ($count <= $current) ? 'activated' : '';
            $code .= wf_tag('li', false, $currentClass, 'style="width:' . $width . '%;"');
            $code .= wf_tag('div', false, 'step');
            $code .= wf_tag('div', false, 'step-image') . wf_tag('span') . wf_tag('span', true) . wf_tag('div', true);
            $code .= wf_tag('div', false, 'step-current') . $io . wf_tag('div', true);
            $code .= wf_tag('div', false, 'step-description') . $each . wf_tag('div', true);
            $code .= wf_tag('div', true);
            $code .= wf_tag('li', true);
            $count++;
        }

        $code .= wf_tag('ul', true);
        $widthBar = $width * $current;
        $code .= wf_tag('div', false, 'step-bar', 'style="width: ' . $widthBar . '%;"') . wf_tag('div', true);
        $code .= wf_tag('div', true);

        $code .= wf_CleanDiv();


        $result = $style . $code;
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
function wf_ConfirmDialog($url, $title, $alerttext, $class = '', $cancelUrl = '') {
    $result = '';
    $dialog = __($alerttext);
    $dialog .= wf_tag('br');
    $dialog .= wf_tag('center', false);
    $dialog .= wf_Link($url, __('Agree'), false, 'confirmagree');
    if ($cancelUrl) {
        $dialog .= wf_Link($cancelUrl, __('Cancel'), false, 'confirmcancel');
    }
    $dialog .= wf_tag('center', true);

    $cleanTitle = strip_tags($title);
    $result .= wf_modalAuto($title, __($cleanTitle), $dialog, $class);
    return($result);
}

/**
 * Returns code that plays some sound from existing audio file
 * 
 * @param string $url
 * 
 * @return string
 */
function wf_doSound($url) {
    $result = wf_tag('script') . "var audio = new Audio('" . $url . "'); audio.play();" . wf_tag('script', true);
    return($result);
}

/**
 * Jqeury Data tables JSON formatting class
 */
class wf_JqDtHelper {

    /**
     * Contains raw array of added grid elements
     *
     * @var array
     */
    protected $allRows = array();

    /**
     * Adds new row to elements array, dont forget unset() data in your loop, after adding new row.
     * 
     * @param array $data
     * 
     * @return void
     */
    public function addRow($data) {
        if (!empty($data)) {
            $jsonItem = array();
            foreach ($data as $io => $each) {
                $jsonItem[] = $each;
            }
            $this->allRows[] = $jsonItem;
        }
    }

    /**
     * Returns JSON acceptible for jquery data tables
     * 
     * @return string
     */
    protected function renderJson() {
        $result = array("aaData" => $this->allRows);
        $result = json_encode($result);
        return ($result);
    }

    /**
     * Renders empty page JSON data for background ajax requests
     * 
     * @return void
     */
    public function getJson() {
        die($this->renderJson());
    }

    /**
     * Extracts rendered JSON data from object
     * 
     * @return string
     */
    public function extractJson() {
        return ($this->renderJson());
    }

    /**
     * Flushes current object instance elements array
     * 
     * @return void
     */
    public function flushData() {
        $this->allRows = array();
    }

}

?>
