<?php

namespace App\Tasks;

use App\Aws\ContinuousIntegration;
use App\Path;
use App\Configs\UnloadConfig;
use Aws\Exception\AwsException;
use Aws\S3\S3Client;
use GuzzleHttp\Promise\Coroutine;
use GuzzleHttp\Promise\Utils;
use Illuminate\Support\Facades\File;

class UploadAssetTask
{
    public function handle(S3Client $s3, UnloadConfig $unload, ContinuousIntegration $ci): void
    {
        $assetBucket = $ci->getAssetsBucketName();
        $assetHash = $unload->tmpBuildAssetHash();
        $commands = [];

        $uploadFn = function ($file) use ($assetBucket, $assetHash, $s3) {
            return Coroutine::of(function () use ($file, $assetHash, $assetBucket, $s3) {
                $fileName = str($file->getPathname())->replace(Path::tmpAssetDirectory(), '')->toString();

                try {
                    yield $s3->headObject([
                        'Bucket' => $assetBucket,
                        'Key'    => "assets/$assetHash{$fileName}",
                    ]);

                    echo "\n   Already exists, skipped |> assets/$assetHash{$fileName}";
                } catch (\Exception $e) {
                    echo "\n   Uploading |> assets/$assetHash{$fileName}";
                    yield $s3->putObject([
                        'Bucket' => $assetBucket,
                        'Key'    => "assets/$assetHash{$fileName}",
                        'Body'   => fopen($file->getRealPath(), 'r'),
                    ]);
                }
            });
        };

        foreach(File::allFiles(Path::tmpAssetDirectory()) as $file) {
            $commands[] = $uploadFn($file);
        }

        try {
            Utils::all($commands);
            echo PHP_EOL;
        } catch (AwsException $e) {
            throw new \Exception('Failed to upload assets: ', $e->getAwsErrorMessage());
        }
    }
}
