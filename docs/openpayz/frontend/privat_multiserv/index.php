<?php

// including API OpenPayz
include ("../../libs/api.openpayz.php");

class PrivatStrictMultiserv extends PaySysProto {
    /**
     * Predefined stuff
     */
    const PATH_CONFIG = 'config/privatmultiserv.ini';

    /**
     * Paysys specific predefines
     * If you need multiple instances of this paysys for somehow -
     * just add a numeric index to HASH_PREFIX and PAYSYS constants, like:
     * PB_MULTISERV_1_, PB_MULTISERV_2_, PB_MULTISERV_n_
     * PB_MULTISERV_1, PB_MULTISERV_2, PB_MULTISERV_n
     * or distinguish it in any other way, suitable for you
     */
    const HASH_PREFIX = 'PB_MULTISERV_';
    const PAYSYS      = 'PB_MULTISERV';

    const PB_XML_XSITYPE_DEBTPACK   = 'DebtPack';
    const PB_XML_XSITYPE_GATEWAY    = 'Gateway';


    /**
     * Placeholder for a "payment_method" GET parameter
     *
     * @var string
     */
    protected $paymentMethod = '';

    /**
     * Placeholder for available payment methods
     *
     * @var array
     */
    protected $paymentMethodsAvailable = array('Search', 'Check', 'Pay');

    /**
     * Placeholder for payment sum amount from PB requests
     *
     * @var double
     */
    protected $paymentSum = 0;

    /**
     * Subscriber's virtual payment ID
     *
     * @var string
     */
    protected $subscriberVirtualID = '';

    /**
     * Subscriber's login from PrivatBank
     *
     * @var string
     */
    protected $subscriberLogin = '';

    /**
     * Paysys merchant credentials from CONTRAGENT EXT INFO module
     *
     * @var string
     */
    protected $merchantCreds = '';

    /**
     * Transaction reference string we return to PB on "Check" and receive on "Pay"
     *
     * @var string
     */
    protected $pbTransactReference = '';

    /**
     * ID attribute of data section on "Pay" request
     *
     * @var string
     */
    protected $pbPaymentID = '';

    /**
     * ServiceCode attribute of ServiceGroup section on "Pay" request
     *
     * @var string
     */
    protected $pbServiceCode = '';

    /**
     * CompanyCode attribute of ServiceGroup section on "Pay" request
     *
     * @var string
     */
    protected $pbCompanyCode = '';


    /**
     * Contains received by listener preprocessed request data
     *
     * @var array
     */
    protected $receivedXML = array();

    /**
     * List of possible error codes
     *
     * @var array
     */
    protected $errorCodes = array(
                                   2 => 'Subscriber not found',
                                   7 => 'Transaction duplicate'
                                 );


    /**
     * Preloads all required configuration, sets needed object properties
     *
     * @return void
     */
    public function __construct() {
        parent::__construct(self::PATH_CONFIG);
        $this->setOptions();
    }

    /**
     * Validates gets PrivatBank merchant ID and password from contragents ext info by Ubilling agent ID
     *
     * @return array
     */
    protected function getMerchantCredsByPaySysName() {
        $this->merchantCreds = $this->getUBAgentDataExten('', self::PAYSYS);

        return ($this->merchantCreds);
    }

    /**
     * Returns XML response head according to conditions
     *
     * @return string
     */
    protected function getXMLResponseHead() {
        if ($this->paymentMethod == 'Search') {
            $transfer   = '';
            $xsitype    = self::PB_XML_XSITYPE_DEBTPACK;
            $attribute  = 'billPeriod="' . date("Ym") . '"';
        } else {
            $transfer   = '</Transfer>';
            $xsitype    = self::PB_XML_XSITYPE_GATEWAY;
            $attribute  = 'reference="' . ($this->paymentMethod == 'Check'
                                           ? PaySysProto::genRandNumString()
                                           : $this->pbTransactReference);
        }

        $xmlHead = '
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Transfer xmlns="http://debt.privatbank.ua/Transfer" interface="Debt" action="' . $this->paymentMethod . '">
    <Data xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:type="' . $xsitype . '" ' . $attribute . ' />'
. $transfer;

        return ($xmlHead);
    }

    /**
     * Returns XML response PayerInfoBlock
     *
     * @param $lsAttrAdd
     *
     * @return string
     */
    protected function getXMLPayerInfoBlock($lsAttrAdd = true) {
        $realname   = $this->getUserRealnames($this->subscriberLogin);
        $address    = $this->getUserAddresses($this->subscriberLogin);
        $mobile     = $this->getUserCellPhone($this->subscriberLogin);
        $lsAttr     = ($lsAttrAdd ? ' ls="' . $this->subscriberVirtualID . '"' : '');

        $xmlPayerBlock = '
        <PayerInfo billIdentifier="' . $this->subscriberVirtualID . '"' . $lsAttr . '>
            <Fio>' . $realname . '</Fio>
            <Phone>' . $mobile . '</Phone>
            <Address>' . $address . '</Address>
        </PayerInfo>        
        ';

        return ($xmlPayerBlock);
    }

