<?php

namespace App;

use App\Tasks\CleanupVendorTask;
use Illuminate\Support\Facades\App;

class Task
{
    private array $tasks;

    public static function chain(array $tasks): self
    {
        $task = new self();
        $task->tasks = $tasks;
        return $task;
    }

    public function add($task): self
    {
        $this->tasks = array_merge($this->tasks, is_array($task) ? $task : [$task]);
        return $this;
    }

    public function when($condition, $task)
    {
        if ($condition) {
            $this->add($task);
        }
        return $this;
    }

    public function execute(Commands\Command $command): void
    {
        /** @var CleanupVendorTask $task */
        foreach($this->tasks as $task) {
            $command->step($task::class, fn() => App::call([$task, 'handle'], ['output' => $command->getOutput()]));
        }
    }
}
