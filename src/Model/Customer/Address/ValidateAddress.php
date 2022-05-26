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

namespace ScandiPWA\CustomerGraphQl\Model\Customer\Address;

use Magento\CustomerGraphQl\Model\Customer\Address\ExtractCustomerAddressData;
use Magento\CustomerGraphQl\Model\Customer\Address\ValidateAddress as SourceValidateAddress;
use Magento\Customer\Api\Data\AddressInterface;
use Magento\Customer\Api\Data\AddressInterfaceFactory;
use Magento\Customer\Api\Data\RegionInterfaceFactory;
use Magento\Directory\Helper\Data as DirectoryData;
use Magento\Directory\Model\ResourceModel\Region\CollectionFactory as RegionCollectionFactory;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;

class ValidateAddress extends SourceValidateAddress
{
    /**
     * @var AddressInterfaceFactory
     */
    private $addressFactory;

    /**
     * @var RegionInterfaceFactory
     */
    private $regionFactory;

    /**
     * @var DirectoryData
     */
    protected $directoryData;

    /**
     * @var RegionCollectionFactory
     */
    protected $regionCollectionFactory;

    /**
     * @var ExtractCustomerAddressData
     */
    protected $extractCustomerAddressData;

    /**
     * ValidateCustomerData constructor.
     *
     * @param AddressInterfaceFactory $addressFactory
     * @param RegionInterfaceFactory $regionFactory
     * @param DirectoryData $directoryData
     * @param RegionCollectionFactory $regionCollectionFactory
     * @param ExtractCustomerAddressData $extractCustomerAddressData
     */
    public function __construct(
        AddressInterfaceFactory $addressFactory,
        RegionInterfaceFactory $regionFactory,
        DirectoryData $directoryData,
        RegionCollectionFactory $regionCollectionFactory,
        ExtractCustomerAddressData $extractCustomerAddressData
    ) {
        parent::__construct(
            $addressFactory,
            $regionFactory,
            $directoryData,
            $regionCollectionFactory,
            $extractCustomerAddressData
        );

        $this->extractCustomerAddressData = $extractCustomerAddressData;
        $this->regionCollectionFactory = $regionCollectionFactory;
        $this->directoryData = $directoryData;
    }

    /**
     * Validate customer address data
     *
     * @param AddressInterface $address
     * @throws GraphQlInputException
     */
    public function execute(AddressInterface $address): void
    {
        $addressData = $this->extractCustomerAddressData->execute($address);

        if (!isset($addressData['country_code'])) {
            return;
        }

        $isRegionRequired = $this->directoryData->isRegionRequired($addressData['country_code']);

        if ($isRegionRequired && is_null($addressData['region']['region_id'])) {
            throw new GraphQlInputException(__('A region_id is required for the specified country code'));
        }

        $regionCollection = $this->regionCollectionFactory
            ->create()
            ->addCountryFilter($addressData['country_code']);

        if (!$isRegionRequired &&
            !empty($addressData['region']['region_id']) &&
            empty($regionCollection->getItemById($addressData['region']['region_id']))) {
            throw new GraphQlInputException(
                __('The region_id does not match the selected country or region')
            );
        }

        if (!isset($addressData['region']['region_code'])) {
            $regionCollection->addRegionCodeFilter($addressData['region']['region_code']);
        }

        // In case if region required, but no options then we getting id 0 which is correct
        if ($addressData['region']['region_id'] === 0) {
            return;
        }

        if (empty($regionCollection->getItemById($addressData['region']['region_id']))) {
            throw new GraphQlInputException(
                __('The specified region is not a part of the selected country or region')
            );
        }
    }
}
