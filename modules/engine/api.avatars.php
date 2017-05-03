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
//config section
$config_avatars = parse_ini_file(CONFIG_PATH . 'avatars.ini');
$avatars_enabled=parse_ini_file(CONFIG_PATH . 'disable.ini');
$avatars_path=DATA_PATH.'avatars/';
$avatar_h=$config_avatars['avatars_h'];
$avatar_w=$config_avatars['avatars_w'];
$avatar_size=$config_avatars['avatars_size'];
//Avatars API functions
//function thats shows avatars requitements
function show_avatar_requirements()
{
global $avatars_path;
global $avatar_h;
global $avatar_w;
global $avatar_size;
$requirements=__('Your avatar must be less than ').$avatar_size.__(' bytes in size'). __(', have resolution at ').$avatar_w.'x'.$avatar_h.__(' and type png, jpg or gif');
return $requirements;
}
// function to show avatar for such user
function show_avatar($user)
{
global $avatars_path;
global $avatar_h;
global $avatar_w;
global $avatars_enabled;
$result='';
if (file_exists($avatars_path.$user.'.img')) 
{
	$result='<img src="'.$avatars_path.$user.'.img">';
}
if (!file_exists($avatars_path.$user.'.img')) 
{
	$result='<img src="'.$avatars_path.'noavatar.img">';
}

if ($user=="guest")
{
        $result="";
}

if (isset($avatars_enabled['avatar.control'])) {
$result="";
}

return $result;
}
// function to upload avatar
function upload_avatar()
{
global $avatars_path;
global $system;
global $avatar_h;
global $avatar_w;
if ((isset($_POST['upload_avatar'])) AND ($_POST['upload_avatar']=="true") AND (is_images($_FILES['avatar']['name'])))
{	
$avarez=getimagesize($_FILES['avatar']['tmp_name']);
$uploadfile = $avatars_path . $system->user['username'].'.img';
if (($avarez[0]<="$avatar_w") AND ($avarez[1]<="$avatar_h")) {
if ((move_uploaded_file($_FILES['avatar']['tmp_name'], $uploadfile))) {
show_window(__('Result'),__('Avatar filesucefully uploaded'),'center');
$config_ext = parse_ini_file(CONFIG_PATH . 'adminpanel.ini');
if($config_ext['chmod_on'])  {
chmod($uploadfile, octdec($config_ext['chmod']));
	}
   	return $_FILES['avatar']['name'];
	}
} 
else {
show_window(__('Result'),__('Your avatar don\'t meet our requirements'),'center');
}
}
}
// Function that shows form to upload avatar
function avatar_upload_box()
{
global $avatar_size;
$form='<form enctype="multipart/form-data" action="" method="POST">
                <input type="hidden" name="upload_avatar" value="true">
       <input type="hidden" name="MAX_FILE_SIZE" value="'.$avatar_size.'" />
       '.__('Select avatar from your HDD').': <input name="avatar" type="file"/>
    <input type="submit" value="'.__('Upload avatar').'" />
</form>';
return $form;
}
//function for check image type
// NB: функция вобще стремная надо будет на досуге переписать, но ломает страшно
function is_images($filename)
{
$ext=strtolower(substr(strrev($filename),0,4));
if (($ext=='gpj.') OR ($ext=='gnp.') OR ($ext=='fig.') OR ($ext=='gepj'))
{
        return true;
}
else
{
        show_window(__('Error'),'<center><b>'.__('Your avatar is not an image or is corrupted').'</b></center>');
        return false;
}
}

?>
