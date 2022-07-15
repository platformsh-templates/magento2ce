<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Catalog\Test\Unit\Model\ResourceModel;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;

class ProductTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\Catalog\Model\ResourceModel\Product
     */
    protected $model;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $setFactoryMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $typeFactoryMock;

    protected function setUp()
    {
        $objectManager = new ObjectManager($this);

        $this->setFactoryMock = $this->createPartialMock(
            \Magento\Eav\Model\Entity\Attribute\SetFactory::class,
            ['create', '__wakeup']
        );
        $this->typeFactoryMock = $this->createPartialMock(
            \Magento\Eav\Model\Entity\TypeFactory::class,
            ['create', '__wakeup']
        );

        $this->model = $objectManager->getObject(
            \Magento\Catalog\Model\ResourceModel\Product::class,
            [
                'setFactory' => $this->setFactoryMock,
                'typeFactory' => $this->typeFactoryMock,
            ]
        );
    }

    public function testValidateWrongAttributeSet()
    {
        $productTypeId = 4;
        $expectedErrorMessage = ['attribute_set' => 'Invalid attribute set entity type'];

        $productMock = $this->createPartialMock(
            \Magento\Framework\DataObject::class,
            ['getAttributeSetId', '__wakeup']
        );
        $attributeSetMock = $this->createPartialMock(
            \Magento\Eav\Model\Entity\Attribute\Set::class,
            ['load', 'getEntityTypeId', '__wakeup']
        );
        $entityTypeMock = $this->createMock(\Magento\Eav\Model\Entity\Type::class);

        $this->typeFactoryMock->expects($this->once())->method('create')->will($this->returnValue($entityTypeMock));
        $entityTypeMock->expects($this->once())->method('loadByCode')->with('catalog_product')->willReturnSelf();

        $productAttributeSetId = 4;
        $productMock->expects($this->once())->method('getAttributeSetId')
            ->will($this->returnValue($productAttributeSetId));

        $this->setFactoryMock->expects($this->once())->method('create')->will($this->returnValue($attributeSetMock));
        $attributeSetMock->expects($this->once())->method('load')->with($productAttributeSetId)->willReturnSelf();

        //attribute set of wrong type
        $attributeSetMock->expects($this->once())->method('getEntityTypeId')->will($this->returnValue(3));
        $entityTypeMock->expects($this->once())->method('getId')->will($this->returnValue($productTypeId));

        $this->assertEquals($expectedErrorMessage, $this->model->validate($productMock));
    }
}
