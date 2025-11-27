<?php

if ((cfr('TAGS')) OR ( cfr('TAGSDIR'))) {
    $alter_conf = $ubillingConfig->getAlter();

    if (!ubRouting::checkGet('username')) {
        if (cfr('TAGSDIR')) {
//new tag type creation
            if (ubRouting::checkPost('addnewtag', false)) {
                if (ubRouting::post('newtext')) {
                    stg_add_tagtype();
                    ubRouting::nav('?module=usertags');
                } else {
                    show_error(__('Required fields'));
                }
            }

//if someone deleting tagtype
            if (ubRouting::checkGet('delete', false)) {
                stg_delete_tagtype(ubRouting::get('delete', 'int'));
                ubRouting::nav('?module=usertags');
            }

//if someone wants to edit tagtype
            if (ubRouting::checkGet('edit', false)) {
                $tagtypeid = ubRouting::get('edit', 'int');

                if (ubRouting::checkPost('edittagcolor', false)) {
                    simple_update_field('tagtypes', 'tagcolor', ubRouting::post('edittagcolor', 'mres'), "WHERE `id`='" . $tagtypeid . "'");
                    simple_update_field('tagtypes', 'tagsize', ubRouting::post('edittagsize', 'int'), "WHERE `id`='" . $tagtypeid . "'");
                    simple_update_field('tagtypes', 'tagname', ubRouting::post('edittagname', 'mres'), "WHERE `id`='" . $tagtypeid . "'");
                    log_register('TAGTYPE CHANGE [' . $tagtypeid . ']');
                    ubRouting::nav('?module=usertags');
                }

                //form construct
                $tagpriorities = array('1' => '1', '2' => '2', '3' => '3', '4' => '4', '5' => '5', '6' => '6');
                $tagtypedata = stg_get_tagtype_data($tagtypeid);
                $editinputs = wf_ColPicker('edittagcolor', __('Color'), $tagtypedata['tagcolor'], true, '20');
                $editinputs .= wf_TextInput('edittagname', 'Text', $tagtypedata['tagname'], true, 20);
                $editinputs .= wf_Selector('edittagsize', $tagpriorities, 'Priority', $tagtypedata['tagsize'], true);
                $editinputs .= wf_Submit('Save');
                $editform = wf_Form('', 'POST', $editinputs, 'glamour');

                show_window(__('Edit') . ' ' . __('Tag') . ': ' . $tagtypedata['tagname'], $editform);
                show_window('', wf_BackLink('?module=usertags', 'Back', true));
            }

//show available tagtypes
            if (!ubRouting::checkGet('edit', false)) {
                show_window(__('Tag types'), stg_show_tagtypes());
            }
        } else {
            show_error(__('Access denied'));
        }
    } else {
        if (cfr('TAGS')) {
//per user actions
            $uname = ubRouting::get('username');
//tag assign
            if (ubRouting::checkPost('tagselector', false)) {
                //reset user if required
                if ($alter_conf['RESETONTAGCHANGE']) {
                    $billing->resetuser($uname);
                    log_register('RESET (' . $uname . ')');
                }
                if (!$alter_conf['CEMETERY_ENABLED']) {
                    //normal tag addition
                    stg_add_user_tag($uname, ubRouting::post('tagselector', 'int'));
                    ubRouting::nav('?module=usertags&username=' . $uname);
                } else {
                    //cemetary tags protection
                    if (ubRouting::post('tagselector') != $alter_conf['DEAD_TAGID']) {
                        stg_add_user_tag($uname, ubRouting::post('tagselector', 'int'));
                        ubRouting::nav('?module=usertags&username=' . $uname);
                    } else {
                        show_warning(__('This tag type is protected by cemetery'));
                    }
                }
            }
// tag deletion
            if (ubRouting::checkGet('deletetag', false)) {
                //reset user if needed
                if ($alter_conf['RESETONTAGCHANGE']) {
                    $billing->resetuser($uname);
                    log_register("RESET (" . $uname . ")");
                }

                if (!$alter_conf['CEMETERY_ENABLED']) {
                    //normal tag deletion
                    stg_del_user_tag(ubRouting::get('deletetag', 'int'));
                    ubRouting::nav('?module=usertags&username=' . $uname);
                } else {
                    //cemetary tags protection
                    $tagDelData = stg_get_tag_data(ubRouting::get('deletetag'));
                    @$tagDelType = $tagDelData['tagid'];
                    if ($tagDelType != $alter_conf['DEAD_TAGID']) {
                        stg_del_user_tag(ubRouting::get('deletetag'));
                        ubRouting::nav('?module=usertags&username=' . $uname);
                    } else {
                        show_warning(__('This tag type is protected by cemetery'));
                    }
                }
            }

            show_window(__('Tags'), stg_show_user_tags($uname, true,true));
            stg_tagadd_selector();
            stg_tagdel_selector($uname);
            show_window('', web_UserControls($uname));
        } else {
            show_error(__('Access denied'));
        }
    }
} else {
    show_error(__('Access denied'));
}