<?php

if (isset($_GET['data'])) {
    $usConfig = parse_ini_file('../../config/userstats.ini');
    if (@$usConfig['PAYMENTID_QR'] or @$usConfig['TG_BOT_QR']) {
        require_once('../engine/api.qrcode.php');
        require_once('../engine/api.ubrouting.php');
        $data = ubRouting::get('data');
        //base 64 decoding required
        if (ubRouting::checkGet('be')) {
            $data = base64_decode($data);
        } else {
            //just URL decode
            $data = urldecode($data);
        }

        $data = (empty($data)) ? 'EMPTY' : $data;
        $options = array('s' => 'qr-h', 'w' => 400, 'h' => 400);
        $qr = new QRCode($data, $options);
        $qr->output_image();
    }
}
