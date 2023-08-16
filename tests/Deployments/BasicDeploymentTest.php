<?php

namespace Tests\Deployments;

use Aws\CloudFormation\CloudFormationClient;
use GuzzleHttp\Client;
use Tests\DeploysApplication;
use Tests\TestCase;

class BasicDeploymentTest extends TestCase
{
    use DeploysApplication;

    public function test_can_bootstrap_and_deploy_basic_application()
    {
        $cloudformation = new CloudFormationClient(['profile' => 'integration', 'region' => 'eu-central-1', 'version' => 'latest']);

        $this->setupTempDirectory();

        $this->process()->run('composer create-project laravel/laravel . 10.0');

        $this->setupLocalPackageRepository();

        $this->process()->run('composer require unload/unload-laravel --update-with-dependencies');

        $bootstrap = $this->process()->run('../../../unload bootstrap --app=test --php=8.1 --env=production --provider=github --profile=integration --repository=test/example --region=eu-central-1 --no-interaction');
        if (!$bootstrap->seeInOutput('Project test bootstrapping has been completed.')) {
            $this->fail($bootstrap->output());
        }

        $this->assertContains(
            $cloudformation->describeStacks(['StackName' => 'unload-production-test-ci'])->search('Stacks[0].StackStatus'),
            ['UPDATE_COMPLETE', 'CREATE_COMPLETE'],
        );

        $this->assertContains(
            $cloudformation->describeStacks(['StackName' => 'unload-production-network'])->search('Stacks[0].StackStatus'),
            ['UPDATE_COMPLETE', 'CREATE_COMPLETE'],
        );

        $deploy = $this->process()->timeout(3600)->run('../../../unload deploy --config=unload.yaml --no-interaction');
        if ($deploy->seeInOutput('ErrorException')) {
            $this->fail($deploy->output());
        }

        $appStack = $cloudformation->describeStacks(['StackName' => 'unload-production-test-app'])->search('Stacks[0]');

        $this->assertContains(
            $appStack['StackStatus'],
            ['UPDATE_COMPLETE', 'CREATE_COMPLETE'],
        );

        $browserResponse = (new Client)->get(collect($appStack['Outputs'])->pluck('OutputValue','OutputKey')->get('AppCloudfrontURL'));

        $this->assertEquals(200, $browserResponse->getStatusCode());
        $this->assertStringContainsString('Laravel News', $browserResponse->getBody()->getContents());

        $destroy = $this->process()->timeout(3600)->run('../../../unload destroy --config=unload.yaml --force --no-interaction');
        if ($destroy->seeInOutput('Error')) {
            $this->fail($deploy->output());
        }

        $this->expectExceptionMessage('Stack with id unload-production-test-app does not exist');
        $cloudformation->describeStacks(['StackName' => 'unload-production-test-app']);

        $this->expectExceptionMessage('Stack with id unload-production-test-app does not exist');
        $cloudformation->describeStacks(['StackName' => 'unload-production-test-ci']);

        $this->expectExceptionMessage('Stack with id unload-production-network does not exist');
        $cloudformation->describeStacks(['StackName' => 'unload-production-test-ci']);
    }
}
