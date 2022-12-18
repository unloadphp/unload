<?php

namespace App\Aws;

use App\Configs\UnloadConfig;
use Aws\Sts\StsClient;
use GuzzleHttp\Client;

/**
 * @see https://docs.aws.amazon.com/IAM/latest/UserGuide/id_roles_providers_enable-console-custom-url.html
 */
class Dashboard
{
    private StsClient $sts;
    private UnloadConfig $unload;

    public function __construct(StsClient $sts, UnloadConfig $unload)
    {
        $this->sts = $sts;
        $this->unload = $unload;
    }

    public function generateUrl(): string
    {
        /** @var \Aws\Credentials\Credentials $session */
        $session = $this->sts->getCredentials()->wait();
        $credentials = [
            'sessionId' => $session->getAccessKeyId(),
            'sessionKey' => $session->getSecretKey(),
            'sessionToken' => $session->getSecurityToken(),
        ];

        $requestParameters = '?Action=getSigninToken';
        $requestParameters .= '&DurationSeconds=43200';
        $requestParameters .= '&Session='.urlencode(json_encode($credentials));

        $federationUrl = "https://signin.aws.amazon.com/federation{$requestParameters}";
        $federation = json_decode((new Client)->get($federationUrl)->getBody()->getContents());

        $requestParameters = '?Action=login';
        $requestParameters .= '&Destination='.urlencode("https://{$this->unload->region()}.console.aws.amazon.com/lambda/home?region={$this->unload->region()}#/applications/{$this->unload->appStackName()}?tab=monitoring");
        $requestParameters .= '&SigninToken='.urlencode($federation->SigninToken);
        $requestParameters .= '&Issuer='.urlencode("https://example.com");

        return "https://signin.aws.amazon.com/federation$requestParameters";
    }
}
