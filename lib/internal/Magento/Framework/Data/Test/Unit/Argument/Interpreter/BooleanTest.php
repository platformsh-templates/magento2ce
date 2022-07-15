<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Framework\Data\Test\Unit\Argument\Interpreter;

use \Magento\Framework\Data\Argument\Interpreter\Boolean;

class BooleanTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var Boolean
     */
    protected $_model;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $_booleanUtils;

    protected function setUp()
    {
        $this->_booleanUtils = $this->createMock(\Magento\Framework\Stdlib\BooleanUtils::class);
        $this->_model = new Boolean($this->_booleanUtils);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Boolean value is missing
     */
    public function testEvaluateException()
    {
        $this->_model->evaluate([]);
    }

    public function testEvaluate()
    {
        $input = new \stdClass();
        $expected = new \stdClass();
        $this->_booleanUtils->expects(
            $this->once()
        )->method(
            'toBoolean'
        )->with(
            $this->identicalTo($input)
        )->will(
            $this->returnValue($expected)
        );
        $actual = $this->_model->evaluate(['value' => $input]);
        $this->assertSame($expected, $actual);
    }
}
