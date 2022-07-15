<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\PageCache\Test\Unit\Observer;

use Magento\Framework\App\PageCache\FormKey as CookieFormKey;
use Magento\Framework\Data\Form\FormKey as DataFormKey;
use Magento\PageCache\Observer\FlushFormKey;
use Magento\Framework\Event\Observer;

class FlushFormKeyTest extends \PHPUnit\Framework\TestCase
{
    /**
     * Test case for deleting the form_key cookie when observer executes
     */
    public function testExecute()
    {
        /** @var CookieFormKey | \PHPUnit_Framework_MockObject_MockObject $cookieFormKey */
        $cookieFormKey = $this->getMockBuilder(CookieFormKey::class)
            ->disableOriginalConstructor()
            ->getMock();

        /** @var DataFormKey | \PHPUnit_Framework_MockObject_MockObject $dataFormKey */
        $dataFormKey = $this->getMockBuilder(DataFormKey::class)
            ->disableOriginalConstructor()
            ->getMock();

        /** @var Observer | \PHPUnit_Framework_MockObject_MockObject $observerObject */
        $observerObject = $this->createMock(Observer::class);
        $observer = new FlushFormKey($cookieFormKey, $dataFormKey);

        $cookieFormKey->expects($this->once())
            ->method('delete');
        $dataFormKey->expects($this->once())
            ->method('set')
            ->with(null);
        $observer->execute($observerObject);
    }
}
