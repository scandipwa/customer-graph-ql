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

class ConfirmEmail implements ResolverInterface {
    const STATUS_TOKEN_EXPIRED = 'token_expired';

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
     * ConfirmEmail constructor.
     * @param Session $customerSession
     * @param AccountManagementInterface $customerAccountManagement
     * @param CustomerRepositoryInterface $customerRepository
     * @param CustomerTokenServiceInterface $customerTokenService
     * @param CustomerDataProvider $customerDataProvider
     */
    public function __construct(
        Session $customerSession,
        AccountManagementInterface $customerAccountManagement,
        CustomerRepositoryInterface $customerRepository,
        CustomerTokenServiceInterface $customerTokenService
    ) {
        $this->customerTokenService = $customerTokenService;
        $this->session = $customerSession;
        $this->customerAccountManagement = $customerAccountManagement;
        $this->customerRepository = $customerRepository;
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
            return [ 'status' => AccountManagementInterface::ACCOUNT_CONFIRMATION_NOT_REQUIRED ];
        }

        try {
            $customerId = $args['id'];
            $key = $args['key'];
            $password = $args['password'];

            $customerEmail = $this->customerRepository->getById($customerId)->getEmail();
            $customer = $this->customerAccountManagement->activate($customerEmail, $key);
            $this->session->setCustomerDataAsLoggedIn($customer);
            $token = $this->customerTokenService->createCustomerAccessToken($customer->getEmail(), $password);

            return [
                'customer' => $this->customerRepository->getById((int)$customer->getId()),
                'status' => AccountManagementInterface::ACCOUNT_CONFIRMED,
                'token' => $token
            ];
        } catch (StateException $e) {
            return [ 'status' => self::STATUS_TOKEN_EXPIRED ];
        } catch (\Exception $e) {
            throw new GraphQlInputException(__('There was an error confirming the account'), $e);
        }
    }
}