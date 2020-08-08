<?php
$this->registerModule($module, 'main', __('Tasks manager'), 'Nightfly', array(
    'TASKMAN' => __('right to control tasks'),
    'TASKMANDATE' => __('right to change tasks date'),
    'TASKMANDONE' => __('right to mark tasks as done'),
    'TASKMANDELETE' => __('right to delete tasks'),
    'TASKMANNODONDATE' => __('deny tasks done date change'),
    'TASKMANNWATCHLOG' => __('can watch log change for tasks'),
    'TASKMANEDITTASK'=> __('right to edit existing tasks'),
    'TASKMANGULAG'=> __('the administrator is repressed'),
    'TSUNCURSED'=>__('immunity for branch curses and gulag')
));

?>
