<?php

/**
 * Returns standard deletion icon
 * 
 * @param string $title
 * @return string
 */
function web_delete_icon($title = 'Delete') {
    $icon = wf_img('skins/icon_del.gif', __($title));
    return($icon);
}

/**
 * Returns addition/insert icon with some title
 * 
 * @param string $title
 * @return string
 */
function web_add_icon($title = 'Add') {
    $icon = wf_img('skins/icon_add.gif', __($title));
    return($icon);
}

/**
 * Returns standard edit icon
 * 
 * @param string $title
 * @return string
 */
function web_edit_icon($title = 'Edit') {
    $icon = wf_img('skins/icon_edit.gif', __($title));
    return($icon);
}

/**
 * Returns standard password/key icon
 * 
 * @param string $title
 * @return string
 */
function web_key_icon($title = 'Password') {
    $icon = wf_img('skins/icon_key.gif', __($title));
    return($icon);
}

/**
 * Returns standard street icon
 * 
 * @param string $title
 * @return string
 */
function web_street_icon($title = 'Street') {
    $icon = wf_img('skins/icon_street.gif', __($title));
    return($icon);
}

/**
 * Returns standard city icon
 * 
 * @param string $title
 * @return string
 */
function web_city_icon($title = 'City') {
    $icon = wf_img('skins/icon_city.gif', __($title));
    return($icon);
}

/**
 * Returns standard build icon
 * 
 * @param string $title
 * @return string
 */
function web_build_icon($title = 'Builds') {
    $icon = wf_img('skins/icon_build.gif', __($title));
    return($icon);
}

/**
 * Returns standard "good" icon
 * 
 * @param string $title
 * @return string
 */
function web_ok_icon($title = 'Ok') {
    $icon = wf_img('skins/icon_ok.gif', __($title));
    return($icon);
}

/**
 * Returns standard profile icon
 * 
 * @param string $title
 * @return string
 */
function web_profile_icon($title = 'Profile') {
    $icon = wf_img('skins/icon_user.gif', __($title));
    return($icon);
}

/**
 * Returns standard stats/graph icon
 * 
 * @param string $title
 * @return string
 */
function web_stats_icon($title = 'Stats') {
    $icon = wf_img('skins/icon_stats.gif', __($title));
    return($icon);
}

/**
 * Returns standard charts icon small
 * 
 * @param string $title
 * @return string
 */
function web_icon_charts($title = 'Stats') {
    $icon = wf_img('skins/icon_charts.png', __($title));
    return($icon);
}

/**
 * Returns standard corporate icon
 * 
 * @param string $title
 * @return string
 */
function web_corporate_icon($title = 'Corporate') {
    $icon = wf_img('skins/corporate_small.gif', __($title));
    return($icon);
}

/**
 * Returns standard green led icon
 * 
 * @param string $title
 * @return string
 */
function web_green_led($title = '') {
    $icon = wf_img('skins/icon_active.gif', __($title));
    return($icon);
}

/**
 * Returns standard yellow led icon
 * 
 * @param string $title
 * @return string
 */
function web_yellow_led($title = '') {
    $icon = wf_img('skins/yellow_led.png', __($title));
    return($icon);
}

/**
 * Returns standard red led icon
 * 
 * @param string $title
 * @return string
 */
function web_red_led($title = '') {

    $icon = wf_img('skins/icon_inactive.gif', __($title));
    return($icon);
}

/**
 * Returns standard star/online icon
 * 
 * @param string $title
 * @return string
 */
function web_star($title = NULL) {
    $icon = wf_img('skins/icon_star.gif', __($title));
    return($icon);
}

/**
 * Returns standard black star/online icon
 * 
 * @param string $title
 * @return string
 */
function web_star_black() {
    $icon = wf_img('skins/icon_nostar.gif');
    return($icon);
}

/**
 * Returns extended configuration icon
 * 
 * @param string $title
 * @return string
 */
function web_icon_extended($title = NULL) {
    $icon = wf_img('skins/icon_extended.png', __($title));
    return $icon;
}

/**
 * Returns new item creation icon
 * 
 * @param string $title
 * @return string
 */
function web_icon_create($title = NULL) {
    $icon = wf_img('skins/add_icon.png', __($title));
    return $icon;
}

/**
 * Returns default settings icon
 * 
 * @param string $title
 * @return string
 */
function web_icon_settings($title = 'Settings') {
    $icon = wf_img('skins/settings.png', __($title));
    return $icon;
}

/**
 * Returns default search icon
 * 
 * @param string $title
 * @return string
 */
function web_icon_search($title = 'Search') {
    $icon = wf_img('skins/icon_search_small.gif', __($title));
    return $icon;
}

/**
 * Returns default download icon
 * 
 * @param string $title
 * @return string
 */
function web_icon_download($title = 'Download') {
    $icon = wf_img('skins/icon_download.png', __($title));
    return ($icon);
}

/**
 * Returns default printing icon
 * 
 * @param string $title
 * @return string
 */
function web_icon_print($title = 'Print') {
    $icon = wf_img('skins/icon_print.png', __($title));
    return $icon;
}

/**
 * Returns FreeRADIUS icon:
 * 
 * @param string $title
 * @return string
 */
function web_icon_freeradius($title = NULL) {
    $icon = wf_img('skins/icon_freeradius_small.png', __($title));
    return $icon;
}

/**
 * Returns default dollar icon
 * 
 * @param string $title
 * @return string
 */
function web_cash_icon($title = 'Cash') {
    $icon = wf_img('skins/icon_dollar.gif', __($title));
    return $icon;
}

/**
 * Returns boolean led indicator
 * 
 * @param bool/string $flag
 * @param bool $text
 * @return string
 */
function web_bool_led($flag, $text = false) {
    if ($text) {
        $no = ' ' . __('No') . ' ';
        $yes = __('Yes') . ' ';
    } else {
        $no = '';
        $yes = '';
    }
    $led = $no . web_red_led();

    if ($flag) {
        $led = $yes . web_green_led();
    }

    return($led);
}

/**
 * Returns boolean star indicator
 * 
 * @param bool/string $flag
 * @param bool $text
 * @return string
 */
function web_bool_star($flag, $text = false) {
    if ($text) {
        $no = ' ' . __('No') . ' ';
        $yes = __('Yes') . ' ';
    } else {
        $no = '';
        $yes = '';
    }
    $led = $no . web_star_black();

    if ($flag) {
        $led = $yes . web_star();
    }

    return($led);
}

/**
 * Returns standard edit icon
 *
 * @param string $title
 * @return string
 */
function web_clone_icon($title = 'Clone') {
    $icon = wf_img('skins/duplicate_icon.gif', __($title));
    return($icon);
}

?>