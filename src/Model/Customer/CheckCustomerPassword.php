<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace ScandiPWA\CustomerGraphQl\Model\Customer;

use Magento\Customer\Model\AuthenticationInterface;
use Magento\Framework\Exception\InvalidEmailOrPasswordException;
use Magento\Framework\Exception\State\UserLockedException;
use Magento\Framework\GraphQl\Exception\GraphQlAuthenticationException;

/**
 * Check customer password
 */
class CheckCustomerPassword extends \Magento\CustomerGraphQl\Model\Customer\CheckCustomerPassword
{
    /**
     * @inheridoc
     */
    public function __construct(
        AuthenticationInterface $authentication
    ) {
        parent::__construct($authentication);
    }

    /**
     * @inheridoc
     */
    public function execute(string $password, int $customerId)
    {
        try {
            parent::execute($password, $customerId);
        } catch (GraphQlAuthenticationException $e){
            if($e->getPrevious() instanceof \Magento\Framework\Exception\InvalidEmailOrPasswordException){
                throw new GraphQlAuthenticationException(__("The password doesn't match this account. Verify the password and try again."), $e->getPrevious());
            }
            throw $e;
        }
    }
}
