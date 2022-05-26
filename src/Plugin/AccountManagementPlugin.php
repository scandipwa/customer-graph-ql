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

namespace ScandiPWA\CustomerGraphQl\Plugin;

use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Model\AccountManagement;
use Magento\Customer\Model\AuthenticationInterface;
use Magento\Customer\Model\ForgotPasswordToken\GetCustomerByToken;
use Magento\Framework\App\ObjectManager;
use Magento\Integration\Model\Oauth\Token\RequestThrottler;

class AccountManagementPlugin {
    protected $authentication;

    /**
     * @var CustomerRepositoryInterface
     */
    protected $customerRepository;

    /**
     * @var GetCustomerByToken|null
     */
    protected $getByToken;

    /**
     * @var RequestThrottler
     */
    protected $requestThrottler;

    /**
     * AccountManagementPlugin constructor.
     * @param GetCustomerByToken|null $getByToken
     * @param CustomerRepositoryInterface $customerRepository
     * @param RequestThrottler $requestThrottler
     */
    public function __construct(
        GetCustomerByToken $getByToken = null,
        CustomerRepositoryInterface $customerRepository,
        RequestThrottler $requestThrottler
    )
    {
        $this->getByToken = $getByToken;
        $this->customerRepository = $customerRepository;
        $this->requestThrottler = $requestThrottler;
    }
    private function getAuthentication()
    {
        return ObjectManager::getInstance()->get(
            AuthenticationInterface::class
        );
    }


    public function aroundResetPassword(
        AccountManagement $accountManagement,
        callable $subject,
        $email,
        $resetToken,
        $newPassword
    ) {
        if (!$email) {
            $customer = $this->getByToken->execute($resetToken);
        } else {
            $customer = $this->customerRepository->get($email);
        }

        // calls original magento resetPassword method
        $result = $subject($email, $resetToken, $newPassword);

        // reset auth failures count to allow user log in with new password
        $this->requestThrottler->resetAuthenticationFailuresCount($customer->getEmail(), RequestThrottler::USER_TYPE_CUSTOMER);

        // reset account lock in db
        $this->getAuthentication()->unlock($customer->getId());

        return $result;
    }
}
