<?php

namespace App\Aws;

use App\Configs\UnloadConfig;
use Aws\CloudFormation\CloudFormationClient;
use Aws\CloudFormation\Exception\CloudFormationException;
use Aws\S3\S3Client;
use Symfony\Component\Yaml\Tag\TaggedValue;
use Symfony\Component\Yaml\Yaml;

class Network
{
    private CloudFormationClient $cloudformation;
    private S3Client $s3;
    private UnloadConfig $unload;
    private ContinuousIntegration $ci;

    public function __construct(UnloadConfig $unload, ContinuousIntegration $ci)
    {
        $this->cloudformation = new CloudFormationClient(['region' => $unload->region(), 'profile' => $unload->profile(), 'version' => 'latest',]);
        $this->s3 = new S3Client(['region' => $unload->region(), 'profile' => $unload->profile(), 'version' => 'latest',]);
        $this->ci = $ci;
        $this->unload = $unload;
    }

    public function createStack($vpc, $nat, $requiresSshAccess): PendingStack
    {
        $artifactsBucketName = $this->ci->getArtifactsBucketName();
        $requiresBastionHost = $requiresSshAccess && $nat == 'gateway';
        $bastionSshSecurityGroupId = '';

        $vpcTemplatePath = "cloudformation/network/vpc-$vpc.yaml";
        $vpcTemplateUrl = $this->s3->putObject([
            'Bucket' => $artifactsBucketName,
            'Key'    => $vpcTemplatePath,
            'Body'   => fopen(base_path($vpcTemplatePath), 'r'),
        ])->get('ObjectURL');

        $natTemplatePath = "cloudformation/network/nat-$nat.yaml";
        $natTemplateUrl = $this->s3->putObject([
            'Bucket' => $artifactsBucketName,
            'Key'    => $natTemplatePath,
            'Body'   => fopen(base_path($natTemplatePath), 'r'),
        ])->get('ObjectURL');

        $bastionTemplatePath = "cloudformation/network/ssh-bastion.yaml";
        $bastionTemplateUrl = $this->s3->putObject([
            'Bucket' => $artifactsBucketName,
            'Key'    => $bastionTemplatePath,
            'Body'   => fopen(base_path($bastionTemplatePath), 'r'),
        ])->get('ObjectURL');

        $securityGroupTemplatePath = "cloudformation/network/vpc-sg.yaml";
        $securityGroupTemplateUrl = $this->s3->putObject([
            'Bucket' => $artifactsBucketName,
            'Key'    => $securityGroupTemplatePath,
            'Body'   => fopen(base_path($securityGroupTemplatePath), 'r'),
        ])->get('ObjectURL');

        $resources = [
            'VpcStack' => [
                'Type' => 'AWS::CloudFormation::Stack',
                'Properties' => [
                    'Tags' => $this->unload->unloadGlobalTags(),
                    'TemplateURL' => $vpcTemplateUrl,
                ],
            ],
            'BastionSecurityGroupStack' => [
                'Type' => 'AWS::CloudFormation::Stack',
                'Properties' => [
                    'Tags' => $this->unload->unloadGlobalTags(),
                    'TemplateURL' => $securityGroupTemplateUrl,
                    'Parameters' => [
                        'VpcId' => new TaggedValue('GetAtt', 'VpcStack.Outputs.VPC'),
                    ],
                ],
            ]
        ];

        foreach (['1az' => ['A'], '2az' => ['A', 'B'],][$vpc] as $subnetZone) {
            if ($nat == 'gateway') {
                $stackName ="Nat{$subnetZone}SubnetZoneStack";
                $resources[$stackName] = [
                    'Type' => 'AWS::CloudFormation::Stack',
                    'Properties' => [
                        'Tags' => $this->unload->unloadGlobalTags(),
                        'TemplateURL' => $natTemplateUrl,
                        'Parameters' => [
                            'VpcRouteTablePrivate' => new TaggedValue('GetAtt', "VpcStack.Outputs.RouteTable{$subnetZone}Private"),
                            'VpcSubnetPublic' => new TaggedValue('GetAtt', "VpcStack.Outputs.Subnet{$subnetZone}Public"),
                        ]
                    ],
                ];

                continue;
            }

            $stackName ="Nat{$subnetZone}SubnetZoneStack";
            $resources[$stackName] = [
                'Type' => 'AWS::CloudFormation::Stack',
                'Properties' => [
                    'Tags' => $this->unload->unloadGlobalTags(),
                    'TemplateURL' => $natTemplateUrl,
                    'Parameters' => [
                        'VpcId' => new TaggedValue('GetAtt', 'VpcStack.Outputs.VPC'),
                        'VpcCidrBlock' => new TaggedValue('GetAtt', 'VpcStack.Outputs.CidrBlock'),
                        'VpcRouteTablePrivate' => new TaggedValue('GetAtt', "VpcStack.Outputs.RouteTable{$subnetZone}Private"),
                        'VpcSubnetPublic' => new TaggedValue('GetAtt', "VpcStack.Outputs.Subnet{$subnetZone}Public"),
                        'IAMUserSSHAccess' => $requiresSshAccess ? 'true' : 'false',
                        'BastionSecurityGroupId' => new TaggedValue('GetAtt', 'BastionSecurityGroupStack.Outputs.ClientSecurityGroup'),
                    ]
                ],
            ];
        }

        if ($requiresBastionHost) {
            $resources["BastionSSHInstanceStack"] = [
                'Type' => 'AWS::CloudFormation::Stack',
                'Properties' => [
                    'Tags' => $this->unload->unloadGlobalTags(),
                    'TemplateURL' => $bastionTemplateUrl,
                    'Parameters' => [
                        'VpcId' => new TaggedValue('GetAtt', 'VpcStack.Outputs.VPC'),
                        'VpcCidrBlock' => new TaggedValue('GetAtt', 'VpcStack.Outputs.CidrBlock'),
                        'VpcSubnetsPublic' => new TaggedValue('GetAtt', "VpcStack.Outputs.SubnetsPublic"),
                        'BastionSecurityGroupId' => new TaggedValue('GetAtt', 'BastionSecurityGroupStack.Outputs.ClientSecurityGroup'),
                    ]
                ],
            ];
        }

        $template =  Yaml::dump([
            'AWSTemplateFormatVersion' => '2010-09-09',
            'Description' => 'VPC: network resources',
            'Resources' => $resources,
            'Outputs' => [
                'VpcId' => [
                    'Description' => 'VPC',
                    'Value' => new TaggedValue('GetAtt', 'VpcStack.Outputs.VPC'),
                    'Export' => [
                        'Name' => new TaggedValue('Sub', '${AWS::StackName}-VPC'),
                    ]
                ],
                'VpcCidrBlock' => [
                    'Description' => 'VPC CIDR Block',
                    'Value' => new TaggedValue('GetAtt', 'VpcStack.Outputs.CidrBlock'),
                    'Export' => [
                        'Name' => new TaggedValue('Sub', '${AWS::StackName}-CidrBlock'),
                    ]
                ],
                'VpcRouteTablesPrivate' => [
                    'Description' => 'List Of Private Route Tables',
                    'Value' => new TaggedValue('GetAtt', 'VpcStack.Outputs.RouteTablesPrivate'),
                    'Export' => [
                        'Name' => new TaggedValue('Sub', '${AWS::StackName}-RouteTablesPrivate'),
                    ]
                ],
                'VpcRouteTablesPublic' => [
                    'Description' => 'List Of Public Route Tables',
                    'Value' => new TaggedValue('GetAtt', 'VpcStack.Outputs.RouteTablesPublic'),
                    'Export' => [
                        'Name' => new TaggedValue('Sub', '${AWS::StackName}-RouteTablesPublic'),
                    ]
                ],
                'VpcSubnetsPrivate' => [
                    'Description' => 'List Of Private Subnets',
                    'Value' => new TaggedValue('GetAtt', 'VpcStack.Outputs.SubnetsPrivate'),
                    'Export' => [
                        'Name' => new TaggedValue('Sub', '${AWS::StackName}-SubnetsPrivate'),
                    ]
                ],
                'VpcSubnetsPublic' => [
                    'Description' => 'List Of Public Subnets',
                    'Value' => new TaggedValue('GetAtt', 'VpcStack.Outputs.SubnetsPublic'),
                    'Export' => [
                        'Name' => new TaggedValue('Sub', '${AWS::StackName}-SubnetsPublic'),
                    ]
                ],
                'VpcBastionSecurityGroupId' => [
                    'Description' => 'Bastion Security Group Id',
                    'Value' => new TaggedValue('GetAtt', 'BastionSecurityGroupStack.Outputs.ClientSecurityGroup'),
                    'Export' => [
                        'Name' => new TaggedValue('Sub', '${AWS::StackName}-BastionSecurityGroupId'),
                    ]
                ],
                'VpcAZsNumber' => [
                    'Description' => 'Number of AZs',
                    'Value' => new TaggedValue('GetAtt', 'VpcStack.Outputs.NumberOfAZs'),
                    'Export' => [
                        'Name' => new TaggedValue('Sub', '${AWS::StackName}-AZs'),
                    ]
                ],
                'VpcAZs' => [
                    'Description' => 'List of AZs',
                    'Value' => new TaggedValue('GetAtt', 'VpcStack.Outputs.AZs'),
                    'Export' => [
                        'Name' => new TaggedValue('Sub', '${AWS::StackName}-AZList'),
                    ]
                ],
            ],
        ]);

        $stackName = $this->unload->networkStackName();
        try {
            $this->cloudformation->describeStacks(['StackName' => $stackName])->get('Stacks');
            $this->cloudformation->updateStack([
                'StackName' => $stackName,
                'EnableTerminationProtection' => true,
                'TemplateBody' => $template,
                'Capabilities' => ['CAPABILITY_IAM'],
                'Tags' => $this->unload->unloadGlobalTags(),
            ]);
        } catch (CloudFormationException) {
            $this->cloudformation->createStack([
                'StackName' => $stackName,
                'EnableTerminationProtection' => true,
                'TemplateBody' => $template,
                'Capabilities' => ['CAPABILITY_IAM'],
                'Tags' => $this->unload->unloadGlobalTags(),
            ]);
        }

        return new PendingStack($stackName, $this->cloudformation);
    }
}