    /**
     * Returns XML response DebtServiceBlock
     *
     * @return string
     */
    protected function getXMLServiceGroupBlock() {
        $userdata   = $this->getUserStargazerData($this->subscriberLogin);
        $userBalance = ($this->subscriberBalanceDecimals < 0)
            ? $userdata['Cash'] : (($this->subscriberBalanceDecimals == 0)
                ? intval($userdata['Cash'], 10) : round($userdata['Cash'], $this->subscriberBalanceDecimals, PHP_ROUND_HALF_EVEN));
        $xmlServiceGroupBlock = '
        <ServiceGroup>
        ';

        foreach ($this->merchantCreds as $io => $eachMerch) {
            $tmpCompanyCode = $eachMerch['internal_paysys_id'];
            $tmpServiceCode = $eachMerch['internal_paysys_srv_id'];
            $tmpServiceName = $eachMerch['paysys_token'];
            $tmpPercent     = $eachMerch['paysys_secret_key'];
            $tmpAmountToPay = ($tmpPercent / 100) * $this->paymentSum;

            $tmpDebtServiceBlock = '
            <DebtService  serviceCode="' . $tmpServiceCode . '" >
            <CompanyInfo>
              <CompanyCode>' . $tmpCompanyCode . '</CompanyCode>
                </CompanyInfo>
                <DebtInfo amountToPay="' . $tmpAmountToPay . '">
                  <Balance>' . $userBalance . '</Balance>
                </DebtInfo>
                <ServiceName>' . $tmpServiceName . '</ServiceName>
                ' . $this->getXMLPayerInfoBlock() . '
            </DebtService>
            ';

            $xmlServiceGroupBlock.= $tmpDebtServiceBlock;
        }

        $xmlServiceGroupBlock.= '
        </ServiceGroup>
        ';

        return ($xmlServiceGroupBlock);
    }

    /**
     * Extracts some essential data, like VirtualID, payment sum, transaction reference ID
     * from a certain PB request, as their payload quite differs
     *
     * @param $paymentMethod
     *
     * @return void
     */
    protected function getEssentialDataFromPBRequest() {
        switch ($this->paymentMethod) {
            case 'Search':
                if (!empty($this->receivedXML['Transfer']['Data']['Unit']) and
                    is_array($this->receivedXML['Transfer']['Data']['Unit'])) {

                    $tmpArr = $this->receivedXML['Transfer']['Data']['Unit'];
                    foreach ($tmpArr as $io => $eachAttr) {
                        if (!empty($eachAttr['name']) and !empty($eachAttr['value'])) {
                            if ($eachAttr['name'] = 'bill_identifier') {
                                $this->subscriberVirtualID = $eachAttr['value'];
                            } elseif ($eachAttr['name'] = 'summ') {
                                $this->paymentSum = $eachAttr['value'];
                            }
                        }
                    }
                }
                break;

            case 'Check':
            case 'Pay':
                if (!empty($this->receivedXML['Transfer']['Data']['PayerInfo_attr']['billIdentifier'])) {
                    $this->subscriberVirtualID = $this->receivedXML['Transfer']['Data']['PayerInfo_attr']['billIdentifier'];
                }

                if (!empty($this->receivedXML['Transfer']['Data']['TotalSum'])) {
                    $this->paymentSum = $this->receivedXML['Transfer']['Data']['TotalSum'];
                }

                if ($this->paymentMethod == 'Pay') {
                    if (!empty($this->receivedXML['Transfer']['Data']['CompanyInfo']['CheckReference'])) {
                        $this->pbTransactReference = $this->receivedXML['Transfer']['Data']['CompanyInfo']['CheckReference'];
                    }
                    if (!empty($this->receivedXML['Transfer']['Data_attr']['id'])) {
                        $this->pbPaymentID = $this->receivedXML['Transfer']['Data_attr']['id'];
                    }
                    if (!empty($this->receivedXML['Transfer']['Data']['ServiceGroup']['Service_attr'])) {
                        $this->pbServiceCode = $this->receivedXML['Transfer']['Data']['ServiceGroup']['Service_attr'];
                    }
                    if (!empty($this->receivedXML['Transfer']['Data']['CompanyInfo']['UnitCode'])) {
                        $this->pbCompanyCode = $this->receivedXML['Transfer']['Data']['CompanyInfo']['UnitCode'];
                    }
                }
                break;
        }
    }


    protected function replySearch() {
        $xmlReply = $this->getXMLResponseHead();
        $xmlReply.= $this->getXMLPayerInfoBlock(false);
        $xmlReply.= $this->getXMLServiceGroupBlock();
        $xmlReply.= '
    </Data>
</Transfer>        
        ';
        $xmlReply = trim($xmlReply);
        die($xmlReply);
    }


