<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Swatches\Test\Unit\Model\Plugin;

use Magento\Swatches\Model\Plugin\FilterRenderer;

class FilterRendererTest extends \PHPUnit\Framework\TestCase
{
    /** @var FilterRenderer|\Magento\Framework\TestFramework\Unit\Helper\ObjectManager */
    protected $plugin;

    /** @var \PHPUnit_Framework_MockObject_MockObject|\Magento\Swatches\Helper\Data */
    protected $swatchHelperMock;

    /** @var \PHPUnit_Framework_MockObject_MockObject|\Magento\Framework\View\Layout */
    protected $layoutMock;

    /** @var \PHPUnit_Framework_MockObject_MockObject|\Magento\Catalog\Model\Layer\Filter\AbstractFilter */
    protected $filterMock;

    /** @var \PHPUnit_Framework_MockObject_MockObject|\Magento\LayeredNavigation\Block\Navigation\FilterRenderer */
    protected $filterRendererMock;

    /** @var \PHPUnit_Framework_MockObject_MockObject|\Magento\Swatches\Block\LayeredNavigation\RenderLayered */
    protected $blockMock;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $closureMock;

    protected function setUp()
    {
        $this->layoutMock = $this->createPartialMock(\Magento\Framework\View\Layout::class, ['createBlock']);

        $this->swatchHelperMock = $this->createPartialMock(\Magento\Swatches\Helper\Data::class, ['isSwatchAttribute']);

        $this->blockMock = $this->createPartialMock(
            \Magento\Swatches\Block\LayeredNavigation\RenderLayered::class,
            ['setSwatchFilter', 'toHtml']
        );

        $this->filterMock = $this->createPartialMock(
            \Magento\Catalog\Model\Layer\Filter\AbstractFilter::class,
            ['getAttributeModel', 'hasAttributeModel']
        );

        $this->filterRendererMock = $this->createMock(
            \Magento\LayeredNavigation\Block\Navigation\FilterRenderer::class
        );

        $this->closureMock = function () {
            return $this->filterMock;
        };

        $objectManager = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);
        $this->plugin = $objectManager->getObject(
            \Magento\Swatches\Model\Plugin\FilterRenderer::class,
            [
                'layout' => $this->layoutMock,
                'swatchHelper' => $this->swatchHelperMock
            ]
        );
    }

    public function testAroundRenderTrue()
    {
        $attributeMock = $this->createMock(\Magento\Catalog\Model\ResourceModel\Eav\Attribute::class);
        $this->filterMock->expects($this->atLeastOnce())->method('getAttributeModel')->willReturn($attributeMock);
        $this->filterMock->expects($this->once())->method('hasAttributeModel')->willReturn(true);
        $this->swatchHelperMock
            ->expects($this->once())
            ->method('isSwatchAttribute')
            ->with($attributeMock)
            ->willReturn(true);

        $this->layoutMock->expects($this->once())->method('createBlock')->willReturn($this->blockMock);
        $this->blockMock->expects($this->once())->method('setSwatchFilter')->will($this->returnSelf());

        $this->plugin->aroundRender($this->filterRendererMock, $this->closureMock, $this->filterMock);
    }

    public function testAroundRenderFalse()
    {
        $attributeMock = $this->createMock(\Magento\Catalog\Model\ResourceModel\Eav\Attribute::class);
        $this->filterMock->expects($this->atLeastOnce())->method('getAttributeModel')->willReturn($attributeMock);
        $this->filterMock->expects($this->once())->method('hasAttributeModel')->willReturn(true);
        $this->swatchHelperMock
            ->expects($this->once())
            ->method('isSwatchAttribute')
            ->with($attributeMock)
            ->willReturn(false);

        $result = $this->plugin->aroundRender($this->filterRendererMock, $this->closureMock, $this->filterMock);
        $this->assertEquals($result, $this->filterMock);
    }
}
