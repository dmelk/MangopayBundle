<?php

namespace Melk\MangopayBundle\Service;
use MangoPay\Money;
use MangoPay\PayIn;
use MangoPay\PayInExecutionDetailsWeb;
use MangoPay\PayInPaymentDetailsCard;

/**
 * This class is adapter to Mangopay Payin services
 *
 * @package   Melk\MangopayBundle\Service
 * @author    Michael Potienko <potienko.m@gmail.com>
 * @copyright 2015 Modera Foundation
 */
class PayinService {

    const PAYIN_TYPE_CARD = 'CARD';

    const PAYIN_TYPE_DIRECT = 'DIRECT_DEBIT';

    const EXECUTION_TYPE_WEB = 'WEB';

    private static $PAYIN_METHODS = array(
        'cb_visa_mastercard' => array(
            'name'           => 'CB/Visa/Mastercard',
            'currency'       => false,
            'type'           => self::PAYIN_TYPE_CARD,
            'card_type'      => 'CB_VISA_MASTERCARD',
            'templateUrlKey' => 'PAYLINE: '
        ),
        'maestro'            => array(
            'name'           => 'Maestro',
            'currency'       => 'EUR',
            'type'           => self::PAYIN_TYPE_CARD,
            'card_type'      => 'MAESTRO',
            'templateUrlKey' => ''
        ),
        'diners'             => array(
            'name'           => 'Diners',
            'currency'       => 'EUR',
            'type'           => self::PAYIN_TYPE_CARD,
            'card_type'      => 'DINERS',
            'templateUrlKey' => ''
        ),
        'master_pass'        => array(
            'name'           => 'MasterPass',
            'currency'       => false,
            'type'           => self::PAYIN_TYPE_CARD,
            'card_type'      => 'MASTERPASS',
            'templateUrlKey' => ''
        ),
        'sofot'              => array(
            'name'           => 'Sofort',
            'currency'       => 'EUR',
            'type'           => self::PAYIN_TYPE_DIRECT,
            'direct_type'    => 'SOFORT',
            'templateUrlKey' => ''
        ),
        'elv'                => array(
            'name'           => 'ELV',
            'currency'       => 'EUR',
            'type'           => self::PAYIN_TYPE_DIRECT,
            'direct_type'    => 'ELV',
            'templateUrlKey' => ''
        ),
        'giropay'            => array(
            'name'           => 'Giropay',
            'currency'       => 'EUR',
            'type'           => self::PAYIN_TYPE_DIRECT,
            'direct_type'    => 'GIROPAY',
            'templateUrlKey' => ''
        )
    );

    /**
     * @var MangopayService
     */
    private $service;

    /**
     * @param MangopayService $service
     */
    public function __construct(MangopayService $service)
    {
        $this->service = $service;
    }

    /**
     * Return all available payin methods for current currencies
     *
     * @param array $currencies
     * @return array
     */
    public function getAvailablePayinMethods(array $currencies)
    {
        $methods = [];
        foreach ($currencies as $currency) {
            $methodsForCurrency =[];
            foreach (self::$PAYIN_METHODS as $key => $method) {
                // TODO: fix this when API will have DIRECT web PayIn methods
                if ($method['type'] != self::PAYIN_TYPE_CARD) continue;
                if ($method['currency'] === false || $method['currency'] == $currency) {
                    $methodsForCurrency[$key] = $method;
                }
            }
            $methods[$currency] = $methodsForCurrency;
        }
        return $methods;
    }

    /**
     * Creates payIn for some wallet
     *
     * @param string $methodName PayIn method name
     * @param string $authorId Author of the payIn
     * @param string $walletId PayIn wallet (to which money will be transfered)
     * @param array $funds Funds: amount, currency
     * @param array $fees Your fees: amount, currency
     * @param string $returnUrl Return URL
     * @param string $culture Locale in ISO code 639-1
     * @param string $templateUrl
     * @return array
     */
    public function makePayin($methodName, $authorId, $walletId, $funds, $fees, $returnUrl, $culture, $templateUrl = '')
    {
        if (!isset(self::$PAYIN_METHODS[$methodName])) {
            throw new \InvalidArgumentException('Method '.$methodName.' not found.');
        }

        $method = self::$PAYIN_METHODS[$methodName];
        if ($method['type'] != self::PAYIN_TYPE_CARD) {
            throw new \InvalidArgumentException('Method '.$methodName.' can not be used for web pay in.');
        }

        $payIn = new PayIn();
        $payIn->CreditedWalletId = $walletId;

        $payIn->ExecutionType = self::EXECUTION_TYPE_WEB;
        $executionDetails = new PayInExecutionDetailsWeb();
        $executionDetails->Culture = $culture;
        $executionDetails->ReturnURL = $returnUrl;
        if ($templateUrl > '') {
            if ($method['templateUrlKey'] != '') {
                $executionDetails->TemplateURL = array($method['templateUrlKey'] => $templateUrl);
            } else {
                $executionDetails->TemplateURL = $templateUrl;
            }
        }
        $payIn->ExecutionDetails = $executionDetails;

        $payIn->PaymentType = $method['type'];
        $paymentDetails = new PayInPaymentDetailsCard();
        $paymentDetails->CardType = $method['card_type'];
        $payIn->PaymentDetails = $paymentDetails;

        $payIn->AuthorId = $authorId;
        $mpFunds = new Money();
        $mpFunds->Amount = $funds['amount'];
        $mpFunds->Currency = $funds['currency'];
        $payIn->DebitedFunds = $mpFunds;

        $mpFees = new Money();
        $mpFees->Amount = $fees['amount'];
        $mpFees->Currency = $fees['currency'];
        $payIn->Fees = $mpFees;

        $payIn = $this->service->getApi()->PayIns->Create($payIn);
        /** @var PayInExecutionDetailsWeb $executionDetails */
        $executionDetails = $payIn->ExecutionDetails;

        return array(
            'funds'       => array(
                'currency' => $payIn->CreditedFunds->Currency,
                'amount'   => $payIn->CreditedFunds->Amount
            ),
            'walletId'    => $payIn->CreditedWalletId,
            'redirectUrl' => $executionDetails->RedirectURL,
            'templateUrl' => $executionDetails->TemplateURL,
            'returnUrl'   => $executionDetails->ReturnURL
        );
    }

}