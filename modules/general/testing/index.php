<?php

//just dummy module for testing purposes
error_reporting(E_ALL);

class SMSZilla {

    /**
     * Contains available templates as id=>data
     *
     * @var array
     */
    protected $templates = array();

    /**
     * System message helper placeholder
     *
     * @var object
     */
    protected $messages = '';

    const URL_ME = '?module=testing';

    /**
     * Creates new SMSZilla instance
     * 
     * @return void
     */
    public function __construct() {
        $this->initMessages();
        $this->loadTemplates();
    }

    /**
     * Loads all existing SMS templates from database
     * 
     * @return void
     */
    protected function loadTemplates() {
        $query = "SELECT * from smz_templates";
        $all = simple_queryall($query);
        if (!empty($all)) {
            foreach ($all as $io => $each) {
                $this->templates[$each['id']] = $each;
            }
        }
    }

    /**
     * Inits system messages helper into protected prop
     * 
     * @return void
     */
    protected function initMessages() {
        $this->messages = new UbillingMessageHelper();
    }

    /**
     * Creates new SMS text template
     * 
     * @param string $name
     * @param string $text
     * 
     * @return int
     */
    public function createTemplate($name, $text) {
        $name = mysql_real_escape_string($name);
        $text = mysql_real_escape_string($text);
        $query = "INSERT INTO `smz_templates` (`id`,`name`,`text`) VALUES ";
        $query.= "(NULL,'" . $name . "','" . $text . "');";
        nr_query($query);
        $newId = simple_get_lastid('smz_templates');
        log_register('SMSZILLA TEMPLATE CREATE [' . $newId . ']');
        return ($newId);
    }

    /**
     * Deletes existing template
     * 
     * @param int $templateId
     * 
     * @return void/string on error
     */
    public function deleteTemplate($templateId) {
        $templateId = vf($templateId, 3);
        $result = '';
        if (isset($this->templates[$templateId])) {
            $query = "DELETE from `smz_templates` WHERE `id`='" . $templateId . "';";
            nr_query($query);
            log_register('SMSZILLA TEMPLATE DELETE [' . $templateId . ']');
        } else {
            $result = __('Something went wrong') . ': TEMPLATE_ID_NOT_EXISTS';
        }
        return ($result);
    }

    /**
     * Saves changes in existing template
     * 
     * @param int $templateId
     * @param string $name
     * @param string $text
     * 
     * @return void/string on error
     */
    public function saveTemplate($templateId, $name, $text) {
        $templateId = vf($templateId, 3);
        $result = '';
        if (isset($this->templates[$templateId])) {
            $where = "WHERE `id`='" . $templateId . "'";
            simple_update_field('smz_templates', 'name', $name, $where);
            simple_update_field('smz_templates', 'text', $text, $where);
            log_register('SMSZILLA TEMPLATE CHANGE [' . $templateId . ']');
        } else {
            $result = __('Something went wrong') . ': TEMPLATE_ID_NOT_EXISTS';
        }
        return ($result);
    }

    /**
     * Renders new template creation form
     * 
     * @return string
     */
    public function renderTemplateCreateForm() {
        $result = '';
        $inputs = wf_TextInput('newtemplatename', __('Name'), '', true, '40');
        $inputs.=__('Template') . wf_tag('br');
        $inputs.= wf_TextArea('newtemplatetext', '', '', true, '45x5');
        $inputs.= wf_Submit(__('Create'));
        $result = wf_Form(self::URL_ME . '&templates=true', 'POST', $inputs, 'glamour');
        return ($result);
    }

    /**
     * Renders existing template edit form
     * 
     * @param int $templateId
     * 
     * @return string
     */
    public function renderTemplateEditForm($templateId) {
        $templateId = vf($templateId, 3);
        $result = '';
        if (isset($this->templates[$templateId])) {
            $templateData = $this->templates[$templateId];
            $inputs = wf_HiddenInput('edittemplateid', $templateId);
            $inputs.= wf_TextInput('edittemplatename', __('Name'), $templateData['name'], true, '40');
            $inputs.=__('Template') . wf_tag('br');
            $inputs.= wf_TextArea('edittemplatetext', '', $templateData['text'], true, '45x5');
            $templateSize = strlen($templateData['text']);
            $inputs.=__('Text size') . ' ~' . $templateSize . wf_tag('br');
            $inputs.= wf_Submit(__('Save'));
            $result = wf_Form(self::URL_ME . '&templates=true&edittemplate=' . $templateId, 'POST', $inputs, 'glamour');
        } else {
            $result = $this->messages->getStyledMessage(__('Something went wrong') . ': TEMPLATE_ID_NOT_EXISTS', 'error');
        }
        return ($result);
    }

