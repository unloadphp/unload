<?php

namespace App\Constructs;

use App\Cloudformation;
use Symfony\Component\Yaml\Tag\TaggedValue;

trait NetworkConstruct
{
    protected function setupNetwork(): self
    {
        $subnetIds = new TaggedValue('Split', [',', new TaggedValue('Ref', 'VpcSubnetsPrivate')]);
        $vpcConfig = [
            'SecurityGroupIds' => [
                new TaggedValue(
                    'GetAtt',
                    'SecurityGroupStack.Outputs.ClientSecurityGroup'
                ),
            ],
            'SubnetIds' => new TaggedValue(
                'If',
                [
                    'HasSingleAZ',
                    new TaggedValue('Split', [',', new TaggedValue('Select', [0, $subnetIds])]),
                    $subnetIds
                ]
            ),
        ];

        $vpcStack = [
            'SecurityGroupStack' => [
                'Type' => 'AWS::Serverless::Application',
                'Properties' => [
                    'Tags' => $this->unloadConfig->unloadTagsPlain(),
                    'Location' => Cloudformation::compile("network/vpc-sg.yaml"),
                    'Parameters' => [
                        'VpcId'  => new TaggedValue('Ref', 'VpcId'),
                    ],
                ],
            ],
        ];

        return $this->append('Globals.Function.VpcConfig', $vpcConfig)->append('Resources', $vpcStack);
    }
}
