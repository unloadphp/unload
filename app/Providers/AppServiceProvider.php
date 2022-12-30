<?php

namespace App\Providers;

use App\Commands\Command;
use App\Configs\UnloadConfig;
use Aws\Lambda\LambdaClient;
use Aws\S3\S3Client;
use Aws\Sts\StsClient;
use Illuminate\Console\Application as Artisan;
use Illuminate\Console\Events\ArtisanStarting;
use Illuminate\Support\Facades\App;
use Illuminate\Support\ServiceProvider;
use Rahul900Day\LaravelConsoleSpinner\SpinnerMixin;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->app->singleton(StsClient::class, function () {
            $unload = $this->app->make(UnloadConfig::class);
            $configuration = ['region' => $unload->region(), 'profile' => $unload->profile(), 'version' => 'latest'];
            return new StsClient($configuration);
        });
        $this->app->singleton(LambdaClient::class, function() {
            $unload = $this->app->make(UnloadConfig::class);
            $configuration = ['region' => $unload->region(), 'profile' => $unload->profile(), 'version' => 'latest'];
            return new LambdaClient($configuration);
        });
        $this->app->singleton(S3Client::class, function () {
            $unload = $this->app->make(UnloadConfig::class);
            $configuration = ['region' => $unload->region(), 'profile' => $unload->profile(), 'version' => 'latest'];
            return new S3Client($configuration);
        });
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        Artisan::starting(
            function ($artisan) {
                $version = shell_exec('sam --version 2> /dev/null');

                if (!$version) {
                    throw new \Exception('AWS SAM binary is required, but is not found on the system. See https://docs.aws.amazon.com/serverless-application-model/latest/developerguide/serverless-sam-cli-install.html');
                }

                $artisan->setVersion(app()->version() . "\n $version");
            }
        );
    }
}
