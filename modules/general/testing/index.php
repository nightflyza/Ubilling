<?php

//just dummy module for testing purposes
error_reporting(E_ALL);
if (cfr('ROOT')) {
    $pc=new PixelCraft();
    $pc->loadImage('exports/volk10.jpg');
    
    //$pc->createImage(640,480);
    //$pc->loadImage('skins/unicornwrong.png');


        // $pc->pixelate(3,true);
        //$pc->scale(0.50);
        //$pc->resize(320,240);
    //$pc->fill('black');
    $pc->setLineWidth(2);
   
        for ($x=0;$x<600;$x++) {
            $pc->drawPixel($x,5,'blue');
        }

        for ($y=0;$y<400;$y++) {
            $pc->drawPixel(5,$y,'yellow');
        }
    
        $pc->drawString(20,20,'some test text','red',5,false);
        $pc->drawString(40,200,'some test text','red',5,true);

        $pc->setLineWidth(20);
        $pc->drawRectangle(300,300,400,400,'blue');

        $pc->setFontSize(18);

        
        $pc->drawLine(300,300,400,400,'yellow');
        $pc->drawLine(300,400,400,300,'grey');

        $pc->setFont('skins/Bebas_Neue_Cyrillic.ttf');
        $pc->drawText(200,400,'test TTF ну і з кирилицею','red');
        $pc->drawTextAutoSize(450,10,'ну піде здається АУФ','white','');

        $pc->loadWatermark('skins/taskbar/exhorse.png');
        $pc->drawWatermark(false,380,100);
       
        $pc->saveImage(null,'jpeg');
     //  debarr($pc);
    

}
