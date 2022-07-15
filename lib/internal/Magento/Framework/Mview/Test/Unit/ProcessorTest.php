<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Framework\Mview\Test\Unit;

class ProcessorTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\Framework\Mview\Processor|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $model;

    /**
     * @var \Magento\Framework\Mview\View\CollectionFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $viewsFactoryMock;

    protected function setUp()
    {
        $this->viewsFactoryMock =
            $this->createPartialMock(\Magento\Framework\Mview\View\CollectionFactory::class, ['create']);
        $this->model = new \Magento\Framework\Mview\Processor($this->viewsFactoryMock);
    }

    /**
     * Return array of mocked views
     *
     * @param string $method
     * @return \Magento\Framework\Mview\ViewInterface[]|\PHPUnit_Framework_MockObject_MockObject[]
     */
    protected function getViews($method)
    {
        $viewMock = $this->createMock(\Magento\Framework\Mview\ViewInterface::class);
        $viewMock->expects($this->exactly(2))->method($method);
        return [$viewMock, $viewMock];
    }

    /**
     * Return view collection mock
     *
     * @return \Magento\Framework\Mview\View\Collection|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function getViewsMock()
    {
        $viewsMock = $this->createMock(\Magento\Framework\Mview\View\Collection::class);
        $this->viewsFactoryMock->expects($this->once())->method('create')->will($this->returnValue($viewsMock));
        return $viewsMock;
    }

    public function testUpdate()
    {
        $viewsMock = $this->getViewsMock();
        $viewsMock->expects($this->once())->method('getItems')->will($this->returnValue($this->getViews('update')));
        $viewsMock->expects($this->never())->method('getItemsByColumnValue');

        $this->model->update();
    }

    public function testUpdateWithGroup()
    {
        $group = 'group';
        $viewsMock = $this->getViewsMock();
        $viewsMock->expects($this->never())->method('getItems');
        $viewsMock->expects(
            $this->once()
        )->method(
            'getItemsByColumnValue'
        )->with(
            $group
        )->will(
            $this->returnValue($this->getViews('update'))
        );

        $this->model->update($group);
    }

    public function testClearChangelog()
    {
        $viewsMock = $this->getViewsMock();
        $viewsMock->expects(
            $this->once()
        )->method(
            'getItems'
        )->will(
            $this->returnValue($this->getViews('clearChangelog'))
        );
        $viewsMock->expects($this->never())->method('getItemsByColumnValue');

        $this->model->clearChangelog();
    }

    public function testClearChangelogWithGroup()
    {
        $group = 'group';
        $viewsMock = $this->getViewsMock();
        $viewsMock->expects($this->never())->method('getItems');
        $viewsMock->expects(
            $this->once()
        )->method(
            'getItemsByColumnValue'
        )->with(
            $group
        )->will(
            $this->returnValue($this->getViews('clearChangelog'))
        );

        $this->model->clearChangelog($group);
    }
}
