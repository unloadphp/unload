---
# Copyright 2022 unload.sh
#
# Licensed under the Apache License, Version 2.0 (the "License");
# you may not use this file except in compliance with the License.
# You may obtain a copy of the License at
#
#     http://www.apache.org/licenses/LICENSE-2.0
#
# Unless required by applicable law or agreed to in writing, software
# distributed under the License is distributed on an "AS IS" BASIS,
# WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
# See the License for the specific language governing permissions and
# limitations under the License.
AWSTemplateFormatVersion: '2010-09-09'
Description: 'ACM: certificate for multiple domains'
Parameters:
    DistributionDomain:
        Type: String
Resources:

    <?php $index = 0; ?>
    <?php foreach($domains as $domain => $zone): ?>
    <?php $index++; ?>

    Route53RecordV2<?= $index ?>:

        Type: 'AWS::Route53::RecordSetGroup'
        Properties:
            HostedZoneId: <?= trim($zone) ?>

            RecordSets:
                -   Name: <?= trim($domain) ?>

                    Type: A
                    AliasTarget:
                        HostedZoneId: Z2FDTNDATAQYW2
                        DNSName: !Ref DistributionDomain

    Route53RecordIPv6<?= $index ?>:

        Type: 'AWS::Route53::RecordSetGroup'
        Properties:
            HostedZoneId: <?= trim($zone) ?>

            RecordSets:
                -   Name: <?= trim($domain) ?>

                    Type: AAAA
                    AliasTarget:
                        HostedZoneId: Z2FDTNDATAQYW2
                        DNSName: !Ref DistributionDomain

    <?php endforeach; ?>
