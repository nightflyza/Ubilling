<?php

//just dummy module for testing purposes
error_reporting(E_ALL);

if (cfr('ROOT')) {
    $test = new MapOn();
    
    $curday = curdate();
    $routes = $test->getRoutes($curday . 'T00:00:00Z', $curday . 'T23:59:59Z');
    
    debarr($routes);
}