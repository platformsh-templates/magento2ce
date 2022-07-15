<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Quote\Test\Unit\Model;

use Magento\Quote\Model\CouponManagement;

class CouponManagementTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var CouponManagement
     */
    protected $couponManagement;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $quoteRepositoryMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $quoteMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $storeMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $quoteAddressMock;

    protected function setUp()
    {
        $this->quoteRepositoryMock = $this->createMock(\Magento\Quote\Api\CartRepositoryInterface::class);
        $this->storeMock = $this->createMock(\Magento\Store\Model\Store::class);
        $this->quoteMock = $this->createPartialMock(\Magento\Quote\Model\Quote::class, [
                'getItemsCount',
                'setCouponCode',
                'collectTotals',
                'save',
                'getShippingAddress',
                'getCouponCode',
                'getStoreId',
                '__wakeup'
            ]);
        $this->quoteAddressMock = $this->createPartialMock(\Magento\Quote\Model\Quote\Address::class, [
                'setCollectShippingRates',
                '__wakeup'
            ]);
        $this->couponManagement = new CouponManagement(
            $this->quoteRepositoryMock
        );
    }

    public function testGetCoupon()
    {
        $cartId = 11;
        $couponCode = 'test_coupon_code';

        $quoteMock = $this->createPartialMock(\Magento\Quote\Model\Quote::class, ['getCouponCode', '__wakeup']);
        $quoteMock->expects($this->any())->method('getCouponCode')->will($this->returnValue($couponCode));

        $this->quoteRepositoryMock->expects($this->once())
            ->method('getActive')
            ->with($cartId)
            ->will($this->returnValue($quoteMock));

        $this->assertEquals($couponCode, $this->couponManagement->get($cartId));
    }

    /**
     * @expectedException \Magento\Framework\Exception\NoSuchEntityException
     * @expectedExceptionMessage The "33" Cart doesn't contain products.
     */
    public function testSetWhenCartDoesNotContainsProducts()
    {
        $cartId = 33;

        $this->quoteRepositoryMock->expects($this->once())
            ->method('getActive')->with($cartId)->will($this->returnValue($this->quoteMock));
        $this->quoteMock->expects($this->once())->method('getItemsCount')->will($this->returnValue(0));

        $this->couponManagement->set($cartId, 'coupon_code');
    }

    /**
     * @expectedException \Magento\Framework\Exception\CouldNotSaveException
     * @expectedExceptionMessage The coupon code couldn't be applied. Verify the coupon code and try again.
     */
    public function testSetWhenCouldNotApplyCoupon()
    {
        $cartId = 33;
        $couponCode = '153a-ABC';

        $this->storeMock->expects($this->any())->method('getId')->will($this->returnValue(1));
        $this->quoteMock->expects($this->once())->method('getStoreId')->willReturn($this->returnValue(1));

        $this->quoteRepositoryMock->expects($this->once())
            ->method('getActive')->with($cartId)->will($this->returnValue($this->quoteMock));
        $this->quoteMock->expects($this->once())->method('getItemsCount')->will($this->returnValue(12));
        $this->quoteMock->expects($this->once())
            ->method('getShippingAddress')->will($this->returnValue($this->quoteAddressMock));
        $this->quoteAddressMock->expects($this->once())->method('setCollectShippingRates')->with(true);
        $this->quoteMock->expects($this->once())->method('setCouponCode')->with($couponCode);
        $exceptionMessage = "The coupon code couldn't be applied. Verify the coupon code and try again.";
        $exception = new \Magento\Framework\Exception\CouldNotDeleteException(__($exceptionMessage));
        $this->quoteMock->expects($this->once())->method('collectTotals')->will($this->returnValue($this->quoteMock));
        $this->quoteRepositoryMock->expects($this->once())
            ->method('save')
            ->with($this->quoteMock)
            ->willThrowException($exception);

        $this->couponManagement->set($cartId, $couponCode);
    }

    /**
     * @expectedException \Magento\Framework\Exception\NoSuchEntityException
     * @expectedExceptionMessage The coupon code isn't valid. Verify the code and try again.
     */
    public function testSetWhenCouponCodeIsInvalid()
    {
        $cartId = 33;
        $couponCode = '153a-ABC';

        $this->storeMock->expects($this->any())->method('getId')->will($this->returnValue(1));
        $this->quoteMock->expects($this->once())->method('getStoreId')->willReturn($this->returnValue(1));

        $this->quoteRepositoryMock->expects($this->once())
            ->method('getActive')->with($cartId)->will($this->returnValue($this->quoteMock));
        $this->quoteMock->expects($this->once())->method('getItemsCount')->will($this->returnValue(12));
        $this->quoteMock->expects($this->once())
            ->method('getShippingAddress')->will($this->returnValue($this->quoteAddressMock));
        $this->quoteAddressMock->expects($this->once())->method('setCollectShippingRates')->with(true);
        $this->quoteMock->expects($this->once())->method('setCouponCode')->with($couponCode);
        $this->quoteMock->expects($this->once())->method('collectTotals')->will($this->returnValue($this->quoteMock));
        $this->quoteRepositoryMock->expects($this->once())->method('save')->with($this->quoteMock);
        $this->quoteMock->expects($this->once())->method('getCouponCode')->will($this->returnValue('invalidCoupon'));

        $this->couponManagement->set($cartId, $couponCode);
    }

    public function testSet()
    {
        $cartId = 33;
        $couponCode = '153a-ABC';

        $this->storeMock->expects($this->any())->method('getId')->will($this->returnValue(1));
        $this->quoteMock->expects($this->once())->method('getStoreId')->willReturn($this->returnValue(1));

        $this->quoteRepositoryMock->expects($this->once())
            ->method('getActive')->with($cartId)->will($this->returnValue($this->quoteMock));
        $this->quoteMock->expects($this->once())->method('getItemsCount')->will($this->returnValue(12));
        $this->quoteMock->expects($this->once())
            ->method('getShippingAddress')->will($this->returnValue($this->quoteAddressMock));
        $this->quoteAddressMock->expects($this->once())->method('setCollectShippingRates')->with(true);
        $this->quoteMock->expects($this->once())->method('setCouponCode')->with($couponCode);
        $this->quoteMock->expects($this->once())->method('collectTotals')->will($this->returnValue($this->quoteMock));
        $this->quoteRepositoryMock->expects($this->once())->method('save')->with($this->quoteMock);
        $this->quoteMock->expects($this->once())->method('getCouponCode')->will($this->returnValue($couponCode));

        $this->assertTrue($this->couponManagement->set($cartId, $couponCode));
    }

    /**
     * @expectedException \Magento\Framework\Exception\NoSuchEntityException
     * @expectedExceptionMessage The "65" Cart doesn't contain products.
     */
    public function testDeleteWhenCartDoesNotContainsProducts()
    {
        $cartId = 65;

        $this->quoteRepositoryMock->expects($this->once())
            ->method('getActive')->with($cartId)->will($this->returnValue($this->quoteMock));
        $this->quoteMock->expects($this->once())->method('getItemsCount')->will($this->returnValue(0));
        $this->quoteMock->expects($this->never())->method('getShippingAddress');

        $this->couponManagement->remove($cartId);
    }

    /**
     * @expectedException \Magento\Framework\Exception\CouldNotDeleteException
     * @expectedExceptionMessage The coupon code couldn't be deleted. Verify the coupon code and try again.
     */
    public function testDeleteWhenCouldNotDeleteCoupon()
    {
        $cartId = 65;

        $this->quoteRepositoryMock->expects($this->once())
            ->method('getActive')->with($cartId)->will($this->returnValue($this->quoteMock));
        $this->quoteMock->expects($this->once())->method('getItemsCount')->will($this->returnValue(12));
        $this->quoteMock->expects($this->once())
            ->method('getShippingAddress')->will($this->returnValue($this->quoteAddressMock));
        $this->quoteAddressMock->expects($this->once())->method('setCollectShippingRates')->with(true);
        $this->quoteMock->expects($this->once())->method('setCouponCode')->with('');
        $this->quoteMock->expects($this->once())->method('collectTotals')->will($this->returnValue($this->quoteMock));
        $exceptionMessage = "The coupon code couldn't be deleted. Verify the coupon code and try again.";
        $exception = new \Magento\Framework\Exception\CouldNotSaveException(__($exceptionMessage));
        $this->quoteMock->expects($this->once())->method('collectTotals')->will($this->returnValue($this->quoteMock));
        $this->quoteRepositoryMock->expects($this->once())
            ->method('save')
            ->with($this->quoteMock)
            ->willThrowException($exception);

        $this->couponManagement->remove($cartId);
    }

    /**
     * @expectedException \Magento\Framework\Exception\CouldNotDeleteException
     * @expectedExceptionMessage The coupon code couldn't be deleted. Verify the coupon code and try again.
     */
    public function testDeleteWhenCouponIsNotEmpty()
    {
        $cartId = 65;

        $this->quoteRepositoryMock->expects($this->once())
            ->method('getActive')->with($cartId)->will($this->returnValue($this->quoteMock));
        $this->quoteMock->expects($this->once())->method('getItemsCount')->will($this->returnValue(12));
        $this->quoteMock->expects($this->once())
            ->method('getShippingAddress')->will($this->returnValue($this->quoteAddressMock));
        $this->quoteAddressMock->expects($this->once())->method('setCollectShippingRates')->with(true);
        $this->quoteMock->expects($this->once())->method('setCouponCode')->with('');
        $this->quoteMock->expects($this->once())->method('collectTotals')->will($this->returnValue($this->quoteMock));
        $this->quoteMock->expects($this->once())->method('collectTotals')->will($this->returnValue($this->quoteMock));
        $this->quoteRepositoryMock->expects($this->once())->method('save')->with($this->quoteMock);
        $this->quoteMock->expects($this->once())->method('getCouponCode')->will($this->returnValue('123_ABC'));

        $this->couponManagement->remove($cartId);
    }

    public function testDelete()
    {
        $cartId = 65;

        $this->quoteRepositoryMock->expects($this->once())
            ->method('getActive')->with($cartId)->will($this->returnValue($this->quoteMock));
        $this->quoteMock->expects($this->once())->method('getItemsCount')->will($this->returnValue(12));
        $this->quoteMock->expects($this->once())
            ->method('getShippingAddress')->will($this->returnValue($this->quoteAddressMock));
        $this->quoteAddressMock->expects($this->once())->method('setCollectShippingRates')->with(true);
        $this->quoteMock->expects($this->once())->method('setCouponCode')->with('');
        $this->quoteMock->expects($this->once())->method('collectTotals')->will($this->returnValue($this->quoteMock));
        $this->quoteMock->expects($this->once())->method('collectTotals')->will($this->returnValue($this->quoteMock));
        $this->quoteRepositoryMock->expects($this->once())->method('save')->with($this->quoteMock);
        $this->quoteMock->expects($this->once())->method('getCouponCode')->will($this->returnValue(''));

        $this->assertTrue($this->couponManagement->remove($cartId));
    }
}
