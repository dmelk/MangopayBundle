<?php

namespace Melk\MangopayBundle\Service;

use MangoPay\Pagination;
use MangoPay\UserLegal;
use MangoPay\UserNatural;

/**
 * This class is adapter to the Mangopay users Api
 *
 * @package   Melk\MangopayBundle\Service
 * @author    Michael Potienko <potienko.m@gmail.com>
 * @copyright 2015 Modera Foundation
 */
class UserService
{

    const COMPANY_USER_TYPE = 'COMPANY';

    const PERSON_USER_TYPE = 'PERSON';

    /**
     * @var MangopayService
     */
    private $service;

    /**
     * @param MangopayService $service
     */
    public function __construct (MangopayService $service)
    {
        $this->service = $service;
    }

    /**
     * Set up natural user attributes from the attributes array
     *
     * @param UserNatural $user
     * @param $attributes
     */
    private function extractNaturalUserParams (UserNatural $user, $attributes)
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
    public function createPerson ($attributes)
    {
        if (!isset($attributes['email']) || !isset($attributes['first_name']) || !isset($attributes['last_name'])
            || !isset($attributes['birthday']) || !isset($attributes['nationality']) || !isset($attributes['country_of_residence'])
        ) {
            throw new \InvalidArgumentException('To create person user please specify all required attributes: email, first_name, last_name, birthday, nationality, country_of_residence');
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
    public function updatePerson ($userId, $attributes = [])
    {
        $user = $this->service->getApi()->Users->Get($userId);

        if (!($user instanceof UserNatural)) {
            throw new \UnexpectedValueException('Person user with id ' . $userId . ' not found in Mangopay');
        }
        $this->extractNaturalUserParams($user, $attributes);
        $this->service->getApi()->Users->Update($user);

        return $user->Id;
    }

    /**
     * Set up legal user attributes from the attributes array
     *
     * @param UserLegal $user
     * @param $attributes
     */
    private function extractLegalUserParams (UserLegal $user, $attributes)
    {
        if (isset($attributes['email'])) $user->Email = $attributes['email'];
        if (isset($attributes['name'])) $user->Name = $attributes['name'];
        if (isset($attributes['is_business'])) $user->LegalPersonType = ($attributes['is_business'])? 'BUSINESS' : 'ORGANIZATION';
        if (isset($attributes['first_name'])) $user->LegalRepresentativeFirstName = $attributes['first_name'];
        if (isset($attributes['last_name'])) $user->LegalRepresentativeLastName = $attributes['last_name'];
        if (isset($attributes['birthday'])) $user->LegalRepresentativeBirthday = $attributes['birthday'];
        if (isset($attributes['nationality'])) $user->LegalRepresentativeNationality = $attributes['nationality'];
        if (isset($attributes['country_of_residence'])) $user->LegalRepresentativeCountryOfResidence = $attributes['country_of_residence'];
        if (isset($attributes['representative_email'])) $user->LegalRepresentativeEmail = $attributes['representative_email'];
        if (isset($attributes['representative_address'])) $user->LegalRepresentativeAddress = $attributes['representative_address'];
        if (isset($attributes['address'])) $user->HeadquartersAddress = $attributes['address'];
        if (isset($attributes['tag'])) $user->Tag = $attributes['tag'];
    }

    /**
     * Creates the user who is company in Mangopay system. Required attributes keys is:
     * email, name, is_business (boolean)m first_name, last_name, birthday, nationality, country_of_residence.
     *
     * @param $attributes
     * @return int
     * @throws \InvalidArgumentException
     */
    public function createCompany ($attributes)
    {
        if (!isset($attributes['email']) || !isset($attributes['name']) || !isset($attributes['is_business'])
            || !isset($attributes['first_name']) || !isset($attributes['last_name']) || !isset($attributes['birthday'])
            || !isset($attributes['nationality']) || !isset($attributes['country_of_residence']))
        {
            throw new \InvalidArgumentException('To create company user please specify all required attributes: email, name, is_business, first_name, last_name, birthday, nationality, country_of_residence');
        }

        $user = new UserLegal();
        $this->extractLegalUserParams($user, $attributes);

        $user = $this->service->getApi()->Users->Create($user);
        return $user->Id;
    }

    /**
     * Updates the user who is company in Mangopay system.
     *
     * @param $userId
     * @param array $attributes
     * @return int
     * @throws \UnexpectedValueException
     */
    public function updateCompany ($userId, $attributes = [])
    {
        $user = $this->service->getApi()->Users->Get($userId);

        if (!($user instanceof UserLegal)) {
            throw new \UnexpectedValueException('Company user with id ' . $userId . ' not found in Mangopay');
        }
        $this->extractLegalUserParams($user, $attributes);
        $this->service->getApi()->Users->Update($user);

        return $user->Id;
    }

    /**
     * Get all users using pagination
     *
     * @param int $page
     * @param int $usersPerPage
     * @return array
     */
    public function getAllUsers($page = 1, $usersPerPage = 100)
    {
        $pagination = new Pagination($page, $usersPerPage);
        $users = $this->service->getApi()->Users->GetAll($pagination);

        $extractedData = [];
        foreach ($users as $user) {
            $extractedData[] = array(
                'type' => ($user instanceof UserLegal) ? self::COMPANY_USER_TYPE : self::PERSON_USER_TYPE,
                'id'   => $user->Id
            );
        }

        return array(
            'users'          => $extractedData,
            'total_items'    => $pagination->TotalItems,
            'total_pages'    => $pagination->TotalPages,
            'current_page'   => $pagination->Page,
            'items_per_page' => $pagination->ItemsPerPage
        );
    }

}
