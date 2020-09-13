<?php
declare(strict_types=1);

namespace S3Bucket\Datasource;

use Aws\Result;
use Aws\S3\S3Client;
use Cake\Datasource\ConnectionInterface;
use Cake\Utility\Hash;
use Psr\Log\LoggerInterface;
use Psr\SimpleCache\CacheInterface;

/**
 * Class Connection
 * @package S3Bucket\Datasource
 * @method object getDriver()
 * @method bool supportsDynamicConstraints()
 * @method \Cake\Database\Schema\Collection getSchemaCollection()
 * @method \Cake\Database\Query newQuery()
 * @method \Cake\Database\StatementInterface prepare($sql)
 * @method \Cake\Database\StatementInterface execute($query, $params = [], array $types = [])
 * @method \Cake\Database\StatementInterface query(string $sql)
 */
class Connection implements ConnectionInterface
{
    /**
     * @var array Connection configure parameter
     */
    protected $_config = [];

    /**
     * @var \Aws\S3\S3Client|null
     */
    protected $_s3Client = null;

    /**
     * Connection constructor.
     *
     * @param array $config
     */
    public function __construct(array $config = [])
    {
        if (empty($config['bucketName']) || (empty($config['client']) || empty($config['client']['region']))) {
            throw new \InvalidArgumentException('Config "bucketName" or "client.region" missing.');
        }

        $this->_config = $config;
        $this->_s3Client = $this->_getS3Client($config);
        $this->_s3Client->registerStreamWrapper();
        $this->_s3Client->getCommand('HeadBucket', ['Bucket' => $this->_config['bucketName']]);
    }

    /**
     * create new S3Client instance
     *
     * @param array $config
     * @return S3Client
     */
    protected function _getS3Client(array $config = []): S3Client
    {
        return new S3Client(Hash::merge([
            'credentials' => null,
            'version' => 'latest',
        ], $config['client']));
    }

    /**
     * Pre processing to convert the key.
     * ex) '/key' => 'key'
     *
     * @param string $key
     * @param array $options
     *
     * @return string
     */
    private function __keyPreProcess(string $key, array $options): string
    {
        $prefix = '';
        if (!empty($options['prefix'])) {
            $prefix = $options['prefix'];
            if (strpos($prefix, '/') === 0) {
                $prefix = substr($prefix, 1);
            }
            if (strpos($prefix, '/') + 1 !== strlen($prefix)) {
                $prefix = $prefix . '/';
            }
        }

        if (strpos($key, '/') === 0) {
            $key = substr($key, 1);
        }

        return $prefix . $key;
    }

    /**
     * Call CopyObject API.
     *
     * @see S3Client::copyObject
     * @see http://docs.aws.amazon.com/aws-sdk-php/v3/api/api-s3-2006-03-01.html#copyobject
     *
     * @param string $srcKey
     * @param string $destKey
     * @param array  $options
     *
     * @return \Aws\Result
     */
    public function copyObject(string $srcKey, string $destKey, array $options = []): Result
    {
        $srcKey = $this->__keyPreProcess($srcKey, $options);
        $destKey = $this->__keyPreProcess($destKey, $options);

        $options += [
            'Bucket' => $this->_config['bucketName'],
            'Key' => $destKey,
            'CopySource' => $this->_config['bucketName'] . '/' . $srcKey,
            'ACL' => $this->_config['acl'] ?? 'private',
        ];

        return $this->_s3Client->copyObject($options);
    }

    /**
     * Call DeleteObject API.
     *
     * @see S3Client::deleteObject
     * @see http://docs.aws.amazon.com/aws-sdk-php/v3/api/api-s3-2006-03-01.html#deleteobject
     *
     * @param string $key
     * @param array  $options
     *
     * @return \Aws\Result
     */
    public function deleteObject(string $key, array $options = []): Result
    {
        $key = $this->__keyPreProcess($key, $options);

        $options += [
            'Bucket' => $this->_config['bucketName'],
            'Key' => $key,
        ];

        return $this->_s3Client->deleteObject($options);
    }

