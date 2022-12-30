<?php

namespace App\Templates;

use App\Cloudformation;
use App\Constructs\BucketConstruct;
use App\Constructs\CacheConstruct;
use App\Constructs\CloudfrontConstruct;
use App\Constructs\DatabaseConstruct;
use App\Constructs\DnsConstruct;
use App\Constructs\EnvironmentConstruct;
use App\Constructs\EventConstruct;
use App\Constructs\NetworkConstruct;
use App\Constructs\PoliciesConstruct;
use App\Constructs\QueueConstruct;
use App\Constructs\SessionConstruct;
use App\Configs\UnloadConfig;
use App\Configs\LayerConfig;
use App\Path;
use Aws\Ssm\SsmClient;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\File;
use Symfony\Component\Yaml\Tag\TaggedValue;
use Symfony\Component\Yaml\Yaml;

class SamTemplate extends Template
{
    use NetworkConstruct;
    use EventConstruct;
    use EnvironmentConstruct;
    use CloudfrontConstruct;
    use QueueConstruct;
    use DatabaseConstruct;
    use SessionConstruct;
    use CacheConstruct;
    use BucketConstruct;
    use DnsConstruct;
    use PoliciesConstruct;

    protected array $samTemplate = [];
    protected SsmClient $ssm;
    protected LayerConfig $layer;

    public function __construct(UnloadConfig $unloadConfig, LayerConfig $layer)
    {
        $this->layer = $layer;
        $this->ssm = new SsmClient(['profile' => $unloadConfig->profile(), 'region' => $unloadConfig->region(), 'version' => 'latest']);
        parent::__construct($unloadConfig);
    }

    public function make(): bool
    {
        return $this
            ->bootstrap()
            ->setupNetwork()
            ->setupEvents()
            ->setupEnvironment()
            ->setupCloudfront()
            ->setupDns()
            ->setupQueues()
            ->setupBuckets()
            ->setupDatabases()
            ->setupSession()
            ->setupCache()
            ->setupPolicies()
            ->toYaml();
    }

