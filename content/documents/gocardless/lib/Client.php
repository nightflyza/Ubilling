<?php

namespace GoCardlessPro;

/**
 * Main GoCardlessPro Client for making API calls
 */
class Client
{

    const CA_CERT_FILENAME = 'cacert.pem';

    /**
    * @var Core\ApiClient Internal reference to Api Client
    */
    private $api_client;

    /**
     * @param array $config
     *     An array of config parameters
     *
     *     @type string $environment
     *     @type string $access_token
     *     @type float $timeout
     *     @type string $http_client
     */
    public function __construct($config)
    {
        $this->validate_config($config);

        $access_token = $config['access_token'];

        if (isset($config['base_url'])) {
            $endpoint_url = $config['base_url'];
        } else if (isset($config['environment'])) {
            $endpoint_url = $this->getUrlForEnvironment($config['environment']);
        } else {
            throw new \InvalidArgumentException("Please specify an environment");
        }

        if (isset($config['http_client'])) {
            $http_client = $config['http_client'];
        } else {
            $stack = \GuzzleHttp\HandlerStack::create();
            $stack->push(RetryMiddlewareFactory::buildMiddleware());

            $timeout = 0;
            if(isset($config['timeout'])) {
                $timeout = $config['timeout'];
            }

            $http_client = new \GuzzleHttp\Client(
                [
                'base_uri' => $endpoint_url,
                'timeout' => $timeout,
                'headers' => array(
                'GoCardless-Version' => '2015-07-06',
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
                'Authorization' => "Bearer " . $access_token,
                'GoCardless-Client-Library' => 'gocardless-pro-php',
                'GoCardless-Client-Version' => '3.2.1',
                'User-Agent' => $this->getUserAgent()
                ),
                'http_errors' => false,
                'verify' => $this->getCACertPath(),
                'handler' => $stack
                ]
            );
        }

        $this->api_client = new \GoCardlessPro\Core\ApiClient($http_client);
    }

    
    /**
     * Service for interacting with bank details lookups
     * @return Services\BankDetailsLookupsService
     */
    public function bankDetailsLookups()
    {
        if (!isset($this->bank_details_lookups)) {
            $this->bank_details_lookups = new Services\BankDetailsLookupsService($this->api_client);
        }

        return $this->bank_details_lookups;
    }
    
    /**
     * Service for interacting with creditors
     * @return Services\CreditorsService
     */
    public function creditors()
    {
        if (!isset($this->creditors)) {
            $this->creditors = new Services\CreditorsService($this->api_client);
        }

        return $this->creditors;
    }
    
    /**
     * Service for interacting with creditor bank accounts
     * @return Services\CreditorBankAccountsService
     */
    public function creditorBankAccounts()
    {
        if (!isset($this->creditor_bank_accounts)) {
            $this->creditor_bank_accounts = new Services\CreditorBankAccountsService($this->api_client);
        }

        return $this->creditor_bank_accounts;
    }
    
    /**
     * Service for interacting with customers
     * @return Services\CustomersService
     */
    public function customers()
    {
        if (!isset($this->customers)) {
            $this->customers = new Services\CustomersService($this->api_client);
        }

        return $this->customers;
    }
    
    /**
     * Service for interacting with customer bank accounts
     * @return Services\CustomerBankAccountsService
     */
    public function customerBankAccounts()
    {
        if (!isset($this->customer_bank_accounts)) {
            $this->customer_bank_accounts = new Services\CustomerBankAccountsService($this->api_client);
        }

        return $this->customer_bank_accounts;
    }
    
    /**
     * Service for interacting with customer notifications
     * @return Services\CustomerNotificationsService
     */
    public function customerNotifications()
    {
        if (!isset($this->customer_notifications)) {
            $this->customer_notifications = new Services\CustomerNotificationsService($this->api_client);
        }

        return $this->customer_notifications;
    }
    
    /**
     * Service for interacting with events
     * @return Services\EventsService
     */
    public function events()
    {
        if (!isset($this->events)) {
            $this->events = new Services\EventsService($this->api_client);
        }

        return $this->events;
    }
    
    /**
     * Service for interacting with mandates
     * @return Services\MandatesService
     */
    public function mandates()
    {
        if (!isset($this->mandates)) {
            $this->mandates = new Services\MandatesService($this->api_client);
        }

        return $this->mandates;
    }
    
