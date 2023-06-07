# [Departed] AWS S3Bucket plugin for CakePHP 4.x

## Installation

You can install this plugin into your CakePHP 4.x application using [composer](https://getcomposer.org).

The recommended way to install composer packages is:

```
composer require horidaisuke/cakephp-aws-s3bucket
```

## How to Use

### 1.Configure DataSource

You can configure S3 bucket datasource like as database at datasource section in `app_xxx.php`.

For example:

```php
'Datasources' => [
    // ... after database configurations
    'name_of_s3_datasource' => [
        'className'  => 'S3Bucket\Datasource\Connection',
        'bucketName' => 'name_of_s3_bucket',
        'acl'        => 'public-read', // if object set to public access (default set to private)
        'client'     => [
            'region' => 'name_of_region',
        ],
    ],
],

```

### 2. Create S3Bucket Model

You can create a model for each object key prefixes at namespace `App\Model\S3Bucket`.

For example:

```php
<?php
declare(strict_types=1);

namespace App\Model\S3Bucket;

use S3Bucket\Datasource\S3Bucket;

class SampleOfS3BucketModel extends S3Bucket
{
    protected static $_connectionName = 'name_of_s3_datasource';
    protected static $_prefix = 'sample_of_object_key_prefix';
}
```

When it's provided object key prefix, object access is controlled under prefix scope.

### 3. Get Model from S3BucketRegistry

You can get created S3 bucket model from `S3BucketRegistry`.

Like this:

```php
$sampleOfS3BucketModel = S3BucketRegistry::init()->get('SampleOfS3BucketModel');
```

### 4. Put, Get, Delete, etc.. S3Object

You can make some operations (put, get, delete, etc..) S3 objects via S3 bucket model.

Like this:

```php
// putObject
$sampleOfS3BucketModel->putObject(`object_key`, file_get_contents(`filename_for_put`));

// getObject
$sampleOfS3BucketModel->getObject(`object_key`);

// getObjectBody
$sampleOfS3BucketModel->getObjectBody(`object_key`);

// deleteObject
$sampleOfS3BucketModel->deleteObject(`object_key`);

// deleteObjects
$sampleOfS3BucketModel->deleteObjects([`object_key1`, `object_key2`]);

// doesObjectExist
$sampleOfS3BucketModel->doesObjectExist(`object_key`);

// moveObject
$sampleOfS3BucketModel->moveObject(`object_key_from`, `object_key_to`);

// copyObject
$sampleOfS3BucketModel->copyObject(`object_key_from`, `object_key_to`);

// headObject
$sampleOfS3BucketModel->headObject(`object_key`);
```

## License
This software licensed under the [MIT License](https://github.com/horidaisuke/cakephp-aws-s3bucket/blob/master/LICENSE)
