<?php

/**
 * Frontend for PUMB (FUIB) payment acceptance via OpenPayz
 *
 * Protocol: JSON + detached JWS (header x-jws-signature, body {"payload":"..."})
 *
 * Endpoints (mod_rewrite or ?method= fallback):
 *   POST /check
 *   POST /confirm
 *   POST /payment-notification
 *   GET  /status
 *   POST /report
 *
 * MULTI_MODE=0 (default): single contragent, DEFAULT_SUBDIVISION_CODE
 * MULTI_MODE=1: subdivision_code from subscriber Ubilling agent (+ mapping)
 */

error_reporting(E_ALL);

include ("../../libs/api.openpayz.php");

class Pumb extends PaySysProto {
    /**
     * Paths / paysys identity
     */
    const PATH_CONFIG = 'config/pumb.ini';
    const PATH_AGENTCODES = 'config/agentcodes_mapping.ini';
    const PATH_TRANSACTS = 'tmp/';
    const PATH_DEBUGLOG = 'processing_debug.log';
    const HASH_PREFIX = 'PUMB_';
    const PAYSYS = 'PUMB';

    /**
     * Available API methods
     *
     * @var array
     */
    protected $methodsAvailable = array(
        'check',
        'confirm',
        'payment-notification',
        'status',
        'report'
    );

    /**
     * Current method name
     *
     * @var string
     */
    protected $method = '';

    /**
     * Decoded request payload (JSON object as array)
     *
     * @var array
     */
    protected $requestPayload = array();

    /**
     * Raw base64url payload from request body
     *
     * @var string
     */
    protected $requestPayloadB64 = '';

    /**
     * Service code from config / request
     *
     * @var string
     */
    protected $serviceCode = '7';

    /**
     * Default subdivision when MULTI_MODE is off
     *
     * @var string
     */
    protected $defaultSubdivisionCode = '1';

    /**
     * Optional multi-contragent mode
     *
     * @var bool
     */
    protected $multiMode = false;

    /**
     * Path to facility private key PEM
     *
     * @var string
     */
    protected $facilityPrivateKeyPath = 'keys/facility_priv_key.pem';

    /**
     * Path to bank public key PEM
     *
     * @var string
     */
    protected $bankPublicKeyPath = 'keys/fuib_pub_key.pem';

    /**
     * JWS alg for our responses
     *
     * @var string
     */
    protected $jwsAlgorithm = 'RS256';

    /**
     * Verify incoming signatures
     *
     * @var bool
     */
    protected $verifySignature = true;

    /**
     * Sign outgoing responses
     *
     * @var bool
     */
    protected $signResponse = true;

    /**
     * REC_AMOUNT amount_restriction: NONE or EQ
     *
     * @var string
     */
    protected $amountRestriction = 'NONE';

    /**
     * Cached facility private key resource
     *
     * @var resource|null
     */
    protected $facilityPrivateKey = null;

    /**
     * Cached bank public key resource
     *
     * @var resource|null
     */
    protected $bankPublicKey = null;

    /**
     * All op_customers virtualid => login
     *
     * @var array
     */
    protected $opCustomersAll = array();

    /**
     * Preloads config and keys
     *
     * @return void
     */
    public function __construct() {
        parent::__construct(self::PATH_CONFIG);
        $this->setOptions();
        $this->loadPumbOptions();
        $this->loadAgentCodesMapping();
        $this->ensureTmpDir();
        $this->loadCryptoKeys();
    }

