<?php

namespace App\Tasks;

use App\Aws\ContinuousIntegration;
use App\Path;
use App\Configs\UnloadConfig;
use Aws\Exception\AwsException;
use Aws\S3\S3Client;
use GuzzleHttp\Promise\Coroutine;
use GuzzleHttp\Promise\Utils;
use Illuminate\Console\OutputStyle;
use Illuminate\Support\Facades\File;

class UploadAssetTask
{
    public function handle(S3Client $s3, UnloadConfig $unload, ContinuousIntegration $ci, OutputStyle $output): void
    {
        $output->newLine();
        $assetBucket = $ci->getAssetsBucketName();
        $assetHash = $unload->tmpBuildAssetHash();
        $commands = [];

        $uploadFn = function ($file) use ($assetBucket, $assetHash, $s3, $output) {
            return Coroutine::of(function () use ($file, $assetHash, $assetBucket, $s3, $output) {
                $fileName = str($file->getPathname())->replace(Path::tmpAssetDirectory(), '')->toString();
                $filePath = "assets/$assetHash{$fileName}";

                if (in_array($fileName, ['/favicon.ico', '/robots.txt'])) {
                    $filePath = trim($fileName, '/');
                }

                try {
                    yield $s3->headObject([
                        'Bucket' => $assetBucket,
                        'Key'    => $filePath,
                    ]);

                    $output->writeln("<comment>Already exists, skipped |> $filePath</comment>");
                } catch (\Exception $e) {
                    $output->writeln("<comment>Uploading |> $filePath</comment>");
                    yield $s3->putObject([
                        'Bucket' => $assetBucket,
                        'Key'    => $filePath,
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
        } catch (AwsException $e) {
            throw new \Exception('Failed to upload assets: ', $e->getAwsErrorMessage());
        }
    }
}
