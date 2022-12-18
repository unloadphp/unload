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
Resources:
  Certificate:
    Type: 'AWS::CertificateManager::Certificate'
    DeletionPolicy: Retain
    Properties:
      DomainName: <?= $domains->keys()->first() ?>

      DomainValidationOptions:
        <?php foreach($domains as $domain => $zone): ?>

        - DomainName: '<?= trim($domain) ?>'

          HostedZoneId: <?= trim($zone) ?>

        <?php endforeach; ?>

      SubjectAlternativeNames:

        <?php foreach($domains as $domain => $zone): ?>

        - '<?= $domain ?>'

        <?php endforeach; ?>

      ValidationMethod: DNS
Outputs:
  CertificateArn:
    Description: 'ACM Certificate ARN'
    Value: !Ref Certificate
    Export:
      Name: !Sub '${AWS::StackName}-CertificateArn'
