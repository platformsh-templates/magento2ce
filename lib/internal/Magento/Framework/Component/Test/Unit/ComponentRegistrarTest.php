<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Framework\Component\Test\Unit;

use Magento\Framework\Component\ComponentRegistrar;

class ComponentRegistrarTest extends \PHPUnit\Framework\TestCase
{
    /**
     * Module registrar object
     *
     * @var ComponentRegistrar
     */
    private $object;

    protected function setUp()
    {
        $this->object = new ComponentRegistrar();
    }

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage 'some_type' is not a valid component type
     */
    public function testWithInvalidType()
    {
        ComponentRegistrar::register('some_type', "test_module_one", "some/path/name/one");
    }

    public function testGetPathsForModule()
    {
        ComponentRegistrar::register(ComponentRegistrar::MODULE, "test_module_one", "some/path/name/one");
        ComponentRegistrar::register(ComponentRegistrar::MODULE, "test_module_two", "some/path/name/two");
        $expected = [
            'test_module_one' => "some/path/name/one",
            'test_module_two' => "some/path/name/two",
        ];
        $this->assertContains($expected['test_module_one'], $this->object->getPaths(ComponentRegistrar::MODULE));
        $this->assertContains($expected['test_module_two'], $this->object->getPaths(ComponentRegistrar::MODULE));
    }

    /**
     * @expectedException \LogicException
     */
    public function testRegistrarWithExceptionForModules()
    {
        ComponentRegistrar::register(ComponentRegistrar::MODULE, "test_module_one", "some/path/name/onemore");
    }

    public function testGetPath()
    {
        $this->assertSame("some/path/name/one", $this->object->getPath(ComponentRegistrar::MODULE, 'test_module_one'));
        $this->assertSame("some/path/name/two", $this->object->getPath(ComponentRegistrar::MODULE, 'test_module_two'));
    }
}
