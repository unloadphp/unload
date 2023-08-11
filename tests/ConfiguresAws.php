<?php

namespace Tests;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

trait ConfiguresAws
{
    public function setUpConfiguresAws(): void
    {
        $credentials = base_path('tests/Fixtures/.aws/credentials');
        putenv('AWS_SHARED_CREDENTIALS_FILE='.$credentials);

        if (File::exists($credentials)) {
            return;
        }

        $id = getenv('AWS_ACCESS_KEY_ID');
        $secret = getenv('AWS_SECRET_ACCESS_KEY');

        File::makeDirectory(dirname($credentials), recursive: true, force: true);
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
