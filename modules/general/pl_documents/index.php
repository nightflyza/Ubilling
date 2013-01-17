<?php
if (cfr('PLDOCS')) {
    
    if (isset($_GET['username'])) {
        $login=vf($_GET['username']);
        
        //delete subroutine
        if (isset($_GET['deletetemplate'])) {
            zb_DocsDeleteTemplate($_GET['deletetemplate']);
            rcms_redirect("?module=pl_documents&username=".$login);
        }
        
        if (isset($_GET['addtemplate'])) {
            //add subroutine
            if (wf_CheckPost(array('newtemplatetitle','newtemplatebody'))) {
                zb_DocsTemplateCreate($_POST['newtemplatetitle'], $_POST['newtemplatebody']);
                rcms_redirect("?module=pl_documents&username=".$login);
            }
            //show add form
            zb_DocsTemplateAddForm();
        }
        
        if (isset($_GET['edittemplate'])) {
            //edit subroutine
            if (wf_CheckPost(array('edittemplatetitle','edittemplatebody'))) {
                zb_DocsTemplateEdit($_GET['edittemplate'],$_POST['edittemplatetitle'], $_POST['edittemplatebody']);
                rcms_redirect("?module=pl_documents&username=".$login);
            }
            //show edit form
            zb_DocsTemplateEditForm($_GET['edittemplate']);
        }
        
        
        if (!isset($_GET['printtemplate'])) {
            //showing document templates list by default
        if ((!isset($_GET['addtemplate'])) AND (!isset($_GET['edittemplate']))) {
            show_window('',  wf_Link('?module=pl_documents&username='.$login.'&addtemplate', 'Create new document template', true, 'ubButton'));
        } else {
            show_window('',  wf_Link('?module=pl_documents&username='.$login, 'Back', true, 'ubButton'));
        }
        
        zb_DocsShowAllTemplates($login);
        show_window('',web_UserControls($login));
        } else {
            //document print subroutine
            $parsed_template=  zb_DocsParseTemplate($_GET['printtemplate'], $login);
            print($parsed_template);
            die();
            
        }
    }    
    
    
    
} else {
      show_error(__('You cant control this module'));
}

?>
