<?php

namespace App\Constructs;

use Aws\CloudFormation\CloudFormationClient;
use Aws\Ssm\SsmClient;
use Symfony\Component\Yaml\Tag\TaggedValue;

trait EnvironmentConstruct
{
    protected function setupEnvironment(): self
    {
        $cloudformation = new CloudFormationClient(['profile' => $this->unloadConfig->profile(), 'region' => $this->unloadConfig->region(), 'version' => 'latest']);
        $vpcParameters = collect($cloudformation->describeStacks(['StackName' => $this->unloadConfig->networkStackName()])->search('Stacks[0].Outputs'))
            ->mapWithKeys(function ($value, $key) {
                return [$value['OutputKey'] => [
                    'Type' => 'String',
                    'Default' => $value['OutputValue'],
                ]];
            });

        $stackParameters = collect([
            'CiSecret' => [
                'Type' => 'AWS::SSM::Parameter::Value<String>',
                'NoEcho' => true,
                'Default' => $this->unloadConfig->ssmCiPath('key'),
            ],
            'EnvAssetUrl' => [
                'Type' => 'String',
                'Default' => '',
            ],
            'CertificateArn' => [
                'Type' => 'String',
                'Default' => '',
            ],
            'PipelineRoleArn' => [
                'Type' => 'String',
                'Default' => '',
            ]
        ])->merge($vpcParameters)->toArray();

        $environmentVariables = [
            'CI_SECRET' => new TaggedValue('Ref', 'CiSecret'),
            'ASSET_URL' => new TaggedValue('Ref', 'EnvAssetUrl'),
            'APP_CONFIG_CACHE' => '/tmp/config.php',
            'BREF_PING_DISABLE' => 1,
            'BREF_AUTOLOAD_PATH' => '/var/task/autoload.php',
        ];

        return $this->append('Parameters', $stackParameters)
            ->append('Globals.Function.Environment.Variables', $environmentVariables);
    }
}