    /**
     * Loads PUMB-specific options from config
     *
     * @return void
     */
    protected function loadPumbOptions() {
        if (!empty($this->config)) {
            if (isset($this->config['SERVICE_CODE'])) {
                $this->serviceCode = $this->config['SERVICE_CODE'];
            }
            if (isset($this->config['DEFAULT_SUBDIVISION_CODE'])) {
                $this->defaultSubdivisionCode = $this->config['DEFAULT_SUBDIVISION_CODE'];
            }
            if (isset($this->config['MULTI_MODE'])) {
                $this->multiMode = ($this->config['MULTI_MODE']) ? true : false;
            }
            if (isset($this->config['FACILITY_PRIVATE_KEY'])) {
                $this->facilityPrivateKeyPath = $this->config['FACILITY_PRIVATE_KEY'];
            }
            if (isset($this->config['BANK_PUBLIC_KEY'])) {
                $this->bankPublicKeyPath = $this->config['BANK_PUBLIC_KEY'];
            }
            if (isset($this->config['JWS_ALGORITHM'])) {
                $this->jwsAlgorithm = strtoupper($this->config['JWS_ALGORITHM']);
            }
            if (isset($this->config['VERIFY_SIGNATURE'])) {
                $this->verifySignature = ($this->config['VERIFY_SIGNATURE']) ? true : false;
            }
            if (isset($this->config['SIGN_RESPONSE'])) {
                $this->signResponse = ($this->config['SIGN_RESPONSE']) ? true : false;
            }
            if (isset($this->config['AMOUNT_RESTRICTION'])) {
                $this->amountRestriction = strtoupper($this->config['AMOUNT_RESTRICTION']);
            }
        }
    }

    /**
     * Creates tmp directory if missing
     *
     * @return void
     */
    protected function ensureTmpDir() {
        if (!file_exists(self::PATH_TRANSACTS)) {
            mkdir(self::PATH_TRANSACTS, 0755, true);
        }
    }

    /**
     * Loads OpenSSL key resources
     *
     * @return void
     */
    protected function loadCryptoKeys() {
        if ($this->signResponse) {
            if (file_exists($this->facilityPrivateKeyPath)) {
                $privPem = file_get_contents($this->facilityPrivateKeyPath);
                $this->facilityPrivateKey = openssl_pkey_get_private($privPem);
                if (!$this->facilityPrivateKey) {
                    $this->replyFatal('Cannot load facility private key');
                }
            } else {
                $this->replyFatal('Facility private key not found: ' . $this->facilityPrivateKeyPath);
            }
        }

        if ($this->verifySignature) {
            if (file_exists($this->bankPublicKeyPath)) {
                $pubPem = file_get_contents($this->bankPublicKeyPath);
                $this->bankPublicKey = openssl_pkey_get_public($pubPem);
                if (!$this->bankPublicKey) {
                    $this->replyFatal('Cannot load bank public key');
                }
            } else {
                $this->replyFatal('Bank public key not found: ' . $this->bankPublicKeyPath);
            }
        }
    }

    /**
     * Maps JWS alg name to OpenSSL algorithm constant
     *
     * @param string $alg
     *
     * @return int|false
     */
    protected function mapOpenSslAlgo($alg) {
        $result = false;
        $alg = strtoupper($alg);
        if ($alg == 'RS256' or $alg == 'ES256') {
            $result = OPENSSL_ALGO_SHA256;
        } else {
            if ($alg == 'RS384' or $alg == 'ES384') {
                $result = OPENSSL_ALGO_SHA384;
            } else {
                if ($alg == 'RS512' or $alg == 'ES512') {
                    $result = OPENSSL_ALGO_SHA512;
                }
            }
        }
        return ($result);
    }

    /**
     * Reads x-jws-signature HTTP header
     *
     * @return string
     */
    protected function getJwsHeader() {
        $result = '';
        if (isset($_SERVER['HTTP_X_JWS_SIGNATURE']) and $_SERVER['HTTP_X_JWS_SIGNATURE'] !== '') {
            $result = $_SERVER['HTTP_X_JWS_SIGNATURE'];
        } else {
            if (function_exists('getallheaders')) {
                $headers = getallheaders();
                if (!empty($headers)) {
                    foreach ($headers as $name => $value) {
                        if (strtolower($name) == 'x-jws-signature') {
                            $result = $value;
                        }
                    }
                }
            }
        }
        return ($result);
    }

