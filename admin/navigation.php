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
if(empty($system)) die();
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=<?=$system->config['encoding']?>">
<link rel="stylesheet" href="<?=ADMIN_PATH?>style.css" type="text/css">
</head>
<body>
<table width="100%" cellpadding="4" cellspacing="1" border="0">
<tr>
	<th style="text-align: left">&#0187; <?=__('Return to')?> ...</th>
</tr>
<tr>
	<td class="row1"><a href="<?=RCMS_ROOT_PATH?>" target="_top">... <?=__('site index')?></a></td>
</tr>
<tr>
	<td class="row1"><a href="?show=module" target="main">... <?=__('admin index')?></a></td>
</tr>
<?php
foreach($MODULES as $category => $blockdata) {
    if(!empty($blockdata[1]) && is_array($blockdata[1])) { ?>
<tr>
	<th style="text-align: left">&#0187; <?=$blockdata[0]?></th>
</tr>
<?php foreach($blockdata[1] as $module => $title) { ?>
<tr>
	<td class="row1"><a href="?show=module&id=<?=$category . '.' . $module?>" target="main"><?=$title?></a></td>
</tr>
<?php
		}
	} elseif($blockdata[0] === @$blockdata[1]) { ?>
<tr>
	<th style="text-align: left">&#0187; <a href="?show=module&id=<?=$category . '.index'?>" target="main" class="th"><?=$blockdata[0]?></a></th>
</tr>
<?php
	}
}
?>
</table>
</body>
</html>