    /**
     * Service for interacting with mandate imports
     * @return Services\MandateImportsService
     */
    public function mandateImports()
    {
        if (!isset($this->mandate_imports)) {
            $this->mandate_imports = new Services\MandateImportsService($this->api_client);
        }

        return $this->mandate_imports;
    }
    
    /**
     * Service for interacting with mandate import entries
     * @return Services\MandateImportEntriesService
     */
    public function mandateImportEntries()
    {
        if (!isset($this->mandate_import_entries)) {
            $this->mandate_import_entries = new Services\MandateImportEntriesService($this->api_client);
        }

        return $this->mandate_import_entries;
    }
    
    /**
     * Service for interacting with mandate pdfs
     * @return Services\MandatePdfsService
     */
    public function mandatePdfs()
    {
        if (!isset($this->mandate_pdfs)) {
            $this->mandate_pdfs = new Services\MandatePdfsService($this->api_client);
        }

        return $this->mandate_pdfs;
    }
    
    /**
     * Service for interacting with payments
     * @return Services\PaymentsService
     */
    public function payments()
    {
        if (!isset($this->payments)) {
            $this->payments = new Services\PaymentsService($this->api_client);
        }

        return $this->payments;
    }
    
    /**
     * Service for interacting with payouts
     * @return Services\PayoutsService
     */
    public function payouts()
    {
        if (!isset($this->payouts)) {
            $this->payouts = new Services\PayoutsService($this->api_client);
        }

        return $this->payouts;
    }
    
    /**
     * Service for interacting with payout items
     * @return Services\PayoutItemsService
     */
    public function payoutItems()
    {
        if (!isset($this->payout_items)) {
            $this->payout_items = new Services\PayoutItemsService($this->api_client);
        }

        return $this->payout_items;
    }
    
    /**
     * Service for interacting with redirect flows
     * @return Services\RedirectFlowsService
     */
    public function redirectFlows()
    {
        if (!isset($this->redirect_flows)) {
            $this->redirect_flows = new Services\RedirectFlowsService($this->api_client);
        }

        return $this->redirect_flows;
    }
    
    /**
     * Service for interacting with refunds
     * @return Services\RefundsService
     */
    public function refunds()
    {
        if (!isset($this->refunds)) {
            $this->refunds = new Services\RefundsService($this->api_client);
        }

        return $this->refunds;
    }
    
    /**
     * Service for interacting with subscriptions
     * @return Services\SubscriptionsService
     */
    public function subscriptions()
    {
        if (!isset($this->subscriptions)) {
            $this->subscriptions = new Services\SubscriptionsService($this->api_client);
        }

        return $this->subscriptions;
    }
    
    private function getUrlForEnvironment($environment)
    {
        $environment_urls = array(
            "live" => "https://api.gocardless.com/",
            "sandbox" => "https://api-sandbox.gocardless.com/"
        );

        if(!array_key_exists($environment, $environment_urls)) {
            throw new \InvalidArgumentException("$environment is not a valid environment, please use one of " . implode(array_keys($environment_urls), ", "));
        }

        return $environment_urls[$environment];
    }

    /**
     * Ensures a config is valid and sets defaults where required
     *
     * @param array[string]mixed $config the client configuration options
     */
    private function validate_config($config)
    {
        $required_option_keys = array('access_token', 'environment');

        foreach ($required_option_keys as $required_option_key) {
            if (!isset($config[$required_option_key])) {
                throw new \Exception('Missing required option `' . $required_option_key . '`.');
            }

            if (!is_string($config[$required_option_key])) {
                throw new \Exception('Option `'. $required_option_key .'` can only be a string.');
            }
        }
    }

    /**
     * Gets the client's user agent for API calls
     *
     * @return string
     */
    private function getUserAgent()
    {
        $curlinfo = curl_version();
        $uagent = array();
        $uagent[] = 'gocardless-pro-php/3.2.1';
        $uagent[] = 'schema-version/2015-07-06';
        $uagent[] = 'GuzzleHttp/' . \GuzzleHttp\Client::VERSION;
        $uagent[] = 'php/' . phpversion();
        if (extension_loaded('curl') && function_exists('curl_version')) {
            $uagent[] = 'curl/' . \curl_version()['version'];
            $uagent[] = 'curl/' . \curl_version()['host'];
        }
        return implode(' ', $uagent);
    }

    /**
     * Internal function for finding the path to cacert.pem
     * @return Path to the cacert.pem file
     */
    private function getCACertPath()
    {
        return dirname(__FILE__) . "/../" . self::CA_CERT_FILENAME;
    }
}
