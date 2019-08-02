<?php

$fdbarchive = new FDBArchive();

if (ubRouting::get('ajax')) {
    $fdbarchive->ajArchiveData();
}

show_window('', $fdbarchive->renderNavigationPanel());
show_window(__('FDB') . ' ' . __('Archive'), $fdbarchive->renderArchive());
