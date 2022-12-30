<?php

namespace App\Constructs;

use Illuminate\Support\Arr;

trait EventConstruct
{
    protected function setupEvents(): self
    {
        $this->append('Resources.CliFunction.Properties.Events.Schedule', [
            'Type' => 'Schedule',
            'Properties' => [
                'Schedule' => 'rate(1 minute)',
                'Input' => '"schedule:run"'
            ]
        ]);

        $warmer = $this->unloadConfig->warm();
        if (is_array($warmer)) {
            $index = 0;
            $timezone = Arr::get($warmer, 'timezone', 'UTC');
            unset($warmer['timezone']);
            $timeStartAt = $this->getCronUTCHourByTimezone(8, $timezone);
            $timeEndAt = $this->getCronUTCHourByTimezone(22, $timezone);
            $preset = [
                'day' => "cron(0/5 $timeStartAt-$timeEndAt ? * * *)",
                'night' => "cron(0/5 $timeEndAt-$timeStartAt ? * * *)"
            ];

            foreach($warmer as $expression => $concurrency) {
                $index++;
                $this->append("Resources.DeployFunction.Properties.Events.Warmer$index", [
                    'Type' => 'ScheduleV2',
                    'Properties' => [
                        'ScheduleExpression' => Arr::get($preset, $expression, $expression),
                        'ScheduleExpressionTimezone' => $timezone,
                        'RetryPolicy' => [
                            'MaximumRetryAttempts' => 0,
                        ],
                        'Input' => sprintf('{"Warmer": true, "WarmerConcurrency": %d}', $concurrency)
                    ]
                ]);
            }
        } elseif ($warmer > 0) {
            $this->append('Resources.DeployFunction.Properties.Events.Warmer', [
                'Type' => 'Schedule',
                'Properties' => [
                    'Schedule' => 'rate(5 minutes)',
                    'RetryPolicy' => [
                        'MaximumRetryAttempts' => 0,
                    ],
                    'Input' => sprintf('{"Warmer": true, "WarmerConcurrency": %d}', (int) $warmer)
                ]
            ]);
        }

        return $this;
    }

    protected function getCronUTCHourByTimezone(int $hour, string $timezone): string
    {
        return (new \DateTime("$hour:00:00", new \DateTimeZone($timezone)))
            ->setTimezone(new \DateTimeZone('UTC'))
            ->format('H');
    }
}
