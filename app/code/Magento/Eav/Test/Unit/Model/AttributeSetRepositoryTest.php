<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Eav\Test\Unit\Model;

use Magento\Eav\Model\AttributeSetRepository;
use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;

/**
 * @SuppressWarnings(PHPMD.LongVariable)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class AttributeSetRepositoryTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var AttributeSetRepository
     */
    private $model;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $resourceMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $setFactoryMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $collectionFactoryMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $eavConfigMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $resultFactoryMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $extensionAttributesJoinProcessorMock;

    /**
     * @var CollectionProcessorInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $collectionProcessor;

    /**
     * @return void
     */
    protected function setUp()
    {
        $this->resourceMock = $this->createMock(\Magento\Eav\Model\ResourceModel\Entity\Attribute\Set::class);
        $this->setFactoryMock = $this->createPartialMock(
            \Magento\Eav\Model\Entity\Attribute\SetFactory::class,
            ['create']
        );
        $this->collectionFactoryMock = $this->createPartialMock(
            \Magento\Eav\Model\ResourceModel\Entity\Attribute\Set\CollectionFactory::class,
            ['create']
        );
        $this->eavConfigMock = $this->createPartialMock(\Magento\Eav\Model\Config::class, ['getEntityType']);
        $this->resultFactoryMock = $this->createPartialMock(
            \Magento\Eav\Api\Data\AttributeSetSearchResultsInterfaceFactory::class,
            ['create']
        );
        $this->extensionAttributesJoinProcessorMock = $this->createPartialMock(
            \Magento\Framework\Api\ExtensionAttribute\JoinProcessor::class,
            ['process']
        );

        $this->collectionProcessor = $this->getMockBuilder(CollectionProcessorInterface::class)
            ->getMockForAbstractClass();

        $this->model = new \Magento\Eav\Model\AttributeSetRepository(
            $this->resourceMock,
            $this->setFactoryMock,
            $this->collectionFactoryMock,
            $this->eavConfigMock,
            $this->resultFactoryMock,
            $this->extensionAttributesJoinProcessorMock,
            $this->collectionProcessor
        );
    }

    /**
     * @return void
     */
    public function testGet()
    {
        $attributeSetId = 1;
        $attributeSetMock = $this->createMock(\Magento\Eav\Model\Entity\Attribute\Set::class);
        $this->setFactoryMock->expects($this->once())->method('create')->will($this->returnValue($attributeSetMock));
        $this->resourceMock->expects($this->once())->method('load')->with($attributeSetMock, $attributeSetId, null);
        $attributeSetMock->expects($this->any())->method('getId')->will($this->returnValue($attributeSetId));
        $this->assertEquals($attributeSetMock, $this->model->get($attributeSetId));
    }

    /**
     * @return void
     * @expectedException \Magento\Framework\Exception\NoSuchEntityException
     * @expectedExceptionMessage No such entity with id = 9999
     */
    public function testGetThrowsExceptionIfRequestedAttributeSetDoesNotExist()
    {
        $attributeSetId = 9999;
        $attributeSetMock = $this->createMock(\Magento\Eav\Model\Entity\Attribute\Set::class);
        $this->setFactoryMock->expects($this->once())->method('create')->will($this->returnValue($attributeSetMock));
        $this->resourceMock->expects($this->once())->method('load')->with($attributeSetMock, $attributeSetId, null);
        $this->model->get($attributeSetId);
    }

    /**
     * @return void
     */
    public function testSave()
    {
        $attributeSetMock = $this->createMock(\Magento\Eav\Model\Entity\Attribute\Set::class);
        $this->resourceMock->expects($this->once())->method('save')->with($attributeSetMock);
        $this->assertEquals($attributeSetMock, $this->model->save($attributeSetMock));
    }

    /**
     * @return void
     * @expectedException \Magento\Framework\Exception\CouldNotSaveException
     */
    public function testSaveThrowsExceptionIfGivenEntityCannotBeSaved()
    {
        $attributeSetMock = $this->createMock(\Magento\Eav\Model\Entity\Attribute\Set::class);
        $this->resourceMock->expects($this->once())->method('save')->with($attributeSetMock)->willThrowException(
            new \Exception('Some internal exception message.')
        );
        $this->model->save($attributeSetMock);

        $this->expectExceptionMessage(
            "The attribute set couldn't be saved due to an error. Verify your information and try again. "
            . "If the error persists, please try again later."
        );
    }

    /**
     * @return void
     */
    public function testDelete()
    {
        $attributeSetMock = $this->createMock(\Magento\Eav\Model\Entity\Attribute\Set::class);
        $this->resourceMock->expects($this->once())->method('delete')->with($attributeSetMock);
        $this->assertTrue($this->model->delete($attributeSetMock));
    }

    /**
     * @return void
     * @expectedException \Magento\Framework\Exception\CouldNotDeleteException
     */
    public function testDeleteThrowsExceptionIfGivenEntityCannotBeDeleted()
    {
        $attributeSetMock = $this->createMock(\Magento\Eav\Model\Entity\Attribute\Set::class);
        $this->resourceMock->expects($this->once())->method('delete')->with($attributeSetMock)->willThrowException(
            new \Magento\Framework\Exception\CouldNotDeleteException(__('Some internal exception message.'))
        );
        $this->model->delete($attributeSetMock);

        $this->expectExceptionMessage(
            "The attribute set couldn't be deleted due to an error. "
            . "Try again — if the error persists, please try again later."
        );
    }

    /**
     * @return void
     * @expectedException \Magento\Framework\Exception\CouldNotDeleteException
     * @expectedExceptionMessage The default attribute set can't be deleted.
     */
    public function testDeleteThrowsExceptionIfGivenAttributeSetIsDefault()
    {
        $attributeSetMock = $this->createMock(\Magento\Eav\Model\Entity\Attribute\Set::class);
        $this->resourceMock->expects($this->once())->method('delete')->with($attributeSetMock)->willThrowException(
            new \Magento\Framework\Exception\StateException(__('Some internal exception message.'))
        );
        $this->model->delete($attributeSetMock);
    }

    /**
     * @return void
     */
    public function testDeleteById()
    {
        $attributeSetId = 1;
        $attributeSetMock = $this->createMock(\Magento\Eav\Model\Entity\Attribute\Set::class);
        $attributeSetMock->expects($this->any())->method('getId')->will($this->returnValue($attributeSetId));
        $this->setFactoryMock->expects($this->once())->method('create')->will($this->returnValue($attributeSetMock));
        $this->resourceMock->expects($this->once())->method('load')->with($attributeSetMock, $attributeSetId, null);
        $this->resourceMock->expects($this->once())->method('delete')->with($attributeSetMock);
        $this->assertTrue($this->model->deleteById($attributeSetId));
    }

    /**
     * @return void
     */
    public function testGetList()
    {
        $attributeSetMock = $this->createMock(\Magento\Eav\Model\Entity\Attribute\Set::class);

        $collectionMock = $this->getMockBuilder(\Magento\Eav\Model\ResourceModel\Entity\Attribute\Set\Collection::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'getItems',
                'getSize',
            ])
            ->getMock();

        $collectionMock->expects($this->once())
            ->method('getItems')
            ->willReturn([$attributeSetMock]);
        $collectionMock->expects($this->once())
            ->method('getSize')
            ->willReturn(1);

        $this->collectionFactoryMock->expects($this->once())->method('create')->willReturn($collectionMock);

        $searchCriteriaMock = $this->createMock(\Magento\Framework\Api\SearchCriteriaInterface::class);

        $resultMock = $this->getMockBuilder(\Magento\Eav\Api\Data\AttributeSetSearchResultsInterface::class)
            ->getMockForAbstractClass();

        $resultMock->expects($this->once())
            ->method('setSearchCriteria')
            ->with($searchCriteriaMock)
            ->willReturnSelf();
        $resultMock->expects($this->once())
            ->method('setItems')
            ->with([$attributeSetMock])
            ->willReturnSelf();
        $resultMock->expects($this->once())
            ->method('setTotalCount')
            ->with(1)
            ->willReturnSelf();

        $this->resultFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($resultMock);

        $this->collectionProcessor->expects($this->once())
            ->method('process')
            ->with($searchCriteriaMock, $collectionMock)
            ->willReturnSelf();

        $this->model->getList($searchCriteriaMock);
    }

    /**
     * @return void
     */
    public function testGetListIfEntityTypeCodeIsNull()
    {
        $attributeSetMock = $this->createMock(\Magento\Eav\Model\Entity\Attribute\Set::class);

        $collectionMock = $this->getMockBuilder(\Magento\Eav\Model\ResourceModel\Entity\Attribute\Set\Collection::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'getItems',
                'getSize',
            ])
            ->getMock();

        $collectionMock->expects($this->once())
            ->method('getItems')
            ->willReturn([$attributeSetMock]);
        $collectionMock->expects($this->once())
            ->method('getSize')
            ->willReturn(1);

        $this->collectionFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($collectionMock);

        $searchCriteriaMock = $this->createMock(\Magento\Framework\Api\SearchCriteriaInterface::class);

        $resultMock = $this->getMockBuilder(\Magento\Eav\Api\Data\AttributeSetSearchResultsInterface::class)
            ->getMockForAbstractClass();

        $resultMock->expects($this->once())
            ->method('setSearchCriteria')
            ->with($searchCriteriaMock)
            ->willReturnSelf();
        $resultMock->expects($this->once())
            ->method('setItems')
            ->with([$attributeSetMock])
            ->willReturnSelf();
        $resultMock->expects($this->once())
            ->method('setTotalCount')
            ->with(1)
            ->willReturnSelf();

        $this->resultFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($resultMock);

        $this->collectionProcessor->expects($this->once())
            ->method('process')
            ->with($searchCriteriaMock, $collectionMock)
            ->willReturnSelf();

        $this->model->getList($searchCriteriaMock);
    }
}
