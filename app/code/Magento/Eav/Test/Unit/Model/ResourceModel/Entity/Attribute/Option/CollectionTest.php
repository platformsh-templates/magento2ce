<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Eav\Test\Unit\Model\ResourceModel\Entity\Attribute\Option;

class CollectionTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\Eav\Model\ResourceModel\Entity\Attribute\Option\Collection|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $model;

    /**
     * @var \Magento\Framework\Data\Collection\EntityFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $entityFactoryMock;

    /**
     * @var \Psr\Log\LoggerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $loggerMock;

    /**
     * @var \Magento\Framework\Data\Collection\Db\FetchStrategyInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $fetchStrategyMock;

    /**
     * @var \Magento\Framework\Event\ManagerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $eventManagerMock;

    /**
     * @var \Magento\Framework\App\ResourceConnection|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $coreResourceMock;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $storeManagerMock;

    /**
     * @var \Magento\Framework\DB\Adapter\AdapterInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $connectionMock;

    /**
     * @var \Magento\Framework\Model\ResourceModel\Db\AbstractDb|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $resourceMock;

    /**
     * @var \Magento\Framework\DB\Select|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $selectMock;

    protected function setUp()
    {
        $this->entityFactoryMock = $this->createMock(\Magento\Framework\Data\Collection\EntityFactory::class);
        $this->loggerMock = $this->createMock(\Psr\Log\LoggerInterface::class);
        $this->fetchStrategyMock = $this->createMock(
            \Magento\Framework\Data\Collection\Db\FetchStrategyInterface::class
        );
        $this->eventManagerMock = $this->createMock(\Magento\Framework\Event\ManagerInterface::class);
        $this->coreResourceMock = $this->createMock(\Magento\Framework\App\ResourceConnection::class);
        $this->storeManagerMock = $this->createMock(\Magento\Store\Model\StoreManagerInterface::class);
        $this->connectionMock = $this->createMock(\Magento\Framework\DB\Adapter\Pdo\Mysql::class);
        $this->resourceMock = $this->getMockForAbstractClass(
            \Magento\Framework\Model\ResourceModel\Db\AbstractDb::class,
            [],
            '',
            false,
            true,
            true,
            ['__wakeup', 'getConnection', 'getMainTable', 'getTable']
        );
        $this->selectMock = $this->createMock(\Magento\Framework\DB\Select::class);

        $this->coreResourceMock->expects($this->any())->method('getConnection')->willReturn($this->connectionMock);
        $this->coreResourceMock->expects($this->any())->method('getTableName')->with('eav_attribute_option_value')
            ->will($this->returnValue(null));

        $this->connectionMock->expects($this->any())->method('select')->will($this->returnValue($this->selectMock));
        $this->connectionMock->expects($this->any())->method('quoteIdentifier')->will($this->returnArgument(0));

        $this->resourceMock->expects(
            $this->any()
        )->method(
            'getConnection'
        )->will(
            $this->returnValue($this->connectionMock)
        );
        $this->resourceMock->expects(
            $this->any()
        )->method(
            'getMainTable'
        )->will(
            $this->returnValue('eav_attribute_option')
        );
        $this->resourceMock->expects(
            $this->any()
        )->method(
            'getTable'
        )->with(
            'eav_attribute_option'
        )->will(
            $this->returnValue('eav_attribute_option')
        );

        $this->model = new \Magento\Eav\Model\ResourceModel\Entity\Attribute\Option\Collection(
            $this->entityFactoryMock,
            $this->loggerMock,
            $this->fetchStrategyMock,
            $this->eventManagerMock,
            $this->coreResourceMock,
            $this->storeManagerMock,
            null,
            $this->resourceMock
        );
    }

    public function testSetIdFilter()
    {
        $this->connectionMock->expects(
            $this->once()
        )->method(
            'prepareSqlCondition'
        )->with(
            'main_table.option_id',
            ['in' => 1]
        )->will(
            $this->returnValue('main_table.option_id IN (1)')
        );

        $this->selectMock->expects(
            $this->once()
        )->method(
            'where'
        )->with(
            'main_table.option_id IN (1)',
            null,
            'TYPE_CONDITION'
        )->will(
            $this->returnSelf()
        );

        $this->assertEquals($this->model, $this->model->setIdFilter(1));
    }
}
