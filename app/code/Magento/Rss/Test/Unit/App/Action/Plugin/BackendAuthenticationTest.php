<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Rss\Test\Unit\App\Action\Plugin;

class BackendAuthenticationTest extends \PHPUnit\Framework\TestCase
{
    public function testAroundDispatch()
    {
        /** @var \Magento\Backend\App\AbstractAction|\PHPUnit_Framework_MockObject_MockObject $subject */
        $subject = $this->createMock(\Magento\Backend\App\AbstractAction::class);

        /** @var \Magento\Framework\App\ResponseInterface|\PHPUnit_Framework_MockObject_MockObject $response */
        $response = $this->createMock(\Magento\Framework\App\ResponseInterface::class);

        $proceed = function () use ($response) {
            return $response;
        };

        /** @var \Magento\Framework\App\Request\Http|\PHPUnit_Framework_MockObject_MockObject $request */
        $request = $this->createMock(\Magento\Framework\App\Request\Http::class);
        $request->expects($this->atLeastOnce())->method('getControllerName')->will($this->returnValue('feed'));
        $request->expects($this->atLeastOnce())->method('getActionName')->will($this->returnValue('index'));
        $request->expects($this->once())->method('getParam')->with('type')->will($this->returnValue('notifystock'));

        /** @var \Magento\Backend\Model\Auth\StorageInterface|\PHPUnit_Framework_MockObject_MockObject $session */
        $session = $this->createMock(\Magento\Backend\Model\Auth\StorageInterface::class);
        $session->expects($this->at(0))->method('isLoggedIn')->will($this->returnValue(false));
        $session->expects($this->at(1))->method('isLoggedIn')->will($this->returnValue(true));

        $username = 'admin';
        $password = '123123qa';
        $auth = $this->createMock(\Magento\Backend\Model\Auth::class);
        $auth->expects($this->once())->method('getAuthStorage')->will($this->returnValue($session));
        $auth->expects($this->once())->method('login')->with($username, $password);

        /** @var \Magento\Framework\HTTP\Authentication|\PHPUnit_Framework_MockObject_MockObject $httpAuthentication */
        $httpAuthentication = $this->createMock(\Magento\Framework\HTTP\Authentication::class);
        $httpAuthentication->expects($this->once())->method('getCredentials')
            ->will($this->returnValue([$username, $password]));
        $httpAuthentication->expects($this->once())->method('setAuthenticationFailed')->with('RSS Feeds');

        $authorization = $this->createMock(\Magento\Framework\AuthorizationInterface::class);
        $authorization->expects($this->at(0))->method('isAllowed')->with('Magento_Rss::rss')
            ->will($this->returnValue(true));
        $authorization->expects($this->at(1))->method('isAllowed')->with('Magento_Catalog::catalog_inventory')
            ->will($this->returnValue(false));

        $aclResources = [
            'feed' => 'Magento_Rss::rss',
            'notifystock' => 'Magento_Catalog::catalog_inventory',
            'new_order' => 'Magento_Sales::actions_view',
            'review' => 'Magento_Reports::review_product'
        ];

        /** @var \Magento\Rss\App\Action\Plugin\BackendAuthentication $plugin */
        $plugin = (new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this))
            ->getObject(
                \Magento\Rss\App\Action\Plugin\BackendAuthentication::class,
                [
                    'auth' => $auth,
                    'httpAuthentication' => $httpAuthentication,
                    'response' => $response,
                    'authorization' => $authorization,
                    'aclResources' => $aclResources
                ]
            );
        $this->assertSame(
            $response,
            $plugin->aroundDispatch($subject, $proceed, $request)
        );
    }
}
