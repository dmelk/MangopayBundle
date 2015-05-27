<?php

namespace Melk\MangopayBundle\Service;

use MangoPay\MangoPayApi;
use Melk\MangopayBundle\MelkMangopayBundle;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * This class create and manage client connection to the Mangopay site.
 *
 * @package   Melk\MangopayBundle\Service
 * @author    Michael Potienko <potienko.m@gmail.com>
 * @copyright 2015 Modera Foundation
 */
class MangopayService {

    const STATUS_SUCCESS = 'SUCCEEDED';

    const STATUS_CREATED = 'CREATED';

    const STATUS_FAILED = 'FAILED';

    /**
     * @var MangoPayApi
     */
    private $api;

    /**
     * Initialize mangopay API client
     *
     * @param KernelInterface $kernel
     * @param $credentials
     */
    public function __construct(KernelInterface $kernel, $credentials)
    {
        $this->api = new MangoPayApi();
        $this->api->Config->ClientId = $credentials['client_id'];
        $this->api->Config->ClientPassword = $credentials['password'];

        $tmpPath = $kernel->getRootDir().'/../app/Resources/';
        if (!file_exists($tmpPath)) mkdir($tmpPath);
        $tmpPath .= MelkMangopayBundle::BUNDLE_NAME.'/';
        if (!file_exists($tmpPath)) mkdir($tmpPath);

        if ($credentials['sandbox']) {
            $tmpPath .= 'sandbox_tmp/';
        }else{
            $tmpPath .= 'live_tmp/';
            $this->api->Config->BaseUrl = 'https://api.mangopay.com';
        }

        if (!file_exists($tmpPath)) mkdir($tmpPath);
        $this->api->Config->TemporaryFolder = $tmpPath;

    }

    /**
     * @return MangoPayApi
     */
    public function getApi()
    {
        return $this->api;
    }

}