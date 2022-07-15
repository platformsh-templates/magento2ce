<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Sales\Test\Unit\Model\ResourceModel\Order\Handler;

/**
 * Class AddressTest
 */
class AddressTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\Sales\Model\ResourceModel\Order\Handler\Address
     */
    protected $address;

    /**
     * @var \Magento\Sales\Model\ResourceModel\Order\Address\Collection|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $addressCollectionMock;

    /**
     * @var \Magento\Sales\Model\ResourceModel\Attribute|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $attributeMock;

    /**
     * @var \Magento\Sales\Model\Order|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $orderMock;

    /**
     * @var \Magento\Sales\Model\Order\Address|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $addressMock;

    protected function setUp()
    {
        $this->attributeMock = $this->createMock(\Magento\Sales\Model\ResourceModel\Attribute::class);
        $this->orderMock = $this->createPartialMock(\Magento\Sales\Model\Order::class, [
                '__wakeup',
                'getAddresses',
                'save',
                'getBillingAddress',
                'getShippingAddress',
                'hasBillingAddressId',
                'getBillingAddressId',
                'setBillingAddressId',
                'unsBillingAddressId',
                'hasShippingAddressId',
                'getShippingAddressId',
                'setShippingAddressId',
                'unsShippingAddressId'
            ]);
        $this->addressMock = $this->createMock(\Magento\Sales\Model\Order\Address::class);
        $this->addressCollectionMock = $this->createMock(
            \Magento\Sales\Model\ResourceModel\Order\Address\Collection::class
        );
        $this->address = new \Magento\Sales\Model\ResourceModel\Order\Handler\Address(
            $this->attributeMock
        );
    }

    /**
     * Test process method with billing_address
     */
    public function testProcessBillingAddress()
    {
        $this->orderMock->expects($this->exactly(2))
            ->method('getAddresses')
            ->willReturn([$this->addressMock]);
        $this->addressMock->expects($this->once())
            ->method('save')
            ->will($this->returnSelf());
        $this->orderMock->expects($this->once())
            ->method('getBillingAddress')
            ->will($this->returnValue($this->addressMock));
        $this->addressMock->expects($this->exactly(2))
            ->method('getId')
            ->will($this->returnValue(2));
        $this->orderMock->expects($this->once())
            ->method('getBillingAddressId')
            ->will($this->returnValue(1));
        $this->orderMock->expects($this->once())
            ->method('setBillingAddressId')
            ->will($this->returnSelf());
        $this->orderMock->expects($this->once())
            ->method('getShippingAddress')
            ->will($this->returnValue(null));
        $this->attributeMock->expects($this->once())
            ->method('saveAttribute')
            ->with($this->orderMock, ['billing_address_id'])
            ->will($this->returnSelf());
        $this->assertEquals($this->address, $this->address->process($this->orderMock));
    }

    /**
     * Test process method with shipping_address
     */
    public function testProcessShippingAddress()
    {
        $this->orderMock->expects($this->exactly(2))
            ->method('getAddresses')
            ->willReturn([$this->addressMock]);
        $this->addressMock->expects($this->once())
            ->method('save')
            ->will($this->returnSelf());
        $this->orderMock->expects($this->once())
            ->method('getBillingAddress')
            ->will($this->returnValue(null));
        $this->orderMock->expects($this->once())
            ->method('getShippingAddress')
            ->will($this->returnValue($this->addressMock));
        $this->addressMock->expects($this->exactly(2))
            ->method('getId')
            ->will($this->returnValue(2));
        $this->orderMock->expects($this->once())
            ->method('setShippingAddressId')
            ->will($this->returnSelf());
        $this->attributeMock->expects($this->once())
            ->method('saveAttribute')
            ->with($this->orderMock, ['shipping_address_id'])
            ->will($this->returnSelf());
        $this->assertEquals($this->address, $this->address->process($this->orderMock));
    }

    /**
     * Test method removeEmptyAddresses
     */
    public function testRemoveEmptyAddresses()
    {
        $this->orderMock->expects($this->once())
            ->method('hasBillingAddressId')
            ->will($this->returnValue(true));
        $this->orderMock->expects($this->once())
            ->method('getBillingAddressId')
            ->will($this->returnValue(null));
        $this->orderMock->expects($this->once())
            ->method('unsBillingAddressId')
            ->will($this->returnSelf());
        $this->orderMock->expects($this->once())
            ->method('hasShippingAddressId')
            ->will($this->returnValue(true));
        $this->orderMock->expects($this->once())
            ->method('getShippingAddressId')
            ->will($this->returnValue(null));
        $this->orderMock->expects($this->once())
            ->method('unsShippingAddressId')
            ->will($this->returnSelf());
        $this->assertEquals($this->address, $this->address->removeEmptyAddresses($this->orderMock));
    }
}
