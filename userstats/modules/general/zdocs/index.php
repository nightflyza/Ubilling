<?php
//error_reporting(E_ALL);

$user_ip = zbs_UserDetectIp('debug');
$user_login = zbs_UserGetLoginByIp($user_ip);
$us_config = zbs_LoadConfig();

if ($us_config['DOCX_SUPPORT']) {
    //load needed APIs
    include('modules/engine/api.morph.php');
    include('modules/engine/api.documents.php');
    include('modules/engine/api.docx.php');
    
    $documents = new UsProfileDocuments();
    $documents->setLogin($user_login);
    $documents->loadAllUserData();
    $documents->loadUserDocuments($user_login);

    show_window(__('Available document templates'),$documents->renderTemplatesList());
    
    
    if (la_CheckGet(array('print'))) {
        $templateId=vf($_GET['print'],3);
        if (!empty($templateId)) {
            $ctemplateData=$documents->getTemplates();
            if (isset($ctemplateData[$templateId])) {
                $templatePublicType=$ctemplateData[$templateId]['public'];
                if ($templatePublicType) {
                    //template is ok
                    show_window(__('Document creation'), $documents->customDocumentFieldsForm());
                    //try to parse template
                    if (la_CheckPost(array('customfields'))) {
                        
                        $templatePath=$documents->tEMPLATES_PATH;
                        $documentsSavePath=$documents->dOCUMENTS_PATH;
            
                        $templateFile=$ctemplateData[$templateId]['path'];
                        $templateName=$ctemplateData[$templateId]['name'];
                        $fullPath=$templatePath.$templateFile;
                        $saveFileName=$documents->getLogin().'_'.$templateId.'_'.  zbs_rand_string(8).'.docx';
                        $saveFullPath=$documentsSavePath.$saveFileName;
                        
                        $templateData=$documents->getUserData();
                        if (isset($us_config['AGENTS_ASSIGN'])) {
                            $userAgentData=$documents->getUserAgentData();
                        } else {
                            $userAgentData=array();
                        }
                        $documents->setCustomFields();
                        
                        $templateData=  array_merge($templateData,$documents->getCustomFields(),$userAgentData);

                    //parse document template
                    $docx = new DOCXTemplate($fullPath);
                    $docx->set($templateData);
                    $docx->saveAs($saveFullPath);
                    //register document
                    $documents->registerDocument($user_login, $templateId, $saveFileName);
                    
                    //output
                    zbs_DownloadFile($saveFullPath,'docx');
                    
                    }
                    
                } else {
                    show_window(__('Sorry'), __('This template is not accessible'));
                }
            } else {
                   show_window(__('Sorry'), __('Not existing template'));
            }
        }
    }
    
    //show docs list
    show_window(__('Previous documents'),$documents->renderUserDocuments());
    //document downloading subroutine
    if (la_CheckGet(array('documentdownload'))) {
      $documents->downloadUserDocument($_GET['documentdownload']);
    }
    
} else {
    show_window(__('Sorry'), __('Unfortunately documents printing is now disabled'));
}
?>
