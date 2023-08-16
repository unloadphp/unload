<?php

namespace App\Aws;

use Aws\CloudFormation\CloudFormationClient;
use Aws\CloudFormation\Exception\CloudFormationException;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Output\ConsoleOutput;

class PendingStack
{
    private CloudFormationClient $cloudformation;
    private string $stackName;

    public function __construct(string $stackName, CloudFormationClient $cloudformation)
    {
        $this->cloudformation = $cloudformation;
        $this->stackName = $stackName;
    }

    public function wait($section = null): void
    {
        $section = $section ?? (new ConsoleOutput())->section();
        $stackEventsTable = new Table($section);
        $stackEventsTable->setStyle('compact');
        $stackEventsTable->render();

        $stackDescribeAttempts = 0;
        $stackInProgress = true;
        $stackEvents = [];

        while($stackInProgress) {
            try {
                foreach ($this->cloudformation->describeStackEvents(['StackName' => $this->stackName])->get('StackEvents') as $event) {
                    if (in_array($event['EventId'], $stackEvents)) {
                        continue;
                    }
                    $stackEvents[] = $event['EventId'];
                    $stackEventsTable->appendRow(
                        collect($event)->only(['ResourceStatus', 'ResourceType', 'LogicalResourceId'])->values()->prepend("\t")->toArray()
                    );
                }

                $stackStatus = $this->cloudformation->describeStacks(['StackName' => $this->stackName])->search('Stacks[0].StackStatus');
                if (in_array($stackStatus, ['CREATE_COMPLETE', 'UPDATE_COMPLETE', 'DELETE_COMPLETE', 'UPDATE_ROLLBACK_COMPLETE', 'ROLLBACK_COMPLETE'])) {
                    $stackInProgress = false;
                }
                $stackDescribeAttempts = 0;
            } catch (CloudFormationException $e) {

                if (str_contains($e->getAwsErrorMessage(), "Stack [$this->stackName] does not exist")) {
                    $stackInProgress = false;
                    continue;
                }

                if ($stackDescribeAttempts > 3) {
                    throw $e;
                }

                $stackDescribeAttempts++;
                sleep($stackDescribeAttempts*5);
            }
            sleep(0.5);
        }
    }
}
