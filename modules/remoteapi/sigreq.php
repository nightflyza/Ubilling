<?php

/**
 * Signup requests API implementation
 *
 * GET  /?module=remoteapi&key=SERIAL&action=sigreq&param=config
 * POST /?module=remoteapi&key=SERIAL&action=sigreq&param=create
 * 
 * POST create payload format: JSON body (Content-Type: application/json)
 *   {
 *     "city":     "",   // optional; used if CITY_DISPLAY. If CITY_SELECTABLE, must be from cities list
 *     "street":   "",   // required. If STREET_SELECTABLE, must be from streets list
 *     "build":    "",   // required
 *     "apt":      "",   // optional; empty stored as "0"
 *     "realname": "",   // required unless NAME_DISPLAY is off (then backend stores "Not specified")
 *     "phone":    "",   // required; digits only
 *     "email":    "",   // optional; accepted only if EMAIL_DISPLAY
 *     "service":  "",   // optional; if SERVICES list is not empty, must match it. Empty list -> "Internet"
 *     "tariff":   "",   // optional; if TARIFFS list is not empty, must match it. 
 *     "notes":    "",   // optional; accepted only if NOTES_DISPLAY
 *     "ip":       ""    // optional visitor IP
 *   }
 * 
 * Reply: { "error": bool, "created": bool, "id": int, "error_message": "" }
 * error_message: EMPTY_REQUEST, REQUIRED_FIELDS, INVALID_CITY, INVALID_STREET,
 *   INVALID_SERVICE, INVALID_TARIFF, INVALID_EMAIL, HIDDEN_ADDRESS, CREATE_FAILED
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
            log_register('SIGREQ FAIL `UNKNOWN_PARAM`');
            die('ERROR:UNKNOWN_PARAM');
        }
    } else {
        $sigreqOp = ubRouting::get('param', 'gigasafe');
        if ($sigreqOp == 'create') {
            log_register('SIGREQ CREATE FAIL `DISABLED`');
        }
        die('ERROR:SIGREQ_DISABLED');
    }
}



//
//
//
//                  __------__
//                /~          ~\
//               |    //^\\//^\|
//             /~~\  ||  o| |o|:~\      Oh.. My great ISP...
//            | |6   ||___|_|_||:|    / Please grant me some internet
//             \__.  /      o  \/'
//              |   (       O   )
//     /~~~~\    `\  \         /
//    | |~~\ |     )  ~------~`\
//   /' |  | |   /     ____ /~~~)\
//  (_/'   | | |     /'    |    ( |
//         | | |     \    /   __)/ \
//         \  \ \      \/    /' \   `\
//           \  \|\        /   | |\___|
//             \ |  \____/     | |
//             /^~>  \        _/ <
//            |  |         \       \
//            |  | \        \        \
//            -^-\  \       |        )
//                 `\_______/^\______/
//
//