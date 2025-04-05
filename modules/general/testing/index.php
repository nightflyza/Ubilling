<?php

//just dummy module for testing purposes
error_reporting(E_ALL);
if (cfr('ROOT')) {
    $inputs=wf_TextInput('someurl','url test','',true,20,'url');
    $inputs.=wf_TextInput('somepath','path test','',true,20,'path');
    $inputs.=wf_TextInput('somepathorurl','path or url test','',true,20,'pathorurl');
    $inputs.=wf_TextInput('somefullpath','full path test','',true,20,'fullpath');
    $inputs.=wf_TextInput('somegeo','geo test','',true,20,'geo');
    $inputs.=wf_TextInput('somemobile','mobile test','',true,20,'mobile');
    $inputs.=wf_TextInput('someip','ip test','',true,20,'ip');
    $inputs.=wf_TextInput('somealpha','alpha test','',true,20,'alpha'); 
    $inputs.=wf_TextInput('somealphanumeric','alphanumeric test','',true,20,'alphanumeric');
    $inputs.=wf_TextInput('somedigits','digits test','',true,20,'digits');
    $inputs.=wf_TextInput('somefinance','finance test','',true,20,'finance');
    $inputs.=wf_TextInput('somefloat','float test','',true,20,'float');
    $inputs.=wf_TextInput('somealpha','alpha test','',true,20,'alpha');
    
    $inputs.=wf_Submit('Save');
    $form=wf_Form('','POST',$inputs,'glamour');
    deb($form);
}
