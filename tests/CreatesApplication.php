<?php

namespace Tests;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\File;

trait CreatesApplication
{
    /**
     * Creates the application.
     *
     * @return \Illuminate\Foundation\Application
     */
    public function createApplication()
    {
        $app = require __DIR__.'/../bootstrap/app.php';

        $app->make(Kernel::class)->bootstrap();
        $app['env'] = 'testing';

        return $app;
    }

    private function configureAwsTestCrendentials(): void
    {
        $credentials = base_path('tests/Fixtures/.aws/credentials');
        $id = env('AWS_ACCESS_KEY_ID');
        $secret = env('AWS_SECRET_ACCESS_KEY');

        File::makeDirectory(dirname($credentials), recursive: true, force: true);
        putenv('AWS_SHARED_CREDENTIALS_FILE='.$credentials);
        file_put_contents($credentials, <<<INI
[unload]
aws_access_key_id=
aws_secret_access_key=

[default]
aws_access_key_id=
aws_secret_access_key=

[integration]
aws_access_key_id=$id
aws_secret_access_key=$secret

INI);
    }
}
