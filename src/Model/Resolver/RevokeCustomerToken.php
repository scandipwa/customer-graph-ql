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

use Magento\CustomerGraphQl\Model\Resolver\RevokeCustomerToken as SourceRevokeCustomerToken;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Customer\Model\Session;
use Magento\Integration\Api\CustomerTokenServiceInterface;

/**
 * @inheritdoc
 */
class RevokeCustomerToken extends SourceRevokeCustomerToken
{
    /**
     * @var Session
     */
    protected Session $session;

    /**
     * @param CustomerTokenServiceInterface $customerTokenService
     * @param Session $session
     */
    public function __construct(
        CustomerTokenServiceInterface $customerTokenService,
        Session $session
    ) {
        parent::__construct($customerTokenService);

        $this->session = $session;
    }

    /**
     * @inheritdoc
     */
    public function resolve(
        Field $field, $context,
        ResolveInfo $info,
        array $value = null,
        array $args = null
    ) {
        $output = parent::resolve($field, $context, $info, $value, $args);
        $this->session->logout();

        return $output;
    }
}
