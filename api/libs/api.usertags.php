<?php

/**
 * backport of user tags API from kvtstg
 */

/**
 * Returns array of user assigned tags as login=>array of tags with their names
 *
 * @param string $login
 *
 * @return array
 */
function zb_UserGetAllTags($login = '') {
    $result = array();
    $tagTypes = stg_get_alltagnames();
    $queryWhere = (empty($login)) ? '' : " WHERE `login` = '" . $login . "'";
    if (!empty($tagTypes)) {
        $query = "SELECT * from `tags`" . $queryWhere;
        $all = simple_queryall($query);
        if (!empty($all)) {
            foreach ($all as $io => $each) {
                $result[$each['login']][$each['tagid']] = @$tagTypes[$each['tagid']];
            }
        }
    }
    return ($result);
}

/**
 * Returns array of user assigned tags as login => array($dbID => $tagID)
 *
 * This function ensures the uniqueness of selected tags assigned to user
 * if user has one and the same tag assigned multiple times for some reason
 *
 * @param string $login
 * @param string $whereTagID
 *
 * @return array
 */
function zb_UserGetAllTagsUnique($login = '', $whereTagID = '') {
    $result = array();
    $queryWhere = (empty($login)) ? '' : " WHERE `login` = '" . $login . "'";

    if (!empty($whereTagID)) {
        if (empty($queryWhere)) {
            $queryWhere = " WHERE `tagid` IN(" . $whereTagID . ")";
        } else {
            $queryWhere .= " AND `tagid` IN(" . $whereTagID . ")";
        }
    }

    $query = "SELECT * from `tags`" . $queryWhere;
    $allTags = simple_queryall($query);

    if (!empty($allTags)) {
        foreach ($allTags as $io => $each) {
            $result[$each['login']][$each['id']] = $each['tagid'];
        }
    }

    return ($result);
}

/**
 * Returns tag creation priority selector
 * 
 * @param int $max
 * @return string
 */
function web_priority_selector($max = 6, $noWebControlReturn = false) {
    $params = array_combine(range($max, 1), range($max, 1));
    $result = $noWebControlReturn ? $params : wf_Selector('newpriority', $params, __('Priority'), '', false);
    return ($result);
}

/**
 * Render available tag types list with all needed controls
 * 
 * @return string
 */
function stg_show_tagtypes() {
    $messages = new UbillingMessageHelper();
    $query = "SELECT * from `tagtypes` ORDER BY `id` ASC";
    $alltypes = simple_queryall($query);

    $cells = wf_TableCell(__('ID'));
    $cells .= wf_TableCell(__('Color'));
    $cells .= wf_TableCell(__('Priority'));
    $cells .= wf_TableCell(__('Text'));
    $cells .= wf_TableCell(__('Actions'));
    $rows = wf_TableRow($cells, 'row1');

    if (!empty($alltypes)) {
        foreach ($alltypes as $io => $eachtype) {
            $eachtagcolor = $eachtype['tagcolor'];
            $actions = wf_JSAlert('?module=usertags&delete=' . $eachtype['id'], web_delete_icon(), $messages->getDeleteAlert());
            $actions .= wf_JSAlert('?module=usertags&edit=' . $eachtype['id'], web_edit_icon(), $messages->getEditAlert());

            $cells = wf_TableCell($eachtype['id']);
            $cells .= wf_TableCell(wf_tag('font', false, '', 'color="' . $eachtagcolor . '"') . $eachtagcolor . wf_tag('font', true));
            $cells .= wf_TableCell($eachtype['tagsize']);
            $cells .= wf_TableCell(wf_Link('?module=tagcloud&tagid=' . $eachtype['id'], $eachtype['tagname']));
            $cells .= wf_TableCell($actions);
            $rows .= wf_TableRow($cells, 'row5');
        }
    }

    $result = wf_TableBody($rows, '100%', 0, 'sortable');

    //construct adding form
    $inputs = wf_ColPicker('newcolor', __('Color'), '#' . rand(11, 99) . rand(11, 99) . rand(11, 99), false, '10');
    $inputs .= wf_TextInput('newtext', __('Text'), '', false, '15');
    $inputs .= web_priority_selector() . ' ';
    $inputs .= wf_HiddenInput('addnewtag', 'true');
    $inputs .= wf_Submit(__('Create'));
    $form = wf_Form("", 'POST', $inputs, 'glamour');
    $result .= $form;


    return ($result);
}

