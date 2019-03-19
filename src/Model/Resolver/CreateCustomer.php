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
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\StateException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Customer\Model\Session;
use Magento\Customer\Model\Registration;
use Magento\Framework\Data\Form\FormKey\Validator;
use Magento\Customer\Model\CustomerFactory;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Customer\Model\AddressFactory;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\Resolver\Value;
use Magento\Framework\GraphQl\Query\Resolver\ContextInterface;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;

class CreateCustomer implements ResolverInterface {
    /**
     * @var \Magento\Customer\Model\Registration
     */
    protected $registration;

    /**
     * @var Session
     */
    protected $session;

    /**
     * @var Validator
     */
    private $formKeyValidator;

    private $customerFactory;

    private $storeManager;

    /**
     * @param Session $customerSession
     * @param Registration $registration
     * @param Validator $formKeyValidator
     *
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        Session $customerSession,
        Registration $registration,
        CustomerFactory $customerFactory,
        StoreManagerInterface $storeManager,
        AddressFactory $addressFactory,
        Validator $formKeyValidator = null
    ) {
        $this->storeManager = $storeManager;
        $this->customerFactory = $customerFactory;
        $this->session = $customerSession;
        $this->registration = $registration;
        $this->addressFactory = $addressFactory;
        $this->formKeyValidator = $formKeyValidator ?: ObjectManager::getInstance()->get(Validator::class);
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
        if ($this->session->isLoggedIn() || !$this->registration->isAllowed()) {
            // customer is logged in and registration is not allowed
        }

//        if (!$this->formKeyValidator->validate($this->getRequest())) {
//            // form key is invalid
//        }

        $this->session->regenerateId();

        try {
            $customerData = $args['customer'];
            $passwordData = $args['password'];



            $websiteId = $this->storeManager->getWebsite()->getWebsiteId();
            $customer = $this->customerFactory->create();
            $customer
                ->setWebsiteId($websiteId)
                ->setEmail($customerData['email'])
                ->setFirstname($customerData['firstname'])
                ->setLastname($customerData['lastname'])
                ->setPassword($passwordData)
                ->save();

            $address = $this->addressFactory->create();
            $address
                ->setCustomerId($customer->getId())
                ->setData($customerData['addresses'])
                ->setIsDefaultBilling('1')
                ->setIsDefaultShipping('1')
                ->setSaveInAddressBook('1')
                ->save();

            return $customer->getData();
        } catch (StateException $e) {
            throw new GraphQlInputException(__('There is already an account with this email address.'), $e);
        } catch (InputException $e) {
            throw new GraphQlInputException(__($e->getMessage()), $e);
        } catch (LocalizedException $e) {
            throw new GraphQlInputException(__($e->getMessage()), $e);
        } catch (\Exception $e) {
            throw new GraphQlInputException(__('We can\'t save the customer.'), $e);
        }
    }
}
