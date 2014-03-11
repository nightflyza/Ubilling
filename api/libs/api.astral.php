<?php

/*
 *  Return web form element id
 *  @return  string
 */
function wf_InputId() {
    // I know it looks really funny. 
    // You can also get a truly random values ​​by throwing dice ;)
    $characters = '0123456789abcdefghijklmnopqrstuvwxyz';
    $result = "";
    for ($p = 0; $p < 8; $p++) {
        $result.= $characters[mt_rand(0, (strlen($characters)-1))];
    }
    return ($result);
}

/**
 *
 * Return web form body
 *
 * @param   $action action URL
 * @param   $method method: POST or GET
 * @param   $inputs inputs string to include
 * @param   $class  class for form
 * @param   $legend form legend
 * @return  string
 *
 */
function wf_Form($action,$method,$inputs,$class='',$legend='') {
    if ($class!='') {
        $form_class=' class="'.$class.'" ';
    } else {
        $form_class='';
    }
    if ($legend!='') {
        $form_legend='<legend>'.__($legend).'</legend> <br>';
    } else {
        $form_legend='';
    }
    
    $form='
        <form action="'.$action.'" method="'.$method.'" '.$form_class.'>
         '.$form_legend.'
        '.$inputs.'
        </form>
        <div style="clear:both;"></div>
        ';
    return ($form);
}
/**
 *
 * Return text input Web From element 
 *
 * @param   $name name of element
 * @param   $label text label for input
 * @param   $value current value
 * @param   $br append new line - bool
 * @param   $size input size
 * @return  string
 *
 */

function wf_TextInput($name,$label='',$value='',$br=false,$size='') {
    $inputid=wf_InputId();
    //set size
    if ($size!='') {
        $input_size='size="'.$size.'"';
    } else {
        $input_size='';
    }
    if ($br) {
        $newline='<br>';
    } else {
        $newline='';
    }
    $result='<input type="text" name="'.$name.'" value="'.$value.'" '.$input_size.' id="'.$inputid.'">'."\n";
    if ($label!='') {
    $result.=' <label for="'.$inputid.'">'.__($label).'</label>'."\n";;
    }
    $result.=$newline."\n";
    return ($result);
}

/**
 *
 * Return link form element
 *
 * @param   $url needed URL
 * @param   $title text title of URL
 * @param   $br append new line - bool
 * @param   $class class for link
 * @return  string
 *
 */

function wf_Link($url,$title,$br=false,$class='') {
    if ($class!='') {
        $link_class='class="'.$class.'"';
    } else {
        $link_class='';
    }
    if ($br) {
        $newline='<br>';
    } else {
        $newline='';
    }
    $result='<a href="'.$url.'" '.$link_class.'>'.__($title).'</a>'."\n";
    $result.=$newline."\n";
    return ($result);
}

/**
 * Return ajax loader compatible link
 *  
 * @param   $url needed URL
 * @param   $title text title of URL
 * @param   $container output container for ajax content
 * @param   $br append new line - bool
 * @param   $class class for link
 * @return  string
 */
function wf_AjaxLink($url,$title,$container,$br=false,$class='') {
    if ($class!='') {
        $link_class='class="'.$class.'"';
    } else {
        $link_class='';
    }
    if ($br) {
        $newline='<br>';
    } else {
        $newline='';
    }
    $result='<a href="#" onclick="goajax(\''.$url.'\',\''.$container.'\');" '.$link_class.'>'.$title.'</a>'."\n";
    $result.=$newline."\n";
    return ($result);
}

/**
 *
 * Return Radio  box Web From element 
 *
 * @param   $name name of element
 * @param   $label text label for input
 * @param   $value current value
 * @param   $br append new line - bool
 * @param   $checked is checked? - bool
 * @return  string
 *
 */

