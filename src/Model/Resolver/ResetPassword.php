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

use Magento\Framework\App\ObjectManager;
use Magento\Framework\Exception\InputException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Customer\Model\Session;
use Magento\Customer\Api\AccountManagementInterface;
use Magento\Customer\Model\ForgotPasswordToken\ConfirmCustomerByToken;

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
     * @var ConfirmCustomerByToken
     */
    protected ConfirmCustomerByToken $confirmByToken;

    /**
     * @param Session $customerSession
     * @param AccountManagementInterface $accountManagement
     * @param ConfirmCustomerByToken|null $confirmByToken
     */
    public function __construct(
        Session $customerSession,
        AccountManagementInterface $accountManagement,
        ConfirmCustomerByToken $confirmByToken = null
    ) {
        $this->session = $customerSession;
        $this->accountManagement = $accountManagement;
        $this->confirmByToken = $confirmByToken
            ?? ObjectManager::getInstance()->get(ConfirmCustomerByToken::class);
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
            // Check if the user has not yet confirmed the account
            // and if not do this along with changing the password
            $this->confirmByToken->execute($resetPasswordToken);

            $this->accountManagement->resetPassword(null, $resetPasswordToken, $password);
            $this->session->unsRpToken();

            return [ 'status' => self::STATUS_PASSWORD_UPDATED ];
        } catch (InputException $e) {
            throw new GraphQlInputException(__($e->getMessage()));
        } catch (\Exception $exception) {
            throw new GraphQlInputException(__('Something went wrong while saving the new password.'));
        }
    }
}
