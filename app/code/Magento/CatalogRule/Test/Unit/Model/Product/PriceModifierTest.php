<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CatalogRule\Test\Unit\Model\Product;

class PriceModifierTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\CatalogRule\Model\Product\PriceModifier
     */
    protected $priceModifier;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $ruleFactoryMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $productMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $ruleMock;

    protected function setUp()
    {
        $this->ruleFactoryMock = $this->createPartialMock(\Magento\CatalogRule\Model\RuleFactory::class, ['create']);
        $this->productMock = $this->createMock(\Magento\Catalog\Model\Product::class);
        $this->ruleMock = $this->createMock(\Magento\CatalogRule\Model\Rule::class);
        $this->priceModifier = new \Magento\CatalogRule\Model\Product\PriceModifier($this->ruleFactoryMock);
    }

    /**
     * @param int|null $resultPrice
     * @param int $expectedPrice
     * @dataProvider modifyPriceDataProvider
     */
    public function testModifyPriceIfPriceExists($resultPrice, $expectedPrice)
    {
        $this->ruleFactoryMock->expects($this->once())->method('create')->will($this->returnValue($this->ruleMock));
        $this->ruleMock->expects(
            $this->once()
        )->method(
            'calcProductPriceRule'
        )->with(
            $this->productMock,
            100
        )->will(
            $this->returnValue($resultPrice)
        );
        $this->assertEquals($expectedPrice, $this->priceModifier->modifyPrice(100, $this->productMock));
    }

    /**
     * @return array
     */
    public function modifyPriceDataProvider()
    {
        return ['resulted_price_exists' => [150, 150], 'resulted_price_not_exists' => [null, 100]];
    }

    public function testModifyPriceIfPriceNotExist()
    {
        $this->ruleFactoryMock->expects($this->never())->method('create');
        $this->assertEquals(null, $this->priceModifier->modifyPrice(null, $this->productMock));
    }
}
