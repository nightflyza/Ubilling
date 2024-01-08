<?php

//just dummy module for testing purposes
error_reporting(E_ALL);
if (cfr('ROOT')) {

    $file='skins/taskbar/globe.jpg';
    if (ubRouting::post('file')) {
        $file='skins/taskbar/'.ubRouting::post('file');
    }
    $pc = new PixelCraft();
    $pc->loadImage($file);
    $pc->resize(32,32);
    
    $width = $pc->getImageWidth();
    $height = $pc->getImageHeight();

    $allIcons=rcms_scandir('skins/taskbar/','*.*');
    $sel=array();
    foreach($allIcons as $io=>$each) {
        $sel[$each]=$each;
    }
    $inputs=wf_Selector('file',$sel,'icon');
    $inputs.=wf_Submit('image 2 ascii');
    $form=wf_Form('','POST',$inputs);
    deb($form);

    $map = $pc->getColorMap(false);
    
    $result = '';
    foreach ($map as $x => $ys) {

        foreach ($ys as $y => $color) {
            $hex=$pc->rgbToHex($color);
            //$pc->addColor($hex,$color['r'],$color['g'],$color['b']);
            //$pc->drawPixel($x,$y,$hex);
            $result .= '<font style="font-weight: bold;" color="' . $hex . '">@</font>';
        }
        $result .= '<br>';
    }

   deb('<small>'.$result.'</small>');
  // $pc->renderImage();
   
    

}
