<?php

namespace App\Constructs;

use App\Cloudformation;
use Illuminate\Support\Arr;
use Symfony\Component\Yaml\Tag\TaggedValue;

trait QueueConstruct
{
    protected function setupQueues(): self
    {
        if (!$this->unloadConfig->queues()) {
            return $this;
        }

        foreach ($this->unloadConfig->queues() as $queueName => $queueDefinition) {
            $queueWorkerFunction = ucfirst(strtolower($queueName)).'WorkerFunction';
            $queueStack = ucfirst(strtolower($queueName)).'QueueStack';
            $queueRef = new TaggedValue('GetAtt', "$queueStack.Outputs.Name");

            $this->append('Policies', [
                ['SQSSendMessagePolicy' => ['QueueName' => $queueRef],],
                ['SQSPollerPolicy' => ['QueueName' => $queueRef],]
            ]);

            $defaultQueueName = strtolower((string) array_keys($this->unloadConfig->queues())[0]);
            $variableName = $queueName == $defaultQueueName ? 'SQS_QUEUE' : 'SQS_QUEUE_'.strtoupper($queueName);
            $this->append('Globals.Function.Environment.Variables', [
                $variableName => $queueRef,
            ]);

            $this->append('Resources', [
                $queueWorkerFunction => [
                    'Type' => 'AWS::Serverless::Function',
                    'Properties' => array_filter([
                        'FunctionName' => $this->unloadConfig->workerFunction($queueName),
                        'MemorySize' => Arr::get($queueDefinition, 'memory', $this->unloadConfig->memory()),
                        'Timeout' => Arr::get($queueDefinition, 'timeout', $this->unloadConfig->timeout()),
                        'Architectures' => [$this->unloadConfig->architecture()],
                        'PackageType' => 'Zip',
                        'Handler' => 'worker.php',
                        'EphemeralStorage' => [
                            'Size' =>  Arr::get($queueDefinition,'tmp', $this->unloadConfig->tmp()),
                        ],
                        'ReservedConcurrentExecutions' => Arr::get($queueDefinition, 'concurrency', $this->unloadConfig->concurrency()),
                        'Events' => [
                            "{$queueStack}Event" => [
                                'Type' => 'SQS',
                                'Properties' => [
                                    'Queue' => new TaggedValue('GetAtt', "$queueStack.Outputs.Arn"),
                                ]
                            ]
                        ],
                        'Environment' => [
                            'Variables' => [
                                'SQS_WORKER_QUEUE' => $queueRef,
                            ],
                        ],
                        'Layers' => array_merge([
                            $this->layer->php(),
                        ], $this->layer->extensions()),
                    ]),
                ],
                $queueStack => [
                    'Type' => 'AWS::Serverless::Application',
                    'Properties' => [
                        'Tags' => $this->unloadConfig->unloadTagsPlain(),
                        'Location' => Cloudformation::compile("queue/standard.yaml"),
                        'Parameters' => array_filter([
                            'DelaySeconds' => Arr::get($queueDefinition, 'delay'),
                            'MessageRetentionPeriod' => (int) Arr::get($queueDefinition, 'retention', 60),
                            'ReceiveMessageWaitTimeSeconds' => (Arr::get($queueDefinition, 'polling') == 'long') ? 20 : 0,
                            'VisibilityTimeout' => Arr::get($queueDefinition, 'timeout', $this->unloadConfig->timeout()),
                            'MaxReceiveCount' => Arr::get($queueDefinition, 'tries'),
                        ])
                    ],
                ]
            ]);
        }

        return $this->append('Globals.Function.Environment.Variables', [
            'QUEUE_CONNECTION' => 'sqs',
            'SQS_PREFIX' => new TaggedValue('Sub', 'https://sqs.${AWS::Region}.amazonaws.com/${AWS::AccountId}'),
        ]);
    }
}
