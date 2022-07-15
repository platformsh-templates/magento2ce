<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Framework\Api\Test\Unit;

use Magento\Framework\Api\SortOrder;

/**
 * @covers \Magento\Framework\Api\SortOrder
 */
class SortOrderTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var SortOrder
     */
    private $sortOrder;

    protected function setUp()
    {
        $this->sortOrder = new SortOrder();
    }
    
    public function testItReturnsNullIfNoOrderIsSet()
    {
        $this->assertNull($this->sortOrder->getDirection());
    }

    /**
     * @dataProvider sortOrderDirectionProvider
     */
    public function testItReturnsTheCorrectValuesIfSortOrderIsSet($sortOrder)
    {
        $this->sortOrder->setDirection($sortOrder);
        $this->assertSame($sortOrder, $this->sortOrder->getDirection());
    }

    /**
     * @return array
     */
    public function sortOrderDirectionProvider()
    {
        return [[SortOrder::SORT_ASC], [SortOrder::SORT_DESC]];
    }
    
    /**
     * @param mixed $invalidDirection
     * @dataProvider invalidSortDirectionProvider
     * @expectedException \Magento\Framework\Exception\InputException
     */
    public function testItThrowsAnExceptionIfAnInvalidSortOrderIsSet($invalidDirection)
    {
        $this->sortOrder->setDirection($invalidDirection);
    }

    /**
     * @return array
     */
    public function invalidSortDirectionProvider()
    {
        return [
            [-1],
            [1],
            [0],
            [true],
            [false],
            [[]],
        ];
    }

    public function testTheSortDirectionCanBeSpecifiedCaseInsensitive()
    {
        $this->sortOrder->setDirection(strtolower(SortOrder::SORT_ASC));
        $this->assertSame(SortOrder::SORT_ASC, $this->sortOrder->getDirection());
        $this->sortOrder->setDirection(strtoupper(SortOrder::SORT_ASC));
        $this->assertSame(SortOrder::SORT_ASC, $this->sortOrder->getDirection());
        
        $this->sortOrder->setDirection(strtolower(SortOrder::SORT_DESC));
        $this->assertSame(SortOrder::SORT_DESC, $this->sortOrder->getDirection());
        $this->sortOrder->setDirection(strtoupper(SortOrder::SORT_DESC));
        $this->assertSame(SortOrder::SORT_DESC, $this->sortOrder->getDirection());
    }

    /**
     * @expectedException \Magento\Framework\Exception\InputException
     */
    public function testItValidatesADirectionAssignedDuringInstantiation()
    {
        $this->sortOrder = new SortOrder([
            SortOrder::DIRECTION => 'not-asc-or-desc'
        ]);
    }

    /**
     * @expectedException \Magento\Framework\Exception\InputException
     */
    public function testValidateField()
    {
        $this->sortOrder = new SortOrder([
            SortOrder::FIELD => 'invalid field (value);'
        ]);
    }
}
