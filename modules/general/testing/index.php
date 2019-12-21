<?php

//just dummy module for testing purposes
error_reporting(E_ALL);

$envy = new Envy();

//new script creation
if (ubRouting::checkPost(array('newscriptmodel'))) {
    $creationResult = $envy->createScript(ubRouting::post('newscriptmodel'), ubRouting::post('newscriptdata'));
    if (empty($creationResult)) {
        ubRouting::nav($envy::URL_ME);
    } else {
        show_error($creationResult);
    }
}

deb($envy->renderScriptCreateForm());
