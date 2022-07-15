<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\GroupedProduct\Test\Unit\Model\Product;

class CatalogPriceTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\GroupedProduct\Model\Product\CatalogPrice
     */
    protected $catalogPrice;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $storeManagerMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $commonPriceMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $productMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $priceModelMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $productTypeMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $associatedProductMock;

    protected function setUp()
    {
        $this->storeManagerMock = $this->createMock(\Magento\Store\Model\StoreManagerInterface::class);
        $this->commonPriceMock = $this->createMock(\Magento\Catalog\Model\Product\CatalogPrice::class);
        $productMethods = ['getWebsiteId', 'getCustomerGroupId', '__wakeup', 'getTypeInstance', 'setTaxClassId'];
        $this->productMock = $this->createPartialMock(\Magento\Catalog\Model\Product::class, $productMethods);
        $methods = ['setWebsiteId', 'isSalable', '__wakeup', 'setCustomerGroupId', 'getTaxClassId'];
        $this->associatedProductMock = $this->createPartialMock(\Magento\Catalog\Model\Product::class, $methods);
        $this->priceModelMock = $this->createPartialMock(
            \Magento\Catalog\Model\Product\Type\Price::class,
            ['getTotalPrices']
        );
        $this->productTypeMock = $this->createMock(\Magento\GroupedProduct\Model\Product\Type\Grouped::class);

        $this->catalogPrice = new \Magento\GroupedProduct\Model\Product\CatalogPrice(
            $this->storeManagerMock,
            $this->commonPriceMock
        );
    }

    public function testGetCatalogPriceWithDefaultStoreAndWhenProductDoesNotHaveAssociatedProducts()
    {
        $this->productMock->expects(
            $this->once()
        )->method(
            'getTypeInstance'
        )->will(
            $this->returnValue($this->productTypeMock)
        );
        $this->productTypeMock->expects(
            $this->once()
        )->method(
            'getAssociatedProducts'
        )->with(
            $this->productMock
        )->will(
            $this->returnValue([])
        );
        $this->storeManagerMock->expects($this->never())->method('getStore');
        $this->storeManagerMock->expects($this->never())->method('setCurrentStore');
        $this->assertEquals(null, $this->catalogPrice->getCatalogPrice($this->productMock));
    }

    public function testGetCatalogPriceWithDefaultStoreAndSubProductIsNotSalable()
    {
        $this->productMock->expects(
            $this->once()
        )->method(
            'getTypeInstance'
        )->will(
            $this->returnValue($this->productTypeMock)
        );
        $this->productTypeMock->expects(
            $this->once()
        )->method(
            'getAssociatedProducts'
        )->with(
            $this->productMock
        )->will(
            $this->returnValue([$this->associatedProductMock])
        );
        $this->productMock->expects($this->once())->method('getWebsiteId')->will($this->returnValue('website_id'));
        $this->productMock->expects($this->once())->method('getCustomerGroupId')->will($this->returnValue('group_id'));
        $this->associatedProductMock->expects(
            $this->once()
        )->method(
            'setWebsiteId'
        )->will(
            $this->returnValue($this->associatedProductMock)
        );
        $this->associatedProductMock->expects(
            $this->once()
        )->method(
            'setCustomerGroupId'
        )->with(
            'group_id'
        )->will(
            $this->returnValue($this->associatedProductMock)
        );
        $this->associatedProductMock->expects($this->once())->method('isSalable')->will($this->returnValue(false));
        $this->productMock->expects($this->never())->method('setTaxClassId');
        $this->storeManagerMock->expects($this->never())->method('getStore');
        $this->storeManagerMock->expects($this->never())->method('setCurrentStore');
        $this->assertEquals(null, $this->catalogPrice->getCatalogPrice($this->productMock));
    }

    public function testGetCatalogPriceWithCustomStoreAndSubProductIsSalable()
    {
        $storeMock = $this->createMock(\Magento\Store\Api\Data\StoreInterface::class);
        $storeMock->expects($this->once())->method('getId')->willReturn('store_id');
        $currentStoreMock = $this->createMock(\Magento\Store\Api\Data\StoreInterface::class);
        $currentStoreMock->expects($this->once())->method('getId')->willReturn('current_store_id');

        $this->productMock->expects(
            $this->once()
        )->method(
            'getTypeInstance'
        )->will(
            $this->returnValue($this->productTypeMock)
        );
        $this->productTypeMock->expects(
            $this->once()
        )->method(
            'getAssociatedProducts'
        )->with(
            $this->productMock
        )->will(
            $this->returnValue([$this->associatedProductMock])
        );
        $this->productMock->expects($this->once())->method('getWebsiteId')->will($this->returnValue('website_id'));
        $this->productMock->expects($this->once())->method('getCustomerGroupId')->will($this->returnValue('group_id'));
        $this->associatedProductMock->expects(
            $this->once()
        )->method(
            'setWebsiteId'
        )->will(
            $this->returnValue($this->associatedProductMock)
        );
        $this->associatedProductMock->expects(
            $this->once()
        )->method(
            'setCustomerGroupId'
        )->with(
            'group_id'
        )->will(
            $this->returnValue($this->associatedProductMock)
        );
        $this->associatedProductMock->expects($this->once())->method('isSalable')->will($this->returnValue(true));
        $this->commonPriceMock->expects(
            $this->exactly(2)
        )->method(
            'getCatalogPrice'
        )->with(
            $this->associatedProductMock
        )->will(
            $this->returnValue(15)
        );
        $this->associatedProductMock->expects(
            $this->once()
        )->method(
            'getTaxClassId'
        )->will(
            $this->returnValue('tax_class')
        );
        $this->productMock->expects($this->once())->method('setTaxClassId')->with('tax_class');

        $this->storeManagerMock->expects($this->at(0))->method('getStore')->willReturn($currentStoreMock);
        $this->storeManagerMock->expects($this->at(1))->method('setCurrentStore')->with('store_id');
        $this->storeManagerMock->expects($this->at(2))->method('setCurrentStore')->with('current_store_id');

        $this->assertEquals(15, $this->catalogPrice->getCatalogPrice($this->productMock, $storeMock, true));
    }

    public function testGetCatalogRegularPrice()
    {
        $this->assertEquals(null, $this->catalogPrice->getCatalogRegularPrice($this->productMock));
    }
}
