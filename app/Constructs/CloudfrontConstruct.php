<?php

namespace App\Constructs;

use App\Cloudformation;
use Symfony\Component\Yaml\Tag\TaggedValue;

trait CloudfrontConstruct
{
    protected function setupCloudfront(): self
    {
        $domains = implode(',', $this->unloadConfig->domains());

        $this->append('Resources', [
            'CloudfrontStack' => [
                'Type' => 'AWS::Serverless::Application',
                'Properties' => [
                    'Tags' => $this->unloadConfig->unloadTagsPlain(),
                    'Location' => Cloudformation::compile("construct/website.yaml"),
                    'Parameters' => [
                        'PipelineRoleArn' => new TaggedValue('Ref', 'PipelineRoleArn'),
                        'GeoRestrictionLocations' => implode(',', $this->unloadConfig->firewallGeoLocations()),
                        'GeoRestrictionType' => $this->unloadConfig->firewallGeoType(),
                        'Domains' => $domains,
                        'ExistingCertificate' => $this->unloadConfig->domains() ?  new TaggedValue('Ref', 'CertificateArn') : '',
                        'DistributionName' =>  $this->unloadConfig->appStackName(),
//                        'EndpointOriginDomain' => new TaggedValue('Sub', '${ServerlessHttpApi}.execute-api.${AWS::Region}.amazonaws.com'),
                        'EndpointOriginDomain' => new TaggedValue('Select', [2, new TaggedValue('Split', ['/', new TaggedValue('GetAtt', 'WebFunctionUrl.FunctionUrl')])]),
                    ],
                ],
            ]
        ]);

        if ($domains) {
            $this->append('Outputs', [
                'AppCloudfrontDomains' => [
                    'Description' => 'Application cloudfront domains',
                    'Value' => $domains
                ],
            ]);
        }

        return $this->append('Outputs', [
            'AppCloudfrontURL' => [
                'Description' => 'Application cloudfront url',
                'Value' => new TaggedValue('GetAtt', 'CloudfrontStack.Outputs.URL'),
            ],
            'AppAssetBucketArn' => [
                'Description' => 'Application assets bucket arn',
                'Value' => new TaggedValue('GetAtt', 'CloudfrontStack.Outputs.AssetsBucketArn'),
            ],
        ]);
    }
}
