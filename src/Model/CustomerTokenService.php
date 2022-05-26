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

use Magento\Customer\Api\AccountManagementInterface;
use Magento\Framework\Exception\EmailNotConfirmedException;
use Magento\Integration\Model\CredentialsValidator;
use Magento\Integration\Model\Oauth\TokenFactory as TokenModelFactory;
use Magento\Integration\Model\ResourceModel\Oauth\Token\CollectionFactory as TokenCollectionFactory;
use Magento\Integration\Model\Oauth\Token\RequestThrottler;
use Magento\Framework\Exception\AuthenticationException;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\UrlInterface;
use Magento\Integration\Model\CustomerTokenService as SourceCustomerTokenService;

/**
 * Class CustomerTokenService
 * @package ScandiPWA\CustomerGraphQl\Model
 */
class CustomerTokenService extends SourceCustomerTokenService
{
    /**
     * Token Model
     *
     * @var TokenModelFactory
     */
    protected $tokenModelFactory;

    /**
     * @var ManagerInterface
     */
    protected $eventManager;

    /**
     * Customer Account Service
     *
     * @var AccountManagementInterface
     */
    protected $accountManagement;

    /**
     * @var CredentialsValidator
     */
    protected $validatorHelper;

    /**
     * Token Collection Factory
     *
     * @var TokenCollectionFactory
     */
    protected $tokenModelCollectionFactory;

    /**
     * @var RequestThrottler
     */
    protected $requestThrottler;

    /**
     * @var UrlInterface
     */
    protected UrlInterface $urlBuilder;

    /**
     * Initialize service
     *
     * @param TokenModelFactory $tokenModelFactory
     * @param AccountManagementInterface $accountManagement
     * @param TokenCollectionFactory $tokenModelCollectionFactory
     * @param CredentialsValidator $validatorHelper
     * @param ManagerInterface $eventManager
     * @param UrlInterface $urlBuilder
     */
    public function __construct(
        TokenModelFactory $tokenModelFactory,
        AccountManagementInterface $accountManagement,
        TokenCollectionFactory $tokenModelCollectionFactory,
        CredentialsValidator $validatorHelper,
        ManagerInterface $eventManager = null,
        UrlInterface $urlBuilder
    ) {
        parent::__construct(
            $tokenModelFactory,
            $accountManagement,
            $tokenModelCollectionFactory,
            $validatorHelper,
            $eventManager ?: ObjectManager::getInstance()
                ->get(ManagerInterface::class)
        );

        $this->tokenModelFactory = $tokenModelFactory;
        $this->accountManagement = $accountManagement;
        $this->validatorHelper = $validatorHelper;
        $this->eventManager = $eventManager ?: ObjectManager::getInstance()
            ->get(ManagerInterface::class);
        $this->urlBuilder = $urlBuilder;
    }

    /**
     * Create customer access token
     * Adding separate message for EmailNotConfirmedException
     * @param $username
     * @param $password
     * @param $storeId
     * @return string
     */
    public function createCustomerAccessToken($username, $password, $storeId = null): string
    {
        $this->validatorHelper->validate($username, $password);
        $this->getRequestThrottler()->throttle($username, RequestThrottler::USER_TYPE_CUSTOMER);

        try {
            $customerDataObject = $this->accountManagement->authenticate($username, $password);
        } catch (EmailNotConfirmedException $e) {
            $href = $this->urlBuilder->getUrl(
                'customer/account/confirmation',
                ['_query' => ['store' => $storeId, 'email' => $username]]
            );

            throw new EmailNotConfirmedException(
                __('This account is not confirmed. <a href="%1">Click here</a> to resend confirmation email.', $href)
            );
        } catch (\Exception $e) {
            $this->getRequestThrottler()->logAuthenticationFailure($username, RequestThrottler::USER_TYPE_CUSTOMER);
            throw new AuthenticationException(
                __(
                    'The account sign-in was incorrect or your account is disabled temporarily. '
                    . 'Please wait and try again later.'
                )
            );
        }

        $this->eventManager->dispatch('customer_login', ['customer' => $customerDataObject]);
        $this->getRequestThrottler()->resetAuthenticationFailuresCount($username, RequestThrottler::USER_TYPE_CUSTOMER);

        return $this->tokenModelFactory->create()->createCustomerToken($customerDataObject->getId())->getToken();
    }

    /**
     * Get request throttler instance
     *
     * @return RequestThrottler
     * @deprecated 100.0.4
     */
    protected function getRequestThrottler(): RequestThrottler
    {
        if (!$this->requestThrottler instanceof RequestThrottler) {
            return ObjectManager::getInstance()->get(RequestThrottler::class);
        }

        return $this->requestThrottler;
    }
}
