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
use Magento\Framework\Exception\InputException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Customer\Model\Session;
use Magento\Customer\Api\AccountManagementInterface;

class ResetPassword implements ResolverInterface {
    const STATUS_PASSWORDS_MISS_MATCH = 'passwords_miss_match';
    const STATUS_PASSWORD_MISSING = 'missing_password';
    const STATUS_PASSWORD_UPDATED = 'password_updated';

    /**
     * @var AccountManagementInterface
     */
    protected $accountManagement;

    /**
     * @var Session
     */
    protected $session;

    /**
     * @var CustomerRepositoryInterface
     */
    protected $customerRepository;

    /**
     * ResetPassword constructor.
     *
     * @param CustomerRepositoryInterface $customerRepository
     */
    public function __construct(
        Session $customerSession,
        CustomerRepositoryInterface $customerRepository,
        AccountManagementInterface $accountManagement
    ) {
        $this->session = $customerSession;
        $this->accountManagement = $accountManagement;
        $this->customerRepository = $customerRepository;
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
        $resetPasswordToken = $args['token'];
        $password = $args['password'];
        $passwordConfirmation = $args['password_confirmation'];
        $customerId = $args['customer_id'];

        try {
            $customerEmail = $this->customerRepository->getById($customerId)->getEmail();
        } catch (\Exception $exception) {
            throw new GraphQlInputException(__('No customer found'));
        }

        if ($password !== $passwordConfirmation) {
            return [
                'token' => $resetPasswordToken,
                'status' => self::STATUS_PASSWORDS_MISS_MATCH
            ];
        }

        if (iconv_strlen($password) <= 0) {
            return [
                'token' => $resetPasswordToken,
                'status' => self::STATUS_PASSWORD_MISSING
            ];
        }

        try {
            $this->accountManagement->resetPassword($customerEmail, $resetPasswordToken, $password);
            $this->session->unsRpToken();
            return [ 'status' => self::STATUS_PASSWORD_UPDATED ];
        } catch (InputException $e) {
            throw new GraphQlInputException(__($e->getMessage()));
        } catch (\Exception $exception) {
            throw new GraphQlInputException(__('Your password reset link has expired.'));
        }
    }
}
