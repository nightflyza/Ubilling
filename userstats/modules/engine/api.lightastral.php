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

?>