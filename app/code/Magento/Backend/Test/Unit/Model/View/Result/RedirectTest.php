<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Backend\Test\Unit\Model\View\Result;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;

class RedirectTest extends \PHPUnit\Framework\TestCase
{
    /** @var \Magento\Backend\Model\View\Result\Redirect */
    protected $action;

    /** @var ObjectManagerHelper */
    protected $objectManagerHelper;

    /** @var \Magento\Backend\Model\Session|\PHPUnit_Framework_MockObject_MockObject */
    protected $session;

    /** @var \Magento\Framework\App\ActionFlag|\PHPUnit_Framework_MockObject_MockObject */
    protected $actionFlag;

    /** @var \Magento\Backend\Model\UrlInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $urlBuilder;

    /** @var \Magento\Framework\App\Response\RedirectInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $redirect;

    protected $url = 'adminhtml/index';

    protected function setUp()
    {
        $this->session = $this->createMock(\Magento\Backend\Model\Session::class);
        $this->actionFlag = $this->createMock(\Magento\Framework\App\ActionFlag::class);
        $this->urlBuilder = $this->createMock(\Magento\Backend\Model\UrlInterface::class);
        $this->redirect = $this->createMock(\Magento\Framework\App\Response\RedirectInterface::class);
        $this->objectManagerHelper = new ObjectManagerHelper($this);
        $this->action = $this->objectManagerHelper->getObject(
            \Magento\Backend\Model\View\Result\Redirect::class,
            [
                'session' => $this->session,
                'actionFlag' => $this->actionFlag,
                'redirect' => $this->redirect,
                'urlBuilder' =>$this->urlBuilder,
            ]
        );
    }

    public function testSetRefererOrBaseUrl()
    {
        $this->urlBuilder->expects($this->once())->method('getUrl')->willReturn($this->url);
        $this->redirect->expects($this->once())->method('getRedirectUrl')->with($this->url)->willReturn('test string');
        $this->action->setRefererOrBaseUrl();
    }
}
