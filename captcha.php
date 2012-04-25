<?php header("Content-type: image/png"); class F70b29c49 { function F817491bc($Vf18d9f2c){
$V78805a22 = imagecreatefrompng("skins/icon.png"); $Vc4564d05 = imageColorAllocate($V78805a22, 0, 0, 0);
$V5f166114 = imageColorAllocate($V78805a22, 255, 255, 255); imageString($V78805a22, 5, 20, 3, $Vf18d9f2c ,$V5f166114);
imagePNG($V78805a22); } function F5584efcd() { if (isset($_GET['ident'])) { if (!empty($_GET['ident'])) {
 $Vb4a88417=substr(md5($_GET['ident']),0,5); } } else { $Vb4a88417=''; } return ($Vb4a88417); } }
$V70b29c49= new F70b29c49(); $V70b29c49->F817491bc($V70b29c49->F5584efcd()); ?> 