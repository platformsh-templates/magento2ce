<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Paypal\Test\Unit\Controller\Express;

use Magento\Paypal\Test\Unit\Controller\ExpressTest;

class ReturnActionTest extends ExpressTest
{
    protected $name = 'ReturnAction';

    /**
     * @param string $path
     */
    protected function _expectRedirect($path = '*/*/review')
    {
        $this->redirect->expects($this->once())
            ->method('redirect')
            ->with($this->anything(), $path, []);
    }

    public function testExecuteAuthorizationRetrial()
    {
        $this->request->expects($this->once())
            ->method('getParam')
            ->with('retry_authorization')
            ->willReturn('true');
        $this->checkoutSession->expects($this->once())
            ->method('__call')
            ->with('getPaypalTransactionData')
            ->willReturn(['any array']);
        $this->_expectForwardPlaceOrder();
        $this->model->execute();
    }

    /**
     * @return array
     */
    public function trueFalseDataProvider()
    {
        return [[true], [false]];
    }

    /**
     * @param bool $canSkipOrderReviewStep
     * @dataProvider trueFalseDataProvider
     */
    public function testExecute($canSkipOrderReviewStep)
    {
        $this->checkoutSession->expects($this->at(0))
            ->method('__call')
            ->with('unsPaypalTransactionData');
        $this->checkout->expects($this->once())
            ->method('canSkipOrderReviewStep')
            ->willReturn($canSkipOrderReviewStep);
        if ($canSkipOrderReviewStep) {
            $this->_expectForwardPlaceOrder();
        } else {
            $this->_expectRedirect();
        }
        $this->model->execute();
    }

    private function _expectForwardPlaceOrder()
    {
        $this->request->expects($this->once())
            ->method('setActionName')
            ->with('placeOrder');
        $this->request->expects($this->once())
            ->method('setDispatched')
            ->with(false);
    }
}
