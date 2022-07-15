<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Sales\Test\Unit\Model\ResourceModel\Order;

/**
 * Class AddressTest
 */
class AddressTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\Sales\Model\ResourceModel\Order\Address
     */
    protected $addressResource;

    /**
     * @var \Magento\Framework\App\ResourceConnection|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $appResourceMock;

    /**
     * @var \Magento\Sales\Model\Order\Address|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $addressMock;

    /**
     * @var \Magento\Sales\Model\Order|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $orderMock;

    /**
     * @var \Magento\Framework\DB\Adapter\AdapterInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $connectionMock;

    /**
     * @var \Magento\Sales\Model\Order\Address\Validator|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $validatorMock;

    /**
     * @var \Magento\Framework\Model\ResourceModel\Db\VersionControl\Snapshot|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $entitySnapshotMock;

    protected function setUp()
    {
        $this->addressMock = $this->createPartialMock(
            \Magento\Sales\Model\Order\Address::class,
            ['__wakeup', 'getParentId', 'hasDataChanges', 'beforeSave', 'afterSave', 'validateBeforeSave', 'getOrder']
        );
        $this->orderMock = $this->createPartialMock(\Magento\Sales\Model\Order::class, ['__wakeup', 'getId']);
        $this->appResourceMock = $this->createMock(\Magento\Framework\App\ResourceConnection::class);
        $this->connectionMock = $this->createMock(\Magento\Framework\DB\Adapter\Pdo\Mysql::class);
        $this->validatorMock = $this->createMock(\Magento\Sales\Model\Order\Address\Validator::class);
        $this->entitySnapshotMock = $this->createMock(
            \Magento\Framework\Model\ResourceModel\Db\VersionControl\Snapshot::class
        );
        $this->appResourceMock->expects($this->any())
            ->method('getConnection')
            ->will($this->returnValue($this->connectionMock));
        $objectManager = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);
        $this->connectionMock->expects($this->any())
            ->method('describeTable')
            ->will($this->returnValue([]));
        $this->connectionMock->expects($this->any())
            ->method('insert');
        $this->connectionMock->expects($this->any())
            ->method('lastInsertId');
        $this->addressResource = $objectManager->getObject(
            \Magento\Sales\Model\ResourceModel\Order\Address::class,
            [
                'resource' => $this->appResourceMock,
                'validator' => $this->validatorMock,
                'entitySnapshot' => $this->entitySnapshotMock
            ]
        );
    }

    /**
     * test _beforeSaveMethod via save()
     */
    public function testSave()
    {
        $this->validatorMock->expects($this->once())
            ->method('validate')
            ->with($this->equalTo($this->addressMock))
            ->will($this->returnValue([]));
        $this->entitySnapshotMock->expects($this->once())
            ->method('isModified')
            ->with($this->addressMock)
            ->willReturn(true);
        $this->addressMock->expects($this->once())
            ->method('getParentId')
            ->will($this->returnValue(1));

        $this->addressResource->save($this->addressMock);
    }

    /**
     * test _beforeSaveMethod via save() with failed validation
     *
     * @expectedException \Magento\Framework\Exception\LocalizedException
     * @expectedExceptionMessage We can't save the address:
     */
    public function testSaveValidationFailed()
    {
        $this->entitySnapshotMock->expects($this->once())
            ->method('isModified')
            ->with($this->addressMock)
            ->willReturn(true);
        $this->addressMock->expects($this->any())
            ->method('hasDataChanges')
            ->will($this->returnValue(true));
        $this->validatorMock->expects($this->once())
            ->method('validate')
            ->with($this->equalTo($this->addressMock))
            ->will($this->returnValue(['warning message']));
        $this->addressResource->save($this->addressMock);
    }
}
