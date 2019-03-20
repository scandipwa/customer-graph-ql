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

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Customer\Api\AccountManagementInterface;
use Magento\Customer\Model\Session;
use Magento\Customer\Model\AccountManagement;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\SecurityViolationException;

class ForgotPassword implements ResolverInterface {
    const STATUS_INCORRECT_EMAIL = 'incorrect_email';
    const STATUS_UNABLE_TO_SEND = 'unable to send';
    CONST STATUS_PASSWORD_RESET_LINK_SENT = 'password_reset_link_sent';

    /**
     * @var AccountManagementInterface
     */
    protected $customerAccountManagement;

    /**
     * @var Session
     */
    protected $session;

    public function __construct(
        Session $customerSession,
        AccountManagementInterface $customerAccountManagement
    ) {
        $this->session = $customerSession;
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
        $email = $args['email'];

        /**
         * WE WILL ALWAYS RETURN SUCCESS FROM THIS FUNCTION, FOR SECURITY REASONS
         */

        if (!\Zend_Validate::is($email, \Magento\Framework\Validator\EmailAddress::class)) {
            $this->session->setForgottenEmail($email);
            // return [ 'status' => self::STATUS_INCORRECT_EMAIL ];
            return [ 'status' => self::STATUS_PASSWORD_RESET_LINK_SENT ];
        }

        try {
            $this->customerAccountManagement->initiatePasswordReset(
                $email,
                AccountManagement::EMAIL_RESET
            );

            return [ 'status' => self::STATUS_PASSWORD_RESET_LINK_SENT ];
        } catch (NoSuchEntityException $e) {
            // return [ 'status' => self::STATUS_UNABLE_TO_SEND ];
            return [ 'status' => self::STATUS_PASSWORD_RESET_LINK_SENT ];
        } catch (SecurityViolationException $e) {
            throw new GraphQlInputException(__('Something went wrong'), $e);
        } catch (\Exception $e) {
            // return [ 'status' => self::STATUS_UNABLE_TO_SEND ];
            return [ 'status' => self::STATUS_PASSWORD_RESET_LINK_SENT ];
        }
    }
}