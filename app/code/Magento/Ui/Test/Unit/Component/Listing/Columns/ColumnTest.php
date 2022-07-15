<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Ui\Test\Unit\Component\Listing\Columns;

use Magento\Ui\Component\Listing\Columns\Column;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Framework\View\Element\UiComponentInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponent\DataProvider\DataProviderInterface;

/**
 * Class ColumnTest
 */
class ColumnTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var ContextInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $contextMock;

    /**
     * @var ObjectManager
     */
    protected $objectManager;

    /**
     * Set up
     */
    protected function setUp()
    {
        $this->objectManager = new ObjectManager($this);

        $this->contextMock = $this->getMockForAbstractClass(
            \Magento\Framework\View\Element\UiComponent\ContextInterface::class,
            [],
            '',
            false,
            true,
            true,
            []
        );
    }

    /**
     * Run test getComponentName method
     *
     * @return void
     */
    public function testGetComponentName()
    {
        $this->contextMock->expects($this->never())->method('getProcessor');
        $column = $this->objectManager->getObject(
            \Magento\Ui\Component\Listing\Columns\Column::class,
            [
                'context' => $this->contextMock,
                'data' => [
                    'js_config' => [
                        'extends' => 'test_config_extends'
                    ],
                    'config' => [
                        'dataType' => 'testType'
                    ]
                ]
            ]
        );

        $this->assertEquals($column->getComponentName(), Column::NAME . '.testType');
    }

    /**
     * Run test prepareItems method
     *
     * @return void
     */
    public function testPrepareItems()
    {
        $testItems = ['item1','item2', 'item3'];
        $column = $this->objectManager->getObject(
            \Magento\Ui\Component\Listing\Columns\Column::class,
            ['context' => $this->contextMock]
        );

        $this->assertEquals($testItems, $column->prepareItems($testItems));
    }

    /**
     * Run test prepare method
     *
     * @return void
     */
    public function testPrepare()
    {
        $processor = $this->getMockBuilder(\Magento\Framework\View\Element\UiComponent\Processor::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->contextMock->expects($this->atLeastOnce())->method('getProcessor')->willReturn($processor);
        $data = [
            'name' => 'test_name',
            'js_config' => ['extends' => 'test_config_extends'],
            'config' => ['dataType' => 'test_type', 'sortable' => true]
        ];

        /** @var UiComponentFactory|\PHPUnit_Framework_MockObject_MockObject $uiComponentFactoryMock */
        $uiComponentFactoryMock = $this->createMock(\Magento\Framework\View\Element\UiComponentFactory::class);

        /** @var UiComponentInterface|\PHPUnit_Framework_MockObject_MockObject $wrappedComponentMock */
        $wrappedComponentMock = $this->getMockForAbstractClass(
            \Magento\Framework\View\Element\UiComponentInterface::class,
            [],
            '',
            false
        );
        /** @var DataProviderInterface|\PHPUnit_Framework_MockObject_MockObject $dataProviderMock */
        $dataProviderMock = $this->getMockForAbstractClass(
            \Magento\Framework\View\Element\UiComponent\DataProvider\DataProviderInterface::class,
            [],
            '',
            false
        );

        $this->contextMock->expects($this->atLeastOnce())
            ->method('getNamespace')
            ->willReturn('test_namespace');
        $this->contextMock->expects($this->atLeastOnce())
            ->method('getDataProvider')
            ->willReturn($dataProviderMock);
        $this->contextMock->expects($this->atLeastOnce())
            ->method('getRequestParam')
            ->with('sorting')
            ->willReturn(['field' => 'test_name', 'direction' => 'asc']);
        $this->contextMock->expects($this->atLeastOnce())
            ->method('addComponentDefinition')
            ->with(Column::NAME . '.test_type', ['extends' => 'test_config_extends']);

        $dataProviderMock->expects($this->once())
            ->method('addOrder')
            ->with('test_name', 'ASC');

        $uiComponentFactoryMock->expects($this->once())
            ->method('create')
            ->with('test_name', 'test_type', array_merge(['context' => $this->contextMock], $data))
            ->willReturn($wrappedComponentMock);

        $wrappedComponentMock->expects($this->once())
            ->method('getContext')
            ->willReturn($this->contextMock);
        $wrappedComponentMock->expects($this->once())
            ->method('prepare');

        /** @var Column $column */
        $column = $this->objectManager->getObject(
            \Magento\Ui\Component\Listing\Columns\Column::class,
            [
                'context' => $this->contextMock,
                'uiComponentFactory' => $uiComponentFactoryMock,
                'data' => $data
            ]
        );

        $column->prepare();
    }
}
