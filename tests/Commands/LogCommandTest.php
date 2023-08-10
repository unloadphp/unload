<?php

namespace Tests\Commands;

use Illuminate\Support\Facades\Process;
use Tests\TestCase;

class LogCommandTest extends TestCase
{
    public function test_can_query_log_history()
    {
        $configuration = base_path('tests/Fixtures/unload.yaml');

        Process::fake();

        $this->artisan('log', [
            'function' => 'web',
            '--start' => '2018-01-01 10:10:10',
            '--end' => '2019-01-01 10:10:10',
            '--filter' => 'test',
            '--tail' => true,
            '--config' => $configuration
        ])
        ->assertSuccessful()
        ->execute();

        Process::assertRan(fn ($process, $result) =>
            $process->command === 'sam logs --profile=default --region=us-east-1 --stack-name=unload-production-sample-app --name=WebFunction --s=2018-01-01 10:10:10 --e=2018-01-01 10:10:10 --filter=test --tail '
        );
    }
}
