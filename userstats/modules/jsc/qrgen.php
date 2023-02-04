<?php

if (isset($_GET['data'])) {
    $usConfig = parse_ini_file('../../config/userstats.ini');
    if ($usConfig['PAYMENTID_QR']) {
        require_once('../engine/api.barcodeqr.php');
        $data = trim($_GET['data']);
        if (empty($data)) {
            $data = 'EMPTY';
        }
        $qr = new BarcodeQR();
        $qr->text($data);
        $qr->draw();
    }
}

