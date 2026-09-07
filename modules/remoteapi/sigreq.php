<?php

/**
 * Public signup requests API for signup3
 *
 * GET  /?module=remoteapi&key=SERIAL&action=sigreq&param=config
 * POST /?module=remoteapi&key=SERIAL&action=sigreq&param=create
 *
 * @return void
 */
if (ubRouting::get('action') == 'sigreq') {
    if (@$alterconf['SIGREQ_ENABLED']) {
        $sigreqOp = ubRouting::get('param', 'gigasafe');
        $sigreqReply = array();
        $sigreqOk = false;

        if ($sigreqOp == 'config') {
            $signupRequests = new SignupRequests();
            $sigreqReply = $signupRequests->getPublicPayload();
            $sigreqOk = true;
        } else {
            if ($sigreqOp == 'create') {
                $rawBody = file_get_contents('php://input');
                $requestData = array();
                if (!empty($rawBody)) {
                    $decodedBody = json_decode($rawBody, true);
                    if (is_array($decodedBody)) {
                        $requestData = $decodedBody;
                    }
                }
                $signupRequests = new SignupRequests();
                $sigreqReply = $signupRequests->createFromApi($requestData);
                $sigreqOk = true;
            }
        }

        if ($sigreqOk) {
            header('Last-Modified: ' . gmdate('r'));
            header('Content-Type: application/json; charset=UTF-8');
            header('Cache-Control: no-store, no-cache, must-revalidate');
            header('Pragma: no-cache');
            die(json_encode($sigreqReply));
        } else {
            die('ERROR:UNKNOWN_PARAM');
        }
    } else {
        die('ERROR:SIGREQ_DISABLED');
    }
}
