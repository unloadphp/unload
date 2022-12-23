<?php

namespace App\Commands;

use App\Configs\BootstrapConfig;
use App\Task;
use App\Tasks\CleanupPipelineConfigTask;
use App\Tasks\GeneratePipelineTask;
use App\Tasks\GeneratePipelineTemplateTask;
use App\Tasks\GenerateUnloadTemplateTask;
use App\Tasks\InitContinuousIntegrationTask;
use App\Tasks\InitEnvironmentTask;
use App\Tasks\InitNetworkTask;
use Aws\Credentials\CredentialProvider;
use Aws\Exception\CredentialsException;

class BootstrapCommand extends Command
{
    protected $signature = 'bootstrap';
    protected $description = 'Bootstrap an environment and application template';

    const REGIONS = [
        'us-east-2',
        'us-east-1',
        'us-west-1',
        'us-west-2',
        'af-south-1',
        'ap-east-1',
        'ap-southeast-3',
        'ap-south-1',
        'ap-northeast-3',
        'ap-northeast-2',
        'ap-southeast-1',
        'ap-southeast-2',
        'ap-northeast-1',
        'ca-central-1',
        'eu-central-1',
        'eu-west-1',
        'eu-west-2',
        'eu-south-1',
        'eu-west-3',
        'eu-north-1',
        'me-south-1',
        'me-central-1',
        'sa-east-1',
    ];

    public function handle(): void
    {
        $app = $this->ask('App name (example: sample)', null);
        $php = $this->choice('App php version', ['8.0', '8.1', '8.2'], '8.1');
        $environments = explode('/', $this->choice('App environment', ['production', 'production/development'], 'production'));
        $provider = $this->choice('App repository provider', ['github', 'bitbucket'], 'github');
        $repository = $this->ask('App repository path (example: username/app-name)', null);
        $audience = null;
        $repositoryUuid = '';

        if ($provider == 'bitbucket') {
            $this->line(" Bitbucket OIDC integration requires Audience and Repository UUID.");
            $this->line(" Visit https://bitbucket.org/$repository/admin/addon/admin/pipelines/openid-connect to find yours.");
            $audience = $this->ask('Bitbucket audience');
            $repositoryUuid = $this->ask('Bitbucket repository uuid');
        }

        $tasks = Task::chain([new CleanupPipelineConfigTask()]);

        foreach($environments as $env) {
            $envName = ucfirst($env);
            $this->info($envName);

            $region = $this->askWithCompletion("$envName aws region", self::REGIONS);
            $branch = $this->ask("$envName $provider branch", 'master');
            $profile = $this->ask("$envName aws profile", "$app-$env");

            try {
                call_user_func(CredentialProvider::ini($profile))->wait();
            } catch (CredentialsException $e) {
                $this->warn("Profile $provider not found at ~/.aws/credentials. Please create one and then retry.");
                $this->error($e->getMessage());
                return;
            }

            $vpc = $this->choice("$envName aws vpc size", ["1az", "2az"], "1az");
            $nat = $this->choice("$envName aws nat type", ["gateway", "instance"], "instance");
            $ssh = $this->confirm("$envName aws vpc ssh access (allow database connection from internet)", true);

            $bootstrap = new BootstrapConfig(compact(
            'region', 'profile', 'env', 'app', 'repositoryUuid',
                'provider', 'repository', 'branch', 'vpc', 'nat', 'audience', 'php', 'ssh'
            ));

            $tasks->add(new GenerateUnloadTemplateTask($bootstrap));
            $tasks->add(new InitContinuousIntegrationTask($bootstrap));
            $tasks->add(new InitNetworkTask($bootstrap));
            $tasks->add(new InitEnvironmentTask($bootstrap));
            $tasks->add(new GeneratePipelineTemplateTask($bootstrap));
        }

        $tasks->add([
            new GeneratePipelineTask($provider, count($environments)),
            new CleanupPipelineConfigTask()
        ])->execute($this);
    }
}
