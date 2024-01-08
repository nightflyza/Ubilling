<?php

//just dummy module for testing purposes
error_reporting(E_ALL);
if (cfr('ROOT')) {
    $pc = new PixelCraft();
    $pc->loadImage('skins/taskbar/user_add.jpg');
    $pc->resize(32,32);
    //$pc->createImage(50, 50);
 //   $pc->fill('green');
   // $pc->drawString(10, 10, 'some', 'black', 3,false);
   // $pc->drawPixel(1, 1, 'red');
    //$pc->saveImage(null,'png');
    
    $width = $pc->getImageWidth();
    $height = $pc->getImageHeight();
    deb($width . 'x' . $height);

    $map = $pc->getColorMap(false);
    //debarr($map);

    $pc->createImage(128,128);
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
