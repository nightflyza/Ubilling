<?php

/*
 *  Return web form element id
 *  @return  string
 */
function la_InputId() {
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
function la_Form($action,$method,$inputs,$class='',$legend='') {
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

function la_TextInput($name,$label='',$value='',$br=false,$size='') {
    $inputid=la_InputId();
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
        $result.=' <label for="' . $inputid . '">' . __($label) . '</label>' . "\n";
        ;
    }
    $result.=$newline . "\n";
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

function la_Link($url,$title,$br=false,$class='') {
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

function la_RadioInput($name,$label='',$value='',$br=false,$checked=false) {
    $inputid=la_InputId();
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

function la_CheckInput($name,$label='',$br=false,$checked=false) {
    $inputid=la_InputId();
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
function la_TextArea($name,$label='',$value='',$br=false,$size='') {
    $inputid=la_InputId();
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
function la_HiddenInput($name,$value='') {
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

function la_Submit($value) {
    $result='<input type="submit" value="'.__($value).'">';
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
function la_Selector($name,$params,$label,$selected='',$br=false) {
    $inputid=la_InputId();
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
 * Return Month select Web From element 
 *
 * @param   $name name of element
 * @param   $label text label for input
 * @param   $selected selected $value for selector
 * @param   $br append new line - bool
 * @return  string
 *
 */
function la_MonthSelector($name,$label,$selected='',$br=false) {
    $allmonth=months_array();
    $params=array();
    
    //localize months
    foreach ($allmonth as $monthnum=>$monthname) {
        $params[$monthnum]=rcms_date_localise($monthname);
    }
    
    $inputid=la_InputId();
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
function la_YearSelector($name,$label='',$br=false) {
    $curyear=curyear();
    $inputid=la_InputId();
    $count=5;
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
function la_CheckPost($params) {
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
function la_CheckGet($params) {
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
   
 function la_TableRow($cells,$class='') {
    if ($class!='') {
        $rowclass='class="'.$class.'"';
    } else {
        $rowclass='';
    }
    $result='<tr '.$rowclass.'>'.$cells.'</tr>'."\n";
    return ($result);
 }

 
 /**
 * Construct HTML table cell element
 * 
 * @param $data table cell data
 * @param $width width of cell element
 * @param $class table cell class
 * @param $customkey table cell custom param
 * @return string
 *  
 */
   
 function la_TableCell($data,$width='',$class='',$customkey='') {
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
 
 /**
 * Construct HTML table body
 * 
 * @param $rows table rows data
 * @param $width width of cell element
 * @param $border table border width
 * @param $class table cell class
 * @return string
 *  
 */
   
 function la_TableBody($rows, $width='',$border='0',$class='') {
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
 * Returns image body
 * 
 * @param $url image url
 * @return string
 *  
 */

function la_img($url,$title='') {
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
 * Returns some count of delimiters
 * 
 * @param $count count of delimited rows
 * @return string
 *  
 */
 function la_delimiter($count=1) {
     $result='';
     for($i=0;$i<=$count;$i++) {
         $result.='<br />';
     }
     return ($result);
 }
 
 /**
 * Returns some html styled tag
 * 
 * @param $tag HTML tag entity
 * @param $closed tag is closing?
 * @param $class tag styling class
 * @param $options tag extra options
 * @return string
 *  
 */
 function la_tag($tag,$closed=false,$class='',$options='') {
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
 * 
 * Returns calendar widget with preset date
 * 
 * @param $field field name to insert calendar
 * @return string
 *  
 */
function la_DatePickerPreset($field,$date) {
    $inputid=la_InputId();
    $us_config=  zbs_LoadConfig();
    $curlang=$us_config['lang'];

    $result='<script>
	$(function() {
		$( "#'.$inputid.'" ).datepicker({
			showOn: "both",
                        buttonImage: "iconz/icon_calendar.gif",
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
                
	$.datepicker.setDefaults($.datepicker.regional[\''.$curlang.'\']);
      

	});
	</script>
        
        <input type="text" id="'.$inputid.'" name="'.$field.'" value="'.$date.'" size="10">
        ';
    return($result);
}



function la_modal($link, $title, $content, $linkclass = '', $width = '',$height='') {

    $wid = la_inputid();
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

?>