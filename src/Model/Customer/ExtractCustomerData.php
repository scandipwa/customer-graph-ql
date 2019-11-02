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

namespace ScandiPWA\CustomerGraphQl\Model\Customer;

use Magento\Customer\Api\Data\CustomerInterface;

/**
 * Transform single customer data from object to in array format
 */
class ExtractCustomerData extends \Magento\CustomerGraphQl\Model\Customer\ExtractCustomerData
{
    /**
     * {@inheritdoc}
     */
    public function execute(CustomerInterface $customer): array
    {
        $confirmationRequired = $customer->getConfirmation() ?: false;
        $customerData = parent::execute($customer);
        $customerData['confirmation_required'] = $confirmationRequired;
        return $customerData;
    }
}
