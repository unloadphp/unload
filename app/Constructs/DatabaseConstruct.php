<?php

namespace App\Constructs;

use App\Cloudformation;
use Illuminate\Support\Arr;
use Symfony\Component\Yaml\Tag\TaggedValue;

trait DatabaseConstruct
{
    protected function setupDatabases(): self
    {
        if (!$this->unloadConfig->database()) {
            return $this;
        }

        $database = $this->unloadConfig->database();

        $this->append('Globals.Function.Environment.Variables', [
            'DB_HOST' => new TaggedValue('GetAtt', 'DatabaseStack.Outputs.DNSName')
        ]);

        if ($database['engine'] == 'mysql') {
            $this->append('Resources', [
                'DatabaseStack' => [
                    'Type' => 'AWS::Serverless::Application',
                    'Properties' => [
                        'Tags' => $this->unloadConfig->unloadTagsPlain(),
                        'Location' => Cloudformation::compile("storage/mysql.yaml"),
                        'Parameters' => array_filter([
                            'VpcId' => new TaggedValue('Ref', 'VpcId'),
                            'VpcSubnetsPrivate' => new TaggedValue('Ref', 'VpcSubnetsPrivate'),
                            'VpcSecurityGroup' => new TaggedValue('GetAtt', 'SecurityGroupStack.Outputs.ClientSecurityGroup'),
                            'VpcBastionSecurityGroup' => new TaggedValue('Ref', 'VpcBastionSecurityGroupId'),

                            'DBInstanceClass' => Arr::get($database, 'size'),
                            'DBAllocatedStorage' => Arr::get($database, 'disk'),
                            'DBBackupRetentionPeriod' => Arr::get($database, 'backup-retention'),
                            'EngineVersion' => Arr::get($database, 'version'),
                            'DBMultiAZ' => Arr::get($database, 'multi-az', 'no') == 'yes',

                            'DBName' => $this->unloadConfig->databaseName(),
                            'DBMasterUsername' => $this->unloadConfig->databaseUsername(),
                            'DBMasterUserPassword' => $this->unloadConfig->ssmCiPath('database'),
                        ]),
                    ],
                ]
            ]);
        }

        if ($database['engine'] == 'aurora') {
            $this->append('Resources', [
                'DatabaseStack' => [
                    'Type' => 'AWS::Serverless::Application',
                    'Properties' => [
                        'Tags' => $this->unloadConfig->unloadTagsPlain(),
                        'Location' => Cloudformation::compile("storage/mysql-serverless.yaml"),
                        'Parameters' => array_filter([
                            'VpcId' => new TaggedValue('Ref', 'VpcId'),
                            'VpcSubnetsPrivate' => new TaggedValue('Ref', 'VpcSubnetsPrivate'),
                            'VpcSecurityGroup' => new TaggedValue('GetAtt', 'SecurityGroupStack.Outputs.ClientSecurityGroup'),
                            'VpcBastionSecurityGroup' => new TaggedValue('Ref', 'VpcBastionSecurityGroupId'),

                            'MinCapacity' => Arr::get($database, 'min-capacity'),
                            'MaxCapacity' => Arr::get($database, 'max-capacity'),
                            'EngineVersion' => Arr::get($database, 'version'),
                            'DBBackupRetentionPeriod' => Arr::get($database, 'backup-retention'),

                            'AutoPause' => isset($database['auto-pause']),
                            'SecondsUntilAutoPause' => Arr::get($database, 'auto-pause'),

                            'DBName' => $this->unloadConfig->databaseName(),
                            'DBMasterUsername' => $this->unloadConfig->databaseUsername(),
                            'DBMasterUserPassword' => $this->unloadConfig->ssmCiPath('database'),
                        ]),
                    ],
                ]
            ]);
        }

        return $this;
    }
}
