<?php

namespace App\Aws;

use App\Configs\UnloadConfig;
use Aws\Rds\RdsClient;
use Aws\RDSDataService\RDSDataServiceClient;
use Aws\S3\S3Client;
use Illuminate\Support\Collection;

class Storage
{
    private S3Client $s3;
    private UnloadConfig $unload;
    private RdsClient $rds;

    public function __construct(UnloadConfig $unload)
    {
        $this->s3 = new S3Client(['region' => $unload->region(), 'profile' => $unload->profile(), 'version' => 'latest',]);
        $this->rds = new RdsClient(['region' => $unload->region(), 'profile' => $unload->profile(), 'version' => 'latest',]);
        $this->unload = $unload;
    }

    public function listApplicationDBClusterSnapshots(): Collection
    {
        return collect($this->rds->describeDBClusterSnapshots()->search('DBClusterSnapshots[].DBClusterSnapshotIdentifier'));
    }

    public function deleteApplicationDBClusterSnapshot(string $id): void
    {
        $this->rds->deleteDBClusterSnapshot(['DBClusterSnapshotIdentifier' => $id]);
    }

    public function listApplicationDbSnapshots(): Collection
    {
        return collect($this->rds->describeDBSnapshots()->search('DBSnapshots[].DBSnapshotIdentifier'));
    }

    public function deleteApplicationDbSnapshot(string $id): void
    {
        $this->rds->deleteDBSnapshot(['DBSnapshotIdentifier' => $id]);
    }

    public function listApplicationBuckets(): Collection
    {
        $buckets = $this->s3->listBuckets();
        $buckets = $buckets['Buckets'];
        $applicationBuckets = [];

        foreach($buckets as $bucket) {
            try {
                $tags = $this->s3->getBucketTagging(['Bucket' => $bucket['Name']])->get('TagSet');
//                if ($tags == $this->unload->unloadTags()) {
                    $bucket['Versioning'] = $this->s3->getBucketVersioning(['Bucket' => $bucket['Name']])->get('Status') == 'Enabled';
                    $applicationBuckets[] = $bucket;
//                }
            } catch (\Exception $e) {}
        }

        return collect($applicationBuckets);
    }

    public function deleteBucket($bucketName)
    {
        $objects = $this->s3->getIterator('ListObjects', ([
            'Bucket' => $bucketName
        ]));

        foreach ($objects as $object) {
            $this->s3->deleteObject([
                'Bucket' => $bucketName,
                'Key' => $object['Key'],
            ]);
        }

        $this->s3->deleteBucket(['Bucket' => $bucketName,]);
    }

    public function deleteVersionedBucket($bucketName)
    {
        $versions = $this->s3->listObjectVersions([
            'Bucket' => $bucketName
        ])->getPath('Versions');

        foreach ((array) $versions as $version) {
            $this->s3->deleteObject([
                'Bucket' => $bucketName,
                'Key' => $version['Key'],
                'VersionId' => $version['VersionId']
            ]);
        }

        $this->s3->putBucketVersioning([
            'Bucket' => $bucketName,
            'VersioningConfiguration' => [
                'Status' => 'Suspended',
            ],
        ]);

        $this->s3->deleteBucket(['Bucket' => $bucketName,]);
    }
}