    /**
     * Call DeleteObjects API.
     *
     * @see S3Client::deleteObjects
     * @see http://docs.aws.amazon.com/aws-sdk-php/v3/api/api-s3-2006-03-01.html#deleteobjects
     *
     * @param array $keys
     * @param array $options
     *
     * @return \Aws\Result
     */
    public function deleteObjects(array $keys, array $options = []): Result
    {
        foreach ($keys as $index => $key) {
            $keys[$index] = [
                'Key' => $this->__keyPreProcess($key, $options),
            ];
        }

        $options += [
            'Bucket' => $this->_config['bucketName'],
            'Delete' => [
                'Objects' => $keys,
            ],
        ];

        return $this->_s3Client->deleteObjects($options);
    }

    /**
     * Call doesObjectExists API.
     *
     * @see S3Client::doesObjectExist
     * @see http://docs.aws.amazon.com/aws-sdk-php/v3/api/api-s3-2006-03-01.html#headobject
     *
     * @param string $key
     * @param array  $options
     *
     * @return bool
     */
    public function doesObjectExist(string $key, array $options = []): bool
    {
        $key = $this->__keyPreProcess($key, $options);

        return $this->_s3Client->doesObjectExist(
            $this->_config['bucketName'],
            $key,
            $options
        );
    }

    /**
     * Call GetObject API.
     *
     * @see S3Client::getObject
     * @see http://docs.aws.amazon.com/aws-sdk-php/v3/api/api-s3-2006-03-01.html#getobject
     *
     * @param string $key
     * @param array  $options
     *
     * @return \Aws\Result
     */
    public function getObject(string $key, array $options = []): Result
    {
        $key = $this->__keyPreProcess($key, $options);

        $options += [
            'Bucket' => $this->_config['bucketName'],
            'Key' => $key,
            'ACL' => $this->_config['acl'] ?? 'private',
        ];

        return $this->_s3Client->getObject($options);
    }

    /**
     * Call HeadObject API.
     *
     * @see S3Client::headObject
     * @see http://docs.aws.amazon.com/aws-sdk-php/v3/api/api-s3-2006-03-01.html#headobject
     *
     * @param string $key
     * @param array  $options
     *
     * @return \Aws\Result
     */
    public function headObject(string $key, array $options = []): Result
    {
        $key = $this->__keyPreProcess($key, $options);

        $options += [
            'Bucket' => $this->_config['bucketName'],
            'Key' => $key,
        ];

        return $this->_s3Client->headObject($options);
    }

    /**
     * Call PutObject API.
     *
     * @see S3Client::putObject
     * @see http://docs.aws.amazon.com/aws-sdk-php/v3/api/api-s3-2006-03-01.html#putobject
     *
     * @param string $key
     * @param string $content
     * @param array  $options
     *
     * @return \Aws\Result
     */
    public function putObject(string $key, $content, array $options = []): Result
    {
        $key = $this->__keyPreProcess($key, $options);

        $options += [
            'Bucket' => $this->_config['bucketName'],
            'Key' => $key,
            'ACL' => $this->_config['acl'] ?? 'private',
            'Body' => $content,
        ];

        return $this->_s3Client->putObject($options);
    }

    /**
     * @inheritDoc
     */
    public function setLogger(LoggerInterface $logger)
    {
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getLogger(): LoggerInterface
    {
        throw new \RuntimeException('This method is not implemented.');
    }

    /**
     * @inheritDoc
     */
    public function setCacher(CacheInterface $cacher)
    {
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getCacher(): CacheInterface
    {
        throw new \RuntimeException('This method is not implemented.');
    }

    /**
     * @inheritDoc
     */
    public function configName(): string
    {
        if (empty($this->_config['name'])) {
            return '';
        }

        return $this->_config['name'];
    }

    /**
     * @inheritDoc
     */
    public function config(): array
    {
        return $this->_config;
    }

    /**
     * @inheritDoc
     */
    public function transactional(callable $transaction)
    {
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function disableConstraints(callable $operation)
    {
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function enableQueryLogging(bool $value = true)
    {
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function disableQueryLogging()
    {
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function isQueryLoggingEnabled(): bool
    {
        return false;
    }
}
