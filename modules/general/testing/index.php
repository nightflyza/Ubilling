<?php

//just dummy module for testing purposes
error_reporting(E_ALL);
if (cfr('ROOT')) {
    $configPath = 'config/tests.ini';
    $specPath = 'config/test.spec';
    
    $forge = new ConfigForge($configPath, $specPath);
    
    // Handle form submission
    $submitResult = $forge->handleSubmit();
    if (!empty($submitResult)) {
        // If there's an error message, show it
        show_error('', $submitResult);
    } elseif (ubRouting::checkPost(ConfigForge::FORM_SUBMIT_KEY)) {
        // If submission was successful, redirect
        ubRouting::nav('?module=testing');
    }

    show_window(__('Config Forge'), $forge->renderEditor());
}
