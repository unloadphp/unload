<?php

namespace Tests\Feature;

use Tests\TestCase;

class InspireCommandTest extends TestCase
{
    public function test_can_create_pipeline_configuration()
    {
        $this->artisan('inspire')->assertExitCode(0);
    }
}
