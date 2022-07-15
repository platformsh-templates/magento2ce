<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Elasticsearch\Test\Unit\SearchAdapter\Query\Builder;

use Magento\Elasticsearch\Model\Adapter\FieldMapper\Product\AttributeAdapter;
use Magento\Elasticsearch\Model\Adapter\FieldMapper\Product\AttributeProvider;
use Magento\Elasticsearch\Model\Adapter\FieldMapper\Product\FieldProvider\FieldType\ResolverInterface as TypeResolver;
use Magento\Elasticsearch\Model\Adapter\FieldMapperInterface;
use Magento\Elasticsearch\Model\Config;
use Magento\Elasticsearch\SearchAdapter\Query\Builder\Match as MatchQueryBuilder;
use Magento\Elasticsearch\SearchAdapter\Query\ValueTransformerInterface;
use Magento\Elasticsearch\SearchAdapter\Query\ValueTransformerPool;
use Magento\Framework\Search\Request\Query\Match as MatchRequestQuery;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Test Match query builder
 */
class MatchTest extends TestCase
{
    /**
     * @var AttributeProvider|MockObject
     */
    private $attributeProvider;

    /**
     * @var TypeResolver|MockObject
     */
    private $fieldTypeResolver;

    /**
     * @var MatchQueryBuilder
     */
    private $matchQueryBuilder;
    /**
     * @var MockObject
     */
    private $config;
    /**
     * @var MockObject
     */
    private $fieldMapper;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->attributeProvider = $this->createMock(AttributeProvider::class);
        $this->fieldTypeResolver = $this->createMock(TypeResolver::class);
        $this->config = $this->createMock(Config::class);
        $this->fieldMapper = $this->getMockForAbstractClass(FieldMapperInterface::class);
        $this->fieldMapper->method('getFieldName')
            ->willReturnArgument(0);
        $valueTransformerPoolMock = $this->createMock(ValueTransformerPool::class);
        $valueTransformerMock = $this->getMockForAbstractClass(ValueTransformerInterface::class);
        $valueTransformerPoolMock->method('get')
            ->willReturn($valueTransformerMock);
        $valueTransformerMock->method('transform')
            ->willReturnArgument(0);
        $this->matchQueryBuilder = (new ObjectManager($this))->getObject(
            MatchQueryBuilder::class,
            [
                'fieldMapper' => $this->fieldMapper,
                'preprocessorContainer' => [],
                'attributeProvider' => $this->attributeProvider,
                'fieldTypeResolver' => $this->fieldTypeResolver,
                'valueTransformerPool' => $valueTransformerPoolMock,
                'config' => $this->config,
            ]
        );
    }

    /**
     * Tests that method constructs a correct select query.
     *
     * @param string $searchQuery
     * @param array $fields
     * @param array $expected
     * @param string|null $minimumShouldMatch
     * @dataProvider buildDataProvider
     * @dataProvider buildDataProviderForMatchPhrasePrefix
     */
    public function testBuild(
        string $searchQuery,
        array $fields,
        array $expected,
        ?string $minimumShouldMatch = null
    ) {
        $this->config->method('getElasticsearchConfigData')
            ->with('minimum_should_match')
            ->willReturn($minimumShouldMatch);

        foreach ($fields as $field) {
            $this->mockAttribute($field['field']);
        }

        $requestQuery = new MatchRequestQuery('match', $searchQuery, 1, $fields);
        $query = $this->matchQueryBuilder->build([], $requestQuery, 'should');

        $expectedSelectQuery = [
            'bool' => [
                'should' => $expected,
            ],
        ];

        $this->assertEquals(
            $expectedSelectQuery,
            $query
        );
    }

    /**
     * @return array
     */
    public function buildDataProvider(): array
    {
        return [
            'match query without minimum_should_match' => [
                'fitness bottle',
                [
                    [
                        'field' => 'name',
                        'boost' => 5
                    ]
                ],
                [
                    [
                        'match' => [
                            'name' => [
                                'query' => 'fitness bottle',
                                'boost' => 6,
                            ],
                        ],
                    ],
                ]
            ],
            'match_phrase query without minimum_should_match' => [
                '"fitness bottle"',
                [
                    [
                        'field' => 'name',
                        'boost' => 5
                    ]
                ],
                [
                    [
                        'match_phrase' => [
                            'name' => [
                                'query' => 'fitness bottle',
                                'boost' => 6,
                            ],
                        ],
                    ],
                ]
            ],
            'match query with minimum_should_match' => [
                'fitness bottle',
                [
                    [
                        'field' => 'name',
                        'boost' => 5
                    ]
                ],
                [
                    [
                        'match' => [
                            'name' => [
                                'query' => 'fitness bottle',
                                'boost' => 6,
                                'minimum_should_match' => '2<75%',
                            ],
                        ],
                    ],
                ],
                '2<75%'
            ],
            'match_phrase query with minimum_should_match' => [
                '"fitness bottle"',
                [
                    [
                        'field' => 'name',
                        'boost' => 5
                    ]
                ],
                [
                    [
                        'match_phrase' => [
                            'name' => [
                                'query' => 'fitness bottle',
                                'boost' => 6,
                                'minimum_should_match' => '2<75%',
                            ],
                        ],
                    ],
                ],
                '2<75%'
            ],

        ];
    }

    /**
     * @return array
     */
    public function buildDataProviderForMatchPhrasePrefix()
    {
        return [
        'match_phrase_prefix query with minimum_should_match' => [
            '"fitness bottle"',
            [
                [
                    'field' => 'name',
                    'boost' => 5,
                    'matchCondition' => 'match_phrase_prefix'
                ]
            ],
            [
                [
                    'match_phrase_prefix' => [
                        'name' => [
                            'query' => 'fitness bottle',
                            'boost' => 6
                        ],
                    ],
                ],
            ],
            '2<75%'
        ],
        'match_phrase_prefix query with no minimum_should_match' => [
            '"fitness bottle"',
            [
                [
                    'field' => 'name',
                    'boost' => 5,
                    'matchCondition' => 'match_phrase_prefix'
                ]
            ],
            [
                [
                    'match_phrase_prefix' => [
                        'name' => [
                            'query' => 'fitness bottle',
                            'boost' => 6
                        ],
                    ],
                ],
            ]
        ]];
    }

    /**
     * Mock attribute
     *
     * @param string $attributeCode
     * @param string $type
     */
    private function mockAttribute(string $attributeCode, string $type = 'text')
    {
        $attributeAdapter = $this->createMock(AttributeAdapter::class);
        $this->attributeProvider->expects($this->once())
            ->method('getByAttributeCode')
            ->with($attributeCode)
            ->willReturn($attributeAdapter);
        $this->fieldTypeResolver->expects($this->once())
            ->method('getFieldType')
            ->with($attributeAdapter)
            ->willReturn($type);
    }
}
