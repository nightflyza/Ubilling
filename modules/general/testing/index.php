<?php

//just dummy module for testing purposes
error_reporting(E_ALL);

if (ubRouting::checkGet('drop')) {
    $result='POST:'.print_r($_POST,true);
    $result.='GET:'.print_r($_GET,true);
    die($result);
}

$url = 'http://jesus.ctv/dev/ubilling/?module=testing&drop=true';
$release = new OmaeUrl($url);

$release->postData('field1', 'test1');

$test=$release->get();
debarr($test);

if ($release->error()) {
    debarr($release->error());
}

