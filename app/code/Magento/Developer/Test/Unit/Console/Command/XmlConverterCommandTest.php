<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Developer\Test\Unit\Console\Command;

use Magento\Developer\Console\Command\XmlConverterCommand;
use Symfony\Component\Console\Tester\CommandTester;
use Magento\Developer\Model\Tools\Formatter;
use Magento\Framework\DomDocument\DomDocumentFactory;
use Magento\Framework\XsltProcessor\XsltProcessorFactory;

class XmlConverterCommandTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var Formatter|\PHPUnit_Framework_MockObject_MockObject
     */
    private $formatter;

    /**
     * @var XmlConverterCommand
     */
    private $command;

    /**
     * @var DomDocumentFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    private $domFactory;

    /**
     * @var XsltProcessorFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    private $xsltProcessorFactory;

    protected function setUp()
    {
        if (!function_exists('libxml_set_external_entity_loader')) {
            $this->markTestSkipped('Skipped on HHVM. Will be fixed in MAGETWO-45033');
        }
        $this->formatter = $this->createMock(\Magento\Developer\Model\Tools\Formatter::class);
        $this->domFactory = $this->createMock(\Magento\Framework\DomDocument\DomDocumentFactory::class);
        $this->xsltProcessorFactory = $this->createMock(\Magento\Framework\XsltProcessor\XsltProcessorFactory::class);

        $this->command = new XmlConverterCommand($this->formatter, $this->domFactory, $this->xsltProcessorFactory);
    }

    public function testExecute()
    {
        $domXml = $this->createMock(\DOMDocument::class);
        $domXsl = clone $domXml;
        $domXml->expects($this->once())->method('load')->with('file.xml');
        $domXsl->expects($this->once())->method('load')->with('file.xsl');

        $this->domFactory->expects($this->at(0))->method('create')->willReturn($domXml);
        $this->domFactory->expects($this->at(1))->method('create')->willReturn($domXsl);

        $xsltProcessor = $this->createMock(\XSLTProcessor::class);
        $xsltProcessor->expects($this->once())->method('transformToXml')->with($domXml)->willReturn('XML');

        $this->xsltProcessorFactory->expects($this->once())->method('create')->willReturn($xsltProcessor);

        $this->formatter->expects($this->once())->method('format')->with('XML')->willReturn('result');

        $commandTester = new CommandTester($this->command);
        $commandTester->execute(
            [
                XmlConverterCommand::XML_FILE_ARGUMENT => 'file.xml',
                XmlConverterCommand::PROCESSOR_ARGUMENT => 'file.xsl'
            ]
        );
        $this->assertContains('result', $commandTester->getDisplay());
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Not enough arguments
     */
    public function testWrongParameter()
    {
        $commandTester = new CommandTester($this->command);
        $commandTester->execute([]);
    }
}
