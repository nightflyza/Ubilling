<?php
if (isset($_GET['data'])) {
	include ("modules/engine/api.barcodeqr.php");
	$data=trim($_GET['data']);
	$qr = new BarcodeQR();
        $qr->text($data);
	$qr->draw();
}


?>
