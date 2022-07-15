<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Authorizenet\Test\Unit\Model\Directpost;

use Magento\Authorizenet\Model\Directpost\Session;
use Magento\Framework\Session\StorageInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;

class SessionTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var ObjectManager
     */
    protected $objectManager;

    /**
     * @var Session
     */
    protected $session;

    /**
     * @var StorageInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $storageMock;

    protected function setUp()
    {
        $this->storageMock = $this
            ->getMockBuilder(\Magento\Framework\Session\StorageInterface::class)
            ->setMethods(['setQuoteId'])
            ->getMockForAbstractClass();

        $this->objectManager = new ObjectManager($this);
        $this->session = $this->objectManager->getObject(
            \Magento\Authorizenet\Model\Directpost\Session::class,
            [
                'storage' => $this->storageMock,
            ]
        );
    }

    public function testSetQuoteId()
    {
        $quoteId = 1;

        $this->storageMock->expects($this->once())
            ->method('setQuoteId')
            ->with($quoteId);

        $this->assertInstanceOf(
            \Magento\Authorizenet\Model\Directpost\Session::class,
            $this->session->setQuoteId($quoteId)
        );
    }
}
