<?php

namespace App\Tasks;

use Symfony\Component\Process\InputStream;
use Symfony\Component\Process\Process;

class GeneratePipelineTask
{
    const USE_CUSTOM_PIPELINE_TEMPLATE = "2\n";

    private string $provider;
    private string $stages;
    private string $definition;

    public function __construct(string $provider, string $stages, string $definition = '')
    {
        $this->provider = $provider;
        $this->stages = $stages;
        $this->definition = $definition;
    }

    public function handle(): void
    {
        if ($this->definition) {
            $tmp = $this->definition;
        } else {
            $tmp = tempnam(sys_get_temp_dir(),'');
            unlink($tmp);
            mkdir($tmp);
            $clone = Process::fromShellCommandline("git clone https://github.com/unloadphp/unload-pipeline.git $tmp");
            $clone->run(function($out, $text) {
                echo $text;
            });
        }

        $templateProvider = [
            'github' => 'GitHub-Actions',
            'bitbucket' => 'Bitbucket-Pipelines',
        ][$this->provider];
        $templateName = [
            1 => 'one-stage-pipeline-template',
            2 => 'two-stage-pipeline-template',
        ][$this->stages];
        $tmpGithubActions = "$tmp/$templateProvider/$templateName\n";

        $input = new InputStream();
        $input->write(self::USE_CUSTOM_PIPELINE_TEMPLATE);
        $input->write($tmpGithubActions);

        $process = Process::fromShellCommandline("sam pipeline init");
        $process->setTimeout(null);
        $process->setInput($input);
        $stream = fopen('php://stdin', 'w+');
        $input->write($stream);

        $process->start(function ($output, $text) use ($input, $process, &$builder) {
            echo $text;

            if (str_contains($text, 'Successfully created the pipeline configuration file(s)')) {
                $input->close();
                $process->stop();
            }
        });

        $process->wait();
    }
}
