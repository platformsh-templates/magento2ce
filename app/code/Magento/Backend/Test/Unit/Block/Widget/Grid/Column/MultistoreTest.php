<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Backend\Test\Unit\Block\Widget\Grid\Column;

class MultistoreTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\Backend\Block\Widget\Grid\Column\Multistore
     */
    protected $_model;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $_storeManagerMock;

    protected function setUp()
    {
        $this->_storeManagerMock = $this->createMock(\Magento\Store\Model\StoreManager::class);

        $arguments = [
            'storeManager' => $this->_storeManagerMock,
            'urlBuilder' => $this->createMock(\Magento\Backend\Model\Url::class),
        ];

        $objectManagerHelper = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);
        $this->_model = $objectManagerHelper->getObject(
            \Magento\Backend\Block\Widget\Grid\Column\Multistore::class,
            $arguments
        );
    }

    protected function tearDown()
    {
        unset($this->_model);
        unset($this->_storeManagerMock);
    }

    public function testIsDisplayedReturnsTrueInMultiStoreMode()
    {
        $this->_storeManagerMock->expects($this->once())->method('isSingleStoreMode')->will($this->returnValue(false));
        $this->assertTrue($this->_model->isDisplayed());
    }

    public function testIsDisplayedReturnsFalseInSingleStoreMode()
    {
        $this->_storeManagerMock->expects($this->once())->method('isSingleStoreMode')->will($this->returnValue(true));
        $this->assertFalse($this->_model->isDisplayed());
    }
}
