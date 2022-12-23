// Copyright 2022 unload.sh
//
// Licensed under the Apache License, Version 2.0 (the "License");
// you may not use this file except in compliance with the License.
// You may obtain a copy of the License at
//
//     http://www.apache.org/licenses/LICENSE-2.0
//
// Unless required by applicable law or agreed to in writing, software
// distributed under the License is distributed on an "AS IS" BASIS,
// WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
// See the License for the specific language governing permissions and
// limitations under the License.
'use strict';

const AWS = require('aws-sdk');
const codedeploy = new AWS.CodeDeploy({apiVersion: '2014-10-06'});
var lambda = new AWS.Lambda();

exports.handler = async (event, context, callback) => {

    var deploymentId = event.DeploymentId;
    var lifecycleEventHookExecutionId = event.LifecycleEventHookExecutionId;
    var cliFunction = process.env.CliFunction;
    var deployCommands = process.env.CliDeployCommand.split("\n").join('').split('');

    console.log("BeforeAllowTraffic cliFunction: " + cliFunction);
    console.log("BeforeAllowTraffic deployCommands: " + deployCommands);

    var lambdaResult = "Succeeded";
    for (const command of deployCommands) {
        console.log("Running: " + command);

        var cliParams = {
            FunctionName: cliFunction,
            Payload: JSON.stringify(command),
            LogType: 'Tail',
        };

        // Invoke the updated Lambda function.
        var response = await lambda.invoke(cliParams).promise();
        var payload = JSON.parse(response.Payload);
        var logResult = Buffer.from(response.LogResult, 'base64');
        var output = logResult.toString('ascii');

        console.log("Output: " +  output);
        console.log("Payload: " +  JSON.stringify(payload));

        // Check if the status code returned by the updated
        // function is 400. If it is, then it failed. If
        // is not, then it succeeded.
        if (payload.errorType){
            lambdaResult = "Failed";
            break;
        }
    }

    // Complete the PreTraffic Hook by sending CodeDeploy the validation status
    var params = {
        deploymentId: deploymentId,
        lifecycleEventHookExecutionId: lifecycleEventHookExecutionId,
        status: lambdaResult // status can be 'Succeeded' or 'Failed'
    };

    // // Pass CodeDeploy the prepared validation test results.
    await codedeploy.putLifecycleEventHookExecutionStatus(params).promise();

    console.log("CodeDeploy status updated successfully");
    callback(null, "CodeDeploy status updated successfully");
}
