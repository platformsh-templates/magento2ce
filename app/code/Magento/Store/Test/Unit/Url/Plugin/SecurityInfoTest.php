<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Store\Test\Unit\Url\Plugin;

class SecurityInfoTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $_scopeConfigMock;

    /**
     * @var \Magento\Store\Url\Plugin\SecurityInfo
     */
    protected $_model;

    protected function setUp()
    {
        $this->_scopeConfigMock = $this->createMock(\Magento\Framework\App\Config\ScopeConfigInterface::class);
        $this->_model = new \Magento\Store\Url\Plugin\SecurityInfo($this->_scopeConfigMock);
    }

    public function testAroundIsSecureDisabledInConfig()
    {
        $this->_scopeConfigMock
            ->expects($this->once())
            ->method('getValue')
            ->with(
                \Magento\Store\Model\Store::XML_PATH_SECURE_IN_FRONTEND,
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE
            )
            ->will($this->returnValue(false));
        $this->assertFalse(
            $this->_model->aroundIsSecure(
                $this->createMock(\Magento\Framework\Url\SecurityInfo::class),
                function () {
                },
                'http://example.com/account'
            )
        );
    }

    public function testAroundIsSecureEnabledInConfig()
    {
        $this->_scopeConfigMock
            ->expects($this->once())
            ->method('getValue')
            ->with(
                \Magento\Store\Model\Store::XML_PATH_SECURE_IN_FRONTEND,
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE
            )
            ->will($this->returnValue(true));
        $this->assertTrue(
            $this->_model->aroundIsSecure(
                $this->createMock(\Magento\Framework\Url\SecurityInfo::class),
                function () {
                    return true;
                },
                'https://example.com/account'
            )
        );
    }
}
