<?php
/**
 * ScandiPWA - Progressive Web App for Magento
 *
 * Copyright © Scandiweb, Inc. All rights reserved.
 * See LICENSE for license details.
 *
 * @license OSL-3.0 (Open Software License ("OSL") v. 3.0)
 * @package scandipwa/customer-graph-ql
 * @link    https://github.com/scandipwa/customer-graph-ql
 */

declare(strict_types=1);

namespace ScandiPWA\CustomerGraphQl\Model\Resolver;

use Magento\CustomerGraphQl\Model\Customer\CreateCustomerAccount;
use Magento\CustomerGraphQl\Model\Customer\ExtractCustomerData;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Newsletter\Model\Config;
use Magento\Sales\Model\OrderFactory;
use Magento\Store\Model\ScopeInterface;
use Magento\CustomerGraphQl\Model\Resolver\CreateCustomer as SourceCreateCustomer;

/**
 * Class CreateCustomer
 * @package ScandiPWA\CustomerGraphQl\Model\Resolver
 */
class CreateCustomer extends SourceCreateCustomer
{
    /**
     * @var ExtractCustomerData
     */
    protected ExtractCustomerData $extractCustomerData;

    /**
     * @var CreateCustomerAccount
     */
    protected CreateCustomerAccount $createCustomerAccount;

    /**
     * @var Config
     */
    protected Config $newsLetterConfig;

    /**
     * @var OrderFactory
     */
    protected OrderFactory $orderFactory;

    /**
     * CreateCustomer constructor.
     *
     * @param ExtractCustomerData $extractCustomerData
     * @param CreateCustomerAccount $createCustomerAccount
     * @param Config $newsLetterConfig
     * @param OrderFactory $orderFactory
     * @param OrderRepositoryInterface $orderRepository
     */
    public function __construct(
        ExtractCustomerData $extractCustomerData,
        CreateCustomerAccount $createCustomerAccount,
        Config $newsLetterConfig,
        OrderFactory $orderFactory
    ) {
        parent::__construct(
            $extractCustomerData,
            $createCustomerAccount,
            $newsLetterConfig
        );

        $this->orderFactory = $orderFactory;
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
        $output = parent::resolve($field, $context, $info, $value, $args);

        if (isset($args['input']['orderID']) && !empty($args['input']['orderID']) && isset($output['customer']['id'])) {
            $orderModel = $this->orderFactory->create()->loadByIncrementId($args['input']['orderID']);
            $orderCustomerEmail = $orderModel->getCustomerEmail();

            if ($args['input']['email'] === $orderCustomerEmail) {
                $orderModel->setCustomerId($output['customer']['id']);
                $orderModel->setCustomerIsGuest(0);

                $orderModel->save();
            }
        }

        return $output;
    }
}
