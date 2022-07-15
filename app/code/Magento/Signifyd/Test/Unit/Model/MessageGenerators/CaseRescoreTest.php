<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Signifyd\Test\Unit\Model\MessageGenerators;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Signifyd\Api\CaseRepositoryInterface;
use Magento\Signifyd\Api\Data\CaseInterface;
use Magento\Signifyd\Model\MessageGenerators\CaseRescore;
use PHPUnit_Framework_MockObject_MockObject as MockObject;

/**
 * Tests for Signifyd CaseRescore message generator.
 *
 * Class CaseRescoreTest
 */
class CaseRescoreTest extends \PHPUnit\Framework\TestCase
{
    private static $data = [
        'caseId' => 100,
        'score'  => 200
    ];

    /**
     * @var ObjectManager
     */
    private $objectManager;

    /**
     * @var CaseRepositoryInterface|MockObject
     */
    private $caseRepository;

    /**
     * @var CaseRescore|MockObject
     */
    private $caseRescore;

    /**
     * @var CaseInterface|MockObject
     */
    private $case;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->case = $this->getMockBuilder(CaseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->objectManager = new ObjectManager($this);
        $this->caseRepository = $this->getMockBuilder(CaseRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->caseRescore = $this->objectManager->getObject(CaseRescore::class, [
            'caseRepository' => $this->caseRepository
        ]);
    }

    /**
     * Data array without required attribute caseId.
     *
     * @expectedException \Magento\Signifyd\Model\MessageGenerators\GeneratorException
     * @expectedExceptionMessage The "caseId" should not be empty
     */
    public function testGenerateEmptyCaseIdException()
    {
        $this->caseRescore->generate([]);
    }

    /**
     * Case entity was not found in DB.
     *
     * @expectedException \Magento\Signifyd\Model\MessageGenerators\GeneratorException
     * @expectedExceptionMessage Case entity not found.
     */
    public function testGenerateNotFoundException()
    {
        $this->caseRepository->expects($this->once())
            ->method('getByCaseId')
            ->with(self::$data['caseId'])
            ->willReturn(null);

        $this->caseRescore = $this->objectManager->getObject(CaseRescore::class, [
            'caseRepository' => $this->caseRepository
        ]);

        $this->caseRescore->generate(self::$data);
    }

    /**
     * Generate case message with not empty previous score.
     */
    public function testGenerateWithPreviousScore()
    {
        $this->case->expects($this->once())
            ->method('getScore')
            ->willReturn(self::$data['score']);

        $this->caseRepository->expects($this->once())
            ->method('getByCaseId')
            ->with(self::$data['caseId'])
            ->willReturn($this->case);

        $this->caseRescore = $this->objectManager->getObject(CaseRescore::class, [
            'caseRepository' => $this->caseRepository
        ]);

        $phrase = __(
            'Case Update: New score for the order is %1. Previous score was %2.',
            self::$data['score'],
            self::$data['score']
        );

        $message = $this->caseRescore->generate(self::$data);

        $this->assertEquals($phrase, $message);
    }

    /**
     * Generate case message with empty previous score.
     */
    public function testGenerateWithoutPreviousScore()
    {
        $this->caseRepository->expects($this->once())
            ->method('getByCaseId')
            ->with(self::$data['caseId'])
            ->willReturn($this->case);

        $this->caseRescore = $this->objectManager->getObject(CaseRescore::class, [
            'caseRepository' => $this->caseRepository
        ]);

        $phrase = __(
            'Case Update: New score for the order is %1. Previous score was %2.',
            self::$data['score'],
            null
        );

        $message = $this->caseRescore->generate(self::$data);

        $this->assertEquals($phrase, $message);
    }
}
