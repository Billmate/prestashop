<?php
class PaymentConfigValidator
{
    /**
     * @var array
     */
    protected $errorsList = [];

    /**
     * @var array
     */
    protected $module = [];


    public function __construct($module)
    {
        $this->module = $module;
    }

    /**
     * @return array
     */
    public function checkAvailabilityTestMode()
    {
        $bmConnection = $this->getBillmateConnection();
        $requestData = $this->getRequestData();

        $accountInfo = $bmConnection->getAccountInfo($requestData);
        $methodsConfigMap = $this->getMethodsMap();

        if (isset($accountInfo['paymentoptions'])) {
            $preparedOptions = $this->prepareAccountOptions($accountInfo['paymentoptions']);
            foreach ($methodsConfigMap as $paymentCode => $option) {
                if ( isset($preparedOptions[$paymentCode])
                    || Configuration::get($option['config_code'])
                ) {
                    continue;
                } else {
                    Configuration::updateValue($option['config_code'], 1);
                    $this->errorsList[] = $option['error_message'];
                }
            }
        } else {
            $this->errorsList[] = $this->l('Invalid Billmate credentials');
        }

        return $this->errorsList;
    }

    public function getMethodsMap()
    {
        $methodsConfigMap = [
            1 => [
                'config_code' => 'BINVOICE_MOD',
                'error_message' => $this->l('The Invoice method not available for you Billmate account')
            ],
            4 => [
                'config_code' => 'BPARTPAY_MOD',
                'error_message' => $this->l('The Partpay method not available for you Billmate account')
            ],
            8 => [
                'config_code' => 'BCARDPAY_MOD',
                'error_message' => $this->l('The Cardpay method not available for you Billmate account')
            ],
            16 => [
                'config_code' => 'BBANKPAY_MOD',
                'error_message' => $this->l('The Bankpay method not available for you Billmate account')
            ],
            32 => [
                'config_code' => 'BINVOICESERVICE_MOD',
                'error_message' => $this->l('The InvoiceService method not available for you Billmate account')
            ]
        ];

        return $methodsConfigMap;
    }

    /**
     * @param $paymentOptions
     *
     * @return array
     */
    public function prepareAccountOptions($paymentOptions)
    {
        $activeOptions = [];
        foreach ($paymentOptions as $paymentOption ) {
            $activeOptions[$paymentOption['method']] = true;
        }

        return $activeOptions;
    }

    /**
     * @param      $string
     * @param bool $specific
     * @param null $locale
     *
     * @return mixed
     */
    public function l($string, $specific = 'billmategateway')
    {
        return Translate::getModuleTranslation($this->module, $string, $specific);
    }

    /**
     * @return BillMate
     */
    public function getBillmateConnection()
    {
        $eid = Configuration::get('BILLMATE_ID');
        $secretKey = Configuration::get('BILLMATE_SECRET');
        $bmConnection = Common::getBillmate($eid, $secretKey, false);
        return $bmConnection;
    }

    /**
     * @return mixed
     */
    public function getRequestData()
    {
        $requestData['PaymentData'] = [
            'currency' => 'SEK',
            'language' => 'sv',
            'country'  => 'se'
        ];
        return $requestData;
    }
}