<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\GroupedProduct\Test\Unit\Block\Product\Grouped\AssociatedProducts;

class ListAssociatedProductsTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $contextMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $registryMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $productMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $typeInstanceMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $storeManagerMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $storeMock;

    /**
     * @var \Magento\GroupedProduct\Block\Product\Grouped\AssociatedProducts\ListAssociatedProducts
     */
    protected $block;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Magento\Framework\Pricing\PriceCurrencyInterface
     */
    protected $priceCurrency;

    protected function setUp()
    {
        $this->contextMock = $this->createMock(\Magento\Backend\Block\Template\Context::class);
        $this->registryMock = $this->createMock(\Magento\Framework\Registry::class);
        $this->productMock = $this->createMock(\Magento\Catalog\Model\Product::class);
        $this->storeMock = $this->createMock(\Magento\Store\Model\Store::class);
        $this->storeManagerMock = $this->createMock(\Magento\Store\Model\StoreManager::class);
        $this->typeInstanceMock = $this->createMock(\Magento\GroupedProduct\Model\Product\Type\Grouped::class);

        $this->contextMock->expects(
            $this->any()
        )->method(
            'getStoreManager'
        )->will(
            $this->returnValue($this->storeManagerMock)
        );

        $this->priceCurrency = $this->getMockBuilder(
            \Magento\Framework\Pricing\PriceCurrencyInterface::class
        )->getMock();

        $this->block = new \Magento\GroupedProduct\Block\Product\Grouped\AssociatedProducts\ListAssociatedProducts(
            $this->contextMock,
            $this->registryMock,
            $this->priceCurrency
        );
    }

    /**
     * @covers \Magento\GroupedProduct\Block\Product\Grouped\AssociatedProducts\ListAssociatedProducts
     *     ::getAssociatedProducts
     */
    public function testGetAssociatedProducts()
    {
        $this->priceCurrency->expects(
            $this->any()
        )->method(
            'format'
        )->with(
            '1.00',
            false
        )->will(
            $this->returnValue('1')
        );

        $this->storeManagerMock->expects($this->any())->method('getStore')->will($this->returnValue($this->storeMock));

        $this->productMock->expects(
            $this->once()
        )->method(
            'getTypeInstance'
        )->will(
            $this->returnValue($this->typeInstanceMock)
        );

        $this->registryMock->expects(
            $this->once()
        )->method(
            'registry'
        )->with(
            'current_product'
        )->will(
            $this->returnValue($this->productMock)
        );

        $this->typeInstanceMock->expects(
            $this->once()
        )->method(
            'getAssociatedProducts'
        )->with(
            $this->productMock
        )->will(
            $this->returnValue([$this->generateAssociatedProduct(1), $this->generateAssociatedProduct(2)])
        );

        $expectedResult = [
            '0' => [
                'id' => 'id1',
                'sku' => 'sku1',
                'name' => 'name1',
                'qty' => 1,
                'position' => 1,
                'price' => '1',
            ],
            '1' => [
                'id' => 'id2',
                'sku' => 'sku2',
                'name' => 'name2',
                'qty' => 2,
                'position' => 2,
                'price' => '1',
            ],
        ];

        $this->assertEquals($expectedResult, $this->block->getAssociatedProducts());
    }

    /**
     * Generate associated product mock
     *
     * @param int $productKey
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function generateAssociatedProduct($productKey = 0)
    {
        $associatedProduct = $this->createPartialMock(
            \Magento\Framework\DataObject::class,
            ['getQty', 'getPosition', 'getId', 'getSku', 'getName', 'getPrice']
        );

        $associatedProduct->expects($this->once())->method('getId')->will($this->returnValue('id' . $productKey));
        $associatedProduct->expects($this->once())->method('getSku')->will($this->returnValue('sku' . $productKey));
        $associatedProduct->expects($this->once())->method('getName')->will($this->returnValue('name' . $productKey));
        $associatedProduct->expects($this->once())->method('getQty')->will($this->returnValue($productKey));
        $associatedProduct->expects($this->once())->method('getPosition')->will($this->returnValue($productKey));
        $associatedProduct->expects($this->once())->method('getPrice')->will($this->returnValue('1.00'));

        return $associatedProduct;
    }
}
