<?php
/**
 * ConcordPay Payment Module
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * @category        ConcordPay
 * @package         concordpay/concordpay
 * @version         1.0
 * @author          ConcordPya
 * @copyright       Copyright (c) 2021 ConcordPay
 * @license         http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 *
 * EXTENSION INFORMATION
 *
 * CONCORDPAY API   https://pay.concord.ua/docs/docs/ru/dispatcher.html
 *
 */

/**
 * Payment method ConcordPay process.
 *
 * @author ConcordPay
 */
class ConcordPay
{
    const SIGNATURE_SEPARATOR   = ';';
    const ORDER_SEPARATOR       = '_';
    const CURRENCY_UAH          = 'UAH';
    const TRANSACTION_APPROVED  = 'Approved';
    const TRANSACTION_DECLINED  = 'Declined';
    const RESPONSE_TYPE_PAYMENT = 'payment';
    const RESPONSE_TYPE_REVERSE = 'reverse';

    /** @var string[] */
    protected $keysForResponseSignature = array(
        'merchantAccount',
        'orderReference',
        'amount',
        'currency'
    );

    /** @var string[] */
    protected $keysForSignature = array(
        'merchant_id',
        'order_id',
        'amount',
        'currency_iso',
        'description'
    );

    /** @var array */
    protected $operationTypes = array(
        self::RESPONSE_TYPE_PAYMENT,
        self::RESPONSE_TYPE_REVERSE,
    );

    /** @var string[] */
    protected $allowedCurrencies = array(
        self::CURRENCY_UAH,
    );

    /** @var string */
    private $secret_key;

    /**
     * @param string $secret_key
     */
    public function __construct($secret_key)
    {
        if (empty($secret_key)) {
            throw new InvalidArgumentException(__('Error: Secret key is empty'));
        }

        $this->secret_key = $secret_key;
    }

    /**
     * Generate payment signature.
     *
     * @param array $data
     * @param array $keys
     * @return false|string
     */
    protected function cp_GenerateSignature($data, $keys)
    {
        $hash = array();
        foreach ($keys as $data_key) {
            if (!isset($data[$data_key])) {
                continue;
            }
            if (is_array($data[$data_key])) {
                foreach ($data[$data_key] as $v) {
                    $hash[] = $v;
                }
            } else {
                $hash [] = $data[$data_key];
            }
        }
        $hash = implode(ConcordPay::SIGNATURE_SEPARATOR, $hash);

        return hash_hmac('md5', $hash, $this->secret_key);
    }

    /**
     * Generate payment signature for Request.
     *
     * @param array $data
     * @return false|string
     */
    public function cp_GenerateRequestSignature($data)
    {
        return $this->cp_GenerateSignature($data, $this->keysForSignature);
    }

    /**
     * Generate payment signature for Response.
     *
     * @param array $data
     * @return false|string
     */
    public function cp_GenerateResponseSignature($data)
    {
        return $this->cp_GenerateSignature($data, $this->keysForResponseSignature);
    }

    /**
     * Permitted types of transactions when making a payment.
     *
     * @return string[]
     */
    public function cp_GetOperationTypes()
    {
        return $this->operationTypes;
    }
}