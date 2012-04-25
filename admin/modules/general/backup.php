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

if (!empty($_POST['backupit'])) {
	if(!empty($_POST['gzip'])) $suffix = '.gz';
	else $suffix = '';
    $bkupfilename = RCMS_ROOT_PATH . 'backups/backup_'.date('H-i-s_d.m.Y').'.tar' . $suffix;
    $backup = new tar();
    $backup->isGzipped = !empty($_POST['gzip']);
    $backup->filename = $bkupfilename;
    $path = getcwd();
    chdir(RCMS_ROOT_PATH);
    $backup->addDirectory('config', true);
    $backup->addDirectory('content', true);
    chdir($path);
    $backup->saveTar();
    rcms_showAdminMessage(__('Backup complete') . ' (' . basename($bkupfilename) . ')');
}

// Interface generation
$frm =new InputForm ('', 'post', __('Backup data'));
$frm->addbreak( __('Backup data'));
$frm->hidden('backupit', '1');
$frm->addrow(__('To backup all your data from directories "config" and "content" press "Create backup" button. Speed of backup creation depends on size of your site. In order to be more secure we do not provide any backups management from there. You must download or delete backups using FTP or another way to reach /backups/ folder, because HTTP access for it was forbidden.'));
$frm->addrow(__('Pack file with gzip (uncheck if you experience problems)'), $frm->checkbox('gzip', '1', '', true));
$frm->show();
?>
