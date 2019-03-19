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
        Validator $formKeyValidator = null
    ) {
        $this->session = $customerSession;
        $this->registration = $registration;
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
            // $customer = $customerFactory->create();
            // $customer->setWebsiteId($websiteId);
            // $customer->setEmail('newemail@mail.com');
            // $customer->setFirstname('FirstName');
            // $customer->setLastname('LastName');
            // $customer->setPassword('123456789');
            // $customer->save();

            // $address = $addresss->create();
            // $address->setCustomerId($customer->getId())
            //     ->setFirstname('FirstName')
            //     ->setLastname('LastName')
            //     ->setCountryId('VN')
            //     ->setPostcode('10000')
            //     ->setCity('HaNoi')
            //     ->setTelephone('1234567890')
            //     ->setFax('123456789')
            //     ->setCompany('Company')
            //     ->setStreet('Street')
            //     ->setIsDefaultBilling('1')
            //     ->setIsDefaultShipping('1')
            //     ->setSaveInAddressBook('1');

            // $address->save();
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
