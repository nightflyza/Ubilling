<?php

//just dummy module for testing purposes
error_reporting(E_ALL);

$wcpe = new WifiCPE();


//rendering available CPE list
if (wf_CheckGet(array('ajcpelist'))) {
    $wcpe->getCPEListJson();
}

//creating new CPE
if (wf_CheckPost(array('createnewcpe', 'newcpemodelid'))) {
    $newCpeBridge = (wf_CheckPost(array('newcpebridge'))) ? true : false;
    $creationResult = $wcpe->createCPE($_POST['newcpemodelid'], $_POST['newcpeip'], $_POST['newcpemac'], $_POST['newcpelocation'], $newCpeBridge, $_POST['newcpeuplinkapid'], $_POST['newcpegeo']);
    if (empty($creationResult)) {
        rcms_redirect($wcpe::URL_ME);
    } else {
        show_window(__('Something went wrong'), $creationResult);
    }
}

//CPE deletion
if (wf_CheckGet(array('deletecpeid'))) {
    $deletionResult = $wcpe->deleteCPE($_GET['deletecpeid']);
    if (empty($deletionResult)) {
        rcms_redirect($wcpe::URL_ME);
    } else {
        show_window(__('Something went wrong'), $deletionResult);
    }
}



show_window(__('Create new CPE'), $wcpe->renderCPECreateForm());
show_window(__('Available CPE list'), $wcpe->renderCPEList());
?>