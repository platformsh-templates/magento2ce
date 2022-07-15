<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\SendFriend\Test\Unit\Model;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;

/**
 * Test SendFriend
 *
 */
class SendFriendTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\SendFriend\Model\SendFriend
     */
    protected $model;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Magento\Framework\Stdlib\CookieManagerInterface
     */
    protected $cookieManagerMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $sendfriendDataMock;

    protected function setUp()
    {
        $objectManager = new ObjectManager($this);
        $this->sendfriendDataMock = $this->getMockBuilder(\Magento\SendFriend\Helper\Data::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->cookieManagerMock = $this->createMock(\Magento\Framework\Stdlib\CookieManagerInterface::class);

        $this->model = $objectManager->getObject(
            \Magento\SendFriend\Model\SendFriend::class,
            [
                'sendfriendData' => $this->sendfriendDataMock,
                'cookieManager' => $this->cookieManagerMock,
            ]
        );
    }

    public function testGetSentCountWithCheckCookie()
    {
        $cookieName = 'testCookieName';
        $this->sendfriendDataMock->expects($this->once())->method('getLimitBy')->with()->will(
            $this->returnValue(\Magento\SendFriend\Helper\Data::CHECK_COOKIE)
        );
        $this->sendfriendDataMock->expects($this->once())->method('getCookieName')->with()->will(
            $this->returnValue($cookieName)
        );

        $this->cookieManagerMock->expects($this->once())->method('getCookie')->with($cookieName);
        $this->assertEquals(0, $this->model->getSentCount());
    }

    public function testSentCountByCookies()
    {
        $cookieName = 'testCookieName';
        $this->sendfriendDataMock->expects($this->once())->method('getCookieName')->with()->will(
            $this->returnValue($cookieName)
        );

        $this->cookieManagerMock->expects($this->once())->method('getCookie')->with($cookieName);
        $this->cookieManagerMock->expects($this->once())->method('setSensitiveCookie');
        $sendFriendClass = new \ReflectionClass(\Magento\SendFriend\Model\SendFriend::class);
        $method = $sendFriendClass->getMethod('_sentCountByCookies');
        $method->setAccessible(true);
        $method->invokeArgs($this->model, [true]);
    }
}
