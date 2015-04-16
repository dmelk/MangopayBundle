<?php

namespace Melk\MangopayBundle\Service;

use MangoPay\UserNatural;

/**
 * This class is adapter to the Mangopay users Api
 *
 * @package   Melk\MangopayBundle\Service
 * @author    Michael Potienko <potienko.m@gmail.com>
 * @copyright 2015 Modera Foundation
 */
class UserService {

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
     * Set up natural user attributes from the attributes array
     *
     * @param UserNatural $user
     * @param $attributes
     */
    private function extractNaturalUserParams(UserNatural $user, $attributes)
    {
        if (isset($attributes['email'])) $user->Email = $attributes['email'];
        if (isset($attributes['first_name'])) $user->FirstName = $attributes['first_name'];
        if (isset($attributes['last_name'])) $user->LastName = $attributes['last_name'];
        if (isset($attributes['birthday'])) $user->Birthday = $attributes['birthday'];
        if (isset($attributes['nationality'])) $user->Nationality = $attributes['nationality'];
        if (isset($attributes['country_of_residence'])) $user->CountryOfResidence = $attributes['country_of_residence'];
        if (isset($attributes['tag'])) $user->Tag = $attributes['tag'];
        if (isset($attributes['address'])) $user->Address = $attributes['address'];
        if (isset($attributes['occupation'])) $user->Occupation = $attributes['occupation'];
        if (isset($attributes['income_range'])) $user->IncomeRange = $attributes['income_range'];
        if (isset($attributes['proof_of_identity'])) $user->ProofOfIdentity = $attributes['proof_of_identity'];
        if (isset($attributes['proof_of_address'])) $user->ProofOfAddress = $attributes['proof_of_address'];
    }

    /**
     * Creates the user who is person in Mangopay system. Required attributes keys is:
     * email, first_name, last_name, birthday, nationality, country_of_residence.
     *
     * @param $attributes
     * @return int
     * @throws \InvalidArgumentException
     */
    public function createPerson($attributes)
    {
        if (!isset($attributes['email']) || !isset($attributes['first_name']) || !isset($attributes['last_name'])
            || !isset($attributes['birthday']) || !isset($attributes['nationality']) || !isset($attributes['country_of_residence'])) {
            throw new \InvalidArgumentException('To create user please specify all required attributes: email, first_name, last_name, birthday, nationality, country_of_residence');
        }

        $user = new UserNatural();
        $this->extractNaturalUserParams($user, $attributes);

        $user = $this->service->getApi()->Users->Create($user);

        return $user->Id;
    }

    /**
     * Updates the user who is person in Mangopay system.
     *
     * @param $userId
     * @param array $attributes
     * @return int
     * @throws \UnexpectedValueException
     */
    public function updatePerson($userId, $attributes = [])
    {
        $user = $this->service->getApi()->Users->Get($userId);

        if (!($user instanceof UserNatural)) {
            throw new \UnexpectedValueException('Person user with id '.$userId.' not found in Mangopay');
        }
        $this->extractNaturalUserParams($user, $attributes);
        $this->service->getApi()->Users->Update($user);

        return $user->Id;
    }
}