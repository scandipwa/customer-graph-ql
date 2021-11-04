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

use Magento\Customer\Api\AddressRepositoryInterface;
use Magento\Customer\Api\Data\AddressInterface;
use Magento\CustomerGraphQl\Model\Customer\Address\GetAllowedAddressAttributes;
use Magento\CustomerGraphQl\Model\Customer\Address\PopulateCustomerAddressFromInput;
use Magento\CustomerGraphQl\Model\Customer\Address\UpdateCustomerAddress as SourceAddress;
use Magento\Directory\Helper\Data as DirectoryData;
use Magento\Directory\Model\ResourceModel\Region\CollectionFactory as RegionCollectionFactory;
use Magento\Framework\Api\DataObjectHelper;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;

/**
 * @inheridoc
 */
class UpdateCustomerAddress extends SourceAddress
{
    /**
     * @var AddressRepositoryInterface
     */
    protected $addressRepository;

    /**
     * @var DataObjectHelper
     */
    protected $dataObjectHelper;

    /**
     * @var array
     */
    protected $restrictedKeys;

    /**
     * @var ValidateAddress
     */
    protected $addressValidator;

    /**
     * @var PopulateCustomerAddressFromInput
     */
    protected $populateCustomerAddressFromInput;

    /**
     * @param GetAllowedAddressAttributes $getAllowedAddressAttributes
     * @param AddressRepositoryInterface $addressRepository
     * @param DataObjectHelper $dataObjectHelper
     * @param DirectoryData $directoryData
     * @param RegionCollectionFactory $regionCollectionFactory
     * @param ValidateAddress $addressValidator
     * @param PopulateCustomerAddressFromInput $populateCustomerAddressFromInput
     * @param array $restrictedKeys
     */
    public function __construct(
        GetAllowedAddressAttributes $getAllowedAddressAttributes,
        AddressRepositoryInterface $addressRepository,
        DataObjectHelper $dataObjectHelper,
        DirectoryData $directoryData,
        RegionCollectionFactory $regionCollectionFactory,
        ValidateAddress $addressValidator,
        PopulateCustomerAddressFromInput $populateCustomerAddressFromInput,
        array $restrictedKeys = []
    ) {
        parent::__construct(
            $getAllowedAddressAttributes,
            $addressRepository,
            $dataObjectHelper,
            $directoryData,
            $regionCollectionFactory,
            $addressValidator,
            $populateCustomerAddressFromInput,
            $restrictedKeys
        );

        $this->addressRepository = $addressRepository;
        $this->dataObjectHelper = $dataObjectHelper;
        $this->addressValidator = $addressValidator;
        $this->populateCustomerAddressFromInput = $populateCustomerAddressFromInput;
        $this->restrictedKeys = $restrictedKeys;
    }

    /**
     * @inheridoc
     */
    public function execute(AddressInterface $address, array $data): void
    {
        if (isset($data['country_code'])) {
            $data['country_id'] = $data['country_code'];
        }
        $this->validateData($data);

        $filteredData = array_diff_key($data, array_flip($this->restrictedKeys));
        $this->dataObjectHelper->populateWithArray($address, $filteredData, AddressInterface::class);

        if (!empty($data['region']['region_id'])) {
            $address->setRegionId($address->getRegion()->getRegionId());
        } else {
            $data['region']['region_id'] = null;

            # If new address doesn't have selectable region, make sure that old region id is removed from DB
            $address->setRegionId(null);
        }

        $this->populateCustomerAddressFromInput->execute($address, $filteredData);
        $this->addressValidator->execute($address);

        try {
            $this->addressRepository->save($address);
        } catch (LocalizedException $e) {
            throw new GraphQlInputException(__($e->getMessage()), $e);
        }
    }
}