    /**
     * Verifies detached JWS and returns decoded payload array
     *
     * @param string $payloadB64
     * @param string $jwsDetached
     *
     * @return array|false
     */
    protected function verifyAndDecodePayload($payloadB64, $jwsDetached) {
        $result = false;

        if (!$this->verifySignature) {
            $json = self::urlSafeBase64Decode($payloadB64);
            $decoded = json_decode($json, true);
            if (is_array($decoded)) {
                $result = $decoded;
            }
        } else {
            if (!empty($payloadB64) and !empty($jwsDetached) and $this->bankPublicKey) {
                $token = preg_replace('/\.\./', '.' . $payloadB64 . '.', $jwsDetached, 1);
                $parts = explode('.', $token);
                if (count($parts) == 3) {
                    $headerJson = self::urlSafeBase64Decode($parts[0]);
                    $header = json_decode($headerJson, true);
                    $alg = (isset($header['alg'])) ? $header['alg'] : $this->jwsAlgorithm;
                    $opensslAlgo = $this->mapOpenSslAlgo($alg);
                    if ($opensslAlgo !== false) {
                        $signingInput = $parts[0] . '.' . $parts[1];
                        $signature = self::urlSafeBase64Decode($parts[2]);
                        $ok = openssl_verify($signingInput, $signature, $this->bankPublicKey, $opensslAlgo);
                        if ($ok === 1) {
                            $payloadJson = self::urlSafeBase64Decode($parts[1]);
                            $decoded = json_decode($payloadJson, true);
                            if (is_array($decoded)) {
                                $result = $decoded;
                            }
                        }
                    }
                }
            }
        }

        return ($result);
    }

    /**
     * Signs payload and sends JSON response with detached JWS header
     *
     * @param array $payload
     * @param int   $httpCode
     *
     * @return void
     */
    protected function replySigned($payload, $httpCode = 200) {
        $payloadB64 = self::urlSafeBase64Encode(json_encode($payload));
        $jwsHeader = '';

        if ($this->signResponse and $this->facilityPrivateKey) {
            $header = array(
                'alg' => $this->jwsAlgorithm,
                'typ' => 'JWT'
            );
            $headerB64 = self::urlSafeBase64Encode(json_encode($header));
            $signingInput = $headerB64 . '.' . $payloadB64;
            $opensslAlgo = $this->mapOpenSslAlgo($this->jwsAlgorithm);
            $signature = '';
            $signed = openssl_sign($signingInput, $signature, $this->facilityPrivateKey, $opensslAlgo);
            if ($signed) {
                $sigB64 = self::urlSafeBase64Encode($signature);
                $jwsHeader = $headerB64 . '..' . $sigB64;
            } else {
                $this->replyFatal('Failed to sign response');
            }
        }

        self::writeDebugLog('REPLY HTTP ' . $httpCode . ' payload=' . print_r($payload, true), $this->debugModeON, 0, self::PATH_DEBUGLOG);

        $statusText = 'OK';
        if ($httpCode == 400) {
            $statusText = 'Bad Request';
        } else {
            if ($httpCode == 500) {
                $statusText = 'Internal Server Error';
            }
        }

        header('HTTP/1.1 ' . $httpCode . ' ' . $statusText);
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store, no-cache, must-revalidate');
        header('Pragma: no-cache');
        if ($jwsHeader !== '') {
            header('x-jws-signature: ' . $jwsHeader);
        }

        $body = json_encode(array('payload' => $payloadB64));
        die($body);
    }

    /**
     * Replies with protocol Error object
     *
     * @param string $code
     * @param string $message
     * @param int    $httpCode
     *
     * @return void
     */
    protected function replyProtocolError($code, $message, $httpCode = 400) {
        $this->replySigned(array(
            'code' => $code,
            'message' => $message
        ), $httpCode);
    }

    /**
     * Fatal boot error (no JWS)
     *
     * @param string $message
     *
     * @return void
     */
    protected function replyFatal($message) {
        self::writeDebugLog('FATAL: ' . $message, $this->debugModeON, 0, self::PATH_DEBUGLOG);
        header('HTTP/1.1 500 Internal Server Error');
        header('Content-Type: text/plain; charset=utf-8');
        die($message);
    }

    /**
     * Override PaySysProto error reply
     *
     * @param string $errorCode
     * @param string $errorMsg
     *
     * @return void
     */
    protected function replyError($errorCode = '', $errorMsg = '') {
        $this->replyFatal($errorCode . ' - ' . $errorMsg);
    }

