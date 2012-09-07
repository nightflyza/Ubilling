<?php
if (cfr('RADIUST')) {



// delete subroutine
if (isset($_GET['delete'])) {
    ra_NasDeteleTemplate($_GET['delete']);
    ra_NasRebuildAll();
    rcms_redirect("?module=radiust");
}

//add subroutine
if (isset($_POST['newnasid'])) {
    if (isset($_POST['newnastemplate'])) {
        ra_NasAddTemplate($_POST['newnasid'], $_POST['newnastemplate']);
        ra_NasRebuildAll();
        rcms_redirect("?module=radiust");
    }
}

//edit subroutine
if (wf_CheckPost(array('editnastemplate'))) {
    simple_update_field('nastemplates', 'nasid', $_POST['editnasid'], "WHERE `id`='".$_POST['edittemplateid']."'");
    simple_update_field('nastemplates', 'template', $_POST['editnastemplate'], "WHERE `id`='".$_POST['edittemplateid']."'");
    ra_NasRebuildAll();
    rcms_redirect("?module=radiust");
}

//rebuildall sub
if (wf_CheckGet(array('rebuildall'))) {
    ra_NasRebuildAll();
    rcms_redirect("?module=radiust");
}


//show available NAS
if (!isset($_GET['edit'])) {
 show_window('',  wf_Link("?module=radiust&rebuildall=true", 'Rebuild attributes for all NAS', true, 'ubButton'));
 web_NasTemplatesShow();
 web_NasTemplateAddForm();
    
} else {
    //show edit form
    web_NasTemplateEditForm($_GET['edit']);
    show_window('',  wf_Link("?module=radiust", 'Back', true, 'ubButton'));
}
    
    
} else {
      show_error(__('You cant control this module'));
}

?>