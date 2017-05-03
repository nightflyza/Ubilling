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
global $skin;
$this->registerModule($module, 'main', __('Index'), 'ReloadCMS Team', array(
    'GENERAL' => __('Right to manage modules, edit configuration and upload files'),
    'GENERAL-M' => __('Right to send e-mails to users and manage feedback requests'),
    'UPLOAD' => __('Right to upload files'),
));
$skin['menu_point']['index-menus'] = __('Menu modules in index');

$this->registerNavModifier('module', '_nav_modifier_module_m', '_nav_modifier_module_h');

function _nav_modifier_module_m($input){
	global $system;
	if(!empty($system->modules['main'][$input])){
		return array('?module=' . $input, $system->modules['main'][$input]['title']);
	} else {
		return false;
	}
}

function _nav_modifier_module_h(){
	return __('This modifier is used to create links to modules, just type module\'s ID after ":". Default title will be localised name of module.');
}
?>