function wf_RadioInput($name,$label='',$value='',$br=false,$checked=false) {
    $inputid=wf_InputId();
    if ($br) {
        $newline='<br>';
    } else {
        $newline='';
    }
    if ($checked) {
        $check='checked=""';
    } else {
        $check='';
    }
    $result='<input type="radio" name="'.$name.'" value="'.$value.'"  id="'.$inputid.'" '.$check.'>'."\n";
    if ($label!='') {
    $result.=' <label for="'.$inputid.'">'.__($label).'</label>'."\n";;
    }
    $result.=$newline."\n";
    return ($result);
}


/**
 *
 * Return check box Web From element 
 *
 * @param   $name name of element
 * @param   $label text label for input
 * @param   $br append new line - bool
 * @param   $checked is checked? - bool
 * @return  string
 *
 */

function wf_CheckInput($name,$label='',$br=false,$checked=false) {
    $inputid=wf_InputId();
    if ($br) {
        $newline='<br>';
    } else {
        $newline='';
    }
    if ($checked) {
        $check='checked=""';
    } else {
        $check='';
    }
    $result='<input type="checkbox" id="'.$inputid.'" name="'.$name.'" '.$check.' />';
    if ($label!='') {
    $result.=' <label for="'.$inputid.'">'.__($label).'</label>'."\n";;
    }
    $result.=$newline."\n";
    return ($result);
}

/**
 *
 * Return textarea Web From element 
 *
 * @param   $name name of element
 * @param   $label text label for input
 * @param   $value value for element
 * @param   $br append new line - bool
 * @param   $size size in format "10x20"
 * @return  string
 *
 */
function wf_TextArea($name,$label='',$value='',$br=false,$size='') {
    $inputid=wf_InputId();
    //set columns and rows count
    if ($size!='') {
        $sizexplode=explode('x',$size);
        $input_size='cols="'.$sizexplode[0].'" rows="'.$sizexplode[1].'" ';
    } else {
        $input_size='';
    }
    if ($br) {
        $newline='<br>';
    } else {
        $newline='';
    }
    $result='<textarea name="'.$name.'" '.$input_size.' id="'.$inputid.'">'.$value.'</textarea>'."\n";
    if ($label!='') {
    $result.=' <label for="'.$inputid.'">'.__($label).'</label>'."\n";;
    }
    $result.=$newline."\n";
    return ($result);
}

/**
 *
 * Return hidden input web form element
 *
 * @param   $name name of element
 * @param   $value value for input
 * @return  string
 *
 */
function wf_HiddenInput($name,$value='') {
    $result='<input type="hidden" name="'.$name.'" value="'.$value.'">';
    return ($result);
}


/**
 *
 * Return submit web form element
 *
 * @param   $value text label for button
 * @return  string
 *
 */

function wf_Submit($value) {
    $result='<input type="submit" value="'.__($value).'">';
    return ($result);
}

/**
 *
 * Return Trigger select web form input
 *
 * @param   $name name of element
 * @param   $label text label for input
 * @param   $state selected $value for trigger
 * @param   $br append new line - bool
 * @return  string
 *
 */
function wf_Trigger($name,$label='',$state='',$br=false) {
    $inputid=wf_InputId();
    if (!$state) {
        $noflag='SELECTED';
    } else {
        $noflag='';
    }
    if ($br) {
        $newline='<br>';
    } else {
        $newline='';
    }
    $result='
           <select name="'.$name.'" id="'.$inputid.'">
                       <option value="1">'.__('Yes').'</option>
                       <option value="0" '.$noflag.'>'.__('No').'</option>
           </select>
        '."\n";
    if ($label!='') {
    $result.=' <label for="'.$inputid.'">'.__($label).'</label>'."\n";;
    }
    $result.=$newline."\n";
    return ($result);
}

/**
 *
 * Return select Web From element 
 *
 * @param   $name name of element
 * @param   $params array of elements $value=>$option
 * @param   $label text label for input
 * @param   $selected selected $value for selector
 * @param   $br append new line - bool
 * @return  string
 *
 */
