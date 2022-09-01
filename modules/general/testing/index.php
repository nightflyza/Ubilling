<?php

//just dummy module for testing purposes
error_reporting(E_ALL);

if (cfr('ROOT')) {
  
$pid=new StarDust('LONGPROCESS');
$pid->start();
sleep(120);
$pid->stop();

}