    /**
     * Detects API method from ?method= or REQUEST_URI path
     *
     * @return string
     */
    protected function detectMethod() {
        $result = '';

        if (isset($_GET['method']) and $_GET['method'] !== '') {
            $result = trim($_GET['method']);
        } else {
            $uri = '';
            if (isset($_SERVER['PATH_INFO']) and $_SERVER['PATH_INFO'] !== '') {
                $uri = $_SERVER['PATH_INFO'];
            } else {
                if (isset($_SERVER['REQUEST_URI'])) {
                    $uri = $_SERVER['REQUEST_URI'];
                }
            }
            if ($uri !== '') {
                $uri = preg_replace('/\?.*$/', '', $uri);
                $uri = trim($uri, '/');
                $parts = explode('/', $uri);
                if (!empty($parts)) {
                    $last = end($parts);
                    if (in_array($last, $this->methodsAvailable)) {
                        $result = $last;
                    }
                }
            }
        }

        return ($result);
    }

    /**
     * Generates UUID v4-like operation_id
     *
     * @return string
     */
    protected function generateUuid() {
        $data = array();
        for ($i = 0; $i < 16; $i++) {
            $data[$i] = mt_rand(0, 255);
        }
        $data[6] = ($data[6] & 0x0f) | 0x40;
        $data[8] = ($data[8] & 0x3f) | 0x80;
        $hex = '';
        for ($i = 0; $i < 16; $i++) {
            $hex .= sprintf('%02x', $data[$i]);
        }
        $result = substr($hex, 0, 8) . '-' .
            substr($hex, 8, 4) . '-' .
            substr($hex, 12, 4) . '-' .
            substr($hex, 16, 4) . '-' .
            substr($hex, 20, 12);
        return ($result);
    }

