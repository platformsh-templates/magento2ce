<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Framework\Locale\Test\Unit;

use Magento\Framework\CurrencyFactory;
use Magento\Framework\Event\Manager;
use Magento\Framework\Locale\Currency;
use Magento\Framework\Locale\Resolver;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CurrencyTest extends TestCase
{
    /**
     * @var Manager|MockObject
     */
    private $mockEventManager;

    /**
     * @var Resolver|MockObject
     */
    private $mockLocaleResolver;

    /**
     * @var CurrencyFactory|MockObject
     */
    private $mockCurrencyFactory;

    /**
     * @var Currency
     */
    private $testCurrencyObject;

    const TEST_NONCACHED_CURRENCY = 'USD';
    const TEST_NONCACHED_CURRENCY_LOCALE = 'en_US';
    const TEST_CACHED_CURRENCY = 'CAD';
    const TEST_CACHED_CURRENCY_LOCALE = 'en_CA';
    const TEST_NONEXISTENT_CURRENCY = 'QQQ';
    const TEST_NONEXISTENT_CURRENCY_LOCALE = 'fr_FR';
    const TEST_EXCEPTION_CURRENCY = 'ZZZ';
    const TEST_EXCEPTION_CURRENCY_LOCALE = 'es_ES';

    protected function setUp(): void
    {
        $this->mockEventManager = $this
            ->getMockBuilder(Manager::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->mockLocaleResolver = $this
            ->getMockBuilder(Resolver::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->mockCurrencyFactory = $this
            ->getMockBuilder(CurrencyFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->testCurrencyObject = (new ObjectManager($this))
            ->getObject(
                Currency::class,
                [
                    'eventManager'     => $this->mockEventManager,
                    'localeResolver'   => $this->mockLocaleResolver,
                    'currencyFactory'  => $this->mockCurrencyFactory,
                ]
            );
    }

    public function testGetDefaultCurrency()
    {
        $expectedDefaultCurrency = Currency::DEFAULT_CURRENCY;
        $retrievedDefaultCurrency = $this->testCurrencyObject->getDefaultCurrency();
        $this->assertEquals($expectedDefaultCurrency, $retrievedDefaultCurrency);
    }

    public function testGetCurrencyNonCached()
    {
        $options = new \Zend_Currency(null, self::TEST_NONCACHED_CURRENCY_LOCALE);

        $this->mockCurrencyFactory
            ->expects($this->once())
            ->method('create')
            ->willReturn($options);

        $this->mockEventManager
            ->expects($this->once())
            ->method('dispatch');

        $retrievedCurrencyObject = $this->testCurrencyObject
            ->getCurrency(self::TEST_NONCACHED_CURRENCY);

        $this->assertInstanceOf('Zend_Currency', $retrievedCurrencyObject);
        $this->assertEquals(self::TEST_NONCACHED_CURRENCY_LOCALE, $retrievedCurrencyObject->getLocale());
        $this->assertEquals('US Dollar', $retrievedCurrencyObject->getName());
        $this->assertEquals([self::TEST_NONCACHED_CURRENCY], $retrievedCurrencyObject->getCurrencyList());
    }

    public function testGetCurrencyCached()
    {
        $options = new \Zend_Currency(null, self::TEST_CACHED_CURRENCY_LOCALE);

        $this->mockCurrencyFactory
            ->expects($this->once())
            ->method('create')
            ->willReturn($options);

        $this->mockEventManager
            ->expects($this->once())
            ->method('dispatch');

        $retrievedCurrencyObject = $this->testCurrencyObject
            ->getCurrency(self::TEST_CACHED_CURRENCY);

        $this->assertInstanceOf('Zend_Currency', $retrievedCurrencyObject);
        $this->assertEquals(self::TEST_CACHED_CURRENCY_LOCALE, $retrievedCurrencyObject->getLocale());
        $this->assertEquals('Canadian Dollar', $retrievedCurrencyObject->getName());
        $this->assertEquals([self::TEST_CACHED_CURRENCY], $retrievedCurrencyObject->getCurrencyList());

        /*
         * Since the CAD currency object was previously retrieved, getCurrency()
         * should return the previously created and cached currency object for CAD, rather
         * than creating another one.
         */

        $this->mockCurrencyFactory
            ->expects($this->never())
            ->method('create')
            ->willReturn($options);

        $this->mockEventManager
            ->expects($this->never())
            ->method('dispatch');

        $retrievedCurrencyObject = $this->testCurrencyObject
            ->getCurrency(self::TEST_CACHED_CURRENCY);

        $this->assertInstanceOf('Zend_Currency', $retrievedCurrencyObject);
        $this->assertEquals(self::TEST_CACHED_CURRENCY_LOCALE, $retrievedCurrencyObject->getLocale());
        $this->assertEquals('Canadian Dollar', $retrievedCurrencyObject->getName());
        $this->assertEquals([self::TEST_CACHED_CURRENCY], $retrievedCurrencyObject->getCurrencyList());
    }

    public function testGetNonExistentCurrency()
    {
        $options = new \Zend_Currency(null, self::TEST_NONEXISTENT_CURRENCY_LOCALE);

        $this->mockCurrencyFactory
            ->expects($this->once())
            ->method('create')
            ->willReturn($options);

        $this->mockEventManager
            ->expects($this->once())
            ->method('dispatch');

        $this->mockEventManager
            ->expects($this->once())
            ->method('dispatch');

        $retrievedCurrencyObject = $this->testCurrencyObject
            ->getCurrency(self::TEST_NONEXISTENT_CURRENCY);

        $this->assertInstanceOf('Zend_Currency', $retrievedCurrencyObject);
        $this->assertEquals(self::TEST_NONEXISTENT_CURRENCY_LOCALE, $retrievedCurrencyObject->getLocale());
        $this->assertEquals('euro', $retrievedCurrencyObject->getName());
        $this->assertEquals(['EUR'], $retrievedCurrencyObject->getCurrencyList());
    }

    public function testExceptionCase()
    {
        $options = new \Zend_Currency(null, self::TEST_EXCEPTION_CURRENCY_LOCALE);

        $this->mockCurrencyFactory
            ->expects($this->at(0))
            ->method('create')
            ->willThrowException(new \Exception());

        $this->mockCurrencyFactory
            ->expects($this->at(1))
            ->method('create')
            ->willReturn($options);

        $this->mockEventManager
            ->expects($this->once())
            ->method('dispatch');

        $this->mockLocaleResolver
            ->expects($this->exactly(5))
            ->method('getLocale');

        $retrievedCurrencyObject = $this->testCurrencyObject
            ->getCurrency(self::TEST_EXCEPTION_CURRENCY);

        $this->assertInstanceOf('Zend_Currency', $retrievedCurrencyObject);
        $this->assertEquals(self::TEST_EXCEPTION_CURRENCY_LOCALE, $retrievedCurrencyObject->getLocale());
        $this->assertEquals(self::TEST_EXCEPTION_CURRENCY, $retrievedCurrencyObject->getName());
        $this->assertEquals(['EUR'], $retrievedCurrencyObject->getCurrencyList());
    }
}
