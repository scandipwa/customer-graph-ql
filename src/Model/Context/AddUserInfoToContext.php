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
use Magento\Framework\Webapi\Request;
use Magento\GraphQl\Model\Query\ContextParametersInterface;
use Magento\CustomerGraphQl\Model\Context\AddUserInfoToContext as CoreAddUserInfoToContext;
use Magento\Integration\Api\CustomerTokenServiceInterface;
use Magento\Integration\Model\Oauth\TokenFactory;
use Magento\Integration\Model\ResourceModel\Oauth\Token\CollectionFactory as TokenCollectionFactory;
use Magento\Framework\Stdlib\DateTime\DateTime;

/**
 * @inheritdoc
 */
class AddUserInfoToContext extends CoreAddUserInfoToContext
{
    const AUTH_HEADER = 'Authorization';
    const AUTH_HEADER_TYPE = 'bearer';

    /**
     * @var UserContextInterface
     */
    protected UserContextInterface $userContext;

    /**
     * @var Session
     */
    protected Session $session;

    /**
     * @var CustomerRepository
     */
    protected CustomerRepository $customerRepository;

    /**
     * @var CustomerTokenServiceInterface
     */
    protected CustomerTokenServiceInterface $customerTokenService;

    /**
     * @var TokenCollectionFactory
     */
    protected TokenCollectionFactory $tokenModelCollectionFactory;

    /**
     * @var DateTime
     */
    protected DateTime $dateTime;

    /**
     * @var Request
     */
    protected Request $request;

    /**
     * @var TokenFactory
     */
    protected TokenFactory $tokenFactory;

    /**
     * @param UserContextInterface $userContext
     * @param Session $session
     * @param CustomerRepository $customerRepository
     * @param CustomerTokenServiceInterface $customerTokenService
     * @param TokenCollectionFactory $tokenModelCollectionFactory
     * @param DateTime $dateTime
     * @param Request $request
     * @param TokenFactory $tokenFactory
     */
    public function __construct(
        UserContextInterface $userContext,
        Session $session,
        CustomerRepository $customerRepository,
        CustomerTokenServiceInterface $customerTokenService,
        TokenCollectionFactory $tokenModelCollectionFactory,
        DateTime $dateTime,
        Request $request,
        TokenFactory $tokenFactory
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
        $this->request = $request;
        $this->tokenFactory = $tokenFactory;
    }

    /**
     * @inheritdoc
     */
    public function execute(ContextParametersInterface $contextParameters): ContextParametersInterface
    {
        $currentUserId = $this->getCurrentUserId();
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
     * Returns authorized customer id; if valid token passed in header.
     *
     * @return int
     */
    public function getCurrentUserId(): int
    {
        $authorizationHeaderValue = $this->request->getHeader(self::AUTH_HEADER);

        if (!$authorizationHeaderValue) {
            return 0;
        }

        $headerPieces = explode(' ', $authorizationHeaderValue);

        if (count($headerPieces) !== 2) {
            return 0;
        }

        $tokenType = strtolower($headerPieces[0]);

        if ($tokenType !== self::AUTH_HEADER_TYPE) {
            return 0;
        }

        $bearerToken = $headerPieces[1];
        $token = $this->tokenFactory->create()->loadByToken($bearerToken);

        if (!$token->getId() || $token->getRevoked()) {
            return 0;
        }

        return (int)$this->userContext->getUserId();
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
