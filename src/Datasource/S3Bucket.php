<?php
declare(strict_types=1);

namespace S3Bucket\Datasource;

use Aws\Result;
use Cake\Datasource\ConnectionInterface;
use Cake\Datasource\ConnectionManager;
use GuzzleHttp\Psr7\Stream;

/**
 * Class S3Bucket
 *
 * @package S3Bucket\Datasource
 */
class S3Bucket
{
    /**
     * @var \Cake\Datasource\ConnectionManager
     */
    protected static $_ConnectionManager = ConnectionManager::class;

    /**
     * @var string Connection configure name
     */
    protected static $_connectionName = '';

    /**
     * @var string object key prefix
     */
    protected static $_prefix = '';

    /**
     * @var \S3Bucket\Datasource\Connection Connection instance
     */
    protected $_connection;

    /**
     * Get default connection name
     *
     * @return string
     */
    public static function defaultConnectionName()
    {
        return static::$_connectionName;
    }

    /**
     * S3Bucket constructor.
     *
     * @param array $config
     */
    public function __construct(array $config = [])
    {
        $this->initialize($config);
    }

    /**
     * Returns the connection instance or sets a new one
     *
     * @param \S3Bucket\Datasource\Connection|null $conn The new connection instance
     * @return \S3Bucket\Datasource\Connection
     */
    public function connection(?ConnectionInterface $conn = null): Connection
    {
        if ($conn === null) {
            return $this->_connection;
        }

        return $this->_connection = $conn;
    }

    /**
     * Initialize method
     *
     * @param array $config The configuration for the Table.
     * @return void
     */
    public function initialize(array $config): void
    {
        $this->connection(static::$_ConnectionManager::get(static::$_connectionName));
    }

    /**
     * Call CopyObject API.
     *
     * @see \S3Bucket\Datasource\S3Client::copyObject
     * @see http://docs.aws.amazon.com/aws-sdk-php/v3/api/api-s3-2006-03-01.html#copyobject
     * @param string $srcKey
     * @param string $destKey
     * @param array  $options
     * @return \Aws\Result
     */
    public function copyObject(string $srcKey, string $destKey, array $options = []): Result
    {
        $options['prefix'] = $options['prefix'] ?? static::$_prefix;

        return $this->connection()->copyObject($srcKey, $destKey, $options);
    }

    /**
     * Call DeleteObject API.
     *
     * @see \S3Bucket\Datasource\S3Client::deleteObject
     * @see http://docs.aws.amazon.com/aws-sdk-php/v3/api/api-s3-2006-03-01.html#deleteobject
     * @param string $key
     * @param array  $options
     * @return \Aws\Result
     */
    public function deleteObject(string $key, array $options = []): Result
    {
        $options['prefix'] = $options['prefix'] ?? static::$_prefix;

        return $this->connection()->deleteObject($key, $options);
    }

    /**
     * Call DeleteObjects API.
     *
     * @see \S3Bucket\Datasource\S3Client::deleteObjects
     * @see http://docs.aws.amazon.com/aws-sdk-php/v3/api/api-s3-2006-03-01.html#deleteobjects
     * @param array $keys
     * @param array $options
     * @return \Aws\Result
     */
    public function deleteObjects(array $keys, array $options = []): Result
    {
        $options['prefix'] = $options['prefix'] ?? static::$_prefix;

        return $this->connection()->deleteObjects($keys, $options);
    }

    /**
     * Call doesObjectExists API.
     *
     * @see \S3Bucket\Datasource\S3Client::doesObjectExist
     * @see http://docs.aws.amazon.com/aws-sdk-php/v3/api/api-s3-2006-03-01.html#headobject
     * @param string $key
     * @param array  $options
     * @return bool
     */
    public function doesObjectExist(string $key, array $options = []): bool
    {
        $options['prefix'] = $options['prefix'] ?? static::$_prefix;

        return $this->connection()->doesObjectExist($key, $options);
    }

    /**
     * Call GetObject API.
     *
     * @see \S3Bucket\Datasource\S3Client::getObject
     * @see http://docs.aws.amazon.com/aws-sdk-php/v3/api/api-s3-2006-03-01.html#getobject
     * @param string $key
     * @param array  $options
     * @return \Aws\Result
     */
    public function getObject(string $key, array $options = []): Result
    {
        $options['prefix'] = $options['prefix'] ?? static::$_prefix;

        return $this->connection()->getObject($key, $options);
    }

    /**
     * Call HeadObject API.
     *
     * @see \S3Bucket\Datasource\S3Client::headObject
     * @see http://docs.aws.amazon.com/aws-sdk-php/v3/api/api-s3-2006-03-01.html#headobject
     * @param string $key
     * @param array  $options
     * @return \Aws\Result
     */
    public function headObject(string $key, array $options = []): Result
    {
        $options['prefix'] = $options['prefix'] ?? static::$_prefix;

        return $this->connection()->headObject($key, $options);
    }

    /**
     * Call PutObject API.
     *
     * @see \S3Bucket\Datasource\S3Client::putObject
     * @see http://docs.aws.amazon.com/aws-sdk-php/v3/api/api-s3-2006-03-01.html#putobject
     * @param string $key
     * @param string $content
     * @param array  $options
     * @return \Aws\Result
     */
    public function putObject(string $key, string $content, array $options = []): Result
    {
        $options['prefix'] = $options['prefix'] ?? static::$_prefix;

        return $this->connection()->putObject($key, $content, $options);
    }

    /**
     * Call GetObject API and get Body attribute.
     *
     * @see \S3Bucket\Datasource\S3Client::getObject
     * @see http://docs.aws.amazon.com/aws-sdk-php/v3/api/api-s3-2006-03-01.html#getobject
     * @param string $key
     * @param array  $options
     * @return \GuzzleHttp\Psr7\Stream
     */
    public function getObjectBody(string $key, array $options = []): Stream
    {
        return $this->getObject($key, $options)->get('Body');
    }

    /**
     * To mimic the moving Object using CopyObject API and DeleteObject API.
     *
     * @see \S3Bucket\Datasource\S3Client::copyObject
     * @see \S3Bucket\Datasource\S3Client::deleteObject
     * @see http://docs.aws.amazon.com/aws-sdk-php/v3/api/api-s3-2006-03-01.html#copyobject
     * @see http://docs.aws.amazon.com/aws-sdk-php/v3/api/api-s3-2006-03-01.html#deleteobject
     * @param string $srcKey
     * @param string $destKey
     * @param array  $options
     * @return \Aws\Result Return the CopyObject API result.
     */
    public function moveObject(string $srcKey, string $destKey, array $options = []): Result
    {
        $result = $this->copyObject($srcKey, $destKey, $options);
        $this->deleteObject($srcKey);

        return $result;
    }
}
