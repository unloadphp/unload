<?php

namespace App\Aws;

use App\Configs\UnloadConfig;
use App\Oidcs\OidcInterface;
use Aws\CloudFormation\CloudFormationClient;
use Aws\CloudFormation\Exception\CloudFormationException;
use Aws\Iam\IamClient;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class ContinuousIntegration
{
    private CloudFormationClient $cloudformation;
    private IamClient $iam;
    private UnloadConfig $unload;
    private static $memoize = null;

    public function __construct(UnloadConfig $unload)
    {
        $this->cloudformation = new CloudFormationClient(['region' => $unload->region(), 'profile' => $unload->profile(), 'version' => 'latest',]);
        $this->iam = new IamClient(['region' => $unload->region(), 'profile' => $unload->profile(), 'version' => 'latest',]);
        $this->unload = $unload;
    }

    public function createStack(OidcInterface $provider): PendingStack
    {
        $oidcArn = $this->getOpenIDConnectProviderArn($provider);
        $parameters = [
            [
                'ParameterKey' => 'Application',
                'ParameterValue' => $this->unload->app(),
            ],
            [
                'ParameterKey' => 'Env',
                'ParameterValue' => $this->unload->env(),
            ],
            [
                'ParameterKey' => 'IdentityProviderThumbprint',
                'ParameterValue' => $provider->thumbprint(),
            ],
            [
                'ParameterKey' => 'OidcClientId',
                'ParameterValue' => $provider->audience(),
            ],
            [
                'ParameterKey' => 'OidcProviderUrl',
                'ParameterValue' => $provider->url(),
            ],
            [
                'ParameterKey' => 'SubjectClaim',
                'ParameterValue' => $provider->claim(),
            ],
            [
                'ParameterKey' => 'CreateNewOidcProvider',
                'ParameterValue' => $oidcArn ? "false" : "true",
            ],
        ];

        try {
            $this->cloudformation->describeStacks(['StackName' => $this->unload->ciStackName()])->get('Stacks');
            $this->cloudformation->updateStack([
                'StackName' => $this->unload->ciStackName(),
                'TemplateBody' => file_get_contents(base_path('cloudformation/construct/ci.yaml')),
                'EnableTerminationProtection' => true,
                'OnFailure' => 'DELETE',
                'Capabilities' => ['CAPABILITY_IAM', 'CAPABILITY_AUTO_EXPAND'],
                'TimeoutInMinutes' => 5,
                'Parameters' => $parameters,
                'Tags' => $this->unload->unloadTags()
            ]);
        } catch (CloudFormationException) {
            $this->cloudformation->createStack([
                'StackName' => $this->unload->ciStackName(),
                'TemplateBody' => file_get_contents(base_path('cloudformation/construct/ci.yaml')),
                'EnableTerminationProtection' => true,
                'OnFailure' => 'DELETE',
                'Capabilities' => ['CAPABILITY_IAM', 'CAPABILITY_AUTO_EXPAND'],
                'TimeoutInMinutes' => 5,
                'Parameters' => $parameters,
                'Tags' => $this->unload->unloadTags()
            ]);
        }

        return new PendingStack($this->unload->ciStackName(), $this->cloudformation);
    }

    public function deleteStack(): ?PendingStack
    {
        try {
            $this->cloudformation->describeStacks(['StackName' => $this->unload->ciStackName()])->get('Stacks');
            $this->cloudformation->deleteStack(['StackName' => $this->unload->ciStackName()]);
            return new PendingStack($this->unload->ciStackName(), $this->cloudformation);
        } catch (CloudFormationException) {
            return null;
        }
    }

    public function getPipelineExecutionRoleArn(): string
    {
        return $this->stackOutput()->get('PipelineExecutionRole')['OutputValue'];
    }

    public function getArtifactsBucketName(): string
    {
        $bucketArn = $this->stackOutput()->get('ArtifactsBucket')['OutputValue'];
        return Str::of($bucketArn)->replace('arn:aws:s3:::', '')->toString();
    }

    public function getCloudformationRole(): string
    {
        return $this->stackOutput()->get('CloudFormationExecutionRole')['OutputValue'];
    }

    public function getOpenIDConnectProviderArn(OidcInterface $oidc): ?string
    {
        $condition = str_replace('https://', '', $oidc->url());

        $arn = $this->iam->listOpenIDConnectProviders()->search(
            "OpenIDConnectProviderList[?contains(Arn, '$condition')].Arn"
        )[0] ?? null;

        return $arn;
    }

    public function getAssetsBucketName(): string
    {
        $outputs = collect($this->cloudformation->describeStacks(['StackName' => $this->unload->appStackName()])
            ->search('Stacks[0].Outputs'))->keyBy('OutputKey');

        $bucketArn = $outputs->get('AppAssetBucketArn')['OutputValue'];
        return Str::of($bucketArn)->replace('arn:aws:s3:::', '')->toString();
    }

    public function applicationStackExists(): bool
    {
        try {
            $this->cloudformation->describeStacks(['StackName' => $this->unload->appStackName()]);
            return true;
        } catch (CloudFormationException $e) {
            return false;
        }
    }

    protected function stackOutput(): Collection
    {
        if(is_null(self::$memoize)) {
            self::$memoize = collect($this->cloudformation->describeStacks(['StackName' => $this->unload->ciStackName()])
                ->search('Stacks[0].Outputs'))->keyBy('OutputKey');
        }
        return self::$memoize;
    }
}
