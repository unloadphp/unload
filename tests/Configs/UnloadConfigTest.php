<?php

namespace Tests\Configs;

use App\Configs\UnloadConfig;
use Tests\TestCase;

class UnloadConfigTest extends TestCase
{
    public function test_fails_if_not_valid_lighsail_bucket_size()
    {
        $this->expectExceptionMessage('The properties must match schema: size');

        UnloadConfig::fromString(<<<YAML
version: 0.1
app: sample

profile: default
env: production
region: us-east-1
runtime: provided
php: 8.1

buckets:
  sample-bucket:
    access: private
    size: 24GB
YAML
);
    }

    public function test_fails_expiration_prefix_not_set_when_expiring_is_set()
    {
        $this->expectExceptionMessage("'expiration-prefix' property is required by 'expiration' property");

        UnloadConfig::fromString(<<<YAML
version: 0.1
app: sample

profile: default
env: production
region: us-east-1
runtime: provided

buckets:
  sample-bucket:
    access: private
    expiration: 1
YAML
        );
    }

    /** @dataProvider  missingPropertiesDataProvider*/
    public function test_invalid_configuration_fails($message, $template)
    {
        $this->expectExceptionMessage($message);

        UnloadConfig::fromString($template);
    }

    public function missingPropertiesDataProvider()
    {
        return [
            [
                'The required properties (app) are missing',
                <<<YAML
version: 0.1

env: production
region: us-east-1
runtime: provided
php: 8.8
YAML
            ],
            [
                'The required properties (env) are missing',
                <<<YAML
version: 0.1
app: sample

region: us-east-1
runtime: provided
php: 8.1
YAML
            ],
            [
            'The required properties (region) are missing',
                <<<YAML
version: 0.1
app: sample

env: production
php: 8.1
YAML
            ],
            [
                'The required properties (min-capacity) are missing',
                <<<YAML
version: 0.1
app: sample

env: production
region: us-east-1
php: 8.2

database:
    engine: aurora
    version: 5.7.mysql-aurora.2.07.3
YAML
            ],
            [
                'The properties must match schema: version',
                <<<YAML
version: 0.1
app: sample

env: production
region: us-east-1
php: 8.2

database:
    engine: aurora
    version: 5.7.mysql-aurora.2.07.3
    min-capacity: 1
    max-capacity: 1
    auto-pause: 30
    backup-retention: 0
YAML
            ],
        ];
    }
}
