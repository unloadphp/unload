<?php

namespace App\Aws;

use App\Configs\UnloadConfig;
use Aws\Lambda\LambdaClient;

class Lambda
{
    private LambdaClient $lambda;
    private UnloadConfig $unload;

    public function __construct(LambdaClient $lambda, UnloadConfig $unload)
    {
        $this->lambda = $lambda;
        $this->unload = $unload;
    }

    public function exec(string $command): string
    {
        $command = json_encode($command);

        $response = $this->lambda->invoke([
            'FunctionName' => $this->unload->cliFunction(),
            'LogType' => 'Tail',
            'Payload' => $command,
        ]);

        $payload = json_decode($response->get('Payload')->getContents());

        if($response->get('FunctionError')) {
            $log = base64_decode($response->get('LogResult'));
            throw new \BadMethodCallException("$payload->errorMessage\n\n$log");
        }

        return $payload->output;
    }
}
