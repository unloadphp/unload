<?php

namespace Tests\Deployments;

use Aws\CloudFormation\CloudFormationClient;
use GuzzleHttp\Client;
use Tests\DeploysApplication;
use Tests\TestCase;

class ComplexDeploymentTest extends TestCase
{
    use DeploysApplication;

    public function test_can_bootstrap_advanced_application_configuration()
    {
        $cloudformation = new CloudFormationClient(['profile' => 'integration', 'region' => 'eu-central-1', 'version' => 'latest']);

        $this->setupTempDirectory();

        $this->process()->run('composer create-project laravel/laravel . 10.0');

        $this->setupLocalPackageRepository();

        $this->process()->run('composer require unload/unload-laravel --update-with-dependencies');

        $bootstrap = $this->process()->run('../../../unload bootstrap --app=complex --php=8.1 --env=production --provider=github --profile=integration --repository=test/example --region=eu-central-1 --no-interaction');
        if (!$bootstrap->seeInOutput('Project complex bootstrapping has been completed.')) {
            $this->fail($bootstrap->output());
        }

        $this->setupSampleApp();

        $deploy = $this->process()->timeout(3600)->run('../../../unload deploy --config=unload.yaml --no-interaction');
        if ($deploy->seeInOutput('ErrorException') || $deploy->seeInOutput('ParseException')) {
            $this->fail($deploy->output());
        }

        $appStack = $cloudformation->describeStacks(['StackName' => 'unload-production-complex-app'])->search('Stacks[0]');
        $this->assertContains(
            $appStack['StackStatus'],
            ['UPDATE_COMPLETE', 'CREATE_COMPLETE'],
        );

        $appUrl = collect($appStack['Outputs'])->pluck('OutputValue','OutputKey')->get('AppCloudfrontURL');

        $healthCheckResponse = (new Client)->get("$appUrl/test");
        $health = json_decode($healthCheckResponse->getBody()->getContents());

        $this->assertEquals(200, $healthCheckResponse->getStatusCode());
        $this->assertEquals("ok", $health->session->status);
        $this->assertEquals("ok", $health->assets->status);
        $this->assertEquals("ok", $health->cache->status);
        $this->assertEquals("ok", $health->db->status);
        $this->assertEquals("ok", $health->job->status);
        $this->assertEquals("ok", $health->view->status);
        $this->assertEquals("ok", $health->http->status);
        $this->assertEquals("ok", $health->disk->status);

        $destroy = $this->process()->timeout(3600)->run('../../../unload destroy --config=unload.yaml --force --no-interaction');
        if ($destroy->seeInOutput('Error')) {
            $this->fail($destroy->output());
        }

        $this->expectExceptionMessage('Stack with id unload-production-complex-app does not exist');
        $cloudformation->describeStacks(['StackName' => 'unload-production-complex-app']);

        $this->expectExceptionMessage('Stack with id unload-production-complex-app does not exist');
        $cloudformation->describeStacks(['StackName' => 'unload-production-complex-ci']);

        $cloudformation->updateTerminationProtection(['StackName' => 'unload-production-network', 'EnableTerminationProtection' => false]);
        $cloudformation->deleteStack(['StackName' => 'unload-production-network']);
    }
}
