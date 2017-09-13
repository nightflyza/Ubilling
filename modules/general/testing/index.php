<?php

//just dummy module for testing purposes
error_reporting(E_ALL);

$wcpe = new WifiCPE();
show_window(__('Create new CPE'), $wcpe->renderCpeCreateForm());
?>