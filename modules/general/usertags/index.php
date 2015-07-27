<?php

if (cfr('TAGS')) {
    $alter_conf = $ubillingConfig->getAlter();

    if (!isset($_GET['username'])) {
//new tag type creation
        if (isset($_POST['addnewtag'])) {
            if (wf_CheckPost(array('newtext'))) {
                stg_add_tagtype();
                rcms_redirect("?module=usertags");
            } else {
                show_window(__('Error'), __('Required fields'));
            }
        }

//if someone deleting tagtype
        if (isset($_GET['delete'])) {
            stg_delete_tagtype($_GET['delete']);
            rcms_redirect("?module=usertags");
        }

//if someone wants to edit tagtype
        if (isset($_GET['edit'])) {
            $tagtypeid = vf($_GET['edit'], 3);

            if (isset($_POST['edittagcolor'])) {
                simple_update_field('tagtypes', 'tagcolor', $_POST['edittagcolor'], "WHERE `id`='" . $tagtypeid . "'");
                simple_update_field('tagtypes', 'tagsize', $_POST['edittagsize'], "WHERE `id`='" . $tagtypeid . "'");
                simple_update_field('tagtypes', 'tagname', $_POST['edittagname'], "WHERE `id`='" . $tagtypeid . "'");
                log_register("TAGTYPE CHANGE [" . $tagtypeid . ']');
                rcms_redirect("?module=usertags");
            }

            //form construct
            $tagpriorities = array('1' => '1', '2' => '2', '3' => '3', '4' => '4', '5' => '5', '6' => '6');
            $tagtypedata = stg_get_tagtype_data($tagtypeid);
            $editinputs = wf_ColPicker('edittagcolor', __('Color'), $tagtypedata['tagcolor'], true, '20');
            $editinputs.=wf_TextInput('edittagname', 'Text', $tagtypedata['tagname'], true, 20);
            $editinputs.=wf_Selector('edittagsize', $tagpriorities, 'Priority', $tagtypedata['tagsize'], true);
            $editinputs.=wf_Submit('Save');
            $editform = wf_Form('', 'POST', $editinputs, 'glamour');

            show_window(__('Edit'), $editform);
            show_window('', wf_Link("?module=usertags", 'Back', true, 'ubButton'));
        }

//show available tagtypes
        show_window(__('Tag types'), stg_show_tagtypes());
    } else {
//per user actions
        $uname = $_GET['username'];
//tag assign
        if (isset($_POST['tagselector'])) {
            //reset user if required
            if ($alter_conf['RESETONTAGCHANGE']) {
                $billing->resetuser($uname);
                log_register("RESET User (" . $uname . ")");
            }
            if (!$alter_conf['CEMETERY_ENABLED']) {
                //normal tag addition
                stg_add_user_tag($uname, $_POST['tagselector']);
                rcms_redirect("?module=usertags&username=" . $uname);
            } else {
                //cemetary tags protection
                if ($_POST['tagselector'] != $alter_conf['DEAD_TAGID']) {
                    stg_add_user_tag($uname, $_POST['tagselector']);
                    rcms_redirect("?module=usertags&username=" . $uname);
                } else {
                    show_warning(__('This tag type is protected by cemetery'));
                }
            }
        }
// tag deletion
        if (isset($_GET['deletetag'])) {
            //reset user if needed
            if ($alter_conf['RESETONTAGCHANGE']) {
                $billing->resetuser($uname);
                log_register("RESET User (" . $uname . ")");
            }

            if (!$alter_conf['CEMETERY_ENABLED']) {
                //normal tag deletion
                stg_del_user_tag($_GET['deletetag']);
                rcms_redirect("?module=usertags&username=" . $uname);
            } else {
                //cemetary tags protection
                $tagDelData = stg_get_tag_data($_GET['deletetag']);
                @$tagDelType = $tagDelData['tagid'];
                if ($tagDelType != $alter_conf['DEAD_TAGID']) {
                    stg_del_user_tag($_GET['deletetag']);
                    rcms_redirect("?module=usertags&username=" . $uname);
                } else {
                    show_warning(__('This tag type is protected by cemetery'));
                }
            }
        }





        show_window(__('Tags'), stg_show_user_tags($uname));
        stg_tagadd_selector();
        stg_tagdel_selector($uname);
        show_window('', web_UserControls($uname));
    }
} else {
    show_error(__('Access denied'));
}
?>