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

use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Newsletter\Model\SubscriberFactory;
use Magento\Framework\Api\DataObjectHelper;
use Magento\Customer\Api\AccountManagementInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Integration\Api\CustomerTokenServiceInterface;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Customer\Model\Session;
use Magento\Customer\Model\Registration;
use Magento\Framework\Data\Form\FormKey\Validator;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Customer\Api\Data\CustomerInterfaceFactory;
use Magento\Customer\Api\GroupManagementInterface;

class CreateCustomer implements ResolverInterface {
    const STATUS_REGISTRATION_SUCCESS = 'account_registered';
    const STATUS_IS_LOGGED_IN = 'is_already_logged_in';

    /**
     * @var \Magento\Customer\Model\Registration
     */
    protected $registration;

    /**
     * @var Session
     */
    protected $session;

    /**
     * @var Validator
     */
    protected $formKeyValidator;

    /**
     * @var AccountManagementInterface
     */
    protected $accountManagement;

    /**
     * @var CustomerInterfaceFactory
     */
    protected $customerFactory;

    /**
     * @var DataObjectHelper
     */
    protected $dataObjectHelper;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var GroupManagementInterface
     */
    protected $customerGroupManagement;

    /**
     * @var SubscriberFactory
     */
    protected $subscriberFactory;

    /**
     * @var CustomerTokenServiceInterface
     */
    protected $customerTokenService;

    /**
     * @var CustomerRepositoryInterface
     */
    protected $customerRepository;

    /**
     * @var CustomerDataProvider
     */
    protected $customerDataProvider;

    /**
     * CreateCustomer constructor.
     * @param Session $customerSession
     * @param Registration $registration
     * @param SubscriberFactory $subscriberFactory
     * @param AccountManagementInterface $accountManagement
     * @param CustomerInterfaceFactory $customerFactory
     * @param DataObjectHelper $dataObjectHelper
     * @param StoreManagerInterface $storeManager
     * @param GroupManagementInterface $customerGroupManagement
     * @param CustomerTokenServiceInterface $customerTokenService
     * @param CustomerRepositoryInterface $customerRepository
     * @param Validator|null $formKeyValidator
     */
    public function __construct(
        Session $customerSession,
        Registration $registration,
        SubscriberFactory $subscriberFactory,
        AccountManagementInterface $accountManagement,
        CustomerInterfaceFactory $customerFactory,
        DataObjectHelper $dataObjectHelper,
        StoreManagerInterface $storeManager,
        GroupManagementInterface $customerGroupManagement,
        CustomerTokenServiceInterface $customerTokenService,
        CustomerRepositoryInterface $customerRepository,
        Validator $formKeyValidator = null
    ) {
        $this->customerTokenService = $customerTokenService;
        $this->dataObjectHelper = $dataObjectHelper;
        $this->customerFactory = $customerFactory;
        $this->accountManagement = $accountManagement;
        $this->customerRepository = $customerRepository;
        $this->session = $customerSession;
        $this->registration = $registration;
        $this->subscriberFactory = $subscriberFactory;
        $this->storeManager = $storeManager;
        $this->customerGroupManagement = $customerGroupManagement;
        $this->formKeyValidator = $formKeyValidator ?: ObjectManager::getInstance()->get(Validator::class);
    }

    public function extractCustomer($customerData) {
        $customerDataObject = $this->customerFactory->create();
        $this->dataObjectHelper->populateWithArray(
            $customerDataObject,
            $customerData,
            \Magento\Customer\Api\Data\CustomerInterface::class
        );

        /**
         * Here we might check for allowed attributes,
         * like in Magento\Customer\Model\CustomerExtractor
         * This might allow to set GroupId from request
         */

        $store = $this->storeManager->getStore();
        $customerDataObject->setGroupId(
            $this->customerGroupManagement->getDefaultGroup($store->getId())->getId()
        );

        $customerDataObject->setWebsiteId($store->getWebsiteId());
        $customerDataObject->setStoreId($store->getId());

        return $customerDataObject;
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
    ) {
        if ($this->session->isLoggedIn() || !$this->registration->isAllowed()) {
            return [ 'status' => self::STATUS_IS_LOGGED_IN ];
        }

        /**
         * Where is my field validation ???
         */

        $this->session->regenerateId();

        try {
            $token = null;
            $status = null;

            $customerData = $args['customer'];
            $password = $args['password'];

            $customer = $this->extractCustomer($customerData);
            $validation = $this->accountManagement->validate($customer);

            if (!$validation->isValid()) {
                throw new GraphQlInputException(__('Customer fields are invalid', $validation->getMessages()));
            }

            $customer = $this->accountManagement
                ->createAccount($customer, $password);

            if (!isset($customerData['is_subscribed']) || $customerData['is_subscribed']) {
                $this->subscriberFactory->create()
                    ->subscribeCustomerById($customer->getId());
            }

            /**
             * We should potentially dispatch an 'customer_register_success'
             * here. Not sure if it is done in GraphQL resolvers.
             */

            $confirmationStatus = $this->accountManagement->getConfirmationStatus($customer->getId());
            if ($confirmationStatus === AccountManagementInterface::ACCOUNT_CONFIRMATION_REQUIRED) {
                $status = AccountManagementInterface::ACCOUNT_CONFIRMATION_REQUIRED;
                /**
                 * We need to send email or what ???
                 */
            } else {
                $this->session->setCustomerDataAsLoggedIn($customer);
                $token = $this->customerTokenService->createCustomerAccessToken($customer->getEmail(), $password);
                $status = self::STATUS_REGISTRATION_SUCCESS;
            }

            return array_merge(
                [ 'customer' => $this->customerRepository->getById((int)$customer->getId()) ],
                [ 'status' => $status ],
                [ 'token' => $token ]
            );
        } catch (\Exception $e) {
            throw new GraphQlInputException(__($e->getMessage()), $e);
        }
    }
}
