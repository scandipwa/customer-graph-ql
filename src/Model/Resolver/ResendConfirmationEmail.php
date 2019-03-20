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

use Magento\Customer\Api\AccountManagementInterface;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Customer\Model\Session;
use Magento\Framework\Exception\State\InvalidTransitionException;
use Magento\Store\Model\StoreManagerInterface;

class ResendConfirmationEmail implements ResolverInterface {
    const STATUS_IS_LOGGED_IN = 'is_already_logged_in';
    const STATUS_WRONG_EMAIL = 'wrong_email';
    const STATUS_CONFIRMATION_SENT = 'confirmation_sent';

    /**
     * @var Session
     */
    protected $session;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var \Magento\Customer\Api\AccountManagementInterface
     */
    protected $customerAccountManagement;

    /**
     * ResendConfirmationEmail constructor.
     * @param Session $customerSession
     * @param StoreManagerInterface $storeManager
     * @param AccountManagementInterface $customerAccountManagement
     */
    public function __construct(
        Session $customerSession,
        StoreManagerInterface $storeManager,
        AccountManagementInterface $customerAccountManagement
    ) {
        $this->session = $customerSession;
        $this->storeManager = $storeManager;
        $this->customerAccountManagement = $customerAccountManagement;
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
        if ($this->session->isLoggedIn()) {
            return [ 'status' => self::STATUS_IS_LOGGED_IN ];
        }

        try {
            $email = $args['email'];
            $this->customerAccountManagement->resendConfirmation(
                $email,
                $this->storeManager->getStore()->getWebsiteId()
            );
            $this->session->setUsername($email);
            return [ 'status' => self::STATUS_CONFIRMATION_SENT ];
        } catch (InvalidTransitionException $e) {
            return [ 'status' => AccountManagementInterface::ACCOUNT_CONFIRMATION_NOT_REQUIRED ];
        } catch (\Exception $e) {
            return [ 'status' => self::STATUS_WRONG_EMAIL ];
        }
    }
}