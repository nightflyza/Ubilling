<?php

//just dummy module for testing purposes
error_reporting(E_ALL);
if (cfr('ROOT')) {
    $configPath = 'config/test.ini';
    $specPath = 'config/test.spec';
    
    $forge = new ConfigForge($configPath, $specPath);
    
    // Process any config editing requests
    $processResult = $forge->process();
    if (!empty($processResult)) {
        show_error($processResult);
    } elseif (ubRouting::checkPost(ConfigForge::FORM_SUBMIT_KEY)) {
        ubRouting::nav('?module=testing');
    }
    
    show_window(__('Config Forge'), $forge->renderEditor());
}
