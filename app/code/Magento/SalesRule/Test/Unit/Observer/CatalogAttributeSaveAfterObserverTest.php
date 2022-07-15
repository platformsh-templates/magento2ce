<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\SalesRule\Test\Unit\Observer;

class CatalogAttributeSaveAfterObserverTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\SalesRule\Observer\CatalogAttributeSaveAfterObserver|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $model;

    /**
     * @var \Magento\SalesRule\Observer\CheckSalesRulesAvailability|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $checkSalesRulesAvailability;

    protected function setUp()
    {
        $helper = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);
        $this->initMocks();

        $this->model = $helper->getObject(
            \Magento\SalesRule\Observer\CatalogAttributeSaveAfterObserver::class,
            [
                'checkSalesRulesAvailability' => $this->checkSalesRulesAvailability
            ]
        );
    }

    protected function initMocks()
    {
        $this->checkSalesRulesAvailability = $this->createMock(
            \Magento\SalesRule\Observer\CheckSalesRulesAvailability::class
        );
    }

    public function testCatalogAttributeSaveAfter()
    {
        $attributeCode = 'attributeCode';
        $observer = $this->createMock(\Magento\Framework\Event\Observer::class);
        $event = $this->createPartialMock(\Magento\Framework\Event::class, ['getAttribute', '__wakeup']);
        $attribute = $this->createPartialMock(
            \Magento\Catalog\Model\ResourceModel\Eav\Attribute::class,
            ['dataHasChangedFor', 'getIsUsedForPromoRules', 'getAttributeCode', '__wakeup']
        );

        $observer->expects($this->once())
            ->method('getEvent')
            ->will($this->returnValue($event));
        $event->expects($this->any())
            ->method('getAttribute')
            ->will($this->returnValue($attribute));
        $attribute->expects($this->any())
            ->method('dataHasChangedFor')
            ->with('is_used_for_promo_rules')
            ->will($this->returnValue(true));
        $attribute->expects($this->any())
            ->method('getIsUsedForPromoRules')
            ->will($this->returnValue(false));
        $attribute->expects($this->any())
            ->method('getAttributeCode')
            ->will($this->returnValue($attributeCode));

        $this->checkSalesRulesAvailability
            ->expects($this->once())
            ->method('checkSalesRulesAvailability')
            ->willReturn('true');

        $this->assertEquals($this->model, $this->model->execute($observer));
    }
}
