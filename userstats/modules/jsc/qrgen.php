<?php

if (isset($_GET['data'])) {
    $usConfig = parse_ini_file('../../config/userstats.ini');
    if ($usConfig['PAYMENTID_QR']) {
        require_once('../engine/api.qrcode.php');
        $data = trim($_GET['data']);
        if (empty($data)) {
            $data = 'EMPTY';
        }
        $options = array('s' => 'qr-h', 'w' => 400, 'h' => 400);
        $qr = new QRCode($data, $options);
        $qr->output_image();
    }
}
