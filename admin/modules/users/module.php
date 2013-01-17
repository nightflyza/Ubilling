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
$MODULES[$category][0] = __('Users configuration');
if($system->checkForRight('USERS')) {
    $MODULES[$category][1]['fields'] = __('Additional profile fields');
    $MODULES[$category][1]['profiles'] = __('Manage users');
	$MODULES[$category][1]['bans'] = __('Manage bans');
}
?>