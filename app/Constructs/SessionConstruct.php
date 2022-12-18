<?php

namespace App\Constructs;

use Symfony\Component\Yaml\Tag\TaggedValue;

trait SessionConstruct
{
    protected function setupSession(): self
    {
        $sessionTableRef = ['TableName' => new TaggedValue('Ref', 'CacheTable')];

        $this->append('Policies', [
            ['DynamoDBCrudPolicy' => $sessionTableRef,],
        ]);

        $this->append(
            'Globals.Function.Environment.Variables.DYNAMODB_CACHE_TABLE',
            new TaggedValue('Ref', 'CacheTable')
        );

        return $this->append('Resources', [
            'CacheTable' => [
                'Type' => 'AWS::DynamoDB::Table',
                'Properties' => [
                    'TableClass' => 'STANDARD',
                    'BillingMode' => 'PAY_PER_REQUEST',
                    'AttributeDefinitions' => [
                        [
                            'AttributeName' => 'key',
                            'AttributeType' => 'S'
                        ]
                    ],
                    'KeySchema' => [
                        [
                            'AttributeName' => 'key',
                            'KeyType' => 'HASH',
                        ]
                    ],
                    'TimeToLiveSpecification' => [
                        'AttributeName' => 'expires_at',
                        'Enabled' => true,
                    ],
                    'Tags' => $this->unloadConfig->unloadTags(),
                ],
            ],
        ]);
    }
}
