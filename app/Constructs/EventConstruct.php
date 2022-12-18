<?php

namespace App\Constructs;

trait EventConstruct
{
    protected function setupEvents(): self
    {
//        $this->append('Resources.WebFunction.Properties.Events.HttpApi', [
//            'Type' => 'HttpApi',
//            'Properties' => [
//                'Method' => 'ANY',
//                'Path' => '/{proxy+}',
//                'RouteSettings' => [
//                    'ThrottlingBurstLimit' => $this->unloadConfig->firewallBurstLimit(),
//                    'ThrottlingRateLimit' => $this->unloadConfig->firewallRateLimit(),
//                ]
//            ]
//        ]);

        $this->append('Resources.CliFunction.Properties.Events.Schedule', [
            'Type' => 'Schedule',
            'Properties' => [
                'Schedule' => 'rate(1 minute)',
                'Input' => '"schedule:run"'
            ]
        ]);

        if ($this->unloadConfig->warm()) {
            $this->append('Resources.WebFunction.Properties.Events.Warmer', [
                'Type' => 'Schedule',
                'Properties' => [
                    'Schedule' => 'rate(5 minutes)',
                    'Input' => '{"warmer": true}'
                ]
            ]);
        }

        return $this;
    }
}
