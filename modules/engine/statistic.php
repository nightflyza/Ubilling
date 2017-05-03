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
function statistic_register(){
    global $system;
    
    return true;    
}

function statistic_get(){
        
		return (false);
        
}

function striptags_array(&$array){
    foreach ($array as $key => $value) {
        if(is_array($array[$key])) {
        	striptags_array($array[$key]);
        } else {
        	$array[$key] = strip_tags($value);
        }
    }
    return true;
}

function statistic_clean(){
  
}
                                  

?>