function wf_Selector($name,$params,$label,$selected='',$br=false) {
    $inputid=wf_InputId();
    if ($br) {
        $newline='<br>';
    } else {
        $newline='';
    }
    $result='<select name="'.$name.'" id="'.$inputid.'">';
    if (!empty ($params)) {
        foreach ($params as $value=>$eachparam) {
             $sel_flag='';
            if ($selected!='') {
                if ($selected==$value) {
                    $sel_flag='SELECTED';
                } 
            }
            $result.='<option value="'.$value.'" '.$sel_flag.'>'.$eachparam.'</option>'."\n";
        }
    }
    
    $result.='</select>'."\n";
    if ($label!='') {
        $result.='<label for="'.$inputid.'">'.__($label).'</label>';
    }
    $result.=$newline."\n";
    return ($result);
}


/**
 *
 * Return select Web From element with auto click option
 *
 * @param   $name name of element
 * @param   $params array of elements $value=>$option
 * @param   $label text label for input
 * @param   $selected selected $value for selector
 * @param   $br append new line - bool
 * @return  string
 *
 */
function wf_SelectorAC($name,$params,$label,$selected='',$br=false) {
    $inputid=wf_InputId();
    if ($br) {
        $newline='<br>';
    } else {
        $newline='';
    }
    $result='<select name="'.$name.'" id="'.$inputid.'" onChange="this.form.submit();">';
    if (!empty ($params)) {
        foreach ($params as $value=>$eachparam) {
             $sel_flag='';
            if ($selected!='') {
                if ($selected==$value) {
                    $sel_flag='SELECTED';
                } 
            }
            $result.='<option value="'.$value.'" '.$sel_flag.'>'.$eachparam.'</option>'."\n";
        }
    }
    
    $result.='</select>'."\n";
    if ($label!='') {
        $result.='<label for="'.$inputid.'">'.__($label).'</label>';
    }
    $result.=$newline."\n";
    return ($result);
}


/**
 *
 * Return Month select Web From element 
 *
 * @param   $name name of element
 * @param   $label text label for input
 * @param   $selected selected $value for selector
 * @param   $br append new line - bool
 * @return  string
 *
 */
function wf_MonthSelector($name,$label,$selected='',$br=false) {
    $allmonth=months_array();
    $params=array();
    
    //localize months
    foreach ($allmonth as $monthnum=>$monthname) {
        $params[$monthnum]=rcms_date_localise($monthname);
    }
    
    $inputid=wf_InputId();
    if ($br) {
        $newline='<br>';
    } else {
        $newline='';
    }
    $result='<select name="'.$name.'" id="'.$inputid.'">';
    if (!empty ($params)) {
        foreach ($params as $value=>$eachparam) {
             $sel_flag='';
            if ($selected!='') {
                if ($selected==$value) {
                    $sel_flag='SELECTED';
                } 
            }
            $result.='<option value="'.$value.'" '.$sel_flag.'>'.$eachparam.'</option>'."\n";
        }
    }
    
    $result.='</select>'."\n";
    if ($label!='') {
        $result.='<label for="'.$inputid.'">'.__($label).'</label>';
    }
    $result.=$newline."\n";
    return ($result);
}

/**
 *
 * Return Year select Web From element 
 *
 * @param   $name name of element
 * @param   $label text label for input
 * @param   $br append new line - bool
 * @return  string
 *
 */
function wf_YearSelector($name,$label='',$br=false) {
    $curyear=curyear();
    $inputid=wf_InputId();
    $count=7;
    if ($br) {
        $newline='<br>';
    } else {
        $newline='';
    }
    $selector='<select name="'.$name.'">';
    for ($i=0;$i<$count;$i++) {
        $selector.='<option value="'.($curyear-$i).'">'.($curyear-$i).'</option>';
    }
    $selector.='</select>';
     if ($label!='') {
        $selector.='<label for="'.$inputid.'">'.__($label).'</label>';
    }
    $selector.=$newline;
    return($selector);
}