/**
 * Creates new tag type in database 
 * 
 * @return void
 */
function stg_add_tagtype() {
    $color = mysql_real_escape_string($_POST['newcolor']);
    $size = vf($_POST['newpriority'], 3);
    $text = mysql_real_escape_string($_POST['newtext']);
    $query = "INSERT INTO `tagtypes` (`id` ,`tagcolor` ,`tagsize` ,`tagname`) VALUES (NULL , '" . $color . "', '" . $size . "', '" . $text . "');";
    nr_query($query);
    $newId = simple_get_lastid('tagtypes');
    log_register('TAGTYPE ADD `' . $text . '` [' . $newId . ']');
}

/**
 * Deletes tag type from database
 * 
 * @param int $tagid
 */
function stg_delete_tagtype($tagid) {
    $tagid = vf($tagid, 3);
    $query = "DELETE from `tagtypes` WHERE `id`='" . $tagid . "'";
    nr_query($query);
    log_register('TAGTYPE DELETE [' . $tagid . ']');
    $query = "UPDATE `employee` SET `tagid` = NULL WHERE `employee`.`tagid` = '" . $tagid . "'";
    nr_query($query);
    $query = "DELETE from `tags` WHERE `tagid`='" . $tagid . "';";
    nr_query($query);
    log_register('TAGTYPE FLUSH [' . $tagid . ']');
}

/**
 * Returns array of available tagtypes as id=>name
 * 
 * @return array
 */
function stg_get_alltagnames() {
    $query = "SELECT * from `tagtypes`";
    $alltagtypes = simple_queryall($query);
    $result = array();
    if (!empty($alltagtypes)) {
        foreach ($alltagtypes as $io => $eachtype) {
            $result[$eachtype['id']] = $eachtype['tagname'];
        }
    }
    return($result);
}

/**
 * Returns array of available tagtypes as id => name, filtered by $tagIDs
 * or just empty array
 *
 * @param $tagIDs
 *
 * @return array
 */
function stg_get_alltagnames_filtered($tagIDs = array()) {
    $result = array();
    $whereStr = zb_ArrayToSQLWHEREIN($tagIDs);

    if (!empty($whereStr)) {
        $query = "SELECT * FROM `tagtypes` WHERE `id` IN (" . $whereStr . ")";
        $alltagtypes = simple_queryall($query);

        if (!empty($alltagtypes)) {
            foreach ($alltagtypes as $io => $eachtype) {
                $result[$eachtype['id']] = $eachtype['tagname'];
            }
        }
    }

    return($result);
}

/**
 * Returns array of some tag type data
 * 
 * @param int $tagtypeid
 * @return array
 */
function stg_get_tagtype_data($tagtypeid) {
    $tagtypeid = vf($tagtypeid, 3);
    $query = "SELECT * from `tagtypes` WHERE `id`='" . $tagtypeid . "'";
    $result = simple_query($query);
    return($result);
}

/**
 * Returns user applied tags as browsable html
 * 
 * @param string $login existing user login
 * @param bool $concat concatenate same tags as power?
 * @param bool $noTagsAlert render notification on empty user tags list
 * 
 * @return string
 */
