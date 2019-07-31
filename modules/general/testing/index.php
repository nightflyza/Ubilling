<?php

//just dummy module for testing purposes
error_reporting(E_ALL);

//Sample ToDo module for wiki documentation!!!!
$moduleBaseUrl = '?module=testing';
$todo = new nya_todo(); // more magic!
$messages = new UbillingMessageHelper();
$result = '';

//rendering inline creation form
$inputs = wf_TextInput('newtasktext', __('Task'), '', false, 40);
$inputs .= wf_HiddenInput('newtaskcreate', 'true');
$inputs .= wf_Submit(__('Create'));
$creationForm = wf_Form('', 'POST', $inputs, 'glamour');
show_window(__('Create new task'), $creationForm);

//catching creation request
if (ubRouting::checkPost(array('newtasktext', 'newtaskcreate'))) {
    $todo->data('text', ubRouting::post('newtasktext', 'mres'));
    $todo->create();
    ubRouting::nav($moduleBaseUrl . '&list=true');
}

//catching deletion request
if (ubRouting::checkGet('deletetodo')) {
    $todo->where('id', '=', ubRouting::get('deletetodo', 'int'));
    $todo->delete();
    ubRouting::nav($moduleBaseUrl . '&list=true');
}

//catching existing todo modification request
if (ubRouting::checkPost(array('edittodoid', 'edittodotext'))) {
    $todo->where('id', '=', ubRouting::post('edittodoid', 'int'));
    $todo->data('text', ubRouting::post('edittodotext', 'mres'));
    $todo->save();
    ubRouting::nav($moduleBaseUrl . '&list=true');
}

//rendering some todo tasks
if (ubRouting::checkGet('list')) {
    $todo->orderBy('id', 'DESC');
    $allTodos = $todo->getAll('id');

    if (!empty($allTodos)) {
        $cells = wf_TableCell(__('Text'));
        $cells .= wf_TableCell(__('Actions'));
        $rows = wf_TableRow($cells, 'row1');
        foreach ($allTodos as $io => $each) {
            $cells = wf_TableCell($each['text']);
            $actControls = wf_JSAlert($moduleBaseUrl . '&deletetodo=' . $each['id'], web_delete_icon(), $messages->getDeleteAlert());
            //building some existing todo record editing form
            $editInputs = wf_HiddenInput('edittodoid', $each['id']);
            $editInputs .= wf_TextInput('edittodotext', __('Text'), $each['text'], false, 40);
            $editInputs .= wf_Submit(__('Save'));
            $editForm = wf_Form('', 'POST', $editInputs, 'glamour');
            $actControls .= wf_modalAuto(web_edit_icon(), __('Edit'), $editForm);
            $cells .= wf_TableCell($actControls);
            $rows .= wf_TableRow($cells, 'row5');
        }
        $result .= wf_TableBody($rows, '100%', 0, 'sortable');
    } else {
        $result .= $messages->getStyledMessage(__('Nothing to show'), 'warning');
    }
    show_window(__('Sample TODO list'), $result);
}




