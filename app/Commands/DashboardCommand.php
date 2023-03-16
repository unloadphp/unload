<?php

namespace App\Commands;

use App\Aws\Dashboard;
use App\System;

class DashboardCommand extends Command
{
    protected $signature = 'dashboard';
    protected $description = 'Open application manager dashboard';

    public function handle(Dashboard $dashboard, System $system): int
    {
        $this->info('Generating dashboard for the application');

        return $system->browser($dashboard->generateUrl());
    }
}
