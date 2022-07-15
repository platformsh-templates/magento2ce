<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Quote\Test\Unit\Observer\Frontend\Quote\Address;

class VatValidatorTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var  \Magento\Quote\Observer\Frontend\Quote\Address\VatValidator
     */
    protected $model;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $customerAddressMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $customerVatMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $quoteAddressMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $storeMock;

    /**
     * @var array
     */
    protected $testData;

    /**
     * @var \Magento\Framework\DataObject
     */
    protected $validationResult;

    protected function setUp()
    {
        $this->customerAddressMock = $this->createMock(\Magento\Customer\Helper\Address::class);
        $this->customerVatMock = $this->createMock(\Magento\Customer\Model\Vat::class);
        $this->customerVatMock->expects($this->any())
            ->method('getMerchantCountryCode')
            ->willReturn('merchantCountryCode');
        $this->customerVatMock->expects($this->any())
            ->method('getMerchantVatNumber')
            ->willReturn('merchantVatNumber');

        $this->storeMock = $this->createMock(\Magento\Store\Model\Store::class);

        $this->quoteAddressMock = $this->createPartialMock(\Magento\Quote\Model\Quote\Address::class, [
                'getCountryId',
                'getVatId',
                'getValidatedCountryCode',
                'getValidatedVatNumber',
                'getVatIsValid',
                'getVatRequestId',
                'getVatRequestDate',
                'getVatRequestSuccess',
                'getAddressType',
                'save',
                '__wakeup'
            ]);

        $this->testData = [
            'is_valid' => true,
            'request_identifier' => 'test_request_identifier',
            'request_date' => 'test_request_date',
            'request_success' => true,
        ];

        $this->quoteAddressMock->expects(
            $this->any()
        )->method(
            'getVatIsValid'
        )->will(
            $this->returnValue($this->testData['is_valid'])
        );
        $this->quoteAddressMock->expects(
            $this->any()
        )->method(
            'getVatRequestId'
        )->will(
            $this->returnValue($this->testData['request_identifier'])
        );
        $this->quoteAddressMock->expects(
            $this->any()
        )->method(
            'getVatRequestDate'
        )->will(
            $this->returnValue($this->testData['request_date'])
        );
        $this->quoteAddressMock->expects(
            $this->any()
        )->method(
            'getVatRequestSuccess'
        )->will(
            $this->returnValue($this->testData['request_success'])
        );
        $this->quoteAddressMock->expects($this->any())->method('getCountryId')->will($this->returnValue('en'));
        $this->quoteAddressMock->expects($this->any())->method('getVatId')->will($this->returnValue('testVatID'));

        $this->validationResult = new \Magento\Framework\DataObject($this->testData);

        $this->model = new \Magento\Quote\Observer\Frontend\Quote\Address\VatValidator(
            $this->customerAddressMock,
            $this->customerVatMock
        );
    }

    public function testValidateWithDisabledValidationOnEachTransaction()
    {
        $this->customerVatMock->expects($this->never())->method('checkVatNumber');

        $this->customerAddressMock->expects(
            $this->once()
        )->method(
            'hasValidateOnEachTransaction'
        )->with(
            $this->storeMock
        )->will(
            $this->returnValue(false)
        );

        $this->quoteAddressMock->expects(
            $this->any()
        )->method(
            'getValidatedCountryCode'
        )->will(
            $this->returnValue('en')
        );

        $this->quoteAddressMock->expects(
            $this->any()
        )->method(
            'getValidatedVatNumber'
        )->will(
            $this->returnValue('testVatID')
        );

        $this->quoteAddressMock->expects($this->never())->method('save');

        $this->assertEquals(
            $this->validationResult,
            $this->model->validate($this->quoteAddressMock, $this->storeMock)
        );
    }

    public function testValidateWithEnabledValidationOnEachTransaction()
    {
        $this->customerVatMock->expects(
            $this->once()
        )->method(
            'checkVatNumber'
        )->with(
            'en',
            'testVatID',
            'merchantCountryCode',
            'merchantVatNumber'
        )->will(
            $this->returnValue($this->validationResult)
        );

        $this->customerAddressMock->expects(
            $this->once()
        )->method(
            'hasValidateOnEachTransaction'
        )->with(
            $this->storeMock
        )->will(
            $this->returnValue(true)
        );

        $this->quoteAddressMock->expects(
            $this->any()
        )->method(
            'getValidatedCountryCode'
        )->will(
            $this->returnValue('en')
        );

        $this->quoteAddressMock->expects(
            $this->any()
        )->method(
            'getValidatedVatNumber'
        )->will(
            $this->returnValue('testVatID')
        );

        $this->quoteAddressMock->expects($this->once())->method('save');

        $this->assertEquals(
            $this->validationResult,
            $this->model->validate($this->quoteAddressMock, $this->storeMock)
        );
    }

    public function testValidateWithDifferentCountryIdAndValidatedCountryCode()
    {
        $this->customerVatMock->expects(
            $this->once()
        )->method(
            'checkVatNumber'
        )->with(
            'en',
            'testVatID',
            'merchantCountryCode',
            'merchantVatNumber'
        )->will(
            $this->returnValue($this->validationResult)
        );

        $this->customerAddressMock->expects(
            $this->once()
        )->method(
            'hasValidateOnEachTransaction'
        )->with(
            $this->storeMock
        )->will(
            $this->returnValue(false)
        );

        $this->quoteAddressMock->expects(
            $this->any()
        )->method(
            'getValidatedCountryCode'
        )->will(
            $this->returnValue('someCountryCode')
        );

        $this->quoteAddressMock->expects($this->any())->method('getVatId')->will($this->returnValue('testVatID'));

        $this->quoteAddressMock->expects($this->once())->method('save');

        $this->assertEquals(
            $this->validationResult,
            $this->model->validate($this->quoteAddressMock, $this->storeMock)
        );
    }

    public function testValidateWithDifferentVatNumberAndValidatedVatNumber()
    {
        $this->customerVatMock->expects(
            $this->once()
        )->method(
            'checkVatNumber'
        )->with(
            'en',
            'testVatID',
            'merchantCountryCode',
            'merchantVatNumber'
        )->will(
            $this->returnValue($this->validationResult)
        );

        $this->customerAddressMock->expects(
            $this->once()
        )->method(
            'hasValidateOnEachTransaction'
        )->with(
            $this->storeMock
        )->will(
            $this->returnValue(false)
        );

        $this->quoteAddressMock->expects(
            $this->any()
        )->method(
            'getValidatedCountryCode'
        )->will(
            $this->returnValue('en')
        );

        $this->quoteAddressMock->expects($this->any())->method('getVatId')->will($this->returnValue('someVatID'));

        $this->quoteAddressMock->expects($this->once())->method('save');

        $this->assertEquals(
            $this->validationResult,
            $this->model->validate($this->quoteAddressMock, $this->storeMock)
        );
    }

    public function testIsEnabledWithBillingTaxCalculationAddressType()
    {
        $this->customerAddressMock->expects(
            $this->any()
        )->method(
            'isVatValidationEnabled'
        )->will(
            $this->returnValue(true)
        );

        $this->customerAddressMock->expects(
            $this->any()
        )->method(
            'getTaxCalculationAddressType'
        )->will(
            $this->returnValue(\Magento\Customer\Model\Address\AbstractAddress::TYPE_BILLING)
        );

        $this->quoteAddressMock->expects(
            $this->any()
        )->method(
            'getAddressType'
        )->will(
            $this->returnValue(\Magento\Customer\Model\Address\AbstractAddress::TYPE_SHIPPING)
        );

        $result = $this->model->isEnabled($this->quoteAddressMock, $this->storeMock);
        $this->assertFalse($result);
    }

    public function testIsEnabledWithEnabledVatValidation()
    {
        $this->customerAddressMock->expects(
            $this->any()
        )->method(
            'isVatValidationEnabled'
        )->will(
            $this->returnValue(true)
        );
        $result = $this->model->isEnabled($this->quoteAddressMock, $this->storeMock);
        $this->assertTrue($result);
    }
}
