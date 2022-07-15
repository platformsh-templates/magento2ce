<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Framework\App\Test\Unit\Config\Data;

class ProcessorFactoryTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\Framework\App\Config\Data\ProcessorFactory
     */
    protected $_model;

    /**
     * @var \Magento\Framework\App\Config\Data\ProcessorInterface
     */
    protected $_processorMock;

    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    protected $_objectManager;

    protected function setUp()
    {
        $this->_objectManager = $this->createMock(\Magento\Framework\ObjectManagerInterface::class);
        $this->_model = new \Magento\Framework\App\Config\Data\ProcessorFactory($this->_objectManager);
        $this->_processorMock = $this->getMockForAbstractClass(
            \Magento\Framework\App\Config\Data\ProcessorInterface::class
        );
    }

    /**
     * @covers \Magento\Framework\App\Config\Data\ProcessorFactory::get
     */
    public function testGetModelWithCorrectInterface()
    {
        $this->_objectManager->expects(
            $this->once()
        )->method(
            'create'
        )->with(
            \Magento\Framework\App\Config\Data\TestBackendModel::class
        )->will(
            $this->returnValue($this->_processorMock)
        );

        $this->assertInstanceOf(
            \Magento\Framework\App\Config\Data\ProcessorInterface::class,
            $this->_model->get(\Magento\Framework\App\Config\Data\TestBackendModel::class)
        );
    }

    /**
     * @covers \Magento\Framework\App\Config\Data\ProcessorFactory::get
     * @expectedException \InvalidArgumentException
     */
    public function testGetModelWithWrongInterface()
    {
        $this->_objectManager->expects(
            $this->once()
        )->method(
            'create'
        )->with(
            \Magento\Framework\App\Config\Data\WrongBackendModel::class
        )->will(
            $this->returnValue(
                $this->getMockBuilder('WrongBackendModel')->getMock()
            )
        );

        $this->_model->get(\Magento\Framework\App\Config\Data\WrongBackendModel::class);
    }

    /**
     * @covers \Magento\Framework\App\Config\Data\ProcessorFactory::get
     */
    public function testGetMemoryCache()
    {
        $this->_objectManager->expects(
            $this->once()
        )->method(
            'create'
        )->with(
            \Magento\Framework\App\Config\Data\TestBackendModel::class
        )->will(
            $this->returnValue($this->_processorMock)
        );

        $this->_model->get(\Magento\Framework\App\Config\Data\TestBackendModel::class);
        $this->_model->get(\Magento\Framework\App\Config\Data\TestBackendModel::class);
    }
}
