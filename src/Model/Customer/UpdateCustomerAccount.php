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
namespace ScandiPWA\CustomerGraphQl\Model\Customer;

use Magento\Customer\Api\Data\CustomerInterface;
use Magento\CustomerGraphQl\Model\Customer\CheckCustomerPassword;
use Magento\CustomerGraphQl\Model\Customer\SaveCustomer;
use Magento\CustomerGraphQl\Model\Customer\ValidateCustomerData;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\GraphQl\Exception\GraphQlAlreadyExistsException;
use Magento\Framework\GraphQl\Exception\GraphQlAuthenticationException;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Exception\GraphQlNoSuchEntityException;
use Magento\Framework\Api\DataObjectHelper;
use Magento\Newsletter\Model\SubscriptionManagerInterface;
use Magento\Store\Api\Data\StoreInterface;
use Magento\CustomerGraphQl\Model\Customer\UpdateCustomerAccount as SourceUpdateCustomerAccount;

class UpdateCustomerAccount extends SourceUpdateCustomerAccount
{
    /**
     * @var SaveCustomer
     */
    public $saveCustomer;

    /**
     * @var CheckCustomerPassword
     */
    public $checkCustomerPassword;

    /**
     * @var DataObjectHelper
     */
    public $dataObjectHelper;

    /**
     * @var ValidateCustomerData
     */
    public $validateCustomerData;

    /**
     * @var array
     */
    public $restrictedKeys;

    /**
     * @var SubscriptionManagerInterface
     */
    public $subscriptionManager;

    /**
     * @param SaveCustomer $saveCustomer
     * @param CheckCustomerPassword $checkCustomerPassword
     * @param DataObjectHelper $dataObjectHelper
     * @param ValidateCustomerData $validateCustomerData
     * @param SubscriptionManagerInterface $subscriptionManager
     * @param array $restrictedKeys
     */
    public function __construct(
        SaveCustomer $saveCustomer,
        CheckCustomerPassword $checkCustomerPassword,
        DataObjectHelper $dataObjectHelper,
        ValidateCustomerData $validateCustomerData,
        SubscriptionManagerInterface $subscriptionManager,
        array $restrictedKeys = []
    ) {
        $this->saveCustomer = $saveCustomer;
        $this->checkCustomerPassword = $checkCustomerPassword;
        $this->dataObjectHelper = $dataObjectHelper;
        $this->restrictedKeys = $restrictedKeys;
        $this->validateCustomerData = $validateCustomerData;
        $this->subscriptionManager = $subscriptionManager;
    }

    /**
     * Update customer account
     *
     * @param CustomerInterface $customer
     * @param array $data
     * @param StoreInterface $store
     * @return void
     * @throws GraphQlAlreadyExistsException
     * @throws GraphQlAuthenticationException
     * @throws GraphQlInputException
     * @throws GraphQlNoSuchEntityException
     */
    public function execute(CustomerInterface $customer, array $data, StoreInterface $store): void
    {
        if (isset($data['email'])) {
            if (!isset($data['password']) || empty($data['password'])) {
                throw new GraphQlInputException(__('Provide the current "password" to change "email".'));
            }

            $this->checkCustomerPassword->execute($data['password'], (int)$customer->getId());

            if ($customer->getEmail() !== $data['email']) {
                $customer->setEmail($data['email']);
            }
        }

        $this->validateCustomerData->execute($data);
        $filteredData = array_diff_key($data, array_flip($this->restrictedKeys));
        $this->dataObjectHelper->populateWithArray($customer, $filteredData, CustomerInterface::class);

        try {
            $customer->setStoreId($store->getId());
        } catch (NoSuchEntityException $exception) {
            throw new GraphQlNoSuchEntityException(__($exception->getMessage()), $exception);
        }

        $this->saveCustomer->execute($customer);

        if (isset($data['is_subscribed'])) {
            if ((bool)$data['is_subscribed']) {
                $this->subscriptionManager->subscribeCustomer((int)$customer->getId(), (int)$store->getId());
            } else {
                $this->subscriptionManager->unsubscribeCustomer((int)$customer->getId(), (int)$store->getId());
            }
        }
    }
}
