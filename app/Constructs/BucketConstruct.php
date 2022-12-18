<?php

namespace App\Constructs;

use App\Path;
use Symfony\Component\Yaml\Tag\TaggedValue;

trait BucketConstruct
{
    protected function setupBuckets(): self
    {
        if (!$this->unloadConfig->buckets()) {
            return $this;
        }

        foreach ($this->unloadConfig->buckets() as $bucketName => $bucketDefinition) {
            $bucketStack = ucfirst(strtolower($bucketName)).'BucketStack';
            $bucketRef = ['BucketName' => new TaggedValue('GetAtt', "$bucketStack.Outputs.BucketName"),];

            $this->append('Policies', [
                ['S3CrudPolicy' => $bucketRef,],
            ]);

            $this->append('Resources', [
                $bucketStack => [
                    'Type' => 'AWS::Serverless::Application',
                    'Properties' => [
                        'Tags' => $this->unloadConfig->unloadTagsPlain(),
                        'Location' => Cloudformation::compile("storage/bucket.yaml"),
                        'Parameters' => [
                            'Access' => ucfirst($bucketDefinition['access'] ?? 'private'),
                            'Versioning' => ($bucketDefinition['versioning'] ?? 'no') == 'yes',
                            'NoncurrentVersionExpirationInDays' => $bucketDefinition['version-expiration'] ?? 0,
                            'ExpirationInDays' => $bucketDefinition['expiration'] ?? 0,
                            'ExpirationPrefix' => $bucketDefinition['expiration-prefix'] ?? '',
                        ]
                    ],
                ]
            ]);
        }

        $defaultBucketStack = ucfirst(strtolower(array_keys($this->unloadConfig->buckets())[0])).'BucketStack';
        return $this->append('Globals.Function.Environment.Variables', [
            'AWS_BUCKET' => new TaggedValue('GetAtt', "$defaultBucketStack.Outputs.BucketName"),
            'FILESYSTEM_DRIVER' => 's3',
        ]);
    }
}
