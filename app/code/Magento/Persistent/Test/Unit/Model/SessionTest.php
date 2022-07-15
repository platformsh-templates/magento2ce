<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Persistent\Test\Unit\Model;

class SessionTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\Persistent\Model\Session
     */
    protected $session;

    /**
     * @var \Magento\Framework\Session\Config\ConfigInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $configMock;

    /**
     * @var \Magento\Framework\Stdlib\CookieManagerInterface |\PHPUnit_Framework_MockObject_MockObject
     */
    protected $cookieManagerMock;

    /**
     * @var \Magento\Framework\Stdlib\Cookie\CookieMetadataFactory |\PHPUnit_Framework_MockObject_MockObject
     */
    protected $cookieMetadataFactoryMock;

    protected function setUp()
    {
        $helper = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);
        $this->configMock = $this->createMock(\Magento\Framework\Session\Config\ConfigInterface::class);
        $this->cookieManagerMock = $this->createMock(\Magento\Framework\Stdlib\CookieManagerInterface::class);
        $this->cookieMetadataFactoryMock = $this->getMockBuilder(
            \Magento\Framework\Stdlib\Cookie\CookieMetadataFactory::class
        )->disableOriginalConstructor()
            ->getMock();

        $resourceMock = $this->getMockForAbstractClass(
            \Magento\Framework\Model\ResourceModel\Db\AbstractDb::class,
            [],
            '',
            false,
            false,
            true,
            ['__wakeup', 'getIdFieldName', 'getConnection', 'beginTransaction', 'delete', 'commit', 'rollBack']
        );

        $actionValidatorMock = $this->createMock(\Magento\Framework\Model\ActionValidator\RemoveAction::class);
        $actionValidatorMock->expects($this->any())->method('isAllowed')->will($this->returnValue(true));

        $context = $helper->getObject(
            \Magento\Framework\Model\Context::class,
            [
                'actionValidator' => $actionValidatorMock,
            ]
        );

        $this->session = $helper->getObject(
            \Magento\Persistent\Model\Session::class,
            [
                'sessionConfig' => $this->configMock,
                'cookieManager' => $this->cookieManagerMock,
                'context'       => $context,
                'cookieMetadataFactory' => $this->cookieMetadataFactoryMock,
                'request' => $this->createMock(\Magento\Framework\App\Request\Http::class),
                'resource' => $resourceMock,
            ]
        );
    }

    public function testLoadByCookieKeyWithNull()
    {
        $this->cookieManagerMock->expects($this->once())
            ->method('getCookie')
            ->with(\Magento\Persistent\Model\Session::COOKIE_NAME)
            ->will($this->returnValue(null));
        $this->session->loadByCookieKey(null);
    }

    /**
     * @covers \Magento\Persistent\Model\Session::removePersistentCookie
     */
    public function testAfterDeleteCommit()
    {
        $cookiePath = 'some_path';
        $this->configMock->expects($this->once())->method('getCookiePath')->will($this->returnValue($cookiePath));
        $cookieMetadataMock = $this->getMockBuilder(\Magento\Framework\Stdlib\Cookie\SensitiveCookieMetadata::class)
            ->disableOriginalConstructor()
            ->getMock();
        $cookieMetadataMock->expects($this->once())
            ->method('setPath')
            ->with($cookiePath)
            ->will($this->returnSelf());
        $this->cookieMetadataFactoryMock->expects($this->once())
            ->method('createSensitiveCookieMetadata')
            ->will($this->returnValue($cookieMetadataMock));
        $this->cookieManagerMock->expects(
            $this->once()
        )->method(
            'deleteCookie'
        )->with(
            \Magento\Persistent\Model\Session::COOKIE_NAME,
            $cookieMetadataMock
        );
        $this->session->afterDeleteCommit();
    }

    public function testSetPersistentCookie()
    {
        $cookiePath = 'some_path';
        $duration = 1000;
        $key = 'sessionKey';
        $this->session->setKey($key);
        $cookieMetadataMock = $this->getMockBuilder(\Magento\Framework\Stdlib\Cookie\PublicCookieMetadata::class)
            ->disableOriginalConstructor()
            ->getMock();
        $cookieMetadataMock->expects($this->once())
            ->method('setPath')
            ->with($cookiePath)
            ->will($this->returnSelf());
        $cookieMetadataMock->expects($this->once())
            ->method('setDuration')
            ->with($duration)
            ->will($this->returnSelf());
        $cookieMetadataMock->expects($this->once())
            ->method('setSecure')
            ->with(false)
            ->will($this->returnSelf());
        $cookieMetadataMock->expects($this->once())
            ->method('setHttpOnly')
            ->with(true)
            ->will($this->returnSelf());
        $this->cookieMetadataFactoryMock->expects($this->once())
            ->method('createPublicCookieMetadata')
            ->will($this->returnValue($cookieMetadataMock));
        $this->cookieManagerMock->expects($this->once())
            ->method('setPublicCookie')
            ->with(
                \Magento\Persistent\Model\Session::COOKIE_NAME,
                $key,
                $cookieMetadataMock
            );
        $this->session->setPersistentCookie($duration, $cookiePath);
    }

    /**
     * @param $numGetCookieCalls
     * @param $numCalls
     * @param int $cookieDuration
     * @param string $cookieValue
     * @param string $cookiePath
     * @dataProvider renewPersistentCookieDataProvider
     */
    public function testRenewPersistentCookie(
        $numGetCookieCalls,
        $numCalls,
        $cookieDuration = 1000,
        $cookieValue = 'cookieValue',
        $cookiePath = 'cookiePath'
    ) {
        $cookieMetadataMock = $this->getMockBuilder(\Magento\Framework\Stdlib\Cookie\PublicCookieMetadata::class)
            ->disableOriginalConstructor()
            ->getMock();
        $cookieMetadataMock->expects($this->exactly($numCalls))
            ->method('setPath')
            ->with($cookiePath)
            ->will($this->returnSelf());
        $cookieMetadataMock->expects($this->exactly($numCalls))
            ->method('setDuration')
            ->with($cookieDuration)
            ->will($this->returnSelf());
        $cookieMetadataMock->expects($this->exactly($numCalls))
            ->method('setSecure')
            ->with(false)
            ->will($this->returnSelf());
        $cookieMetadataMock->expects($this->exactly($numCalls))
            ->method('setHttpOnly')
            ->with(true)
            ->will($this->returnSelf());
        $this->cookieMetadataFactoryMock->expects($this->exactly($numCalls))
            ->method('createPublicCookieMetadata')
            ->will($this->returnValue($cookieMetadataMock));
        $this->cookieManagerMock->expects($this->exactly($numGetCookieCalls))
            ->method('getCookie')
            ->with(\Magento\Persistent\Model\Session::COOKIE_NAME)
            ->will($this->returnValue($cookieValue));
        $this->cookieManagerMock->expects($this->exactly($numCalls))
            ->method('setPublicCookie')
            ->with(
                \Magento\Persistent\Model\Session::COOKIE_NAME,
                $cookieValue,
                $cookieMetadataMock
            );
        $this->session->renewPersistentCookie($cookieDuration, $cookiePath);
    }

    /**
     * Data provider for testRenewPersistentCookie
     *
     * @return array
     */
    public function renewPersistentCookieDataProvider()
    {
        return [
            'no duration' => [0, 0, null ],
            'no cookie' => [1, 0, 1000, null],
            'all' => [1, 1],
        ];
    }
}
