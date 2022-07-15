<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\SalesRule\Test\Unit\Observer;

class AddSalesRuleNameToOrderObserverTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\SalesRule\Observer\AddSalesRuleNameToOrderObserver|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $model;

    /**
     * @var \Magento\SalesRule\Model\Coupon|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $couponMock;

    /**
     * @var \Magento\SalesRule\Model\RuleFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $ruleFactory;

    protected function setUp()
    {
        $helper = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);
        $this->initMocks();

        $this->model = $helper->getObject(
            \Magento\SalesRule\Observer\AddSalesRuleNameToOrderObserver::class,
            [
                'ruleFactory' => $this->ruleFactory,
                'coupon' => $this->couponMock,
            ]
        );
    }

    protected function initMocks()
    {
        $this->couponMock = $this->createPartialMock(\Magento\SalesRule\Model\Coupon::class, [
                '__wakeup',
                'save',
                'load',
                'getId',
                'setTimesUsed',
                'getTimesUsed',
                'getRuleId',
                'loadByCode',
                'updateCustomerCouponTimesUsed'
            ]);
        $this->ruleFactory = $this->createPartialMock(\Magento\SalesRule\Model\RuleFactory::class, ['create']);
    }

    public function testAddSalesRuleNameToOrderWithoutCouponCode()
    {
        $observer = $this->createPartialMock(\Magento\Framework\Event\Observer::class, ['getOrder']);
        $order = $this->createPartialMock(
            \Magento\Sales\Model\Order::class,
            ['setCouponRuleName', 'getCouponCode', '__wakeup']
        );

        $observer->expects($this->any())
            ->method('getOrder')
            ->will($this->returnValue($order));

        $this->couponMock->expects($this->never())
            ->method('loadByCode');

        $this->assertEquals($this->model, $this->model->execute($observer));
    }

    public function testAddSalesRuleNameToOrderWithoutRule()
    {
        $observer = $this->createPartialMock(\Magento\Framework\Event\Observer::class, ['getOrder']);
        $order = $this->createPartialMock(
            \Magento\Sales\Model\Order::class,
            ['setCouponRuleName', 'getCouponCode', '__wakeup']
        );
        $couponCode = 'coupon code';

        $observer->expects($this->any())
            ->method('getOrder')
            ->will($this->returnValue($order));

        $order->expects($this->once())
            ->method('getCouponCode')
            ->will($this->returnValue($couponCode));
        $this->ruleFactory->expects($this->never())
            ->method('create');

        $this->assertEquals($this->model, $this->model->execute($observer));
    }

    public function testAddSalesRuleNameToOrder()
    {
        $observer = $this->createPartialMock(\Magento\Framework\Event\Observer::class, ['getOrder']);
        $rule = $this->createPartialMock(\Magento\SalesRule\Model\Rule::class, ['load', 'getName', '__wakeup']);
        $order = $this->createPartialMock(
            \Magento\Sales\Model\Order::class,
            ['setCouponRuleName', 'getCouponCode', '__wakeup']
        );
        $couponCode = 'coupon code';
        $ruleId = 1;

        $observer->expects($this->any())
            ->method('getOrder')
            ->will($this->returnValue($order));

        $order->expects($this->once())
            ->method('getCouponCode')
            ->will($this->returnValue($couponCode));
        $this->couponMock->expects($this->once())
            ->method('getRuleId')
            ->will($this->returnValue($ruleId));
        $this->ruleFactory->expects($this->once())
            ->method('create')
            ->will($this->returnValue($rule));
        $rule->expects($this->once())
            ->method('load')
            ->with($ruleId)
            ->will($this->returnSelf());
        $order->expects($this->once())
            ->method('setCouponRuleName');

        $this->assertEquals($this->model, $this->model->execute($observer));
    }
}