/**
 *
 * Check for POST have needed variables
 *
 * @param   $params array of POST variables to check
 * @return  bool
 *
 */
function wf_CheckPost($params) {
    $result=true;
    if (!empty ($params)) {
        foreach ($params as $eachparam) {
            if (isset($_POST[$eachparam])) {
                if (empty ($_POST[$eachparam])) {
                $result=false;                    
                }
            } else {
                $result=false;
            }
        }
     }
     return ($result);
   }
 
   
   
/**
 *
 * Check for GET have needed variables
 *
 * @param   $params array of GET variables to check
 * @return  bool
 *
 */
function wf_CheckGet($params) {
    $result=true;
    if (!empty ($params)) {
        foreach ($params as $eachparam) {
            if (isset($_GET[$eachparam])) {
                if (empty ($_GET[$eachparam])) {
                $result=false;                    
                }
            } else {
                $result=false;
            }
        }
     }
     return ($result);
   } 

/*
 * 
 * Construct HTML table row element
 * 
 * @param $cells table row cells
 * @param $class table row class
 * @return string
 *  
 */
   
 function wf_TableRow($cells,$class='') {
    if ($class!='') {
        $rowclass='class="'.$class.'"';
    } else {
        $rowclass='';
    }
    $result='<tr '.$rowclass.'>'.$cells.'</tr>'."\n";
    return ($result);
 }

 
 /*
 * 
 * Construct HTML table cell element
 * 
 * @param $data table cell data
 * @param $width width of cell element
 * @param $class table cell class
 * @param $customkey table cell custom param
 * @return string
 *  
 */
   
 function wf_TableCell($data,$width='',$class='',$customkey='') {
    if ($width!='') {
        $cellwidth='width="'.$width.'"';
    } else {
        $cellwidth='';
    }
    if ($class!='') {
        $cellclass='class="'.$class.'"';
    } else {
        $cellclass='';
    }
    if ($customkey!='') {
        $customkey=$customkey;
    } else {
        $customkey='';
    }
    $result='<td '.$cellwidth.' '.$cellclass.' '.$customkey.'>'.$data.'</td>'."\n";
    return ($result);
 }
 
 /*
 * 
 * Construct HTML table body
 * 
 * @param $rows table rows data
 * @param $width width of cell element
 * @param $border table border width
 * @param $class table cell class
 * @return string
 *  
 */
   
 function wf_TableBody($rows, $width='',$border='0',$class='') {
    if ($width!='') {
        $tablewidth='width="'.$width.'"';
    } else {
        $tablewidth='';
    }
    if ($class!='') {
        $tableclass='class="'.$class.'"';
    } else {
        $tableclass='';
    }
    
    if ($border!='') {
        $tableborder='border="'.$border.'"';
    } else {
        $tableborder='';
    }
    
    $result='
        <table '.$tablewidth.' '.$tableborder.' '.$tableclass.' >
            '.$rows.'
        </table>
        ';
    return ($result);
 }
 
 
 /*
 * 
 * Returns JS confirmation url 
 * 
 * @param $url URL if confirmed
 * @param $title link title
 * @param $alerttext alert text
 * @return string
 *  
 */
 function wf_JSAlert($url,$title,$alerttext) {
    $result='<a  onclick="if(!confirm(\'' . __($alerttext) . '\')) { return false;}" href="'.$url.'">'.$title.'</a>';
    return ($result);
}


 /*
 * 
 * Returns filled paginator
 * 
 * @param $total Total items count
 * @param $perpage Per page items count
 * @param $current current page
 * @param $link module link which use paginator
 * @param $class page links class
 * @return string
 *  
 */
