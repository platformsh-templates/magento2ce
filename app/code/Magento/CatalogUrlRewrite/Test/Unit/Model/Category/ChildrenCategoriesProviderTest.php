<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CatalogUrlRewrite\Test\Unit\Model\Category;

use Magento\Catalog\Model\Category;
use Magento\Catalog\Model\ResourceModel\Collection\AbstractCollection;
use Magento\CatalogUrlRewrite\Model\Category\ChildrenCategoriesProvider;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Select;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ChildrenCategoriesProviderTest extends TestCase
{
    /** @var MockObject */
    protected $category;

    /** @var MockObject */
    protected $select;

    /** @var MockObject */
    protected $connection;

    /** @var ChildrenCategoriesProvider */
    protected $childrenCategoriesProvider;

    protected function setUp(): void
    {
        $this->category = $this->getMockBuilder(Category::class)
            ->disableOriginalConstructor()
            ->setMethods(['getPath', 'getResourceCollection', 'getResource', 'getLevel', '__wakeup', 'isObjectNew'])
            ->getMock();
        $categoryCollection = $this->getMockBuilder(
            AbstractCollection::class
        )->disableOriginalConstructor()
            ->setMethods(['addAttributeToSelect', 'addIdFilter'])->getMock();
        $this->category->expects($this->any())->method('getPath')->willReturn('category-path');
        $this->category->expects($this->any())->method('getResourceCollection')->willReturn($categoryCollection);
        $categoryCollection->expects($this->any())->method('addAttributeToSelect')->willReturnSelf();
        $categoryCollection->expects($this->any())->method('addIdFilter')->with(['id'])->willReturnSelf();
        $this->select = $this->getMockBuilder(Select::class)
            ->disableOriginalConstructor()
            ->setMethods(['from', 'where', 'deleteFromSelect'])->getMock();
        $this->connection = $this->getMockForAbstractClass(AdapterInterface::class);
        $categoryResource = $this->getMockBuilder(\Magento\Catalog\Model\ResourceModel\Category::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->category->expects($this->any())->method('getResource')->willReturn($categoryResource);
        $categoryResource->expects($this->any())->method('getConnection')->willReturn($this->connection);
        $this->connection->expects($this->any())->method('select')->willReturn($this->select);
        $this->connection->expects($this->any())->method('quoteIdentifier')->willReturnArgument(0);
        $this->select->expects($this->any())->method('from')->willReturnSelf();

        $this->childrenCategoriesProvider = (new ObjectManager($this))->getObject(
            ChildrenCategoriesProvider::class
        );
    }

    public function testGetChildrenRecursive()
    {
        $bind = ['c_path' => 'category-path/%'];
        $this->category->expects($this->once())->method('isObjectNew')->willReturn(false);
        $this->select->expects($this->any())->method('where')->with('path LIKE :c_path')->willReturnSelf();
        $this->connection->expects($this->any())->method('fetchCol')->with($this->select, $bind)->willReturn(['id']);
        $this->childrenCategoriesProvider->getChildren($this->category, true);
    }

    public function testGetChildrenForNewCategory()
    {
        $this->category->expects($this->once())->method('isObjectNew')->willReturn(true);
        $this->assertEquals([], $this->childrenCategoriesProvider->getChildren($this->category));
    }

    public function testGetChildren()
    {
        $categoryLevel = 3;
        $this->select->expects($this->at(1))->method('where')->with('path LIKE :c_path')->willReturnSelf();
        $this->select->expects($this->at(2))->method('where')->with('level <= :c_level')->willReturnSelf();
        $this->category->expects($this->once())->method('isObjectNew')->willReturn(false);
        $this->category->expects($this->once())->method('getLevel')->willReturn($categoryLevel);
        $bind = ['c_path' => 'category-path/%', 'c_level' => $categoryLevel + 1];
        $this->connection->expects($this->any())->method('fetchCol')->with($this->select, $bind)->willReturn(['id']);

        $this->childrenCategoriesProvider->getChildren($this->category, false);
    }
}
