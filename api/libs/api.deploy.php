<?php

/*
 * Appends new option to config 
 * 
 * @param $path - config file path
 * @param $option - option key
 * @param $value - option raw value
 * 
 * @return void
 */
function zb_DeployConfigOption($path,$option,$value) {
    if (file_exists($path)) {
        $currentData=  rcms_parse_ini_file($path);
        if (!isset($currentData[$option])) {
            file_put_contents($path, "\n".$option.'='.$value."\n", FILE_APPEND | LOCK_EX);
            show_window(__('Added'),__('New option key').': '.$option.' '.__('with value').': '.$value.' to: '.$path);
            log_register("DEPLOY CFG OPT (".$option.") GOOD");
        } else {
           show_window(__('Warning'), __('Option already exist - skipping'));
           log_register("DEPLOY CFG OPT (".$option.") SKIP");
        }
    } else {
        show_window(__('Error'), __('Config not exists'));
        log_register("DEPLOY CFG OPT (".$option.") FAIL");
    }
    
}

/*
 * Appends new option to config with override if old option if exists
 * 
 * @param $path - config file path
 * @param $option - option key
 * @param $value - option raw value
 * 
 * @return void
 */
function zb_DeployConfigOptionOverride($path,$option,$value) {
    if (file_exists($path)) {
        $currentData=  rcms_parse_ini_file($path);
        if (!isset($currentData[$option])) {
            file_put_contents($path, "\n".$option.'='.$value."\n", FILE_APPEND | LOCK_EX);
            show_window(__('Added'),__('New option key').': '.$option.' '.__('with value').': '.$value.' to: '.$path);
             log_register("DEPLOY CFG OPTOVR (".$option.") GOOD");
        } else {
           file_put_contents($path, "\n".$option.'='.$value."\n", FILE_APPEND | LOCK_EX);
           show_window(__('Notice'), __('Option already exist - overriding').': '.$option.' value:'.$value.' in:'.$path);
           log_register("DEPLOY CFG OPTOVR (".$option.") OVR");
        }
    } else {
        show_window(__('Error'), __('Config not exists'));
        log_register("DEPLOY CFG OPTOVR (".$option.") FAIL");
    }
}

/*
 * Create new config file if not exist
 * 
 * @param $path - config file path
 * 
 * @return void
 */
function zb_DeployConfigCreate($path) {
    if (file_exists($path)) {
        show_window(__('Warning'), __('Config already exists - skipping'));
        log_register("DEPLOY CFG CRE (".$path.") SKIP");
    } else {
        file_put_contents($path, ';created by deploy API'.time()."\n");
        show_window(__('Created'),__('New config file').': '.$path);
        log_register("DEPLOY CFG CRE (".$path.") GOOD");
    }
}


function zb_DeployDBQuery($query) {
    nr_query($query);
    log_register("DEPLOY DB QUERY");
}

?>
