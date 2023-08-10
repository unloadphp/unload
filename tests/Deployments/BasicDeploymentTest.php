<?php

namespace Tests\Deployments;

use Aws\CloudFormation\CloudFormationClient;
use Tests\DeploysApplication;
use Tests\TestCase;

class BasicDeploymentTest extends TestCase
{
    use DeploysApplication;

    public function test_can_bootstrap_and_deploy_basic_application()
    {
        $cloudformation = new CloudFormationClient(['profile' => 'integration', 'region' => 'eu-central-1', 'version' => 'latest']);

        $this->setupTempDirectory();

        $this->process()->run('composer create-project laravel/laravel .');

        $this->setupLocalPackageRepository();

        $this->process()->run('composer require unload/unload-laravel');

        $bootstrap = $this->process()->run('../../../unload bootstrap --app=test --php=8.1 --env=production --provider=github --profile=integration --repository=test/example --no-interaction');

        $this->assertTrue(
            $bootstrap->seeInOutput('Project test bootstrapping has been completed.'),
            'Cloudformation environment configured succesfully.'
        );

        $this->assertContains(
            $cloudformation->describeStacks(['StackName' => 'unload-production-test-ci'])->search('Stacks[0].StackStatus'),
            ['UPDATE_COMPLETE', 'CREATE_COMPLETE'],
        );

        $this->assertContains(
            $cloudformation->describeStacks(['StackName' => 'unload-production-network'])->search('Stacks[0].StackStatus'),
            ['UPDATE_COMPLETE', 'CREATE_COMPLETE'],
        );

        $deploy = $this->process()->timeout(3600)->run('../../../unload deploy --config=unload.yaml --no-interaction');

        $this->assertFalse(
            $deploy->seeInOutput('AWS sam failed to deploy the stack. Please check cloudformation output for exact reason.'),
            'Application was deployed successfully.'
        );
    }
}
