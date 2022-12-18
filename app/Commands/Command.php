<?php

namespace App\Commands;

use App\Configs\UnloadConfig;
use Illuminate\Console\View\Components\Info;
use Illuminate\Console\View\Components\Task;
use Illuminate\Support\Facades\App;
use LaravelZero\Framework\Commands\Command as ZeroCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\ConsoleSectionOutput;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\OutputInterface;

abstract class Command extends ZeroCommand
{
    protected ?UnloadConfig $unload = null;
    protected ?ConsoleSectionOutput $section = null;

    public function __construct()
    {
        parent::__construct();
        $this->addOption('config', 'c', InputOption::VALUE_OPTIONAL);
    }

    public function initialize(InputInterface $input, OutputInterface $output)
    {
        parent::initialize($input, $output);
        $config = UnloadConfig::fromCommand($input);
        App::singleton(UnloadConfig::class, fn () => $config);
        $this->section = (new ConsoleOutput())->section();
        $this->unload = $config;
    }

    public function call($command, array $arguments = [])
    {
        $arguments = collect($this->options())->mapWithKeys(fn($value, $key) => ["--$key" => $value])->merge($arguments);
        return parent::call($command, $arguments->toArray());
    }

    public function step($description, $task = null, bool $nested = false): mixed
    {
        $result = null;

        if ($task) {
            $task = function () use (&$result, $task, $description, $nested) {
                $result = $task();
                $this->output->write("\033[10000D");
                $this->output->write("  $description ");
                return $result;
            };
        }

        with(new Task(
            $this->output ?: new NullOutput()
        ))->render($description, $task);

        return $result;
    }

    public function info($string, $verbosity = null): void
    {
        with(new Info(
            $this->output ?: new NullOutput()
        ))->render($string, $verbosity ??= OutputInterface::VERBOSITY_NORMAL);
    }
}
