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
use Magento\Customer\Model\AccountConfirmation;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Framework\Webapi\ServiceOutputProcessor;
use Magento\Store\Model\ScopeInterface;

/**
 * Transform single customer data from object to in array format
 */
class ExtractCustomerData extends \Magento\CustomerGraphQl\Model\Customer\ExtractCustomerData
{
    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var ServiceOutputProcessor
     */
    private $serviceOutputProcessor;

    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * @param ServiceOutputProcessor $serviceOutputProcessor
     * @param ScopeConfigInterface $scopeConfig
     * @param SerializerInterface $serializer
     */
    public function __construct(
        ServiceOutputProcessor $serviceOutputProcessor,
        ScopeConfigInterface $scopeConfig,
        SerializerInterface $serializer
    ) {
        parent::__construct(
            $serviceOutputProcessor,
            $serializer
        );
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * Transform single customer data from object to in array format
     *
     * @param CustomerInterface $customer
     * @return array
     * @throws LocalizedException
     */
    public function execute(CustomerInterface $customer): array
    {
        $customerData = parent::execute($customer);

        $isConfirmationRequired =  $this->scopeConfig->isSetFlag(
            AccountConfirmation::XML_PATH_IS_CONFIRM,
            ScopeInterface::SCOPE_WEBSITES,
            $customer->getWebsiteId()
        );

        $customerData['confirmation_required'] = $isConfirmationRequired;
        $customerData['group_id'] = $customer->getGroupId();

        return $customerData;
    }
}
