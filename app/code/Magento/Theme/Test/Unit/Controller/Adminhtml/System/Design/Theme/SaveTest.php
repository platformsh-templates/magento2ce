<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Theme\Test\Unit\Controller\Adminhtml\System\Design\Theme;

class SaveTest extends \Magento\Theme\Test\Unit\Controller\Adminhtml\System\Design\ThemeTest
{
    /**
     * @var string
     */
    protected $name = 'Save';

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testSaveAction()
    {
        $themeData = ['theme_id' => 123];
        $customCssContent = 'custom css content';
        $jsRemovedFiles = [3, 4];
        $jsOrder = [1 => '1', 2 => 'test'];

        $this->_request->expects($this->at(0))
            ->method('getParam')
            ->with('back', false)
            ->will($this->returnValue(true));

        $this->_request->expects($this->at(1))
            ->method('getParam')
            ->with('theme')
            ->will($this->returnValue($themeData));

        $this->_request->expects($this->at(2))
            ->method('getParam')
            ->with('custom_css_content')
            ->will($this->returnValue($customCssContent));

        $this->_request->expects($this->at(3))
            ->method('getParam')
            ->with('js_removed_files')
            ->will($this->returnValue($jsRemovedFiles));

        $this->_request->expects($this->at(4))
            ->method('getParam')
            ->with('js_order')
            ->will($this->returnValue($jsOrder));

        $this->_request->expects($this->once(5))->method('getPostValue')->will($this->returnValue(true));

        $themeMock = $this->createPartialMock(
            \Magento\Theme\Model\Theme::class,
            ['save', 'load', 'setCustomization', 'getThemeImage', '__wakeup']
        );

        $themeImage = $this->createMock(\Magento\Theme\Model\Theme\Data::class);
        $themeMock->expects($this->any())->method('getThemeImage')->will($this->returnValue($themeImage));

        $themeFactory = $this->createPartialMock(
            \Magento\Framework\View\Design\Theme\FlyweightFactory::class,
            ['create']
        );
        $themeFactory->expects($this->once())->method('create')->will($this->returnValue($themeMock));

        $this->_objectManagerMock->expects($this->at(0))
            ->method('get')
            ->with(\Magento\Framework\View\Design\Theme\FlyweightFactory::class)
            ->will($this->returnValue($themeFactory));

        $this->_objectManagerMock->expects($this->at(1))
            ->method('get')
            ->with(\Magento\Theme\Model\Theme\Customization\File\CustomCss::class)
            ->will($this->returnValue(null));

        $this->_objectManagerMock->expects($this->at(2))
            ->method('create')
            ->with(\Magento\Theme\Model\Theme\SingleFile::class)
            ->will($this->returnValue(null));

        $this->_model->execute();
    }
}
