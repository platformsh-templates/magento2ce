<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Config\Test\Unit\Block\System\Config\Form;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class FieldsetTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\Config\Block\System\Config\Form\Fieldset
     */
    protected $_object;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $_elementMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $_requestMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $_urlModelMock;

    /**
     * @var array
     */
    protected $testData = [
        'htmlId' => 'test_field_id',
        'name' => 'test_name',
        'label' => 'test_label',
        'elementHTML' => 'test_html',
        'legend' => 'test_legend',
        'comment' => 'test_comment',
        'tooltip'     => 'test_tooltip',
    ];

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $_layoutMock;

    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $_testHelper;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $_helperMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $authSessionMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $userMock;

    /**
     * @return void
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    protected function setUp()
    {
        $this->authSessionMock = $this->getMockBuilder(\Magento\Backend\Model\Auth\Session::class)
            ->setMethods(['getUser'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->userMock = $this->getMockBuilder(\Magento\User\Model\User::class)
            ->setMethods(['getExtra'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->authSessionMock->expects($this->any())
            ->method('getUser')
            ->willReturn($this->userMock);

        $this->_requestMock = $this->getMockBuilder(\Magento\Framework\App\RequestInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->_requestMock->expects($this->any())
            ->method('getParam')
            ->willReturn('Test Param');
        $this->_urlModelMock = $this->createMock(\Magento\Backend\Model\Url::class);
        $this->_layoutMock = $this->createMock(\Magento\Framework\View\Layout::class);
        $groupMock = $this->createMock(\Magento\Config\Model\Config\Structure\Element\Group::class);
        $groupMock->expects($this->any())->method('getFieldsetCss')->will($this->returnValue('test_fieldset_css'));

        $this->_helperMock = $this->createMock(\Magento\Framework\View\Helper\Js::class);

        $data = [
            'request' => $this->_requestMock,
            'authSession' => $this->authSessionMock,
            'urlBuilder' => $this->_urlModelMock,
            'layout' => $this->_layoutMock,
            'jsHelper' => $this->_helperMock,
            'data' => ['group' => $groupMock],
        ];
        $this->_testHelper = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);
        $this->_object = $this->_testHelper->getObject(\Magento\Config\Block\System\Config\Form\Fieldset::class, $data);

        $this->_elementMock = $this->createPartialMock(
            \Magento\Framework\Data\Form\Element\Text::class,
            [
                'getId',
                'getHtmlId',
                'getName',
                'getElements',
                'getLegend',
                'getComment',
                'getIsNested',
                'getExpanded',
                'getForm'
            ]
        );

        $this->_elementMock->expects($this->any())
            ->method('getId')
            ->will($this->returnValue($this->testData['htmlId']));
        $this->_elementMock->expects($this->any())
            ->method('getHtmlId')
            ->will($this->returnValue($this->testData['htmlId']));
        $this->_elementMock->expects($this->any())
            ->method('getName')
            ->will($this->returnValue($this->testData['name']));
        $this->_elementMock->expects($this->any())
            ->method('getLegend')
            ->will($this->returnValue($this->testData['legend']));
        $this->_elementMock->expects($this->any())
            ->method('getComment')
            ->will($this->returnValue($this->testData['comment']));
    }

    /**
     * @param $expanded
     * @param $nested
     * @param extra
     * @dataProvider renderWithoutStoredElementsDataProvider
     */
    public function testRenderWithoutStoredElements($expanded, $nested, $extra)
    {
        $this->userMock->expects($this->any())->method('getExtra')->willReturn($extra);
        $collection = $this->_testHelper->getObject(\Magento\Framework\Data\Form\Element\Collection::class);
        $formMock = $this->createMock(\Magento\Framework\Data\Form::class);
        $this->_elementMock->expects($this->any())->method('getForm')->willReturn($formMock);
        $formMock->expects($this->any())->method('getElements')->willReturn($collection);
        $this->_elementMock->expects($this->any())->method('getElements')->will($this->returnValue($collection));
        $this->_elementMock->expects($this->any())->method('getIsNested')->will($this->returnValue($nested));
        $this->_elementMock->expects($this->any())->method('getExpanded')->will($this->returnValue($expanded));
        $actualHtml = $this->_object->render($this->_elementMock);
        $this->assertContains($this->testData['htmlId'], $actualHtml);
        $this->assertContains($this->testData['legend'], $actualHtml);
        $this->assertContains($this->testData['comment'], $actualHtml);
        if ($nested) {
            $this->assertContains('nested', $actualHtml);
        }
    }

    /**
     * @param $expanded
     * @param $nested
     * @param $extra
     * @dataProvider renderWithStoredElementsDataProvider
     */
    public function testRenderWithStoredElements($expanded, $nested, $extra)
    {
        $this->userMock->expects($this->any())->method('getExtra')->willReturn($extra);
        $this->_helperMock->expects($this->any())->method('getScript')->will($this->returnArgument(0));
        $fieldMock = $this->getMockBuilder(\Magento\Framework\Data\Form\Element\Text::class)
            ->setMethods(['getId', 'getTooltip', 'toHtml', 'getHtmlId', 'getIsNested', 'getExpanded'])
            ->disableOriginalConstructor()
            ->getMock();
        $fieldMock->expects($this->any())->method('getId')->will($this->returnValue('test_field_id'));
        $fieldMock->expects($this->any())->method('getTooltip')->will($this->returnValue('test_field_tootip'));
        $fieldMock->expects($this->any())->method('toHtml')->will($this->returnValue('test_field_toHTML'));
        $fieldMock->expects($this->any())->method('getHtmlId')->willReturn('test_field_HTML_id');

        $fieldSetMock = $this->getMockBuilder(\Magento\Framework\Data\Form\Element\Fieldset::class)
            ->setMethods(['getId', 'getTooltip', 'toHtml', 'getHtmlId', 'getIsNested', 'getExpanded'])
            ->disableOriginalConstructor()
            ->getMock();
        $fieldSetMock->expects($this->any())->method('getId')->will($this->returnValue('test_fieldset_id'));
        $fieldSetMock->expects($this->any())->method('getTooltip')->will($this->returnValue('test_fieldset_tootip'));
        $fieldSetMock->expects($this->any())->method('toHtml')->will($this->returnValue('test_fieldset_toHTML'));
        $fieldSetMock->expects($this->any())->method('getHtmlId')->willReturn('test_fieldset_HTML_id');

        $factory = $this->createMock(\Magento\Framework\Data\Form\Element\Factory::class);

        $factoryColl = $this->createMock(\Magento\Framework\Data\Form\Element\CollectionFactory::class);

        $formMock = $this->getMockBuilder(\Magento\Framework\Data\Form\AbstractForm::class)
            ->setConstructorArgs([$factory, $factoryColl])
            ->getMock();

        $collection = $this->_testHelper->getObject(
            \Magento\Framework\Data\Form\Element\Collection::class,
            ['container' => $formMock]
        );
        $collection->add($fieldMock);
        $collection->add($fieldSetMock);
        $formMock = $this->createMock(\Magento\Framework\Data\Form::class);
        $this->_elementMock->expects($this->any())->method('getForm')->willReturn($formMock);
        $formMock->expects($this->any())->method('getElements')->willReturn($collection);
        $this->_elementMock->expects($this->any())->method('getElements')->will($this->returnValue($collection));
        $this->_elementMock->expects($this->any())->method('getIsNested')->will($this->returnValue($nested));
        $this->_elementMock->expects($this->any())->method('getExpanded')->will($this->returnValue($expanded));

        $actual = $this->_object->render($this->_elementMock);

        $this->assertContains('test_field_toHTML', $actual);

        $expected = '<div id="row_test_field_id_comment" class="system-tooltip-box"' .
            ' style="display:none;">test_field_tootip</div>';
        $this->assertContains($expected, $actual);
        if ($nested) {
            $this->assertContains('nested', $actual);
        }
    }

    /**
     * @return array
     */
    public function renderWithoutStoredElementsDataProvider()
    {
        return $this->dataProvider();
    }

    /**
     * @return array
     */
    public function renderWithStoredElementsDataProvider()
    {
        return $this->dataProvider();
    }

    /**
     * @return array
     */
    protected function dataProvider()
    {
        return [
            'expandedNestedExtra' => [
                'expanded' => true,
                'nested'   => true,
                'extra'    => [],
            ],
            'collapsedNotNestedExtra' => [
                'expanded' => false,
                'nested'   => false,
                'extra'    => ['configState' => [$this->testData['htmlId'] => true]],
            ],
            'collapsedNotNestedNoExtra' => [
                'expanded' => true,
                'nested'   => false,
                'extra'    => [],
            ],
        ];
    }
}
