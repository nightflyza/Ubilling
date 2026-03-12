<?php

//just dummy module for testing purposes
error_reporting(E_ALL);
if (cfr('ROOT')) {

    $cmirr = new CMIRR();
    $cmirr->setMode('text/css');
    $inputs=$cmirr->getEditorArea('test', '');
    $form=wf_Form('', 'POST', $inputs, '');
    show_window('Test', $form);
}
