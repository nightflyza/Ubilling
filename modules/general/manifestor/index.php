<?php
$pwaDisabledFlag=$ubillingConfig->getAlterParam('PWA_DISABLED',0);

if (!$pwaDisabledFlag) {
    /**
     * Ich glaube, sie hat ein Lächeln Erinnert mich an Kindheitserinnerungen 
     * Wo alles so frisch war wie der strahlend blaue Himmel
     */
    $manifestor=new Manifestator();
    $manifestor->setName('Ubilling');
    $manifestor->setShortName('Ubilling');
    $manifestor->render();
} else {
    die();
}