    /**
     * Sanitizes id for filesystem name
     *
     * @param string $id
     *
     * @return string
     */
    protected function safeId($id) {
        $result = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $id);
        return ($result);
    }

    /**
     * Pending state file path by payment_id
     *
     * @param string $paymentId
     *
     * @return string
     */
    protected function statePathByPayment($paymentId) {
        $result = rtrim(self::PATH_TRANSACTS, '/') . '/pay_' . $this->safeId($paymentId);
        return ($result);
    }

    /**
     * Index file path by operation_id
     *
     * @param string $operationId
     *
     * @return string
     */
    protected function statePathByOperation($operationId) {
        $result = rtrim(self::PATH_TRANSACTS, '/') . '/op_' . $this->safeId($operationId);
        return ($result);
    }

    /**
     * Loads pending/confirmed payment state
     *
     * @param string $paymentId
     * @param string $operationId
     *
     * @return array
     */
    protected function loadState($paymentId = '', $operationId = '') {
        $result = array();
        $path = '';

        if ($paymentId !== '') {
            $path = $this->statePathByPayment($paymentId);
        } else {
            if ($operationId !== '') {
                $opPath = $this->statePathByOperation($operationId);
                if (file_exists($opPath)) {
                    $paymentIdFromIdx = trim(file_get_contents($opPath));
                    if ($paymentIdFromIdx !== '') {
                        $path = $this->statePathByPayment($paymentIdFromIdx);
                    }
                }
            }
        }

        if ($path !== '' and file_exists($path)) {
            $raw = file_get_contents($path);
            $data = unserialize($raw);
            if (is_array($data)) {
                $result = $data;
            }
        }

        return ($result);
    }

    /**
     * Saves payment state and operation_id index
     *
     * @param array $state
     *
     * @return bool
     */
    protected function saveState($state) {
        $result = false;
        if (!empty($state['payment_id'])) {
            $path = $this->statePathByPayment($state['payment_id']);
            $written = file_put_contents($path, serialize($state));
            if ($written !== false) {
                $result = true;
                if (!empty($state['operation_id'])) {
                    file_put_contents($this->statePathByOperation($state['operation_id']), $state['payment_id']);
                }
            }
        }
        return ($result);
    }

    /**
     * Extracts CLIENT_ID from check fields[]
     *
     * @param array $fields
     *
     * @return string
     */
    protected function extractClientId($fields) {
        $result = '';
        if (!empty($fields) and is_array($fields)) {
            foreach ($fields as $io => $field) {
                if (isset($field['alias']) and $field['alias'] == 'CLIENT_ID') {
                    if (isset($field['value'])) {
                        $result = trim($field['value']);
                    }
                }
            }
        }
        return ($result);
    }

    /**
     * Resolves subdivision_code for check response
     *
     * @param string $userLogin
     *
     * @return string
     */
    protected function resolveSubdivisionCode($userLogin) {
        $result = $this->defaultSubdivisionCode;

        if ($this->multiMode) {
            $agentId = $this->getUBAgentAssignedID($userLogin);
            if (!empty($agentId)) {
                if ($this->agentcodesON and !empty($this->agentcodesMapping[$agentId])) {
                    $result = $this->agentcodesMapping[$agentId];
                } else {
                    $result = strval($agentId);
                }
            }
        }

        return ($result);
    }

    /**
     * Builds ExtraField list for check (DEBT, REC_AMOUNT, ADDRESS, FULL_NAME)
     *
     * @param string $userLogin
     * @param array  $userData
     *
     * @return array
     */
    protected function buildExtraFields($userLogin, $userData) {
        $result = array();
        $cash = 0;
        if (isset($userData['Cash'])) {
            $cash = $userData['Cash'];
        }

        $debtKop = 0;
        $recKop = 0;
        if ($cash < 0) {
            $debtKop = intval(round(abs($cash) * 100));
            $recKop = $debtKop;
        } else {
            $debtKop = 0;
            if (!empty($userData['Tariff'])) {
                $prices = self::getTariffPriceAll($userData['Tariff']);
                if (isset($prices[$userData['Tariff']])) {
                    $recKop = intval(round($prices[$userData['Tariff']] * 100));
                }
            }
        }

        $result[] = array(
            'alias' => 'DEBT',
            'value' => strval($debtKop)
        );

        $recField = array(
            'alias' => 'REC_AMOUNT',
            'value' => strval($recKop)
        );
        if ($this->amountRestriction == 'EQ') {
            $recField['options'] = array(
                'amount_restriction' => 'EQ'
            );
        } else {
            $recField['options'] = array(
                'amount_restriction' => 'NONE'
            );
        }
        $result[] = $recField;

        $realname = self::getUserRealnames($userLogin);
        if ($realname !== '') {
            $result[] = array(
                'alias' => 'FULL_NAME',
                'value' => $realname
            );
        }

        $address = self::getUserAddresses($userLogin, $this->addressCityDisplay);
        if ($address !== '') {
            $result[] = array(
                'alias' => 'ADDRESS',
                'value' => $address
            );
        }

        return ($result);
    }

    /**
     * Builds PayerInfo for check
     *
     * @param string $userLogin
     *
     * @return array
     */
    protected function buildPayerInfo($userLogin) {
        $result = array();
        $realname = self::getUserRealnames($userLogin);
        $address = self::getUserAddresses($userLogin, $this->addressCityDisplay);
        $phones = self::getUserCellPhone($userLogin, false);
        $msisdn = '';
        if (!empty($phones[$userLogin]) and is_array($phones[$userLogin])) {
            $msisdn = $phones[$userLogin][0];
        }

        if ($realname !== '') {
            $result['name'] = $realname;
        }
        if ($msisdn !== '') {
            $result['msisdn'] = $msisdn;
        }
        if ($address !== '') {
            $result['address'] = $address;
        }

        return ($result);
    }

    /**
     * OpenPayz hash for payment_id
     *
     * @param string $paymentId
     *
     * @return string
     */
    protected function buildHash($paymentId) {
        $result = self::HASH_PREFIX . $paymentId;
        return ($result);
    }

    /**
     * Converts kopiykas to UAH float string for OpenPayz
     *
     * @param int $amountKop
     *
     * @return string
     */
    protected function kopToSumm($amountKop) {
        $result = number_format(($amountKop / 100), 2, '.', '');
        return ($result);
    }

    /**
     * POST /check - payer search
     *
     * @return void
     */
    protected function handleCheck() {
        $fields = (isset($this->requestPayload['fields'])) ? $this->requestPayload['fields'] : array();
        $paymentId = (isset($this->requestPayload['payment_id'])) ? strval($this->requestPayload['payment_id']) : '';
        $clientId = $this->extractClientId($fields);

        if ($clientId === '') {
            $this->replyProtocolError('VALIDATION_ERROR', 'CLIENT_ID is required', 400);
        }

        if (!isset($this->opCustomersAll[$clientId])) {
            $this->replyProtocolError('CLIENT_NOT_FOUND', 'Payer not found', 400);
        }

        $userLogin = $this->opCustomersAll[$clientId];
        $userData = self::getUserStargazerData($userLogin);
        $payerId = $this->generateUuid();
        $operationId = $this->generateUuid();
        $extraFields = $this->buildExtraFields($userLogin, $userData);
        $payerInfo = $this->buildPayerInfo($userLogin);
        $subdivision = $this->resolveSubdivisionCode($userLogin);

        $reply = array(
            'payer_id' => $payerId,
            'service_code' => $this->serviceCode,
            'fields' => $extraFields,
            'operation_id' => $operationId
        );

        if (!empty($payerInfo)) {
            $reply['payer_info'] = $payerInfo;
        }

        if ($subdivision !== '') {
            $reply['receiver_info'] = array(
                'subdivision_code' => $subdivision
            );
        }

        $state = array(
            'payment_id' => ($paymentId !== '') ? $paymentId : $operationId,
            'operation_id' => $operationId,
            'payer_id' => $payerId,
            'customer_id' => $clientId,
            'user_login' => $userLogin,
            'service_code' => $this->serviceCode,
            'subdivision_code' => $subdivision,
            'fields' => $extraFields,
            'status' => 'PENDING',
            'amount' => 0,
            'created' => curdatetime()
        );

        // If bank already sent payment_id - index by it; else by operation_id until confirm
        if ($paymentId === '') {
            $state['payment_id'] = 'pending_' . $operationId;
        }
        $this->saveState($state);

        $this->replySigned($reply, 200);
    }

    /**
     * POST /confirm - accept payment (idempotent)
     *
     * @return void
     */
    protected function handleConfirm() {
        $payerId = (isset($this->requestPayload['payer_id'])) ? strval($this->requestPayload['payer_id']) : '';
        $paymentId = (isset($this->requestPayload['payment_id'])) ? strval($this->requestPayload['payment_id']) : '';
        $serviceCode = (isset($this->requestPayload['service_code'])) ? strval($this->requestPayload['service_code']) : $this->serviceCode;
        $amountKop = (isset($this->requestPayload['amount'])) ? intval($this->requestPayload['amount']) : 0;
        $operationIdIn = (isset($this->requestPayload['operation_id'])) ? strval($this->requestPayload['operation_id']) : '';

        if ($paymentId === '' or $amountKop <= 0) {
            $this->replyProtocolError('VALIDATION_ERROR', 'payment_id and positive amount required', 400);
        }

        $state = $this->loadState($paymentId, $operationIdIn);

        // Try find by operation_id if payment_id was unknown at check time
        if (empty($state) and $operationIdIn !== '') {
            $state = $this->loadState('', $operationIdIn);
        }

        // Fallback: search pending states by payer_id
        if (empty($state) and $payerId !== '') {
            $state = $this->findStateByPayerId($payerId);
        }

        if (empty($state)) {
            $this->replyProtocolError('OPERATION_FORBIDDEN', 'Unknown payment / check required first', 400);
        }

        $customerId = $state['customer_id'];
        $operationId = $state['operation_id'];
        if (!isset($this->opCustomersAll[$customerId])) {
            $this->replyProtocolError('CLIENT_NOT_FOUND', 'Payer not found', 400);
        }

        // Idempotent re-confirm
        if (isset($state['status']) and $state['status'] == 'SUCCESS') {
            $this->replySigned(array(
                'service_code' => $serviceCode,
                'operation_id' => $operationId,
                'status' => 'SUCCESS'
            ), 200);
        }

        $hash = $this->buildHash($paymentId);
        $existing = self::getOPTransactDataByHash($hash);

        if (empty($existing)) {
            $summ = $this->kopToSumm($amountKop);
            $note = 'payment_id=' . $paymentId . '; operation_id=' . $operationId . '; payer_id=' . $payerId;
            op_TransactionAdd($hash, $summ, $customerId, self::PAYSYS, $note);
            op_ProcessHandlers();
        }

        $state['payment_id'] = $paymentId;
        $state['amount'] = $amountKop;
        $state['status'] = 'SUCCESS';
        $state['confirmed'] = curdatetime();
        $state['service_code'] = $serviceCode;
        $this->saveState($state);

        $this->replySigned(array(
            'service_code' => $serviceCode,
            'operation_id' => $operationId,
            'status' => 'SUCCESS'
        ), 200);
    }

    /**
     * Finds state file by payer_id (scan tmp - rare fallback)
     *
     * @param string $payerId
     *
     * @return array
     */
    protected function findStateByPayerId($payerId) {
        $result = array();
        $found = false;
        $dir = rtrim(self::PATH_TRANSACTS, '/');
        if (is_dir($dir)) {
            $files = scandir($dir);
            if (!empty($files)) {
                foreach ($files as $file) {
                    if (!$found and strpos($file, 'pay_') === 0) {
                        $raw = file_get_contents($dir . '/' . $file);
                        $data = unserialize($raw);
                        if (is_array($data) and isset($data['payer_id']) and $data['payer_id'] == $payerId) {
                            $result = $data;
                            $found = true;
                        }
                    }
                }
            }
        }
        return ($result);
    }

    /**
     * POST /payment-notification - final bank status
     *
     * @return void
     */
    protected function handlePaymentNotification() {
        $paymentId = (isset($this->requestPayload['payment_id'])) ? strval($this->requestPayload['payment_id']) : '';
        $operationId = (isset($this->requestPayload['operation_id'])) ? strval($this->requestPayload['operation_id']) : '';
        $amountKop = (isset($this->requestPayload['amount'])) ? intval($this->requestPayload['amount']) : 0;
        $status = (isset($this->requestPayload['status'])) ? strtoupper(strval($this->requestPayload['status'])) : '';

        if ($paymentId === '' or $status === '') {
            $this->replyProtocolError('VALIDATION_ERROR', 'payment_id and status required', 400);
        }

        $state = $this->loadState($paymentId, $operationId);

        if ($status == 'SUCCESS') {
            if (!empty($state)) {
                $customerId = $state['customer_id'];
                $hash = $this->buildHash($paymentId);
                $existing = self::getOPTransactDataByHash($hash);
                if (empty($existing) and $amountKop > 0 and isset($this->opCustomersAll[$customerId])) {
                    // Safety net if confirm did not credit yet
                    $summ = $this->kopToSumm($amountKop);
                    $note = 'notify payment_id=' . $paymentId . '; operation_id=' . $operationId;
                    op_TransactionAdd($hash, $summ, $customerId, self::PAYSYS, $note);
                    op_ProcessHandlers();
                }
                $state['status'] = 'SUCCESS';
                $state['amount'] = $amountKop;
                $state['notified'] = curdatetime();
                $this->saveState($state);
            }
        } else {
            // FAILED / CANCELED - do not credit; mark local state
            if (!empty($state)) {
                $state['status'] = $status;
                $state['amount'] = $amountKop;
                $state['notified'] = curdatetime();
                $this->saveState($state);
            }
        }

        // Empty object per protocol
        $this->replySigned(array(), 200);
    }

    /**
     * GET /status
     *
     * @return void
     */
    protected function handleStatus() {
        $paymentId = (isset($_GET['payment_id'])) ? strval($_GET['payment_id']) : '';
        $operationId = (isset($_GET['operation_id'])) ? strval($_GET['operation_id']) : '';

        if ($paymentId === '' and $operationId === '') {
            $this->replyProtocolError('VALIDATION_ERROR', 'payment_id or operation_id required', 400);
        }

        $state = $this->loadState($paymentId, $operationId);
        if (empty($state)) {
            // Fallback: check OpenPayz transaction by payment_id
            if ($paymentId !== '') {
                $existing = self::getOPTransactDataByHash($this->buildHash($paymentId));
                if (!empty($existing)) {
                    $this->replySigned(array(
                        'service_code' => $this->serviceCode,
                        'operation_id' => ($operationId !== '') ? $operationId : '',
                        'payment_id' => $paymentId,
                        'status' => 'SUCCESS'
                    ), 200);
                }
            }
            $this->replyProtocolError('OPERATION_FORBIDDEN', 'Operation not found', 400);
        }

        $reply = array(
            'service_code' => isset($state['service_code']) ? $state['service_code'] : $this->serviceCode,
            'operation_id' => $state['operation_id'],
            'payment_id' => $state['payment_id'],
            'status' => $state['status']
        );
        $this->replySigned($reply, 200);
    }

    /**
     * POST /report - daily registry acknowledge
     *
     * @return void
     */
    protected function handleReport() {
        $reportIdBank = (isset($this->requestPayload['id'])) ? strval($this->requestPayload['id']) : '';
        if ($reportIdBank === '') {
            $reportIdBank = $this->generateUuid();
        }

        $localId = 'RPT_' . $this->safeId($reportIdBank);
        $path = rtrim(self::PATH_TRANSACTS, '/') . '/' . $localId;
        file_put_contents($path, serialize($this->requestPayload));

        $this->replySigned(array(
            'report_id' => $localId
        ), 200);
    }

    /**
     * Parses POST body and verifies JWS
     *
     * @return void
     */
    protected function ingestSignedPost() {
        $raw = file_get_contents('php://input');
        self::writeDebugLog('RAW POST: ' . $raw, $this->debugModeON, 1, self::PATH_DEBUGLOG);

        if ($raw === '' or $raw === false) {
            $this->replyProtocolError('VALIDATION_ERROR', 'Empty request body', 400);
        }

        $envelope = json_decode($raw, true);
        if (!is_array($envelope) or !isset($envelope['payload'])) {
            $this->replyProtocolError('VALIDATION_ERROR', 'payload field required', 400);
        }

        $this->requestPayloadB64 = $envelope['payload'];
        $jws = $this->getJwsHeader();
        self::writeDebugLog('JWS header: ' . $jws, $this->debugModeON, 0, self::PATH_DEBUGLOG);

        $decoded = $this->verifyAndDecodePayload($this->requestPayloadB64, $jws);
        if ($decoded === false) {
            $this->replyProtocolError('VALIDATION_ERROR', 'JWS validation failed', 400);
        }

        $this->requestPayload = $decoded;
        self::writeDebugLog('DECODED: ' . print_r($decoded, true), $this->debugModeON, 0, self::PATH_DEBUGLOG);
    }

    /**
     * Dispatches current method
     *
     * @return void
     */
    protected function processRequests() {
        $this->opCustomersAll = op_CustomersGetAll();

        if ($this->method == 'status') {
            $this->handleStatus();
        } else {
            $this->ingestSignedPost();

            if ($this->method == 'check') {
                $this->handleCheck();
            } else {
                if ($this->method == 'confirm') {
                    $this->handleConfirm();
                } else {
                    if ($this->method == 'payment-notification') {
                        $this->handlePaymentNotification();
                    } else {
                        if ($this->method == 'report') {
                            $this->handleReport();
                        } else {
                            $this->replyProtocolError('VALIDATION_ERROR', 'Unknown method', 400);
                        }
                    }
                }
            }
        }
    }

    /**
     * Entry point
     *
     * @return void
     */
    public function listen() {
        $this->method = $this->detectMethod();
        self::writeDebugLog('METHOD=' . $this->method . ' URI=' . (isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : ''), $this->debugModeON, 1, self::PATH_DEBUGLOG);

        if ($this->method === '' or !in_array($this->method, $this->methodsAvailable)) {
            header('HTTP/1.1 400 Bad Request');
            header('Content-Type: text/plain; charset=utf-8');
            die('Unknown or missing method. Use /check|/confirm|/payment-notification|/status|/report or ?method=');
        }

        $this->processRequests();
    }
}

$frontend = new Pumb();
$frontend->listen();
