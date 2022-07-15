<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Catalog\Test\Unit\Model\Rss\Product;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;

/**
 * Class SpecialTest
 * @package Magento\Catalog\Model\Rss\Product
 */
class SpecialTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\Catalog\Model\Rss\Product\Special
     */
    protected $special;

    /**
     * @var ObjectManagerHelper
     */
    protected $objectManagerHelper;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $productFactory;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Magento\Catalog\Model\Product
     */
    protected $product;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $storeManager;

    protected function setUp()
    {
        $this->product = $this->createMock(\Magento\Catalog\Model\Product::class);
        $this->productFactory = $this->createPartialMock(\Magento\Catalog\Model\ProductFactory::class, ['create']);
        $this->productFactory->expects($this->any())->method('create')->will($this->returnValue($this->product));
        $this->storeManager = $this->createMock(\Magento\Store\Model\StoreManager::class);

        $this->objectManagerHelper = new ObjectManagerHelper($this);
        $this->special = $this->objectManagerHelper->getObject(
            \Magento\Catalog\Model\Rss\Product\Special::class,
            [
                'productFactory' => $this->productFactory,
                'storeManager' => $this->storeManager
            ]
        );
    }

    public function testGetProductsCollection()
    {
        $storeId = 1;
        $store = $this->createMock(\Magento\Store\Model\Store::class);
        $this->storeManager->expects($this->once())->method('getStore')->with($storeId)->will(
            $this->returnValue($store)
        );
        $websiteId = 1;
        $store->expects($this->once())->method('getWebsiteId')->will($this->returnValue($websiteId));

        /** @var \Magento\Catalog\Model\ResourceModel\Product\Collection $productCollection */
        $productCollection =
            $this->createMock(\Magento\Catalog\Model\ResourceModel\Product\Collection::class);
        $this->product->expects($this->once())->method('getResourceCollection')->will(
            $this->returnValue($productCollection)
        );
        $customerGroupId = 1;
        $productCollection->expects($this->once())->method('addPriceDataFieldFilter')->will($this->returnSelf());
        $productCollection->expects($this->once())->method('addPriceData')->with($storeId, $customerGroupId)->will(
            $this->returnSelf()
        );
        $productCollection->expects($this->once())->method('addAttributeToSelect')->will($this->returnSelf());
        $productCollection->expects($this->once())->method('addAttributeToSort')->will($this->returnSelf());

        $products = $this->special->getProductsCollection($storeId, $customerGroupId);
        $this->assertEquals($productCollection, $products);
    }
}
