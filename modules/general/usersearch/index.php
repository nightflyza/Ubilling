<?php

if (cfr('USERSEARCH')) {
    //catch ajax backend callback
    if (ubRouting::checkGet('glosearch')) {
        $globalSearch = new GlobalSearch();
        $globalSearch->ajaxCallback();
    }

    if (ubRouting::checkGet('sphinxsearch')) {
        $fallback = json_encode(array());
        if (ubRouting::checkPost('search')) {
            $sphinxSearch = new SphinxSearch(ubRouting::post('search'));
            return $fallback;
        }
        return $fallback;
    }

    // rendering some search forms
    show_window(__('User search'), web_UserSearchGrid());

    // default fields search
    if (ubRouting::checkPost('searchquery')) {
        $query = ubRouting::post('searchquery');
        $searchtype = ubRouting::post('searchtype');
        if (!empty($query)) {
            show_window(__('Search results') . ' - ' . zb_UserSearchTypeLocalize($searchtype, $query), ($searchtype == 'full') ? zb_UserSearchAllFields($query) : zb_UserSearchFields($query, $searchtype));
        }
    }

    //full address search
    if (ubRouting::checkPost('aptsearch')) {
        $aptquery = ubRouting::post('aptsearch');
        show_window(__('Search results'), zb_UserSearchFields($aptquery, 'apt'));
    }

    //partial address search
    if (ubRouting::checkPost('partialaddr')) {
        $search_query = ubRouting::post('partialaddr', 'callback', 'trim');
        if (!empty($search_query)) {
            $found_users = zb_UserSearchAddressPartial($search_query);
            show_window(__('Search results') . ' - ' . zb_UserSearchTypeLocalize('partialaddr', $search_query), web_UserArrayShower($found_users));
        }
    }

    //CF search
    if (ubRouting::checkPost('cfquery', false)) {
        $search_query = ubRouting::post('cfquery');
        if (strlen($search_query) > 0) {
            $found_users = zb_UserSearchCF(ubRouting::post('cftypeid'), $search_query);
            show_window(__('Search results') . ' - ' . __('Additional profile fields'), web_UserArrayShower($found_users));
        }
    }

    //do the global search
    if (ubRouting::checkPost('globalsearchquery')) {
        $globalSearchQuery = ubRouting::post('globalsearchquery');
        if (ubRouting::checkPost('globalsearch_type')) {
            $globalSearchType = ubRouting::post('globalsearch_type');
        } else {
            $globalSearch = new GlobalSearch();
            $globalSearchType = $globalSearch->detectSearchType($globalSearchQuery);
        }


        if ($globalSearchType) {
            //partial address search
            if ($globalSearchType == 'address') {
                $globalSearchQuery = trim($globalSearchQuery);
                $found_users = zb_UserSearchAddressPartial($globalSearchQuery);
                show_window(__('Search results') . ' - ' . zb_UserSearchTypeLocalize('partialaddr', $globalSearchQuery), web_UserArrayShower($found_users));
            } elseif ($globalSearchType == 'address_extend') {
                $globalSearchQuery = trim($globalSearchQuery);
                $found_users = zb_UserSearchAddressPartial($globalSearchQuery, true);
                show_window(__('Search results') . ' - ' . zb_UserSearchTypeLocalize('extenaddr', $globalSearchQuery), web_UserArrayShower($found_users));
            } else {
                if ($globalSearchType != 'full') {
                    //other fields search
                    if (!empty($globalSearchQuery)) {
                        show_window(__('Search results') . ' - ' . zb_UserSearchTypeLocalize($globalSearchType, $globalSearchQuery), zb_UserSearchFields($globalSearchQuery, $globalSearchType));
                    }
                } else {
                    //all fields search for sphinx
                    show_window(__('Search results') . ' - ' . zb_UserSearchTypeLocalize($globalSearchType, $globalSearchQuery), zb_UserSearchAllFields($globalSearchQuery));
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
