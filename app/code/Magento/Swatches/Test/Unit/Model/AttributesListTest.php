<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Swatches\Test\Unit\Model;

class AttributesListTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\Swatches\Model\AttributesList
     */
    protected $attributeListModel;

    /**
     * @var \Magento\Catalog\Model\ResourceModel\Product\Attribute\Collection|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $collectionMock;

    /**
     * @var \Magento\Catalog\Model\ResourceModel\Eav\Attribute|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $attributeMock;

    /** @var \Magento\Swatches\Helper\Data|\PHPUnit_Framework_MockObject_MockObject */
    protected $swatchHelper;

    protected function setUp()
    {
        $this->swatchHelper = $this->createMock(\Magento\Swatches\Helper\Data::class);

        $this->collectionMock = $this->createMock(
            \Magento\Catalog\Model\ResourceModel\Product\Attribute\Collection::class
        );

        /** @var  \Magento\Catalog\Model\ResourceModel\Product\Attribute\CollectionFactory $collectionFactoryMock */
        $collectionFactoryMock = $this->createPartialMock(
            \Magento\Catalog\Model\ResourceModel\Product\Attribute\CollectionFactory::class,
            ['create']
        );
        $collectionFactoryMock->expects($this->once())->method('create')->willReturn($this->collectionMock);

        $methods = ['getId', 'getFrontendLabel', 'getAttributeCode', 'getSource'];
        $this->attributeMock = $this->createPartialMock(
            \Magento\Catalog\Model\ResourceModel\Eav\Attribute::class,
            $methods
        );
        $this->collectionMock
            ->expects($this->once())
            ->method('getItems')
            ->will($this->returnValue(['id' => $this->attributeMock]));

        $this->attributeListModel = new \Magento\Swatches\Model\AttributesList(
            $collectionFactoryMock,
            $this->swatchHelper
        );
    }

    public function testGetAttributes()
    {
        $ids = [1, 2, 3];
        $result = [
            [
                'id' => 'id',
                'label' => 'label',
                'code' => 'code',
                'options' => ['options'],
                'canCreateOption' => false
            ]
        ];

        $this->collectionMock
            ->expects($this->any())
            ->method('addFieldToFilter')
            ->with('main_table.attribute_id', $ids);

        $this->attributeMock->expects($this->once())->method('getId')->will($this->returnValue('id'));
        $this->attributeMock->expects($this->once())->method('getFrontendLabel')->will($this->returnValue('label'));
        $this->attributeMock->expects($this->once())->method('getAttributeCode')->will($this->returnValue('code'));

        $source = $this->createMock(\Magento\Eav\Model\Entity\Attribute\Source\AbstractSource::class);
        $source->expects($this->once())->method('getAllOptions')->with(false)->will($this->returnValue(['options']));
        $this->attributeMock->expects($this->once())->method('getSource')->will($this->returnValue($source));

        $this->swatchHelper->expects($this->once())->method('isSwatchAttribute')
            ->with($this->attributeMock)
            ->willReturn(true);

        $this->assertEquals($result, $this->attributeListModel->getAttributes($ids));
    }
}
