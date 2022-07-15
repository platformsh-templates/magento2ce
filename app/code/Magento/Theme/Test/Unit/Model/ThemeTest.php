<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

/**
 * Test theme model
 */
namespace Magento\Theme\Test\Unit\Model;

use Magento\Framework\App\State;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\View\Design\Theme\CustomizationInterface;
use Magento\Framework\View\Design\Theme\Domain\Factory;
use Magento\Framework\View\Design\Theme\FlyweightFactory;
use Magento\Framework\View\Design\Theme\ImageFactory;
use Magento\Framework\View\Design\Theme\Validator;
use Magento\Framework\View\Design\ThemeInterface;
use Magento\Theme\Model\Config\Customization;
use Magento\Theme\Model\ResourceModel\Theme\Collection;
use Magento\Theme\Model\Theme;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ThemeTest extends TestCase
{
    /**
     * @var Theme|MockObject
     */
    protected $_model;

    /**
     * @var MockObject
     */
    protected $_imageFactory;

    /**
     * @var MockObject|FlyweightFactory
     */
    protected $themeFactory;

    /**
     * @var MockObject|Collection
     */
    protected $resourceCollection;

    /**
     * @var MockObject|Factory
     */
    protected $domainFactory;

    /**
     * @var MockObject|Validator
     */
    protected $validator;

    /**
     * @var MockObject|\Magento\Framework\View\Design\Theme\CustomizationFactory
     */
    protected $customizationFactory;

    /**
     * @var MockObject|State
     */
    protected $appState;

    /**
     * @var MockObject|\Magento\Theme\Model\ThemeFactory
     */
    private $themeModelFactory;

    protected function setUp(): void
    {
        $customizationConfig = $this->createMock(Customization::class);
        $this->customizationFactory = $this->createPartialMock(
            \Magento\Framework\View\Design\Theme\CustomizationFactory::class,
            ['create']
        );
        $this->resourceCollection = $this->createMock(Collection::class);
        $this->_imageFactory = $this->createPartialMock(
            ImageFactory::class,
            ['create']
        );
        $this->themeFactory = $this->createPartialMock(
            FlyweightFactory::class,
            ['create']
        );
        $this->domainFactory = $this->createPartialMock(
            Factory::class,
            ['create']
        );
        $this->themeModelFactory = $this->createPartialMock(\Magento\Theme\Model\ThemeFactory::class, ['create']);
        $this->validator = $this->createMock(Validator::class);
        $this->appState = $this->createMock(State::class);

        $objectManagerHelper = new ObjectManager($this);
        $arguments = $objectManagerHelper->getConstructArguments(
            Theme::class,
            [
                'customizationFactory' => $this->customizationFactory,
                'customizationConfig' => $customizationConfig,
                'imageFactory' => $this->_imageFactory,
                'resourceCollection' => $this->resourceCollection,
                'themeFactory' => $this->themeFactory,
                'domainFactory' => $this->domainFactory,
                'validator' => $this->validator,
                'appState' => $this->appState,
                'themeModelFactory' => $this->themeModelFactory
            ]
        );

        $this->_model = $objectManagerHelper->getObject(Theme::class, $arguments);
    }

    protected function tearDown(): void
    {
        $this->_model = null;
    }

    /**
     * @covers \Magento\Theme\Model\Theme::getThemeImage
     */
    public function testThemeImageGetter()
    {
        $this->_imageFactory->expects($this->once())->method('create')->with(['theme' => $this->_model]);
        $this->_model->getThemeImage();
    }

    /**
     * @dataProvider isVirtualDataProvider
     * @param int $type
     * @param string $isVirtual
     * @covers \Magento\Theme\Model\Theme::isVirtual
     */
    public function testIsVirtual($type, $isVirtual)
    {
        $this->_model->setType($type);
        $this->assertEquals($isVirtual, $this->_model->isVirtual());
    }

    /**
     * @return array
     */
    public function isVirtualDataProvider()
    {
        return [
            ['type' => ThemeInterface::TYPE_VIRTUAL, 'isVirtual' => true],
            ['type' => ThemeInterface::TYPE_STAGING, 'isVirtual' => false],
            ['type' => ThemeInterface::TYPE_PHYSICAL, 'isVirtual' => false]
        ];
    }

    /**
     * @dataProvider isPhysicalDataProvider
     * @param int $type
     * @param string $isPhysical
     * @covers \Magento\Theme\Model\Theme::isPhysical
     */
    public function testIsPhysical($type, $isPhysical)
    {
        $this->_model->setType($type);
        $this->assertEquals($isPhysical, $this->_model->isPhysical());
    }

    /**
     * @return array
     */
    public function isPhysicalDataProvider()
    {
        return [
            ['type' => ThemeInterface::TYPE_VIRTUAL, 'isPhysical' => false],
            ['type' => ThemeInterface::TYPE_STAGING, 'isPhysical' => false],
            ['type' => ThemeInterface::TYPE_PHYSICAL, 'isPhysical' => true]
        ];
    }

    /**
     * @dataProvider isVisibleDataProvider
     * @param int $type
     * @param string $isVisible
     * @covers \Magento\Theme\Model\Theme::isVisible
     */
    public function testIsVisible($type, $isVisible)
    {
        $this->_model->setType($type);
        $this->assertEquals($isVisible, $this->_model->isVisible());
    }

    /**
     * @return array
     */
    public function isVisibleDataProvider()
    {
        return [
            ['type' => ThemeInterface::TYPE_VIRTUAL, 'isVisible' => true],
            ['type' => ThemeInterface::TYPE_STAGING, 'isVisible' => false],
            ['type' => ThemeInterface::TYPE_PHYSICAL, 'isVisible' => true]
        ];
    }

    /**
     * Test id deletable
     *
     * @dataProvider isDeletableDataProvider
     * @param string $themeType
     * @param bool $isDeletable
     * @covers \Magento\Theme\Model\Theme::isDeletable
     */
    public function testIsDeletable($themeType, $isDeletable)
    {
        $themeModel = $this->getMockBuilder(Theme::class)
            ->addMethods(['getType'])
            ->disableOriginalConstructor()
            ->getMock();
        $themeModel->expects($this->once())->method('getType')->willReturn($themeType);
        /** @var \Magento\Theme\Model\Theme $themeModel */
        $this->assertEquals($isDeletable, $themeModel->isDeletable());
    }

    /**
     * @return array
     */
    public function isDeletableDataProvider()
    {
        return [
            [ThemeInterface::TYPE_VIRTUAL, true],
            [ThemeInterface::TYPE_STAGING, true],
            [ThemeInterface::TYPE_PHYSICAL, false]
        ];
    }

    /**
     * @param mixed $originalCode
     * @param string $expectedCode
     * @dataProvider getCodeDataProvider
     */
    public function testGetCode($originalCode, $expectedCode)
    {
        $this->_model->setCode($originalCode);
        $this->assertSame($expectedCode, $this->_model->getCode());
    }

    /**
     * @return array
     */
    public function getCodeDataProvider()
    {
        return [
            'string code' => ['theme/code', 'theme/code'],
            'null code' => [null, ''],
            'number code' => [10, '10']
        ];
    }

    /**
     * @test
     * @return void
     */
    public function testGetInheritedThemes()
    {
        $inheritedTheme = $this->getMockBuilder(ThemeInterface::class)
            ->getMock();

        $this->_model->setParentId(10);
        $this->themeFactory->expects($this->once())
            ->method('create')
            ->with(10)
            ->willReturn($inheritedTheme);

        $this->assertContainsOnlyInstancesOf(
            ThemeInterface::class,
            $this->_model->getInheritedThemes()
        );
        $this->assertCount(2, $this->_model->getInheritedThemes());
    }

    /**
     * @test
     * @return void
     */
    public function testAfterDelete()
    {
        $expectId = 101;
        $theme = $this->getMockBuilder(ThemeInterface::class)
            ->setMethods(['delete', 'getId'])
            ->getMockForAbstractClass();
        $theme->expects($this->once())
            ->method('getId')
            ->willReturn($expectId);
        $theme->expects($this->once())
            ->method('delete')
            ->willReturnSelf();

        $this->_model->setId(1);
        $this->resourceCollection->expects($this->at(0))
            ->method('addFieldToFilter')
            ->with('parent_id', 1)
            ->willReturnSelf();
        $this->resourceCollection->expects($this->at(1))
            ->method('addFieldToFilter')
            ->with('type', Theme::TYPE_STAGING)
            ->willReturnSelf();
        $this->resourceCollection->expects($this->once())
            ->method('getFirstItem')
            ->willReturn($theme);
        $this->resourceCollection->expects($this->once())
            ->method('updateChildRelations')
            ->with($this->_model);

        $this->assertInstanceOf(get_class($this->_model), $this->_model->afterDelete());
    }

    /**
     * @test
     * @return void
     */
    public function testGetStagingVersion()
    {
        $theme = $this->getMockBuilder(ThemeInterface::class)
            ->setMethods(['getId'])
            ->getMockForAbstractClass();
        $theme->expects($this->once())
            ->method('getId')
            ->willReturn(null);

        $this->_model->setId(1);
        $this->resourceCollection->expects($this->at(0))
            ->method('addFieldToFilter')
            ->with('parent_id', 1)
            ->willReturnSelf();
        $this->resourceCollection->expects($this->at(1))
            ->method('addFieldToFilter')
            ->with('type', Theme::TYPE_STAGING)
            ->willReturnSelf();
        $this->resourceCollection->expects($this->once())
            ->method('getFirstItem')
            ->willReturn($theme);

        $this->assertNull($this->_model->getStagingVersion());
    }

    /**
     * @test
     * @return void
     */
    public function testGetStagingVersionWithoutTheme()
    {
        $this->assertNull($this->_model->getStagingVersion());
    }

    /**
     * @test
     * @return void
     */
    public function testGetDomainModel()
    {
        $result = 'res';
        $this->domainFactory->expects($this->once())
            ->method('create')
            ->with($this->_model)
            ->willReturn($result);
        $this->assertEquals($result, $this->_model->getDomainModel());
    }

    /**
     * @test
     * @return void
     */
    public function testGetDomainModelWithIncorrectType()
    {
        $this->expectException('InvalidArgumentException');
        $this->_model->getDomainModel('bla-bla-bla');
    }

    /**
     * @test
     * @return void
     */
    public function testValidate()
    {
        $this->expectException('Magento\Framework\Exception\LocalizedException');
        $this->expectExceptionMessage('testMessage');
        $this->validator->expects($this->once())
            ->method('validate')
            ->with($this->_model)
            ->willReturn(false);
        $this->validator->expects($this->once())
            ->method('getErrorMessages')
            ->willReturn([[__('testMessage')]]);
        $this->assertInstanceOf(get_class($this->_model), $this->_model->beforeSave());
    }

    /**
     * @test
     * @return void
     */
    public function testValidatePass()
    {
        $this->validator->expects($this->once())
            ->method('validate')
            ->with($this->_model)
            ->willReturn(true);
        $this->assertInstanceOf(get_class($this->_model), $this->_model->beforeSave());
    }

    /**
     * @test
     * @return void
     */
    public function testHasChildThemes()
    {
        $this->_model->setId(1);
        $this->resourceCollection->expects($this->once())
            ->method('addTypeFilter')
            ->with(Theme::TYPE_VIRTUAL)
            ->willReturnSelf();
        $this->resourceCollection->expects($this->once())
            ->method('addFieldToFilter')
            ->with('parent_id', ['eq' => 1])
            ->willReturnSelf();
        $this->resourceCollection->expects($this->once())
            ->method('getSize')
            ->willReturn(10);
        $this->assertTrue($this->_model->hasChildThemes());
    }

    /**
     * @test
     * @return void
     */
    public function testGetCustomization()
    {
        $this->customizationFactory->expects($this->once())
            ->method('create')
            ->willReturn(
                $this->getMockBuilder(CustomizationInterface::class)
                    ->getMock()
            );
        $this->assertInstanceOf(
            CustomizationInterface::class,
            $this->_model->getCustomization()
        );
    }

    /**
     * @test
     * @return void
     */
    public function testIsEditable()
    {
        $this->_model->setType(Theme::TYPE_VIRTUAL);
        $this->assertTrue($this->_model->isEditable());
        $this->_model->setType(Theme::TYPE_PHYSICAL);
        $this->assertFalse($this->_model->isEditable());
    }

    /**
     * @test
     * @return void
     */
    public function getFullThemePath()
    {
        $areaCode = 'frontend';
        $this->appState->expects($this->once())
            ->method('getAreaCode')
            ->willReturn($areaCode);

        $path = 'some/path';
        $this->_model->setThemePath($path);

        $this->assertEquals($areaCode . Theme::PATH_SEPARATOR . $path, $this->_model->getFullPath());
    }

    /**
     * @test
     * @return void
     */
    public function getParentTheme()
    {
        $this->_model->setParentTheme('parent_theme');
        $this->assertEquals('parent_theme', $this->_model->getParentTheme());
    }

    /**
     * @param array $themeData
     * @param array $expected
     * @dataProvider toArrayDataProvider
     */
    public function testToArray(array $themeData, array $expected)
    {
        $this->_model->setData($themeData);
        $this->assertEquals($expected, $this->_model->toArray());
    }

    /**
     * @return array
     */
    public function toArrayDataProvider()
    {
        $parentTheme = $this->getMockBuilder(Theme::class)
            ->disableOriginalConstructor()
            ->getMock();
        $childTheme = clone $parentTheme;

        $parentTheme->expects($this->once())
            ->method('toArray')
            ->willReturn('parent_theme');

        $childTheme->expects($this->exactly(2))
            ->method('toArray')
            ->willReturn('child_theme');

        return [
            'null' => [[], []],
            'valid' => [
                ['theme_data' => 'theme_data'],
                ['theme_data' => 'theme_data']
            ],
            'valid with parent' => [
                [
                    'theme_data' => 'theme_data',
                    'parent_theme' => $parentTheme
                ],
                [
                    'theme_data' => 'theme_data',
                    'parent_theme' => 'parent_theme'
                ]
            ],
            'valid with children' => [
                [
                    'theme_data' => 'theme_data',
                    'inherited_themes' => [
                        'key1' => $childTheme,
                        'key2' => $childTheme
                    ]
                ],
                [
                    'theme_data' => 'theme_data',
                    'inherited_themes' => [
                        'key1' => 'child_theme',
                        'key2' => 'child_theme'
                    ]
                ]
            ]
        ];
    }

    /**
     * @param array $value
     * @param array $expected
     * @param int $expectedCallCount
     *
     * @dataProvider populateFromArrayDataProvider
     */
    public function testPopulateFromArray(array $value, array $expected, $expectedCallCount = 0)
    {
        $themeMock = $this->getMockBuilder(Theme::class)
            ->disableOriginalConstructor()
            ->getMock();
        $themeMock->expects($this->exactly($expectedCallCount))
            ->method('populateFromArray')
            ->willReturn('theme_instance');

        $this->themeModelFactory->expects($this->exactly($expectedCallCount))
            ->method('create')
            ->willReturn($themeMock);

        $this->_model->populateFromArray($value);
        $this->assertEquals($expected, $this->_model->getData());
    }

    /**
     * @return array
     */
    public function populateFromArrayDataProvider()
    {
        return [
            'valid data' => [
                'value' => ['theme_data' => 'theme_data'],
                'expected' => ['theme_data' => 'theme_data']
            ],
            'valid data with parent' => [
                'value' => [
                    'theme_data' => 'theme_data',
                    'parent_theme' => [
                        'theme_data' => 'theme_data'
                    ]
                ],
                'expected' => [
                    'theme_data' => 'theme_data',
                    'parent_theme' => 'theme_instance'
                ],
                'expected call count' => 1
            ],
            'valid data with children' => [
                'value' => [
                    'theme_data' => 'theme_data',
                    'inherited_themes' => [
                        'key1' => ['theme_data' => 'theme_data'],
                        'key2' => ['theme_data' => 'theme_data']
                    ]
                ],
                'expected' => [
                    'theme_data' => 'theme_data',
                    'inherited_themes' => [
                        'key1' => 'theme_instance',
                        'key2' => 'theme_instance'
                    ]
                ],
                'expected call count' => 2
            ]
        ];
    }
}
