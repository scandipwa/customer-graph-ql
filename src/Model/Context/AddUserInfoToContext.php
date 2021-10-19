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

namespace ScandiPWA\CustomerGraphQl\Model\Context;

use Magento\Authorization\Model\UserContextInterface;
use Magento\Customer\Model\ResourceModel\CustomerRepository;
use Magento\Customer\Model\Session;
use Magento\GraphQl\Model\Query\ContextParametersInterface;
use Magento\CustomerGraphQl\Model\Context\AddUserInfoToContext as CoreAddUserInfoToContext;

/**
 * @inheritdoc
 */
class AddUserInfoToContext extends CoreAddUserInfoToContext
{
    /**
     * @var UserContextInterface
     */
    protected $userContext;

    /**
     * @var Session
     */
    protected $session;

    /**
     * @var CustomerRepository
     */
    protected $customerRepository;

    /**
     * @param UserContextInterface $userContext
     * @param Session $session
     * @param CustomerRepository $customerRepository
     */
    public function __construct(
        UserContextInterface $userContext,
        Session $session,
        CustomerRepository $customerRepository
    ) {
        parent::__construct(
            $userContext,
            $session,
            $customerRepository
        );

        $this->userContext = $userContext;
        $this->session = $session;
        $this->customerRepository = $customerRepository;
    }

    /**
     * @inheritdoc
     */
    public function execute(ContextParametersInterface $contextParameters): ContextParametersInterface
    {
        $currentUserId = $this->userContext->getUserId();

        if ($currentUserId !== null) {
            $currentUserId = (int)$currentUserId;
        }

        $contextParameters->setUserId($currentUserId);

        $currentUserType = $this->userContext->getUserType();

        if ($currentUserType !== null) {
            $currentUserType = (int)$currentUserType;
        }

        $contextParameters->setUserType($currentUserType);

        $isCustomer = $this->isCustomer($currentUserId, $currentUserType);
        $contextParameters->addExtensionAttribute('is_customer', $isCustomer);

        /*
         * TODO: implement support for sessions
         *
            if ($isCustomer) {
                $customer = $this->customerRepository->getById($currentUserId);
                $this->session->setCustomerData($customer);
                $this->session->setCustomerGroupId($customer->getGroupId());
            }
        */

        return $contextParameters;
    }

    /**
     * Checking if current user is logged
     *
     * @param int|null $customerId
     * @param int|null $customerType
     * @return bool
     */
    protected function isCustomer(?int $customerId, ?int $customerType): bool
    {
        return !empty($customerId)
            && !empty($customerType)
            && $customerType === UserContextInterface::USER_TYPE_CUSTOMER;
    }
}