    /**
     * Renders existing templates list with some controls
     * 
     * @return string
     */
    public function renderTemplatesList() {
        $result = '';
        if (!empty($this->templates)) {
            $cells = wf_TableCell(__('ID'));
            $cells.=wf_TableCell(__('Name'));
            $cells.=wf_TableCell(__('Text'));
            $cells.=wf_TableCell(__('Actions'));
            $rows = wf_TableRow($cells, 'row1');
            foreach ($this->templates as $io => $each) {
                $cells = wf_TableCell($each['id']);
                $cells.=wf_TableCell($each['name']);
                $cells.=wf_TableCell($each['text']);
                $actLinks = wf_JSAlert(self::URL_ME . '&templates=true&deletetemplate=' . $each['id'], web_delete_icon(), $this->messages->getDeleteAlert()) . ' ';
                $actLinks.= wf_JSAlert(self::URL_ME . '&templates=true&edittemplate=' . $each['id'], web_edit_icon(), $this->messages->getEditAlert());

                $cells.=wf_TableCell($actLinks);
                $rows.= wf_TableRow($cells, 'row5');
            }
            $result = wf_TableBody($rows, '100%', 0, 'sortable');
        } else {
            $result = $this->messages->getStyledMessage(__('No existing templates available'), 'warning');
        }
        return ($result);
    }

    public function panel() {
        $result = '';
        $result.=wf_Link(self::URL_ME . '&sending=true', wf_img('skins/icon_sms_micro.gif') . ' ' . __('SMS sending'), false, 'ubButton') . ' ';
        $result.=wf_Link(self::URL_ME . '&templates=true', wf_img('skins/icon_template.png') . ' ' . __('Templates'), false, 'ubButton') . ' ';
        $result.=wf_Link(self::URL_ME . '&filters=true', web_icon_extended() . ' ' . __('Filters'), true, 'ubButton') . ' ';
        if (wf_CheckGet(array('templates'))) {
            $result.=wf_tag('br');
            if (wf_CheckGet(array('edittemplate'))) {
                $result.=wf_BackLink(self::URL_ME . '&templates=true') . ' ';
            } else {
                $result.=wf_modalAuto(web_icon_create() . ' ' . __('Create new template'), __('Create new template'), $this->renderTemplateCreateForm(), 'ubButton');
            }
        }
        return ($result);
    }

}

$smszilla = new SMSZilla();

//rendering module control panel
show_window('', $smszilla->panel());

//templates management
if (wf_CheckGet(array('templates'))) {
//creating new template
    if (wf_CheckPost(array('newtemplatename', 'newtemplatetext'))) {
        $smszilla->createTemplate($_POST['newtemplatename'], $_POST['newtemplatetext']);
        rcms_redirect($smszilla::URL_ME . '&templates=true');
    }

//deleting existing template
    if (wf_CheckGet(array('deletetemplate'))) {
        $templateDeletionResult = $smszilla->deleteTemplate($_GET['deletetemplate']);
        if (empty($templateDeletionResult)) {
            rcms_redirect($smszilla::URL_ME . '&templates=true');
        } else {
            show_error($templateDeletionResult);
        }
    }

//editing existing template
    if (wf_CheckGet(array('edittemplate'))) {
        //save changes to database
        if (wf_CheckPost(array('edittemplateid', 'edittemplatename', 'edittemplatetext'))) {
            $templateEditingResult = $smszilla->saveTemplate($_POST['edittemplateid'], $_POST['edittemplatename'], $_POST['edittemplatetext']);
            if (empty($templateEditingResult)) {
                rcms_redirect($smszilla::URL_ME . '&templates=true&edittemplate=' . $_POST['edittemplateid']);
            } else {
                show_error($templateEditingResult);
            }
        }
        show_window(__('Edit template'), $smszilla->renderTemplateEditForm($_GET['edittemplate']));
    } else {
        show_window(__('Available templates'), $smszilla->renderTemplatesList());
    }
}
?>
