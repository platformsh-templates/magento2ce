<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Catalog\Test\Unit\Block\Adminhtml\Rss\Grid;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;

/**
 * Class LinkTest
 * @package Magento\Catalog\Block\Adminhtml\Rss\Grid
 */
class LinkTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\Catalog\Block\Adminhtml\Rss\Grid\Link
     */
    protected $link;

    /**
     * @var ObjectManagerHelper
     */
    protected $objectManagerHelper;

    /**
     * @var \Magento\Framework\App\Rss\UrlBuilderInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $urlBuilderInterface;

    protected function setUp()
    {
        $this->urlBuilderInterface = $this->createMock(\Magento\Framework\App\Rss\UrlBuilderInterface::class);

        $this->objectManagerHelper = new ObjectManagerHelper($this);
        $this->link = $this->objectManagerHelper->getObject(
            \Magento\Catalog\Block\Adminhtml\Rss\Grid\Link::class,
            [
                'rssUrlBuilder' => $this->urlBuilderInterface
            ]
        );
    }

    public function testGetLink()
    {
        $rssUrl = 'http://rss.magento.com';
        $this->urlBuilderInterface->expects($this->once())->method('getUrl')->will($this->returnValue($rssUrl));
        $this->assertEquals($rssUrl, $this->link->getLink());
    }

    public function testGetLabel()
    {
        $this->assertEquals('Notify Low Stock RSS', $this->link->getLabel());
    }

    public function testIsRssAllowed()
    {
        $this->assertEquals(true, $this->link->isRssAllowed());
    }
}
