<?php

if (cfr('USERSEARCH')) {
    //catch ajax backend callback
    if (wf_CheckGet(array('glosearch'))) {
        $globalSearch=new GlobalSearch();
        $globalSearch->ajaxCallback();
    }
    

    // show search forms
    $gridRows = wf_tag('tr', false, '', 'valign="top"');
    $gridRows.= wf_TableCell(wf_tag('h3', false, 'row3') . __('Full address') . wf_tag('h3', true) . web_UserSearchAddressForm(), '60%', '');
    $gridRows.= wf_TableCell(wf_tag('h3', false, 'row3') . __('Partial address') . wf_tag('h3', true) . web_UserSearchAddressPartialForm(), '', '');
    $gridRows.= wf_tag('tr', true);
    $gridRows.= wf_tag('tr', false, '', 'valign="top"');
    $gridRows.= wf_TableCell(wf_tag('h3', false) . __('Profile fields search') . wf_tag('h3', true) . web_UserSearchFieldsForm(), '', 'row3');
    $gridRows.= wf_TableCell(web_CorpsSearchForm().web_UserSearchContractForm() . web_UserSearchCFForm(), '', 'row3');
    $gridRows.= wf_tag('tr', true);

    $search_forms_grid = wf_TableBody($gridRows, '100%', 0, '');
    show_window(__('User search'), $search_forms_grid);


    // default fields search
    if (isset($_POST['searchquery'])) {
        $query = $_POST['searchquery'];
        $searchtype = $_POST['searchtype'];
        if (!empty($query)) {
            show_window(__('Search results').' - '.  zb_UserSearchTypeLocalize($searchtype,$query), ($searchtype == 'full') ? zb_UserSearchAllFields($query) : zb_UserSearchFields($query, $searchtype));
        }
    }

    //full address search
    if (isset($_POST['aptsearch'])) {
        $aptquery = $_POST['aptsearch'];
        show_window(__('Search results'), zb_UserSearchFields($aptquery, 'apt'));
    }

    //partial address search
    if (isset($_POST['partialaddr'])) {
        $search_query = trim($_POST['partialaddr']);
        if (!empty($search_query)) {
            $found_users = zb_UserSearchAddressPartial($search_query);
            show_window(__('Search results').' - '.  zb_UserSearchTypeLocalize('partialaddr', $search_query), web_UserArrayShower($found_users));
        }
    }

    //CF search
    if (isset($_POST['cfquery'])) {
        $search_query = $_POST['cfquery'];
        if (sizeof($search_query) > 0) {
            $found_users = zb_UserSearchCF($_POST['cftypeid'], $search_query);
            show_window(__('Search results').' - '.__('Additional profile fields'), web_UserArrayShower($found_users));
        }
    }
    
    //do the global search
    if (wf_CheckPost(array('globalsearchquery'))) {
         $globalSearchQuery = $_POST['globalsearchquery'];
        if (wf_CheckPost(array('globalsearch_type'))) {
            $globalSearchType=$_POST['globalsearch_type'];
        } else {
            $globalSearch=new GlobalSearch();
            $globalSearchType=$globalSearch->detectSearchType($globalSearchQuery);
        }
        
        
        if ($globalSearchType) {
            //partial address search
            if ($globalSearchType=='address') {
                 $globalSearchQuery=trim($globalSearchQuery);
                 $found_users = zb_UserSearchAddressPartial($globalSearchQuery);
                 show_window(__('Search results').' - '.  zb_UserSearchTypeLocalize('partialaddr',$globalSearchQuery), web_UserArrayShower($found_users));
            } else {
              //other fields search
               if (!empty($globalSearchQuery)) {
                    show_window(__('Search results').' - '.  zb_UserSearchTypeLocalize($globalSearchType,$globalSearchQuery), zb_UserSearchFields($globalSearchQuery, $globalSearchType));
                }
            }
        } else {
            show_warning(__('Nothing found'));
        }    
        
    }
    
    

    zb_BillingStats(true);
} else {
    show_error(__('Access denied'));
}
?>