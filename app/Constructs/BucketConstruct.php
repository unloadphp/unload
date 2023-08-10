<?php

namespace App\Constructs;

use App\Cloudformation;
use Illuminate\Support\Arr;
use Symfony\Component\Yaml\Tag\TaggedValue;

trait BucketConstruct
{
    protected function setupBuckets(): self
    {
        if (!$this->unloadConfig->buckets()) {
            return $this;
        }

        foreach ($this->unloadConfig->buckets() as $bucketName => $bucketDefinition) {
            $bucketLightsail = isset($bucketDefinition['size']);
            $bucketStack = ucfirst(strtolower($bucketName)).'BucketStack';
            $bucketRef = ['BucketName' => new TaggedValue('GetAtt', "$bucketStack.Outputs.BucketName"),];
            $bucketAccess = [
                'private' => 'Private',
                'public-read' => 'PublicRead'
            ][Arr::get($bucketDefinition, 'access')] ?? 'Private';

            $this->append('Policies', [
                ['S3CrudPolicy' => $bucketRef,],
            ]);

            if ($bucketLightsail) {
                $this->addLightsailBucket($bucketStack, $bucketAccess, $bucketDefinition);
                continue;
            }

            $this->addS3Bucket($bucketStack, $bucketAccess, $bucketDefinition);
        }

        $defaultBucketStack = ucfirst(strtolower(array_keys($this->unloadConfig->buckets())[0])).'BucketStack';
        return $this->append('Globals.Function.Environment.Variables', [
            'AWS_BUCKET' => new TaggedValue('GetAtt', "$defaultBucketStack.Outputs.BucketName"),
            'FILESYSTEM_DRIVER' => 's3',
        ]);
    }

    private function addS3Bucket(string $bucketStack, string $bucketAccess, array $bucketDefinition): void
    {
        $this->append('Resources', [
            $bucketStack => [
                'Type' => 'AWS::Serverless::Application',
                'Properties' => [
                    'Tags' => $this->unloadConfig->unloadTagsPlain(),
                    'Location' => Cloudformation::compile("storage/bucket.yaml"),
                    'Parameters' => [
                        'Access' => $bucketAccess,
                        'Versioning' => ($bucketDefinition['versioning'] ?? 'no') == 'yes',
                        'NoncurrentVersionExpirationInDays' => $bucketDefinition['version-expiration'] ?? 0,
                        'ExpirationInDays' => $bucketDefinition['expiration'] ?? 0,
                        'ExpirationPrefix' => $bucketDefinition['expiration-prefix'] ?? '',
                    ]
                ],
            ]
        ]);
    }

    private function addLightsailBucket(string $bucketStack, string $bucketAccess, array $bucketDefinition): void
    {
        $this->append('Resources', [
            $bucketStack => [
                'Type' => 'AWS::Serverless::Application',
                'Properties' => [
                    'Tags' => $this->unloadConfig->unloadTagsPlain(),
                    'Location' => Cloudformation::compile("storage/bucket-lightsail.yaml"),
                    'Parameters' => [
                        'Access' => $bucketAccess,
                        'Versioning' => ($bucketDefinition['versioning'] ?? 'no') == 'yes',
                        'BundleId' => match ($bucketDefinition['size']) {
                            '1GB' => 'small_1_0',
                            '100GB' => 'medium_1_0',
                            '250GB' => 'large_1_0',
                            default => 'small_1_0'
                        },
                    ]
                ],
            ]
        ]);
    }
}
