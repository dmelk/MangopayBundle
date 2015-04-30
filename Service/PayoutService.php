<?php

namespace Melk\MangopayBundle\Service;

use MangoPay\BankAccountDetailsCA;
use MangoPay\BankAccountDetailsGB;
use MangoPay\BankAccountDetailsIBAN;
use MangoPay\BankAccount;
use MangoPay\BankAccountDetailsOTHER;
use MangoPay\BankAccountDetailsUS;
use MangoPay\Money;
use MangoPay\Pagination;
use MangoPay\PayOut;
use MangoPay\PayOutPaymentDetailsBankWire;

/**
 * This class is adapter to Mangopay Payout services
 *
 * @package   Melk\MangopayBundle\Service
 * @author    Michael Potienko <potienko.m@gmail.com>
 * @copyright 2015 Modera Foundation
 */
class PayoutService {

    const PAYOUT_BANK_WIRE = 'BANK_WIRE';

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
     * Creates new bank account for user.
     * Params array keys:
     *  - type: IBAN, GB, US, CA, OTHER
     *  - owner_name, owner_address - required
     *  - for IBAN: iban (required), bic
     *  - for GB: account_number, sort_code - required
     *  - for US: account_number, aba - required
     *  - for CA: account_number, bank_name, branch_code, institution_number - required
     *  - for OTHER: country, account_number, bic - required
     *
     * @param $userId
     * @param $parameters
     * @return array
     */
    public function createAccount($userId, $parameters)
    {
        if (!isset($parameters['type'])) {
            throw new \InvalidArgumentException('To create bank account please specify it\'s type.');
        }
        if (!isset($parameters['owner_name']) || !isset($parameters['owner_address'])) {
            throw new \InvalidArgumentException('To create bank account please specify next parameters: owner_name, owner_address.');
        }

        $account = new BankAccount();
        $account->OwnerName = $parameters['owner_name'];
        $account->OwnerAddress = $parameters['owner_address'];
        if (isset($parameters['user_id'])) $account->UserId = $parameters['user_id'];
        $details = null;

        if ($parameters['type'] == 'IBAN') {

            $details = new BankAccountDetailsIBAN();
            if (!isset($parameters['iban'])) {
                throw new \InvalidArgumentException('To create IBAN bank account please specify IBAN.');
            }

            $details->IBAN = $parameters['iban'];
            if (isset($parameters['bic'])) $details->BIC = $parameters['bic'];

        } else if ($parameters['type'] == 'GB') {

            $details = new BankAccountDetailsGB();
            if (!isset($parameters['account_number']) || !isset($parameters['sort_code'])) {
                throw new \InvalidArgumentException('To create GB bank account please specify next parameters: account_number, sort_code.');
            }

            $details->AccountNumber = $parameters['account_number'];
            $details->SortCode = $parameters['sort_code'];

        } else if ($parameters['type'] == 'US') {

            $details = new BankAccountDetailsUS();
            if (!isset($parameters['account_number']) || !isset($parameters['aba'])) {
                throw new \InvalidArgumentException('To create US bank account please specify next parameters: account_number, aba.');
            }

            $details->AccountNumber = $parameters['account_number'];
            $details->ABA = $parameters['aba'];

        } else if ($parameters['type'] == 'CA') {

            $details = new BankAccountDetailsCA();
            if (!isset($parameters['account_number']) || !isset($parameters['bank_name']) || !isset($parameters['institution_number']) || !isset($parameters['branch_code'])) {
                throw new \InvalidArgumentException('To create CA bank account please specify next parameters: account_number, bank_name, institution_number, branch_code.');
            }

            $details->BankName = $parameters['bank_name'];
            $details->InstitutionNumber = $parameters['institution_number'];
            $details->BranchCode = $parameters['branch_code'];
            $details->AccountNumber = $parameters['account_number'];

        } else if ($parameters['type'] == 'OTHER') {
            $details = new BankAccountDetailsOTHER();
            if (!isset($parameters['account_number']) || !isset($parameters['country']) || !isset($parameters['bic'])) {
                throw new \InvalidArgumentException('To create OTHER bank account please specify next parameters: account_number, country, bic.');
            }

            $details->Country = $parameters['country'];
            $details->AccountNumber = $parameters['account_number'];
            $details->BIC = $parameters['bic'];

        } else {
            throw new \InvalidArgumentException('Supported bank account types: IBAN, GB, OTHER.');
        }
        $account->Details = $details;

        $account = $this->service->getApi()->Users->CreateBankAccount($userId, $account);

        return array(
            'id'   => $account->Id,
            'type' => $account->Type
        );
    }

    /**
     * Return extracted bank accounts data for some user
     *
     * @param $userId
     * @param int $page
     * @param int $usersPerPage
     * @return array
     */
    public function getAccounts($userId, $page = 1, $usersPerPage = 100)
    {
        $pagination = new Pagination($page, $usersPerPage);
        $accounts = $this->service->getApi()->Users->GetBankAccounts($userId, $pagination);
        $extractedData = [];

        /** @var BankAccount $account */
        foreach ($accounts as $account) {
            $extractedData[] = array(
                'type' => $account->Type,
                'id'   => $account->Id
            );
        }

        return array(
            'accounts'       => $extractedData,
            'total_items'    => $pagination->TotalItems,
            'total_pages'    => $pagination->TotalPages,
            'current_page'   => $pagination->Page,
            'items_per_page' => $pagination->ItemsPerPage
        );
    }

    /**
     * Makes payout
     *
     * @param $userId
     * @param $walletId
     * @param $bankAccountId
     * @param $funds
     * @param $fees
     * @return array
     */
    public function makePayout($userId, $walletId, $bankAccountId, $funds, $fees)
    {

        $payOut = new PayOut();
        $payOut->AuthorId = $userId;
        $payOut->DebitedWalletId = $walletId;
        $payOut->PaymentType = self::PAYOUT_BANK_WIRE;

        $details = new PayOutPaymentDetailsBankWire();
        $details->BankAccountId = $bankAccountId;
        $payOut->MeanOfPaymentDetails = $details;

        $mpFunds = new Money();
        $mpFunds->Amount = $funds['amount'];
        $mpFunds->Currency = $funds['currency'];
        $payOut->DebitedFunds = $mpFunds;

        $mpFees = new Money();
        $mpFees->Amount = $fees['amount'];
        $mpFees->Currency = $fees['currency'];
        $payOut->Fees = $mpFees;

        $payOut = $this->service->getApi()->PayOuts->Create($payOut);
        return array(
            'id'        => $payOut->Id,
            'wallet_id' => $payOut->DebitedWalletId
        );
    }

}