function wf_pagination($total, $perpage, $current, $link,$class=''){
    if ($class!='') {
        $pageclass='class="'.$class.'"';
    } else {
        $pageclass='';
    }
    
    $return = '';
    $link = preg_replace("/((&amp;|&)page=(\d*))/", '', $link);
    if(!empty($perpage)) {
        $pages = ceil($total/$perpage);
        if($pages != 1){
            $c = 1;
            while($c <= $pages){
                if($c != $current) $return .= ' ' . '<a href="' . $link . '&amp;page=' . $c . '" '.$pageclass.'>' . $c . '</a> ';
                else $return .= ' ' . '<a href="#" '.$pageclass.' style="color: #ff0000;">' . $c . '</a> ';
                $c++;
            }
        }
    }
    return $return;
}


 /*
 * 
 * Returns image body
 * 
 * @param $url image url
 * @return string
 *  
 */

function wf_img($url,$title='') {
    if ($title!='') {
        $imgtitle='title="'.$title.'"';
    } else {
        $imgtitle='';
    }
    $result='<img src="'.$url.'" '.$imgtitle.' border="0">';
    return ($result);
}



 /*
 * 
 * Returns link that calls new modal window
 * 
 * @param $link link text
 * @param $title modal window title
 * @param $content modal window content
 * @param $linkclass link class
 * @param $width modal window width 
 * @param $height modal window height
 * @return string
 *  
 */

function wf_modal($link, $title, $content, $linkclass = '', $width = '',$height='') {

    $wid = wf_inputid();
//    $content=  str_replace("'", '', $content);
//    $content=  str_replace('"', '', $content);    
//    $content=  str_replace('’', '', $content);   
     
    
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
                        height: '.$height.',
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
        '.$content.'
        </p>
</div>

<a href="#" id="opener_' . $wid . '" ' . $link_class . '>' . $link . '</a>
';

    return($dialog);
}


 /*
 * 
 * Returns calendar widget
 * 
 * @param $field field name to insert calendar
 * @param $extControls extended year and month controls
 * 
 * @return string
 *  
 */
