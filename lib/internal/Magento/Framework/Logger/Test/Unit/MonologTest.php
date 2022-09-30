<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Framework\Logger\Test\Unit;

use Magento\Framework\Logger\Monolog;
use Monolog\Handler\TestHandler;
use PHPUnit\Framework\TestCase;

class MonologTest extends TestCase
{
    public function testAddRecord()
    {
        $logger = new Monolog(__METHOD__);
        $handler = new TestHandler();

        $logger->pushHandler($handler);

        $logger->addError('test');
        list($record) = $handler->getRecords();

        $this->assertSame('test', $record['message']);
    }

    public function testAddRecordAsException()
    {
        $logger = new Monolog(__METHOD__);
        $handler = new TestHandler();

        $logger->pushHandler($handler);

        $logger->addError(new \Exception('Some exception'));
        list($record) = $handler->getRecords();

        $this->assertInstanceOf(\Exception::class, $record['context']['exception']);
        $this->assertSame('Some exception', $record['message']);
    }
}
