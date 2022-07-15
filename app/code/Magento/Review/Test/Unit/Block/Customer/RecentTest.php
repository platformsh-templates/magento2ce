<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Review\Test\Unit\Block\Customer;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;

class RecentTest extends \PHPUnit\Framework\TestCase
{
    /** @var \Magento\Review\Block\Customer\Recent */
    protected $object;

    /** @var ObjectManagerHelper */
    protected $objectManagerHelper;

    /** @var \Magento\Framework\View\Element\Template\Context|\PHPUnit_Framework_MockObject_MockObject */
    protected $context;

    /** @var \Magento\Review\Model\ResourceModel\Review\Product\Collection|\PHPUnit_Framework_MockObject_MockObject */
    protected $collection;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $collectionFactory;

    /** @var \Magento\Customer\Helper\Session\CurrentCustomer|\PHPUnit_Framework_MockObject_MockObject */
    protected $currentCustomer;

    /** @var \Magento\Store\Model\StoreManagerInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $storeManager;

    protected function setUp()
    {
        $this->storeManager = $this->createMock(\Magento\Store\Model\StoreManagerInterface::class);
        $this->context = $this->createMock(\Magento\Framework\View\Element\Template\Context::class);
        $this->context->expects(
            $this->any()
        )->method(
            'getStoreManager'
        )->will(
            $this->returnValue($this->storeManager)
        );
        $this->collection = $this->createMock(\Magento\Review\Model\ResourceModel\Review\Product\Collection::class);
        $this->collectionFactory = $this->createPartialMock(
            \Magento\Review\Model\ResourceModel\Review\Product\CollectionFactory::class,
            ['create']
        );
        $this->collectionFactory->expects(
            $this->once()
        )->method(
            'create'
        )->will(
            $this->returnValue($this->collection)
        );
        $this->currentCustomer = $this->createMock(\Magento\Customer\Helper\Session\CurrentCustomer::class);

        $this->objectManagerHelper = new ObjectManagerHelper($this);
        $this->object = $this->objectManagerHelper->getObject(
            \Magento\Review\Block\Customer\Recent::class,
            [
                'context' => $this->context,
                'collectionFactory' => $this->collectionFactory,
                'currentCustomer' => $this->currentCustomer
            ]
        );
    }

    public function testGetCollection()
    {
        $this->storeManager->expects(
            $this->any()
        )->method(
            'getStore'
        )->will(
            $this->returnValue(new \Magento\Framework\DataObject(['id' => 42]))
        );
        $this->currentCustomer->expects($this->any())->method('getCustomerId')->will($this->returnValue(4242));

        $this->collection->expects(
            $this->any()
        )->method(
            'addStoreFilter'
        )->with(
            42
        )->will(
            $this->returnValue($this->collection)
        );
        $this->collection->expects(
            $this->any()
        )->method(
            'addCustomerFilter'
        )->with(
            4242
        )->will(
            $this->returnValue($this->collection)
        );
        $this->collection->expects(
            $this->any()
        )->method(
            'setDateOrder'
        )->with()->will(
            $this->returnValue($this->collection)
        );
        $this->collection->expects(
            $this->any()
        )->method(
            'setPageSize'
        )->with(
            5
        )->will(
            $this->returnValue($this->collection)
        );
        $this->collection->expects($this->any())->method('load')->with()->will($this->returnValue($this->collection));
        $this->collection->expects(
            $this->any()
        )->method(
            'addReviewSummary'
        )->with()->will(
            $this->returnValue($this->collection)
        );

        $this->assertSame($this->collection, $this->object->getReviews());
    }
}
