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
use Magento\Integration\Api\CustomerTokenServiceInterface;
use Magento\Integration\Model\ResourceModel\Oauth\Token\CollectionFactory as TokenCollectionFactory;
use Magento\Framework\Stdlib\DateTime\DateTime;

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
     * @var CustomerTokenServiceInterface
     */
    protected $customerTokenService;

    /**
     * @var TokenCollectionFactory
     */
    protected $tokenModelCollectionFactory;

    /**
     * @var DateTime
     */
    protected $dateTime;

    /**
     * @param UserContextInterface $userContext
     * @param Session $session
     * @param CustomerRepository $customerRepository
     * @param CustomerTokenServiceInterface $customerTokenService
     * @param TokenCollectionFactory $tokenModelCollectionFactory
     * @param DateTime $dateTime
     */
    public function __construct(
        UserContextInterface $userContext,
        Session $session,
        CustomerRepository $customerRepository,
        CustomerTokenServiceInterface $customerTokenService,
        TokenCollectionFactory $tokenModelCollectionFactory,
        DateTime $dateTime
    ) {
        parent::__construct(
            $userContext,
            $session,
            $customerRepository
        );

        $this->userContext = $userContext;
        $this->session = $session;
        $this->customerRepository = $customerRepository;
        $this->customerTokenService = $customerTokenService;
        $this->tokenModelCollectionFactory = $tokenModelCollectionFactory;
        $this->dateTime = $dateTime;
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

        if ($isCustomer) {
            $customer = $this->customerRepository->getById($currentUserId);
            $this->session->setCustomerData($customer);
            $this->session->setCustomerGroupId($customer->getGroupId());

            // Added next lines to update token on each request if user token is still exist
            $tokenCollection = $this->tokenModelCollectionFactory->create()->addFilterByCustomerId($currentUserId);

            if ($tokenCollection->getSize() > 0) {
                $tokenItems = $tokenCollection->getitems();
                // get last token of current user and update its create date since magento expires it depending on it
                end($tokenItems)->setCreatedAt($this->dateTime->gmtDate())->save();
            }
        }

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