    protected function bootstrap(): self
    {
        $this->samTemplate = [
            'AWSTemplateFormatVersion' => '2010-09-09',
            'Transform' => 'AWS::Serverless-2016-10-31',
            'Description' => 'App: application resources',
            'Parameters' => [],
            'Policies' => [],
            'Outputs' => [],
            'Conditions' => [
                'HasSingleAZ' => new TaggedValue('Equals', [new TaggedValue('Ref', 'VpcAZsNumber'), '1']),
            ],
            'Globals' => [
                'Function' => [
                    'AutoPublishAlias' => $this->unloadConfig->env(),
                    'Runtime' => $this->unloadConfig->runtime(),
                    'Tags' => $this->unloadConfig->unloadTagsPlain(),
                ],
            ],
            'Resources' => [
                'WebFunction' => [
                    'Type' => 'AWS::Serverless::Function',
                    'Properties' => array_filter([
                        'FunctionName' => $this->unloadConfig->webFunction(),
                        'MemorySize' => $this->unloadConfig->webFunctionMemory(),
                        'Timeout' => $this->unloadConfig->webFunctionTimeout(),
                        'Architectures' => [$this->unloadConfig->architecture()],
                        'PackageType' => 'Zip',
                        'Handler' => 'web.php',
                        'FunctionUrlConfig' => [
                            'AuthType' => 'NONE'
                        ],
                        'DeploymentPreference' => [
                            'Type' => 'AllAtOnce',
                            'Hooks' => [
                                'PreTraffic' => new TaggedValue('Ref', 'DeployFunction')
                            ],
                        ],
                        'ReservedConcurrentExecutions' => $this->unloadConfig->webFunctionConcurrency(),
                        'ProvisionedConcurrencyConfig' => array_filter([
                            'ProvisionedConcurrentExecutions' => $this->unloadConfig->webFunctionProvision(),
                        ]),
                        'EphemeralStorage' => [
                            'Size' => $this->unloadConfig->webFunctionTmp(),
                        ],
                        'Layers' => array_merge([
                            $this->layer->fpm(),
                        ], $this->layer->extensions()),
                    ]),
                ],
                'CliFunction' => [
                    'Type' => 'AWS::Serverless::Function',
                    'Properties' => array_filter([
                        'FunctionName' => $this->unloadConfig->cliFunction(),
                        'MemorySize' => $this->unloadConfig->cliFunctionMemory(),
                        'Timeout' => $this->unloadConfig->cliFunctionTimeout(),
                        'Architectures' => [$this->unloadConfig->architecture()],
                        'PackageType' => 'Zip',
                        'Handler' => 'cli.php',
                        'ReservedConcurrentExecutions' => $this->unloadConfig->cliFunctionConcurrency(),
                        'ProvisionedConcurrencyConfig' => array_filter([
                            'ProvisionedConcurrentExecutions' => $this->unloadConfig->cliFunctionProvision(),
                        ]),
                        'EphemeralStorage' => [
                            'Size' => $this->unloadConfig->cliFunctionTmp(),
                        ],
                        'Layers' => array_merge([
                            $this->layer->php(),
                            $this->layer->console(),
                        ], $this->layer->extensions())
                    ]),
                ],
                'DeployFunction' => [
                    'Type' => 'AWS::Serverless::Function',
                    'Properties' => array_filter([
                        'FunctionName' => $this->unloadConfig->deployFunction(),
                        'PackageType' => 'Zip',
                        'Architectures' => ['arm64'],
                        'Policies' => [
                            [
                                'Version' => "2012-10-17",
                                'Statement' => [
                                    [
                                        'Effect' => 'Allow',
                                        'Action' => ['codedeploy:PutLifecycleEventHookExecutionStatus'],
                                        'Resource' => new TaggedValue('Sub', 'arn:${AWS::Partition}:codedeploy:${AWS::Region}:${AWS::AccountId}:deploymentgroup:${ServerlessDeploymentApplication}/*'),
                                    ]
                                ]
                            ],
                            [
                                'Version' => "2012-10-17",
                                'Statement' => [
                                    [
                                        'Effect' => 'Allow',
                                        'Action' => ['lambda:InvokeFunction'],
                                        'Resource' => [
                                            new TaggedValue('Sub', ['${CliFunctionArn}:*',  ['CliFunctionArn' => new TaggedValue('GetAtt', 'CliFunction.Arn')]]),
                                            new TaggedValue('Sub', ['${WebFunctionArn}:*',  ['WebFunctionArn' => new TaggedValue('GetAtt', 'WebFunction.Arn')]]),
                                        ],
                                    ]
                                ]
                            ]
                        ],
                        'DeploymentPreference' => [
                            'Enabled' => false,
                            'Role' => '',
                        ],
                        'Environment' => [
                            'Variables' => [
                                'CLI_FUNCTION' => new TaggedValue('Ref', 'CliFunction.Version'),
                                'CLI_COMMAND' => $this->unloadConfig->deploy(),
                                'WEB_FUNCTION' => new TaggedValue('Ref', 'WebFunction.Version'),
                                'WEB_CONCURRENCY' => $this->unloadConfig->defaultWarm(),
                            ],
                        ],
                        'Runtime' => 'nodejs12.x',
                        'Timeout' => 900,
                        'InlineCode' => Cloudformation::get('deploy.js'),
                        'Handler' => 'index.handler',
                    ]),
                ],
            ]
        ];
        return $this;
    }

    protected function append($key, $config): self
    {
        if (is_array($config)) {
            $templatePart = Arr::get($this->samTemplate, $key, []);
            $templatePart = array_merge_recursive($templatePart, $config);
            Arr::set($this->samTemplate, $key, $templatePart);
        } else {
            Arr::set($this->samTemplate, $key, $config);
        }

        return $this;
    }

    protected function get($key): mixed
    {
        return Arr::get($this->samTemplate, $key);
    }

    protected function forget($key): self
    {
        Arr::forget($this->samTemplate, $key);
        return $this;
    }

    protected function toYaml(): bool
    {
        return File::put(Path::tmpTemplate(), Yaml::dump($this->samTemplate, 15));
    }
}
