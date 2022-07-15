<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\SalesInventory\Test\Unit\Model\Plugin\Order\Validation;

use Magento\SalesInventory\Model\Order\ReturnValidator;
use Magento\SalesInventory\Model\Plugin\Order\Validation\InvoiceRefundCreationArguments;
use Magento\Sales\Model\ValidatorResultInterface;
use Magento\Sales\Api\Data\CreditmemoCreationArgumentsInterface;
use Magento\Sales\Api\Data\CreditmemoCreationArgumentsExtensionInterface;
use Magento\Sales\Api\Data\CreditmemoInterface;
use Magento\Sales\Api\Data\InvoiceInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\Order\Validation\RefundInvoiceInterface;

/**
 * Class InvoiceRefundCreationArgumentsTest
 */
class InvoiceRefundCreationArgumentsTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var InvoiceRefundCreationArguments
     */
    private $plugin;

    /**
     * @var ReturnValidator|\PHPUnit_Framework_MockObject_MockObject
     */
    private $returnValidatorMock;

    /**
     * @var CreditmemoCreationArgumentsExtensionInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $extensionAttributesMock;

    /**
     * @var CreditmemoCreationArgumentsInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $creditmemoCreationArgumentsMock;

    /**
     * @var RefundInvoiceInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $refundInvoiceValidatorMock;

    /**
     * @var InvoiceInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $invoiceMock;

    /**
     * @var ValidatorResultInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $validateResultMock;

    /**
     * @var OrderInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $orderMock;

    /**
     * @var CreditmemoInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $creditmemoMock;

    protected function setUp()
    {
        $this->returnValidatorMock = $this->getMockBuilder(ReturnValidator::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->creditmemoCreationArgumentsMock = $this->getMockBuilder(CreditmemoCreationArgumentsInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->extensionAttributesMock = $this->getMockBuilder(CreditmemoCreationArgumentsExtensionInterface::class)
            ->setMethods(['getReturnToStockItems'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->validateResultMock = $this->getMockBuilder(ValidatorResultInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->refundInvoiceValidatorMock = $this->getMockBuilder(RefundInvoiceInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->invoiceMock = $this->getMockBuilder(InvoiceInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->orderMock = $this->getMockBuilder(OrderInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->creditmemoMock = $this->getMockBuilder(CreditmemoInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->plugin = new InvoiceRefundCreationArguments($this->returnValidatorMock);
    }

    /**
     * @dataProvider dataProvider
     */
    public function testAfterValidation($erroMessage)
    {
        $returnToStockItems = [1];
        $this->creditmemoCreationArgumentsMock->expects($this->exactly(3))
            ->method('getExtensionAttributes')
            ->willReturn($this->extensionAttributesMock);

        $this->extensionAttributesMock->expects($this->exactly(2))
            ->method('getReturnToStockItems')
            ->willReturn($returnToStockItems);

        $this->returnValidatorMock->expects($this->once())
            ->method('validate')
            ->willReturn($erroMessage);

        $this->validateResultMock->expects($erroMessage ? $this->once() : $this->never())
            ->method('addMessage')
            ->with($erroMessage);

        $this->plugin->afterValidate(
            $this->refundInvoiceValidatorMock,
            $this->validateResultMock,
            $this->invoiceMock,
            $this->orderMock,
            $this->creditmemoMock,
            [],
            false,
            false,
            false,
            null,
            $this->creditmemoCreationArgumentsMock
        );
    }

    /**
     * @return array
     */
    public function dataProvider()
    {
        return [
            'withErrors' => ['Error!'],
            'withoutErrors' => ['null'],
        ];
    }
}
