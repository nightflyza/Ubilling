<?php

//just dummy module for testing purposes
error_reporting(E_ALL);

if (cfr('ROOT')) {

    $taskRanks = new Stigma('TASKRANKS');
    debarr($taskRanks);
}