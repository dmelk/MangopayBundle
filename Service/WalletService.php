<?php

namespace Melk\MangopayBundle\Service;

use MangoPay\FilterTransactions;
use MangoPay\Pagination;
use MangoPay\Transaction;
use MangoPay\Wallet;

/**
 * This class is adapter to Mangopay wallet Api
 *
 * @package   Melk\MangopayBundle\Service
 * @author    Michael Potienko <potienko.m@gmail.com>
 * @copyright 2015 Modera Foundation
 */
class WalletService {

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
     * Creates wallet for some users
     *
     * @param array $owners
     * @param $currency
     * @param $description
     * @param null $tag
     * @return array
     */
    public function createWallet(array $owners, $currency, $description, $tag = null)
    {
        $wallet = new Wallet();
        $wallet->Owners = $owners;
        $wallet->Description = $description;
        $wallet->Currency = $currency;
        if (isset($tag)) $wallet->Tag = $tag;

        $wallet = $this->service->getApi()->Wallets->Create($wallet);

        return array(
            'id'       => $wallet->Id,
            'currency' => $wallet->Balance->Currency,
            'balance'  => $wallet->Balance->Amount
        );
    }

    /**
     * Updates wallet information. Only 2 attributes can be updated: description and tag
     *
     * @param $walletId
     * @param $attributes
     * @return array
     */
    public function updateWallet($walletId, $attributes)
    {
        $wallet = $this->service->getApi()->Wallets->Get($walletId);
        if (!($wallet instanceof Wallet)) {
            throw new \UnexpectedValueException('Person user with id '.$walletId.' not found in Mangopay');
        }

        if (isset($attributes['description'])) $wallet->Description = $attributes['description'];
        if (isset($attributes['tag'])) $wallet->Tag = $attributes['tag'];

        $wallet = $this->service->getApi()->Wallets->Update($wallet);

        return array(
            'id'       => $wallet->Id,
            'currency' => $wallet->Balance->Currency,
            'balance'  => $wallet->Balance->Amount
        );
    }

    /**
     * Searches for wallet and returns it's id, currency and balance if found.
     * If wallet not found returns null
     *
     * @param $walletId
     * @return array|null
     */
    public function getWallet($walletId)
    {
        $wallet = $this->service->getApi()->Wallets->Get($walletId);
        if (!($wallet instanceof Wallet)) {
            return null;
        }

        return array(
            'id'       => $wallet->Id,
            'currency' => $wallet->Balance->Currency,
            'balance'  => $wallet->Balance->Amount
        );
    }

    /**
     * Get transactions for wallet.
     * Filters can be: direction, nature, status or type/
     * Result has next keys:
     * - transactions: extracted transactions list in array format
     * - total items: total found transactions
     * - total pages: total pages count
     * - current_page: current page number
     * - items_per_page: amount of items per page
     *
     * @param $walletId
     * @param array $filters
     * @param int $page
     * @param int $itemsPerPage
     * @return array
     */
    public function getTransactions($walletId, $filters = [], $page = 1, $itemsPerPage = 100)
    {
        $pagination = new Pagination($page, $itemsPerPage);

        $isFiltered = false;
        $filter = new FilterTransactions();
        if (isset($filters['direction'])) {
            $filter->Direction = $filter['direction'];
            $isFiltered = true;
        }
        if (isset($filters['nature'])) {
            $filter->Nature = $filter['nature'];
            $isFiltered = true;
        }
        if (isset($filters['status'])) {
            $filter->Status = $filter['status'];
            $isFiltered = true;
        }
        if (isset($filters['type'])) {
            $filter->Type = $filter['type'];
            $isFiltered = true;
        }

        $transactions = $this->service->getApi()->Wallets->GetTransactions($walletId, $pagination, (($isFiltered)? $filter : null));

        $extractedData = [];

        /** @var Transaction $transaction */
        foreach ($transactions as $transaction) {
            $extractedData[] = array(
                'id'                => $transaction->Id,
                'author_id'         => $transaction->AuthorId,
                'credited_id'       => $transaction->CreditedUserId,
                'tag'               => $transaction->Tag,
                'created_date'      => $transaction->CreationDate,
                'status'            => $transaction->Status,
                'code'              => $transaction->ResultCode,
                'message'           => $transaction->ResultMessage,
                'type'              => $transaction->Tag,
                'nature'            => $transaction->Nature,
                'credited_currency' => $transaction->CreditedFunds->Currency,
                'credited_amount'   => $transaction->CreditedFunds->Amount,
                'debited_currency'  => $transaction->DebitedFunds->Currency,
                'debited_amount'    => $transaction->DebitedFunds->Amount,
                'fees_currency'     => $transaction->Fees->Currency,
                'fees_amount'       => $transaction->Fees->Amount
            );
        }

        return array(
            'transactions'   => $extractedData,
            'total_items'    => $pagination->TotalItems,
            'total_pages'    => $pagination->TotalPages,
            'current_page'   => $pagination->Page,
            'items_per_page' => $pagination->ItemsPerPage
        );
    }

}