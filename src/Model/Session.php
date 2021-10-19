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

namespace ScandiPWA\CustomerGraphQl\Model;

use Magento\Authorization\Model\UserContextInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Api\GroupManagementInterface;
use Magento\Customer\Model\CustomerFactory;
use Magento\Customer\Model\ResourceModel\Customer as ResourceCustomer;
use Magento\Customer\Model\Session as SourceSession;
use Magento\Customer\Model\Config\Share;
use Magento\Customer\Model\Url;
use Magento\Framework\App\Http\Context;
use Magento\Framework\App\Request\Http;
use Magento\Framework\App\Response\Http as ResponseHttp;
use Magento\Framework\App\State;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Session\Config\ConfigInterface;
use Magento\Framework\Session\Generic;
use Magento\Framework\Session\SaveHandlerInterface;
use Magento\Framework\Session\SidResolverInterface;
use Magento\Framework\Session\StorageInterface;
use Magento\Framework\Session\ValidatorInterface;
use Magento\Framework\Stdlib\Cookie\CookieMetadataFactory;
use Magento\Framework\Stdlib\CookieManagerInterface;
use Magento\Framework\Url\Helper\Data;
use Magento\Framework\UrlFactory;

/**
 * Class Session
 * @package ScandiPWA\CustomerGraphQl\Model
 */
class Session extends SourceSession
{
    /** @var UserContextInterface */
    protected $userContext;

    /** @var CustomerInterface */
    protected $customer;

	/**
	 * Session constructor
	 * @param Http $request
	 * @param SidResolverInterface $sidResolver
	 * @param ConfigInterface $sessionConfig
	 * @param SaveHandlerInterface $saveHandler
	 * @param ValidatorInterface $validator,
     * @param StorageInterface $storage,
     * @param CookieManagerInterface $cookieManager,
     * @param CookieMetadataFactory $cookieMetadataFactory,
     * @param State $appState,
     * @param Share $configShare,
     * @param Data $coreUrl,
     * @param Url $customerUrl,
     * @param ResourceCustomer $customerResource,
     * @param CustomerFactory $customerFactory,
     * @param UrlFactory $urlFactory,
     * @param Generic $session,
     * @param ManagerInterface $eventManager,
     * @param Context $httpContext,
     * @param CustomerRepositoryInterface $customerRepository,
     * @param GroupManagementInterface $groupManagement,
     * @param ResponseHttp $response,
     * @param UserContextInterface $userContext,
     * @param CustomerInterface $customer
	 */
    public function __construct(
        Http $request,
        SidResolverInterface $sidResolver,
        ConfigInterface $sessionConfig,
        SaveHandlerInterface $saveHandler,
        ValidatorInterface $validator,
        StorageInterface $storage,
        CookieManagerInterface $cookieManager,
        CookieMetadataFactory $cookieMetadataFactory,
        State $appState,
        Share $configShare,
        Data $coreUrl,
        Url $customerUrl,
        ResourceCustomer $customerResource,
        CustomerFactory $customerFactory,
        UrlFactory $urlFactory,
        Generic $session,
        ManagerInterface $eventManager,
        Context $httpContext,
        CustomerRepositoryInterface $customerRepository,
        GroupManagementInterface $groupManagement,
        ResponseHttp $response,
        UserContextInterface $userContext,
        CustomerInterface $customer
    ) {
        $this->userContext = $userContext;
        $this->customer = $customer;
        parent::__construct(
            $request,
            $sidResolver,
            $sessionConfig,
            $saveHandler,
            $validator,
            $storage,
            $cookieManager,
            $cookieMetadataFactory,
            $appState,
            $configShare,
            $coreUrl,
            $customerUrl,
            $customerResource,
            $customerFactory,
            $urlFactory,
            $session,
            $eventManager,
            $httpContext,
            $customerRepository,
            $groupManagement,
            $response
        );

        $this->getCustomer();
    }

    /**
     * Returns customer model object
     * @return CustomerInterface
     */
    public function getCustomer()
    {
        if (!$this->customer->getId()) {
            try {
                $this->customer = $this->loadCustomerById($this->getCustomerId());
            } catch (\Exception $e) {
                return $this->customer;
            }
        }

        return $this->customer;
    }

    /**
     * Retrieves customer id from userContext
     * @return int|null
     */
    public function getCustomerId()
    {
        if ($this->userContext->getUserType() === UserContextInterface::USER_TYPE_CUSTOMER) {
            return $this->userContext->getUserId();
        }

        return null;
    }

    /**
     * Returns customer default shipping address or null if such does not exist
     * @return array|null
     */
    public function getDefaultTaxShippingAddress()
    {
        return $this->getUserTaxAddressById($this->customer->getDefaultShipping());
    }

    /**
     * Returns customer default billing address or null if such does not exist
     * @return array|null
     */
    public function getDefaultTaxBillingAddress()
    {
        return $this->getUserTaxAddressById($this->customer->getDefaultBilling());
    }

    /**
     * Returns customer group id
     * @return int|null
     */
    public function getCustomerGroupId()
    {
        return $this->customer->getGroupId();
    }

    /**
     * Load customer by id
     * @param $id
     * @return CustomerInterface
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    private function loadCustomerById($id)
    {
        return $this->customerRepository->getById($id);
    }

    /**
     * Returns user address by specified address id or null if such address does not exist
     * @param $addressId
     * @return array|null
     */
    private function getUserTaxAddressById($addressId)
    {
        $addresses = $this->customer->getAddresses();

        if (!$addresses) {
            return null;
        }

        foreach ($addresses as $address) {
            if ($address->getId() === $addressId) {
                return [
                    'country_id' => $address->getCountryId(),
                    'region_id' => $address->getRegionId(),
                    'postcode' => $address->getPostcode()
                ];
            }
        }

        return null;
    }
}