    protected function replyCheck() {
        $xmlReply = $this->getXMLResponseHead();
        die($xmlReply);
    }


    protected function replyPay() {
        $opHash     = self::HASH_PREFIX . $this->pbTransactReference;
        $opHashData = $this->getOPTransactDataByHash($opHash);

        if (empty($opHashData)) {
            $srvName    = '';
            $merchCreds = $this->getMerchantCredsByPaySysName();
            foreach ($merchCreds as $io => $eachMerch) {
                if ($eachMerch['internal_paysys_srv_id'] == $this->pbServiceCode and
                    $eachMerch['internal_paysys_id'] == $this->pbCompanyCode) {

                    $srvName = $eachMerch['paysys_token'];
                }
            }

            //push transaction to database
            op_TransactionAdd($opHash, $this->paymentSum, $this->subscriberVirtualID,
                              self::PAYSYS,
                              self::PAYSYS . ': [' . $this->pbPaymentID . '] - ' . $srvName);
            op_ProcessHandlers();

            $xmlReply = $this->getXMLResponseHead();
            die($xmlReply);
        } else {
            $this->replyError(400, 'TRANSACTION_ALREADY_EXISTS', 7);
        }
    }

    /**
     * Sets HTTP headers before reply
     */
    protected function setHTTPHeaders() {
        header('Content-Type: text/xml; charset=UTF-8');
    }

    /**
     * Returns XML error reply
     *
     * @param $errorCode
     *
     * @return false|string
     */
    protected function replyError($errorCode = 400, $errorMsg = 'SOMETHING WENT WRONG', $xmlTplCode = 0) {
        if (empty($xmlTplCode)) {
            die($errorCode . ' - ' . $errorMsg);
        } else {
            $xmlTplMsg = $this->errorCodes[$xmlTplCode];
            $xmlErrorTemplate = '
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Transfer xmlns="http://debt.privatbank.ua/Transfer" interface="Debt" action="' . $this->paymentMethod . '">
    <Data xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:type="ErrorInfo" code="' . $xmlTplCode . '">
        <Message>' . $xmlTplMsg . '</Message>
    </Data>
</Transfer>';

            die($xmlErrorTemplate);
        }
    }

    /**
     * Processes requests
     */
    protected function processRequests() {
        $this->getEssentialDataFromPBRequest();

        if (empty($this->subscriberVirtualID)) {
            $this->replyError(422, 'SUBSCRIBER_ID_UNSPECIFIED');
        }

        if (empty($this->paymentSum)) {
            $this->replyError(422, 'PAYMENT_AMOUNT_UNSPECIFIED');
        }

        if ($this->paymentMethod == 'Pay' and empty($this->pbTransactReference)) {
            $this->replyError(422, 'TRANSACTION_REFERENCE_ID_UNSPECIFIED');
        }

        $opCustomersAll = op_CustomersGetAll();

        if (empty($opCustomersAll[$this->subscriberVirtualID])) {
            $this->replyError(404, 'SUBSCRIBER_NOT_FOUND', 2);
        }

        $this->subscriberLogin = $opCustomersAll[$this->subscriberVirtualID];

        switch ($this->paymentMethod) {
            case 'Search':
                $this->getMerchantCredsByPaySysName();

                if (empty($this->merchantCreds)) {
                    $this->replyError(422, 'MERCHANT_EXTINFO_ABSENT');
                }

                $this->replySearch();
                break;

            case 'Check':
                $this->replyCheck();
                break;

            case 'Pay':
                $this->replyPay();
                break;

            default:
                $this->replyError(422, 'PAYMENT_METHOD_UNKNOWN');
        }
    }

    /**
     * Listen to your heart when he's calling for you
     * Listen to your heart, there's nothing else you can do
     *
     * @return void
     */
    public function listen() {
        $rawRequest = file_get_contents('php://input');
        $this->receivedXML = xml2array($rawRequest);
        $this->setHTTPHeaders();

        if (empty($this->receivedXML) or empty($this->receivedXML['Transfer']['Data']) or empty($this->receivedXML['Transfer_attr'])) {
            $this->replyError(400, 'PAYLOAD_EMPTY_OR_INCONSISTENT');
        } else {
            $this->paymentMethod = (empty($this->receivedXML['Transfer_attr']['action']) ? '' : trim($this->receivedXML['Transfer_attr']['action']));

            if (in_array($this->paymentMethod, $this->paymentMethodsAvailable)) {
                $this->processRequests();
            } else {
                $this->replyError(422, 'PAYMENT_METHOD_UNKNOWN');
            }
        }
    }
}

$frontend = new PrivatStrictMultiserv();
$frontend->listen();

