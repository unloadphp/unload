<?php

namespace Tests;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\File;

trait CreatesApplication
{
    protected static $config = null;

    /**
     * Creates the application.
     *
     * @return \Illuminate\Foundation\Application
     */
    public function createApplication()
    {
        app()->instance('config', static::$config);

        $app = require __DIR__ . '/../bootstrap/app.php';
        $app->make(Kernel::class)->bootstrap();

        static::$config = app('config');

        if (!method_exists($this, 'setupLocalPackageRepository')) {
            $app['env'] = 'testing';
        }

        return $app;
    }
}
