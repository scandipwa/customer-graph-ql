<?php
/**
 * ScandiPWA - Progressive Web App for Magento
 *
 * Copyright © Scandiweb, Inc. All rights reserved.
 * See LICENSE for license details.
 *
 * @license OSL-3.0 (Open Software License ("OSL") v. 3.0)
 * @package scandipwa/module-customer-graph-ql
 * @link https://github.com/scandipwa/module-customer-graph-ql
 */
declare(strict_types=1);

namespace ScandiPWA\CustomerGraphQl\Model\Resolver;

use Magento\Framework\Exception\StateException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Customer\Model\Session;
use Magento\Customer\Api\AccountManagementInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Integration\Api\CustomerTokenServiceInterface;
use Magento\Framework\Encryption\EncryptorInterface as Encryptor;
use Magento\Customer\Model\AuthenticationInterface;
use Magento\Customer\Model\CustomerRegistry;

class ConfirmEmail implements ResolverInterface {
    const STATUS_TOKEN_EXPIRED = 'token_expired';

    /**
     * @var AuthenticationInterface
     */
    private $authentication;

    /**
     * @var Session
     */
    protected $session;

    /**
     * @var AccountManagementInterface
     */
    protected $customerAccountManagement;

    /**
     * @var CustomerRepositoryInterface
     */
    protected $customerRepository;

    /**
     * @var CustomerTokenServiceInterface
     */
    protected $customerTokenService;

    /**
     * @var Encryptor
     */
    protected $encryptor;

    /**
     * @var CustomerRegistry
     */
    protected $customerRegistry;

    /**
     * ConfirmEmail constructor.
     * @param AuthenticationInterface $authentication
     * @param Session $customerSession
     * @param AccountManagementInterface $customerAccountManagement
     * @param CustomerRepositoryInterface $customerRepository
     * @param CustomerTokenServiceInterface $customerTokenService
     * @param Encryptor $encryptor
     * @param CustomerRegistry $customerRegistry
     */
    public function __construct(
        AuthenticationInterface $authentication,
        Session $customerSession,
        AccountManagementInterface $customerAccountManagement,
        CustomerRepositoryInterface $customerRepository,
        CustomerTokenServiceInterface $customerTokenService,
        Encryptor $encryptor,
        CustomerRegistry $customerRegistry
    ) {
        $this->authentication = $authentication;
        $this->customerTokenService = $customerTokenService;
        $this->session = $customerSession;
        $this->customerAccountManagement = $customerAccountManagement;
        $this->customerRepository = $customerRepository;
        $this->encryptor = $encryptor;
        $this->customerRegistry = $customerRegistry;
    }

    /**
     * @inheritdoc
     */
    public function resolve(
        Field $field,
        $context,
        ResolveInfo $info,
        array $value = null,
        array $args = null
    )
    {
        if ($this->session->isLoggedIn()) {
            $this->session->logOut();
        }

        try {
            $customerEmail = $args['email'];
            $key = $args['key'];
            $password = $args['password'];

            $id = $this->customerRepository->get($customerEmail)->getId();
            $currentPasswordHash = $this->customerRegistry->retrieveSecureData($id)->getPasswordHash();

            if ($this->encryptor->validateHash($password, $currentPasswordHash)) {
                $customer = $this->customerAccountManagement->activate($customerEmail, $key);

                $this->session->setCustomerDataAsLoggedIn($customer);
                $token = $this->customerTokenService->createCustomerAccessToken($customerEmail, $password);
                return [
                    'status' => AccountManagementInterface::ACCOUNT_CONFIRMED,
                    'token' => $token
                ];
            } else {
                throw new GraphQlInputException(__('Password is incorrect'));
            }
        } catch (StateException $e) {
            return ['status' => self::STATUS_TOKEN_EXPIRED];
        } catch (\Exception $e) {
            throw new GraphQlInputException(__('There was an error confirming the account'), $e);
        }
    }
}