<?php
/* Laravel 4 */
namespace Jp7\Flysystem;

use Aws\S3\S3Client;
use League\Flysystem\AwsS3v3\AwsS3Adapter;
use League\Flysystem\Filesystem;

class FlysystemFactory {

    public function disk($name) {
        $config = \Config::get('flysystem.'. $name);

        if ($config['client'] == 's3') {
            $client = new S3Client($config);
            $adapter = new AwsS3Adapter($client, $config['bucket']);
        } else {
            throw new \BadMethodCallException('Client not implemented');
        }
        return new Filesystem($adapter);
    }
}
