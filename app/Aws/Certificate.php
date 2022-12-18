<?php

namespace App\Aws;

use App\Cloudformation;
use App\Configs\UnloadConfig;
use App\Path;
use Aws\CloudFormation\CloudFormationClient;
use Aws\CloudFormation\Exception\CloudFormationException;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class Certificate
{
    private CloudFormationClient $cloudformation;
    private UnloadConfig $unload;
    private Domain $domain;

    public function __construct(UnloadConfig $unload, Domain $domain)
    {
        $this->cloudformation = new CloudFormationClient(['region' => 'us-east-1', 'profile' => $unload->profile(), 'version' => 'latest',]);
        $this->domain = $domain;
        $this->unload = $unload;
    }

    public function createStack(): ?PendingStack
    {
        if (!$this->unload->domains()) {
            return null;
        }

        $domains = $this->domain->listRoot();
        $stackName = $this->unload->certificateStackName();

        try {
            $this->cloudformation->describeStacks(['StackName' => $stackName])->get('Stacks');
            $this->cloudformation->updateStack([
                'StackName' => $stackName,
                'EnableTerminationProtection' => true,
                'TemplateBody' => Cloudformation::get("construct/certificate", compact('domains')),
                'Capabilities' => ['CAPABILITY_IAM'],
                'Tags' => $this->unload->unloadTags(),
            ]);
        } catch (CloudFormationException $e) {

            if (Str::of($e->getMessage())->contains('No updates are to be performed')) {
                return null;
            }

            $this->cloudformation->createStack([
                'StackName' => $stackName,
                'EnableTerminationProtection' => true,
                'TemplateBody' => Cloudformation::get("construct/certificate", compact('domains')),
                'Capabilities' => ['CAPABILITY_IAM'],
                'Tags' => $this->unload->unloadTags(),
            ]);
        }

        return new PendingStack($stackName, $this->cloudformation);
    }

    public function deleteStack(): ?PendingStack
    {
        try {
            $this->cloudformation->describeStacks(['StackName' => $this->unload->certificateStackName()])->get('Stacks');
            $this->cloudformation->deleteStack(['StackName' => $this->unload->certificateStackName()]);
            return new PendingStack($this->unload->certificateStackName(), $this->cloudformation);
        } catch (CloudFormationException) {
            return null;
        }
    }

    public function getCertificateArn(): string
    {
        $outputs = collect($this->cloudformation->describeStacks(['StackName' => $this->unload->certificateStackName()])
            ->search('Stacks[0].Outputs'))->keyBy('OutputKey');

        return $outputs->get('CertificateArn')['OutputValue'];
    }
}