function stg_show_user_tags($login, $concat = false, $noTagsAlert = false) {
    global $ubillingConfig;
    $newLineFlag = $ubillingConfig->getAlterParam('TAG_NEWLINE_PZDTS');
    $query = "SELECT * from `tags` INNER JOIN (SELECT * from `tagtypes`) AS tt ON (`tags`.`tagid`=`tt`.`id`) LEFT JOIN (SELECT `mobile`,`tagid` AS emtag FROM `employee` WHERE `tagid` != '') as tem ON (`tags`.`tagid`=`tem`.`emtag`) WHERE `login`='" . $login . "';";
    $alltags = simple_queryall($query);
    $result = '';
    if (!empty($alltags)) {
        if (!$concat) {
            //just render each tag as is
            foreach ($alltags as $io => $eachtag) {
                $emploeeMobile = ($eachtag['mobile']) ? wf_modal(wf_img('skins/icon_mobile.gif', $eachtag['tagname']), $eachtag['tagname'] . ' - ' . __('Mobile'), $eachtag['mobile'], '', 400, 200) : '';
                $result .= wf_tag('font', false, '', 'color="' . $eachtag['tagcolor'] . '" size="' . $eachtag['tagsize'] . '"');
                $result .= wf_tag('a', false, '', 'href="?module=tagcloud&tagid=' . $eachtag['tagid'] . '" style="color: ' . $eachtag['tagcolor'] . ';"') . $eachtag['tagname'] . wf_tag('a', true);
                $result .= $emploeeMobile;
                $result .= wf_tag('font', true);
                $result .= '&nbsp;';
            }
        } else {
            //appending counter of each tag type assigned to user
            $userTagsCount = array();
            $powerDelimiter = $ubillingConfig->getAlterParam('TAG_MULTPOWER_DELIMITER');
            if (!$powerDelimiter) {
                $powerDelimiter = '';
            }
            foreach ($alltags as $io => $eachtag) {
                if (isset($userTagsCount[$eachtag['tagid']])) {
                    $userTagsCount[$eachtag['tagid']]['count'] ++;
                } else {
                    $userTagsCount[$eachtag['tagid']] = $eachtag;
                    $userTagsCount[$eachtag['tagid']]['count'] = 1;
                }
            }

            foreach ($userTagsCount as $io => $eachtag) {
                $emploeeMobile = ($eachtag['mobile']) ? wf_modal(wf_img('skins/icon_mobile.gif', $eachtag['tagname']), $eachtag['tagname'] . ' - ' . __('Mobile'), $eachtag['mobile'], '', 400, 200) : '';
                $powerLabel = '';
                if ($eachtag['count'] > 1) {
                    $powerLabel = wf_tag('small') . wf_tag('sup') . $powerDelimiter . $eachtag['count'] . wf_tag('sup', true) . wf_tag('small', true);
                }
                $result .= wf_tag('font', false, '', 'color="' . $eachtag['tagcolor'] . '" size="' . $eachtag['tagsize'] . '"');
                $result .= wf_tag('a', false, '', 'href="?module=tagcloud&tagid=' . $eachtag['tagid'] . '" style="color: ' . $eachtag['tagcolor'] . ';"') . $eachtag['tagname'] . wf_tag('a', true);
                $result .= $powerLabel;
                $result .= $emploeeMobile;
                $result .= wf_tag('font', true);
                $result .= '&nbsp;';
                if ($newLineFlag) {
                    $result .= wf_delimiter(0);
                }
            }
        }
    } else {
        //Optional empty tags list notification
        if ($noTagsAlert) {
            $messages = new UbillingMessageHelper();
            $result .= $messages->getStyledMessage(__('This user has no tags assigned'), 'info');
        }
    }

    return ($result);
}

/**
 * Shows tag addition dialogue
 * 
 * @return void
 */
function stg_tagadd_selector() {
    global $ubillingConfig;
    $searchableFlag = $ubillingConfig->getAlterParam('TAGSEL_SEARCHBL');

    $query = "SELECT * from `tagtypes` ORDER by `id` ASC";
    $alltypes = simple_queryall($query);
    $tagArr = array();
    $result = '';
    if (!empty($alltypes)) {
        foreach ($alltypes as $io => $eachtype) {
            $tagArr[$eachtype['id']] = $eachtype['tagname'];
        }


        if ($searchableFlag) {
            $inputs = wf_SelectorSearchable('tagselector', $tagArr, '', '', false);
        } else {
            $inputs = wf_Selector('tagselector', $tagArr, '', '', false);
        }

        $inputs .= wf_Submit(__('Save'));
        $result .= wf_Form('', 'POST', $inputs, '');
    } else {
        $messages = new UbillingMessageHelper();
        $result .= $messages->getStyledMessage(__('There are currently no existing tag types'), 'warning');
    }
    show_window(__('Add tag'), $result);
}

/**
 * Returns tag id selector 
 * 
 * @return string
 */
function stg_tagid_selector($noWebControlReturn = false) {
    $query = "SELECT * from `tagtypes`";
    $alltypes = simple_queryall($query);
    $tmpArr = array();
    if (!empty($alltypes)) {
        foreach ($alltypes as $io => $eachtype) {
            $tmpArr[$eachtype['id']] = $eachtype['tagname'];
        }
    }

    $result = $noWebControlReturn ? $tmpArr : wf_Selector('newtagid', $tmpArr, __('Tag'), '', false);
    return ($result);
}

/**
 * shows tag deletion controls
 * 
 * @param string $login
 * 
 * @return void
 */
