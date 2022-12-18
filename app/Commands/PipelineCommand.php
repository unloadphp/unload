<?php

namespace App\Commands;

use App\Task;
use App\Tasks\GeneratePipelineTask;

class PipelineCommand extends Command
{
    protected $signature = 'pipeline {--stages=} {--provider=} {--definition=}';
    protected $description = 'Generate provider specific pipeline configuration';

    public function handle()
    {
        $this->info("Generating {$this->option('provider')} continuous integration pipeline for {$this->option('stages')} stage deployment");

        Task::chain([
            new GeneratePipelineTask($this->option('provider'), $this->option('stages'), $this->option('definition'))
        ])->execute($this);
    }
}



















