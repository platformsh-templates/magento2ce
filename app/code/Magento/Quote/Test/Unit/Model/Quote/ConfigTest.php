<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Quote\Test\Unit\Model\Quote;

class ConfigTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\Quote\Model\Quote\Config
     */
    protected $_model;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $_attributeConfig;

    protected function setUp()
    {
        $this->_attributeConfig = $this->createMock(\Magento\Catalog\Model\Attribute\Config::class);
        $this->_model = new \Magento\Quote\Model\Quote\Config($this->_attributeConfig);
    }

    public function testGetProductAttributes()
    {
        $attributes = ['attribute_one', 'attribute_two'];
        $this->_attributeConfig->expects(
            $this->once()
        )->method(
            'getAttributeNames'
        )->with(
            'quote_item'
        )->will(
            $this->returnValue($attributes)
        );
        $this->assertEquals($attributes, $this->_model->getProductAttributes());
    }
}
