<?php

//just dummy module for testing purposes
error_reporting(E_ALL);

if (cfr('ROOT')) {

    $states = new TaskStates();
    deb($states->renderStatePanel(666));
}