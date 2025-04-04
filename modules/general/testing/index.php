<?php

//just dummy module for testing purposes
error_reporting(E_ALL);
if (cfr('ROOT')) {
    $configPath = 'config/test.ini';
    $specPath = 'config/test.spec';
    
    //ic(ubRouting::rawPost());
    $forge = new ConfigForge($configPath, $specPath);
    
    if (ubRouting::checkPost(array('configforge_submit'))) {
        $message = $forge->handleSubmit();
        if (!empty($message)) {
            show_window('', $message);
        }
        $configContent = $forge->getConfigAsText();
        show_window('New config content', wf_TextArea('configcontent', '', $configContent, true, '40x10'));
    }

    

    show_window(__('Config Forge'), $forge->renderEditor());
}
