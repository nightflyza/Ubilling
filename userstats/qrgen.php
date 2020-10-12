<?php
if (isset($_GET['data'])) {
    include ("modules/engine/api.barcodeqr.php");
    $data = trim($_GET['data']);
    if (empty($data)) {
        $data = 'EMPTY';
    }
    $qr = new BarcodeQR();
    $qr->text($data);
    $qr->draw();
}