function wf_DatePicker($field,$extControls=false) {
    $inputid=wf_InputId();
    $curlang=curlang();
    if ($extControls) {
        $extControls=',
                        changeMonth: true,
                        changeYear: true';
    } else {
        $extControls='';
    }
    $result='<script>
	$(function() {
		$( "#'.$inputid.'" ).datepicker({
			showOn: "both",
			buttonImage: "skins/icon_calendar.gif",
			buttonImageOnly: true,
                        dateFormat:  "yy-mm-dd",
                        showAnim: "slideDown"'.$extControls.'
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
                
	$.datepicker.setDefaults($.datepicker.regional[\''.$curlang.'\']);
      

	});
	</script>
        
        <input type="text" id="'.$inputid.'" name="'.$field.'" size="10">
        ';
    return($result);
}

 /*
 * 
 * Returns calendar widget with preset date
 * 
 * @param $field field name to insert calendar
 * @return string
 *  
 */
function wf_DatePickerPreset($field,$date,$extControls=false) {
    $inputid=wf_InputId();
    $curlang=curlang();
    if ($extControls) {
        $extControls=',
                        changeMonth: true,
                        changeYear: true';
    } else {
        $extControls='';
    }
    $result='<script>
	$(function() {
		$( "#'.$inputid.'" ).datepicker({
			showOn: "both",
			buttonImage: "skins/icon_calendar.gif",
			buttonImageOnly: true,
                        dateFormat:  "yy-mm-dd",
                        showAnim: "slideDown"'.$extControls.'
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
                
	$.datepicker.setDefaults($.datepicker.regional[\''.$curlang.'\']);
      

	});
	</script>
        
        <input type="text" id="'.$inputid.'" name="'.$field.'" value="'.$date.'" size="10">
        ';
    return($result);
}


 /*
 * 
 * Returns FullCalendar widget
 * 
 * @param $data prepeared data to show
 * @return string
 *  
 */
function wf_FullCalendar($data) {
    
    $elementid=wf_InputId();
   
    $calendar="<script type='text/javascript'>

	$(document).ready(function() {
	
		var date = new Date();
		var d = date.getDate();
		var m = date.getMonth();
		var y = date.getFullYear();
         
		$('#".$elementid."').fullCalendar({
			editable: false,
                        theme: true,
                        weekends: true,
                        monthNamesShort: [
                        '".  rcms_date_localise('Jan')."',
                        '".  rcms_date_localise('Feb')."',
                        '".  rcms_date_localise('Mar')."',
                        '".  rcms_date_localise('Apr')."',
                        '".  rcms_date_localise('May')."',
                        '".  rcms_date_localise('Jun')."',
                        '".  rcms_date_localise('Jul')."',
                        '".  rcms_date_localise('Aug')."',
                        '".  rcms_date_localise('Sep')."',
                        '".  rcms_date_localise('Oct')."',
                        '".  rcms_date_localise('Nov')."',
                        '".  rcms_date_localise('Dec')."'
                        ],

                        monthNames: [
                        '".  rcms_date_localise('January')."',
                        '".  rcms_date_localise('February')."',
                        '".  rcms_date_localise('March')."',
                        '".  rcms_date_localise('April')."',
                        '".  rcms_date_localise('May')."',
                        '".  rcms_date_localise('June')."',
                        '".  rcms_date_localise('July')."',
                        '".  rcms_date_localise('August')."',
                        '".  rcms_date_localise('September')."',
                        '".  rcms_date_localise('October')."',
                        '".  rcms_date_localise('November')."',
                        '".  rcms_date_localise('December')."'
                        ],
                        
                        dayNamesShort: [
                        '".  rcms_date_localise('Sun')."',
                        '".  rcms_date_localise('Mon')."',
                        '".  rcms_date_localise('Tue')."',
                        '".  rcms_date_localise('Wed')."',
                        '".  rcms_date_localise('Thu')."',
                        '".  rcms_date_localise('Fri')."',
                        '".  rcms_date_localise('Sat')."'
                        ],
                        
                        dayNames: [
                        '".  rcms_date_localise('Sunday')."',
                        '".  rcms_date_localise('Monday')."',
                        '".  rcms_date_localise('Tuesday')."',
                        '".  rcms_date_localise('Wednesday')."',
                        '".  rcms_date_localise('Thursday')."',
                        '".  rcms_date_localise('Friday')."',
                        '".  rcms_date_localise('Saturday')."'
                        ],
                        
                        buttonText: {
                            today:    '".__('Today')."',
                            month:    '".__('Month')."',
                            week:     '".__('Week')."',
                            day:      '".__('Day')."'
                        },

                        header: {
				left: 'prev,next today',
				center: 'title',
				right: 'month,basicWeek,basicDay'
			},
                        
			events: [
				".$data."
			
			]
                        
		});
		
	});

</script>
<div id='".$elementid."'></div>
";
    
return($calendar);
}

function wf_Plate($content, $width='', $height='', $class='') {
    if ($width!='') {
        $width='width: '.$width.';';
    } 
    
    if ($height!='') {
        $height='height: '.$height.';';
    } 
    
       
    if ($class!='') {
        $class='class="'.$class.'"';
    } 
    
    $result='
        <div style="'.$width.' '.$height.' float: left;" '.$class.'>
		'.$content.'
        </div>
        ';
    return ($result);
 }
 
 
 /*
 * 
 * Returns some count of delimiters
 * 
 * @param $count count of delimited rows
 * @return string
 *  
 */
 function wf_delimiter($count=1) {
     $result='';
     for($i=0;$i<=$count;$i++) {
         $result.='<br />';
     }
     return ($result);
 }
 
 
 /*
 * 
 * Returns some html styled tag
 * 
 * @param $tag HTML tag entity
 * @param $closed tag is closing?
 * @param $class tag styling class
 * @param $options tag extra options
 * @return string
 *  
 */
 function wf_tag($tag,$closed=false,$class='',$options='') {
     if (!empty($class)) {
         $tagclass=' class="'.$class.'"';
     } else {
         $tagclass='';
     }
     
     if ($closed) {
         $tagclose='/';
     } else {
         $tagclose='';
     }
     
     if ($options!='') {
         $tagoptions=$options;
     } else {
         $tagoptions='';
     }
     
     $result='<'.$tagclose.$tag.$tagclass.' '.$tagoptions.'>';
     return ($result);
 }
 
 
 /*
  * Constructs ajax loader 
  * 
  * @return string
  */    
     
  function wf_AjaxLoader() {
      $result='
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
       contentElem.innerHTML = \'<img src=skins/ajaxloader.gif>\';
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
                    contentElem.innerHTML = \''.__('Error').'\';
                }
            }
 
        }
        myrequest.send();
    }
    </script>
          ';
      return ($result);
  } 

  
   /*
 * 
 * Returns new opened modal window with some content
 * 
 * @param $title modal window title
 * @param $content modal window content
 * @param $width modal window width 
 * @param $height modal window height
 * @return string
 *  
 */

function wf_modalOpened($title, $content, $width = '',$height='') {

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
                        height: '.$height.',
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
        '.$content.'
        </p>
</div>
';

    return($dialog);
}


     /*
       * Returns Chart source
       * 
       * @param $data      - CSV formatted data
       * @param $widht     - graph width in pixels
       * @param $height    - graph height in pixels
       * @param $errorbars - display error bars around data series
       * 
       * @return string
       */
      function wf_Graph($data,$width='500',$height='300',$errorbars=false) {
          $randomId=wf_InputId();
          $objectId='graph_'.$randomId;
          $data=trim($data);
          $data=  explodeRows($data);
          $cleandata='';
          if ($errorbars) {
              $errorbars='true';
          } else {
              $errorbars='false';
          }
          if (!empty($data)) {
              foreach ($data as $eachrow) {
                  $cleandata.='"'.trim($eachrow).'\n" +'."\n";
              }
              $cleandata=mb_substr($cleandata, 0, -2,'utf-8');
          }
          
          $result=  wf_tag('div', false, '', 'id="'.$randomId.'" style="width:'.$width.'px; height:'.$height.'px;"').wf_tag('div',true);
          $result.= wf_tag('script', false, '', 'type="text/javascript"');
          $result.= $objectId.' = new Dygraph(';
          $result.= 'document.getElementById("'.$randomId.'"),'."\n";
          $result.= $cleandata;
          
          $result.=', {  errorBars: '.$errorbars.' }'."\n";
            
          $result.=');';
          $result.= wf_tag('script', true);
          
          return ($result);
      }

    function wf_ColPicker($name, $label='', $value='', $br=false, $size='') {
        $id  = wf_InputId();
        $css = '
            <link rel="stylesheet" href="modules/jsc/colpick/colpick.css" type="text/css"/>';
        $js  = '
            <script src="modules/jsc/colpick/colpick.js" type="text/javascript"></script>
            <script type="text/javascript">
            $(document).ready(function() {
                $("#' . $id . '").colpick({
                    colorScheme: "light",
                    layout: "hex",
                    submit: true,
                    color:  "' . ( !empty($value) ? $value : "#f57601" ) . '",
                    onSubmit: function(hsb,hex,rgb,el) {
                        var hex_str = $("div.colpick_hex_field > input").val();
                        $(el).val("#" + hex_str);
                        $(el).colpickHide();
                    }
                });
            });
            </script>
        ';
        $size = ( !empty($size) ) ? 'size="' . $size . '"' : null;
        $result  = '<input type="text" name="' . $name . '" value="' . $value . '" id="' . $id . '" ' . $size . '>'."\n";
        $result .= ( !empty($label) ) ? '<label for="' . $id . '">' . __($label) . '</label>' : null ;
        $result .= ( !empty($br)    ) ? '<br>' : null;
        $result .= "\n";
        return $css . $js . $result;
    }

?>