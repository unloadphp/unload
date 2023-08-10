<?php

namespace Tests;

use Illuminate\Support\Facades\File;

trait ConfiguresAws
{
    public function setUpConfiguresAws(): void
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

    public function tearDownConfiguresAws(): void
    {
        unlink(base_path('tests/Fixtures/.aws/credentials'));
        rmdir(base_path('tests/Fixtures/.aws'));
    }
}
