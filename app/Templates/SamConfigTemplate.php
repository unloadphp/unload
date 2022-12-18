<?php

namespace App\Templates;

use App\Aws\Certificate;
use App\Path;
use Aws\CloudFormation\CloudFormationClient;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\File;

class SamConfigTemplate extends Template
{
    public function make(): bool
    {
        list($assetBucket, $assumeRole) = $this->ciConfiguration();
        $vpcParameters = $this->vpcConfiguration();
        $certificate = $this->getCertificate();
        $assetHash = $this->calculateAssetHash();

        $profile = '';
        if($this->unloadConfig->profile()) {
            $profile = sprintf('profile = "%s"', $this->unloadConfig->profile());
        }

        return File::put(
            Path::tmpSamConfig(),
            <<<SAM
version = 0.1
[default]
[default.deploy]
[default.deploy.parameters]
stack_name = "{$this->unloadConfig->appStackName()}"
s3_bucket = "{$assetBucket}"
s3_prefix = "{$this->unloadConfig->app()}"
region = "{$this->unloadConfig->region()}"
role_arn = "{$assumeRole}"
confirm_changeset = false
capabilities = "CAPABILITY_IAM"
image_repositories = []
no_fail_on_empty_changeset = true
disable_rollback = false
{$profile}
parameter_overrides = "EnvAssetUrl=/assets/{$assetHash} CertificateArn=$certificate PipelineRoleArn={$assumeRole} $vpcParameters"
SAM
        );
    }

    protected function vpcConfiguration(): string
    {
        $vpcParameters = ' ';
        $cloudformation = new CloudFormationClient(['profile' => $this->unloadConfig->profile(), 'region' => $this->unloadConfig->region(), 'version' => 'latest']);
        collect($cloudformation->describeStacks(['StackName' => $this->unloadConfig->networkStackName()])->search('Stacks[0].Outputs'))
            ->each(function ($value) use (&$vpcParameters) {
                $vpcParameters .= "{$value['OutputKey']}={$value['OutputValue']} ";
            });
        return $vpcParameters;
    }

    protected function ciConfiguration(): array
    {
        $cloudformation = new CloudFormationClient(['profile' => $this->unloadConfig->profile(), 'region' => $this->unloadConfig->region(), 'version' => 'latest']);
        $outputs = collect($cloudformation->describeStacks(['StackName' => $this->unloadConfig->ciStackName()])->search('Stacks[0].Outputs'))->keyBy('OutputKey');
        return [
            str_replace('arn:aws:s3:::', '', $outputs->get('ArtifactsBucket')['OutputValue']),
            $outputs->get('CloudFormationExecutionRole')['OutputValue']
        ];
    }

    protected function calculateAssetHash(): string
    {
        $filesHash = [];
        foreach (File::allFiles(Path::tmpAssetDirectory()) as $file) {
            $filesHash[] = md5_file($file->getRealPath());
        }
        $assetHash = md5(implode(',', $filesHash));
        file_put_contents(Path::tmpAssetHash(), $assetHash);
        return $assetHash;
    }

    protected function getCertificate(): string
    {
        try {
            return App::make(Certificate::class)->getCertificateArn();
        } catch (\Exception $e) {
            return '';
        }
    }
}
