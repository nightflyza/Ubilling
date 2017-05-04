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
rcms_loadAdminLib('ucm');

////////////////////////////////////////////////////////////////////////////////
// Menus control                                                              //
////////////////////////////////////////////////////////////////////////////////
if(!empty($_POST['delete']) && is_array($_POST['delete'])) {
    $msg = '';
    foreach ($_POST['delete'] as $id => $cond){
        if($cond){
            if(ucm_delete($id)) {
                $msg .= __('Module removed')  . ': ' . $id . '<br />';
            } else {
                $msg .= __('Error occurred')  . ': ' . $id . '<br />';
            }
        }
    }
    rcms_showAdminMessage($msg);
    unset($_POST['edit']);
} elseif (!empty($_POST['newsave'])) {
    if(ucm_create($_POST['id'], $_POST['title'], $_POST['text'], $_POST['align'])){
        rcms_showAdminMessage(__('Module created'));
    } else {
        rcms_showAdminMessage(__('Error occurred'));
    }
} elseif (!empty($_POST['edit']) && !empty($_POST['save'])) {
    if(ucm_change($_POST['edit'], $_POST['id'], $_POST['title'], $_POST['text'], $_POST['align'])){
        rcms_showAdminMessage(__('Module updated'));
        $_POST['edit'] = $_POST['id'];
    } else {
        rcms_showAdminMessage(__('Error occurred'));
    }
}

////////////////////////////////////////////////////////////////////////////////
// Interface generation                                                       //
////////////////////////////////////////////////////////////////////////////////
if(!empty($_POST['new'])){
    $frm = new InputForm ('', 'post', __('Submit'));
    $frm->addmessage('<a href="">&lt;&lt;&lt; ' . __('Back') . '</a>');
    $frm->addbreak(__('Create menu'));
    $frm->hidden('newsave', '1');
    $frm->addrow('<abbr title="' . __('Use only small Latin letters and digits') . '">' . __('MenuID') . '</abbr>', $frm->text_box('id', ''));
    $frm->addrow(__('Title'), $frm->text_box('title', ''));
    $frm->addrow(__('Alignment'), $frm->select_tag('align', array('center' => __('Center'), 'left' => __('Left'), 'right' => __('Right'), 'justify' => __('Justify'))));
    $frm->addrow(__('Text') . '<br>' .  __('All HTML is allowed in this field and line breaks will not be transformed to &lt;br&gt; tags!'), $frm->textarea('text', '', 70, 25), 'top');
    $frm->show();
} elseif(!empty($_POST['edit'])){
    if($menu = ucm_get($_POST['edit'])){
        $frm = new InputForm ("", "post", __('Submit'));
        $frm->addmessage('<a href="">&lt;&lt;&lt; ' . __('Back') . '</a>');
        $frm->addbreak(__('Menu editing'));
        $frm->hidden('edit', $_POST['edit']);
        $frm->hidden('save', '1');
        $frm->addrow('<abbr title="' . __('Use only small Latin letters and digits') . '">' . __('MenuID') . '</abbr>', $frm->text_box('id', $_POST['edit']));
        $frm->addrow(__('Title'), $frm->text_box('title', $menu[0]));
        $frm->addrow(__('Alignment'), $frm->select_tag('align', array('center' => __('Center'), 'left' => __('Left'), 'right' => __('Right'), 'justify' => __('Justify')), $menu[2]));
        $frm->addrow(__('Text') . '<br>' . __('All HTML is allowed in this field and line breaks will not be transformed to &lt;br&gt; tags!'), $frm->textarea('text', $menu[1], 70, 25), 'top');
        $frm->show();
    } else rcms_showAdminMessage(__('Cannot open menu for editing'));
} else {
    $frm = new InputForm ('', 'post', __('Create menu')); $frm->hidden('new', '1'); $frm->show();
    $frm = new InputForm ('', 'post', __('Submit'), __('Reset'));
    $frm->addbreak(__('User-Created-Menus'));
    $menus = ucm_list();
    foreach ($menus as $id => $menu){
        $frm->addrow(__('Menu module') . ': "ucm:' . $id . '", ' . __('Title') . ': ' . $menu[0],
            $frm->checkbox('delete[' . $id . ']', '1', __('Delete')) . ' ' .
            $frm->radio_button('edit', array($id => __('Edit')))
        );
    }
    $frm->show();
}
?>