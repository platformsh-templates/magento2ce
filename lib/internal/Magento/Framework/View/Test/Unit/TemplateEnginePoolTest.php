<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Framework\View\Test\Unit;

use \Magento\Framework\View\TemplateEnginePool;

class TemplateEnginePoolTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var TemplateEnginePool
     */
    protected $_model;

    /**
     * @var\PHPUnit_Framework_MockObject_MockObject
     */
    protected $_factory;

    protected function setUp()
    {
        $this->_factory = $this->createMock(\Magento\Framework\View\TemplateEngineFactory::class);
        $this->_model = new TemplateEnginePool($this->_factory);
    }

    public function testGet()
    {
        $engine = $this->createMock(\Magento\Framework\View\TemplateEngineInterface::class);
        $this->_factory->expects($this->once())->method('create')->with('test')->will($this->returnValue($engine));
        $this->assertSame($engine, $this->_model->get('test'));
        // Make sure factory is invoked only once and the same instance is returned afterwards
        $this->assertSame($engine, $this->_model->get('test'));
    }
}
