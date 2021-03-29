<?php

//just dummy module for testing purposes
error_reporting(E_ALL);

if (cfr('ROOT')) {
    
    $ptv=new PTV();
    
    $subData=$ptv->getUserData('sometestuser');
    debarr($subData);
    debarr($ptv->getPlaylistsAll($subData['id']));
    //debarr($ptv->createPlayList($subData['id']));
    
}