<?php

//just dummy module for testing purposes
error_reporting(E_ALL);

if (cfr('ROOT')) {
 
 
    $inputData = array(
    13279 => 9230,
    13374 => 9204,
    13448 => 9218,
    13518 => 8827,
    13604 => 9050,
    13695 => 9148,
    13840 => 9246,
    13950 => 9355,
    14121 => 9576,
    );

 

   $mrnn=new MRNN();
   debarr($mrnn->learnDataSet($inputData));
   deb($mrnn->processInputData(13279));


}