function stg_tagdel_selector($login) {
    $login = vf($login);
    $query = "SELECT * from `tags` where `login`='" . $login . "'";
    $usertags = simple_queryall($query);
    $result = '';
    if (!empty($usertags)) {
        foreach ($usertags as $io => $eachtag) {
            $result .= stg_get_tag_body_deleter($eachtag['tagid'], $login, $eachtag['id']);
        }
        show_window(__('Delete tag'), $result);
    }
}

/**
 * Assosicates some tag with existing user login
 * 
 * @param string $login
 * @param int $tagid
 * 
 * @return void
 */
function stg_add_user_tag($login, $tagid) {
    $login = ubRouting::filters($login, 'mres');
    $tagid = ubRouting::filters($tagid, 'int');
    $tagsDb = new nya_tags();

    $tagsDb->data('login', $login);
    $tagsDb->data('tagid', $tagid);
    $tagsDb->create();

    log_register('TAG ADD (' . $login . ') TAGID [' . $tagid . ']');
}

/**
 * Deletes user tag by its ID
 * 
 * @param int $tagid
 * 
 * @return void
 */
function stg_del_user_tag($tagid) {
    $tagid = ubRouting::filters($tagid, 'int');
    $tagsDb = new nya_tags();
    $tagsDb->where('id', '=', $tagid);
    $tagData = $tagsDb->getAll();
    if (!empty($tagData)) {
        $tagLogin = $tagData[0]['login'];
        $tagType = $tagData[0]['tagid'];
        $tagsDb->where('id', '=', $tagid);
        $tagsDb->delete();
        log_register('TAG DELETE (' . $tagLogin . ') TAGID [' . $tagType . ']');
    } else {
        log_register('TAG DELETE TAGID [' . $tagid . '] FAIL_NOT_EXISTS');
    }
}

/**
 * Deletes user tag by tagid
 *  
 * @param string $login
 * @param int $tagid
 * 
 * @return void
 */
function stg_del_user_tagid($login, $tagid) {
    $login = mysql_real_escape_string($login);
    $tagid = vf($tagid, 3);
    $query = "DELETE from `tags` WHERE `login`='" . $login . "' AND `tagid`='" . $tagid . "'";
    nr_query($query);
    stg_putlogevent('TAG DELETE (' . $login . ') TAGID [' . $tagid . ']');
}

/**
 * Returns tag data by its ID
 * 
 * @param int $tagid
 * 
 * @return array
 */
function stg_get_tag_data($tagid) {
    $tagid = vf($tagid, 3);
    $query = "SELECT * from `tags` where `id`='" . $tagid . "';";
    $result = simple_query($query);
    return ($result);
}

/**
 * Returns user tag deletion HTML control 
 * 
 * @param int $id
 * @param string $login
 * @param int $tagid
 * @return string
 */
function stg_get_tag_body_deleter($id, $login, $tagid) {
    $messages = new UbillingMessageHelper();
    $query = "SELECT * from `tagtypes` where `id`='" . $id . "'";
    $tagbody = simple_query($query);
    $tagNotExists = empty($tagbody);
    $result = '';

    $tagColor = ($tagNotExists) ? '#FF0000' : $tagbody['tagcolor'];
    $tagSize  = ($tagNotExists) ? '6' : $tagbody['tagsize'];
    $tagName  = ($tagNotExists) ? __('Non-existent tag with ID') . ': ' . $id : $tagbody['tagname'];

    $result .= wf_tag('font', false, '', 'color="' . $tagColor . '" size="' . $tagSize . '"');
    $result .= $tagName;
    $result .= wf_tag('sup');
    $deleteUrl = '?module=usertags&username=' . $login . '&deletetag=' . $tagid;
    $cancelUrl = '?module=usertags&username=' . $login;
    $dialogTitle = __('Delete tag') . ' ' . $tagName . '?';
    $deleteDialog = wf_ConfirmDialog($deleteUrl, web_delete_icon(), $messages->getDeleteAlert(), '', $cancelUrl, $dialogTitle);
    $result .= $deleteDialog;
    $result .= wf_tag('sup', true);
    $result .= wf_tag('font', true);
    $result .= wf_nbsp();

    return($result);
}

/**
 * Flushes all tags associated with some user login
 * 
 * @param string $login
 * 
 * @return void
 */
function zb_FlushAllUserTags($login) {
    $login = mysql_real_escape_string($login);
    $query = "DELETE from `tags` WHERE `login`='" . $login . "'";
    nr_query($query);
    log_register("USER TAG FLUSH (" . $login . ")");
}
