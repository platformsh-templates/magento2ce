<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\GiftMessage\Test\Unit\Block\Cart;

use Magento\GiftMessage\Block\Cart\GiftOptions;

class GiftOptionsTest extends \PHPUnit\Framework\TestCase
{
    /** @var \Magento\Backend\Block\Template\Context|\PHPUnit_Framework_MockObject_MockObject */
    protected $context;

    /** @var \Magento\GiftMessage\Model\CompositeConfigProvider|\PHPUnit_Framework_MockObject_MockObject */
    protected $compositeConfigProvider;

    /** @var \Magento\Checkout\Model\CompositeConfigProvider|\PHPUnit_Framework_MockObject_MockObject */
    protected $layoutProcessorMock;

    /** @var \Magento\GiftMessage\Block\Cart\GiftOptions */
    protected $model;

    /** @var \Magento\Framework\Json\Encoder|\PHPUnit_Framework_MockObject_MockObject */
    protected $jsonEncoderMock;

    /** @var array  */
    protected $jsLayout = ['root' => 'node'];

    protected function setUp()
    {
        $this->context = $this->createMock(\Magento\Backend\Block\Template\Context::class);
        $this->jsonEncoderMock = $this->createMock(\Magento\Framework\Json\Encoder::class);
        $this->compositeConfigProvider = $this->createMock(\Magento\GiftMessage\Model\CompositeConfigProvider::class);
        $this->layoutProcessorMock = $this->getMockForAbstractClass(
            \Magento\Checkout\Block\Checkout\LayoutProcessorInterface::class,
            [],
            '',
            false
        );
        $this->model = new GiftOptions(
            $this->context,
            $this->jsonEncoderMock,
            $this->compositeConfigProvider,
            [$this->layoutProcessorMock],
            ['jsLayout' => $this->jsLayout]
        );
    }

    public function testGetJsLayout()
    {
        $this->layoutProcessorMock->expects($this->once())
            ->method('process')
            ->with($this->jsLayout)
            ->willReturnArgument(0);
        $this->jsonEncoderMock->expects($this->once())
            ->method('encode')
            ->with($this->jsLayout)
            ->willReturnArgument(0);
        $this->assertEquals($this->jsLayout, $this->model->getJsLayout());
    }

    public function testGetGiftOptionsConfigJson()
    {
        $this->compositeConfigProvider->expects($this->once())
            ->method('getConfig')
            ->willReturn($this->jsLayout);
        $this->jsonEncoderMock->expects($this->once())
            ->method('encode')
            ->with($this->jsLayout)
            ->willReturnArgument(0);
        $this->assertEquals($this->jsLayout, $this->model->getGiftOptionsConfigJson());
    }
}
