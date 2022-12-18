<?php

namespace App\Constructs;

use App\Cloudformation;
use Illuminate\Support\Arr;
use Symfony\Component\Yaml\Tag\TaggedValue;

trait CacheConstruct
{
    protected function setupCache(): self
    {
        if (!$this->unloadConfig->cache()) {
            return $this;
        }

        $cache = $this->unloadConfig->cache();

        $this->append('Globals.Function.Environment.Variables', [
            'REDIS_HOST' => new TaggedValue('GetAtt', 'CacheStack.Outputs.PrimaryEndPointAddress'),
            'REDIS_PORT' => new TaggedValue('GetAtt', 'CacheStack.Outputs.PrimaryEndPointPort'),
        ]);

        $this->append('Resources', [
            'CacheStack' => [
                'Type' => 'AWS::Serverless::Application',
                'Properties' => [
                    'Tags' => $this->unloadConfig->unloadTagsPlain(),
                    'Location' => Cloudformation::compile("storage/redis.yaml"),
                    'Parameters' => Arr::whereNotNull([
                        'VpcId' => new TaggedValue('Ref', 'VpcId'),
                        'VpcSubnetsPrivate' => new TaggedValue('Ref', 'VpcSubnetsPrivate'),
                        'VpcSecurityGroup' => new TaggedValue('GetAtt', 'SecurityGroupStack.Outputs.ClientSecurityGroup'),
                        'CacheNodeType' => Arr::get($cache, 'size'),
                        'EngineVersion' => Arr::get($cache, 'version'),
                        'NumShards' =>  Arr::get($cache, 'shards'),
                        'NumReplicas' =>  Arr::get($cache, 'replicas'),
                        'SnapshotRetentionLimit' => Arr::get($cache, 'snapshot-retention'),
                    ]),
                ],
            ]
        ]);

        return $this;
    }
}
