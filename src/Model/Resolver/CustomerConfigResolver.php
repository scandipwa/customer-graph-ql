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
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;

class CustomerConfigResolver implements ResolverInterface {
    const XML_PATH_VAT_FRONTEND_VISIBILITY = 'customer/create_account/vat_frontend_visibility';
    const XML_PATH_TAXVAT_SHOW = 'customer/address/taxvat_show';

    /** @var ScopeConfigInterface */
    private $scopeConfig;

    /** @var $storeManager */
    private $storeManager;

    public function __construct(ScopeConfigInterface $scopeConfig, StoreManagerInterface $storeManager) {
        $this->scopeConfig = $scopeConfig;
        $this->storeManager = $storeManager;
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
    )
    {
        return [
            'show_vat_number_on_storefront' => $this->getVatFrontendVisibility(),
            'show_tax_vat_number' => $this->getTaxVatShow()
        ];
    }

    private function getVatFrontendVisibility() {
        $storeId = $this->storeManager->getStore()->getId();
        return $this->scopeConfig->isSetFlag(
          self::XML_PATH_VAT_FRONTEND_VISIBILITY,
          ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    private function getTaxVatShow() {
        $storeId = $this->storeManager->getStore()->getId();
        return $this->scopeConfig->getValue(
            self::XML_PATH_TAXVAT_SHOW,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }
}
