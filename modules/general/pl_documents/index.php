<?php

if (cfr('PLDOCS')) {
    $altercfg = rcms_parse_ini_file(CONFIG_PATH . "alter.ini");

    //old html templates
    if (!$altercfg['DOCX_SUPPORT']) {
        if (isset($_GET['username'])) {
            $login = vf($_GET['username']);

            //delete subroutine
            if (isset($_GET['deletetemplate'])) {
                zb_DocsDeleteTemplate($_GET['deletetemplate']);
                rcms_redirect("?module=pl_documents&username=" . $login);
            }

            if (isset($_GET['addtemplate'])) {
                //add subroutine
                if (wf_CheckPost(array('newtemplatetitle', 'newtemplatebody'))) {
                    zb_DocsTemplateCreate($_POST['newtemplatetitle'], $_POST['newtemplatebody']);
                    rcms_redirect("?module=pl_documents&username=" . $login);
                }
                //show add form
                zb_DocsTemplateAddForm();
            }

            if (isset($_GET['edittemplate'])) {
                //edit subroutine
                if (wf_CheckPost(array('edittemplatetitle', 'edittemplatebody'))) {
                    zb_DocsTemplateEdit($_GET['edittemplate'], $_POST['edittemplatetitle'], $_POST['edittemplatebody']);
                    rcms_redirect("?module=pl_documents&username=" . $login);
                }
                //show edit form
                zb_DocsTemplateEditForm($_GET['edittemplate']);
            }


            if (!isset($_GET['printtemplate'])) {
                //showing document templates list by default
                if ((!isset($_GET['addtemplate'])) AND ( !isset($_GET['edittemplate']))) {
                    show_window('', wf_Link('?module=pl_documents&username=' . $login . '&addtemplate', 'Create new document template', true, 'ubButton'));
                } else {
                    show_window('', wf_BackLink('?module=pl_documents&username=' . $login, '', true));
                }

                zb_DocsShowAllTemplates($login);
                show_window('', web_UserControls($login));
            } else {
                //document print subroutine
                $parsed_template = zb_DocsParseTemplate($_GET['printtemplate'], $login);
                print($parsed_template);
                die();
            }
        }
    } else {
        //new docx templates processing

        $documents = new ProfileDocuments();

        if (wf_CheckGet(array('username'))) {
            $documents->setLogin($_GET['username']);
        }

        //template printing subroutine
        if (wf_CheckGet(array('print'))) {
            //back link
            show_window('', wf_BackLink('?module=pl_documents&username=' . $documents->getLogin(), '', true));

            $docId = vf($_GET['print'], 3);
            $availableTemplates = $documents->getTemplates();
            $templatePath = $documents::TEMPLATES_PATH;
            $documentsSavePath = $documents::DOCUMENTS_PATH;

            $templateFile = $availableTemplates[$docId]['path'];
            $templateName = $availableTemplates[$docId]['name'];
            $fullPath = $templatePath . $templateFile;
            $saveFileName = $documents->getLogin() . '_' . $docId . '_' . zb_rand_string(8) . '.docx';
            $saveFullPath = $documentsSavePath . $saveFileName;

            $documents->loadAllUserData();
            $templateData = $documents->getUserData();
            $userAgentData = $documents->getUserAgentData();
            $templateData = array_merge($templateData, $userAgentData);

            if (wf_checkget(array('custom'))) {
                show_window(__('Custom template fields'), $documents->customDocumentFieldsForm());

                if (wf_CheckPost(array('customfields'))) {
                    $documents->setCustomFields();
                    $templateData = array_merge($templateData, $documents->getCustomFields());

                    //parse document template
                    $docx = new DOCXTemplate($fullPath);
                    $docx->set($templateData);
                    $docx->saveAs($saveFullPath);
                    //registering generated custom fields document
                    $documents->registerDocument($documents->getLogin(), $docId, $saveFileName);
                    //output
                    zb_DownloadFile($saveFullPath, 'docx');
                }
            } else {

                //parse document template
                $docx = new DOCXTemplate($fullPath);
                $docx->set($templateData);
                $docx->saveAs($saveFullPath);
                //registering generated document
                $documents->registerDocument($documents->getLogin(), $docId, $saveFileName);
                //output
                zb_DownloadFile($saveFullPath, 'docx');
            }
        } else {
            //template downloading
            if (wf_CheckGet(array('download'))) {
                zb_DownloadFile($documents::TEMPLATES_PATH . $_GET['download'], 'docx');
            }

            //template deletion
            if (wf_CheckGet(array('deletetemplate'))) {
                $documents->deleteTemplate($_GET['deletetemplate']);
                rcms_redirect('?module=pl_documents&username=' . $documents->getLogin());
            }

            //showing available templates
            show_window(__('Available document templates'), $documents->renderTemplatesList());

            //uploading new templates

            $uploadControl = wf_modal(__('Upload template'), __('Upload template'), $documents->uploadForm(), 'ubButton', '600', '300');
            show_window(__('Settings'), $uploadControl);
            //template upload subroutine
            if (wf_CheckPost(array('uploadtemplate'))) {
                $documents->doUpload();
                rcms_redirect('?module=pl_documents&username=' . $documents->getLogin());
            }

            //showing user personal documents
            $documents->loadUserDocuments($documents->getLogin());
            show_window(__('Previously generated documents for this user'), $documents->renderUserDocuments());
        }

        //existing document downloading
        if (wf_CheckGet(array('documentdownload'))) {
            zb_DownloadFile($documents::DOCUMENTS_PATH . $_GET['documentdownload'], 'docx');
        }

        //document deletion from database
        if (wf_CheckGet(array('deletedocument'))) {
            $documents->unregisterDocument($_GET['deletedocument']);
            rcms_redirect('?module=pl_documents&username=' . $documents->getLogin());
        }

        show_window('', web_UserControls($documents->getLogin()));
    }
} else {
    show_error(__('You cant control this module'));
}
?>
