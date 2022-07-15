<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\ProductAlert\Test\Unit\Block\Product;

class ViewTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $block;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $postHelper;

    protected function setUp()
    {
        $objectManager = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);
        $this->postHelper = $this->createMock(\Magento\Framework\Data\Helper\PostHelper::class);
        $this->block = $objectManager->getObject(
            \Magento\ProductAlert\Block\Product\View::class,
            ['coreHelper' => $this->postHelper]
        );
    }

    public function testGetPostAction()
    {
        $this->block->setSignupUrl('someUrl');
        $this->postHelper->expects($this->once())
            ->method('getPostData')
            ->with('someUrl')
            ->will($this->returnValue('{parsedAction}'));
        $this->assertEquals('{parsedAction}', $this->block->getPostAction());
    }
}
