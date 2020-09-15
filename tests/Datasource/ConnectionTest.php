<?php
declare(strict_types=1);

namespace S3Bucket\Test\TestCase\Datasource;

use Aws\Result;
use Aws\S3\S3Client;
use Cake\TestSuite\TestCase;
use S3Bucket\Datasource\Connection;

class MockConnection extends Connection
{
    public static $s3Client;

    protected function _getS3Client(array $config = []): S3Client
    {
        return self::$s3Client;
    }
}

/**
 * Connection Testcase
 *
 * @property \S3Bucket\Datasource\Connection $Connection
 * @property \Aws\S3\S3Client $S3Client
 * @property \Aws\Result $Result
 */
class ConnectionTest extends TestCase
{
    /**
     * setup
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();

        $config = [
            'bucketName' => 'test-bucket',
            'acl' => 'public-read',
            'client' => [
                'credentials' => [
                    'key' => 'test-key',
                    'secret' => 'test-secret',
                ],
                'region' => 'test-region',
                'version' => '2006-03-01',
            ],
        ];
        $this->S3Client = $this->createPartialMock(S3Client::class, [
            '__call',
            'registerStreamWrapper',
            'getCommand',
            'doesObjectExist',
        ]);
        $this->S3Client->expects($this->once())->method('registerStreamWrapper');
        $this->S3Client->expects($this->once())->method('getCommand')->with($this->equalTo('HeadBucket'), $this->equalTo(['Bucket' => $config['bucketName']]))->willReturn(true);
        MockConnection::$s3Client = $this->S3Client;
        $this->Connection = new MockConnection($config);
        $this->Result = $this->createMock(Result::class);
    }

    /**
     * tear down
     *
     * @return void
     */
    public function tearDown(): void
    {
        unset($this->Connection);
        unset($this->S3Client);
    }

    /**
     * Test new instance success
     *
     * @return void
     */
    public function testNewInstanceSuccess()
    {
        $config = $this->Connection->config();
        $this->assertEquals('test-key', $config['client']['credentials']['key']);
    }

    /**
     * Test new instance failed, missing arguments
     *
     * @return void
     */
    public function testNewInstanceMissingArguments()
    {
        $this->expectException(\InvalidArgumentException::class);
        $params = [];
        new MockConnection($params);
    }

    /**
     * Test copyObject method
     */
    public function testCopyObject()
    {
        $this->S3Client->expects($this->once())->method('__call')->with($this->equalTo('copyObject'), $this->equalTo([[
            'Bucket' => 'test-bucket',
            'Key' => 'test-dest-key',
            'CopySource' => 'test-bucket/test-src-key',
            'ACL' => 'public-read',
        ]]))->willReturn($this->Result);

        $this->Connection->copyObject('/test-src-key', '/test-dest-key');
    }

    /**
     * Test copyObject method
     */
    public function testCopyObjectOverWroteOptions()
    {
        $this->S3Client->expects($this->once())->method('__call')->with($this->equalTo('copyObject'), $this->equalTo([[
            'Bucket' => 'test-bucket',
            'Key' => 'test-dest-key',
            'CopySource' => 'test-bucket/test-src-key',
            'ACL' => 'overwrote',
            'overwrote-options' => true,
        ]]))->willReturn($this->Result);

        $this->Connection->copyObject(
            '/test-src-key',
            '/test-dest-key',
            [
                'ACL' => 'overwrote',
                'overwrote-options' => true,
            ]
        );
    }

    /**
     * Test deleteObject method
     */
    public function testDeleteObject()
    {
        $this->S3Client->expects($this->once())->method('__call')->with($this->equalTo('deleteObject'), $this->equalTo([[
            'Bucket' => 'test-bucket',
            'Key' => 'test-key',
        ]]))->willReturn($this->Result);

        $this->Connection->deleteObject('/test-key');
    }

    /**
     * Test deleteObject method
     */
    public function testDeleteObjectOverWroteOptions()
    {
        $this->S3Client->expects($this->once())->method('__call')->with($this->equalTo('deleteObject'), $this->equalTo([[
            'Bucket' => 'test-bucket',
            'Key' => 'test-key',
            'overwrote-options' => true,
        ]]))->willReturn($this->Result);

        $this->Connection->deleteObject(
            '/test-key',
            [
                'overwrote-options' => true,
            ]
        );
    }

    /**
     * Test deleteObjects method
     */
    public function testDeleteObjects()
    {
        $this->S3Client->expects($this->once())->method('__call')->with($this->equalTo('deleteObjects'), $this->equalTo([[
            'Bucket' => 'test-bucket',
            'Delete' => [
                'Objects' => [
                    ['Key' => 'test-key1'],
                    ['Key' => 'test-key2'],
                    ['Key' => 'test-key3'],
                ],
            ],
        ]]))->willReturn($this->Result);

        $this->Connection->deleteObjects([
            '/test-key1',
            '/test-key2',
            '/test-key3',
        ]);
    }

    /**
     * Test deleteObjects method
     */
    public function testDeleteObjectsOverWroteOptions()
    {
        $this->S3Client->expects($this->once())->method('__call')->with($this->equalTo('deleteObjects'), $this->equalTo([[
            'Bucket' => 'test-bucket',
            'Delete' => [
                'Objects' => [
                    ['Key' => 'test-key1'],
                    ['Key' => 'test-key2'],
                    ['Key' => 'test-key3'],
                ],
            ],
            'overwrote-options' => true,
        ]]))->willReturn($this->Result);

        $this->Connection->deleteObjects(
            [
                '/test-key1',
                '/test-key2',
                '/test-key3',
            ],
            [
                'overwrote-options' => true,
            ]
        );
    }

    /**
     * Test doesObjectExist method
     */
    public function testDoesObjectExist()
    {
        $this->S3Client->expects($this->once())->method('doesObjectExist')->with($this->equalTo('test-bucket'), $this->equalTo('test-key'), $this->equalTo([]))->willReturn(true);

        $this->Connection->doesObjectExist('/test-key');
    }

    /**
     * Test doesObjectExist method
     */
    public function testDoesObjectExistOverWroteOptions()
    {
        $this->S3Client->expects($this->once())->method('doesObjectExist')->with($this->equalTo('test-bucket'), $this->equalTo('test-key'), $this->equalTo([
            'overwrote-options' => true,
        ]))->willReturn(true);

        $this->Connection->doesObjectExist(
            '/test-key',
            [
                'overwrote-options' => true,
            ]
        );
    }

    /**
     * Test getObject method
     */
    public function testGetObject()
    {
        $this->S3Client->expects($this->exactly(2))->method('__call')->with($this->equalTo('getObject'), $this->equalTo([[
            'Bucket' => 'test-bucket',
            'Key' => 'test-key',
            'ACL' => 'public-read',
        ]]))->willReturn($this->Result);

        $this->Connection->getObject('test-key');
        $this->Connection->getObject('/test-key');
    }

    /**
     * Test getObject method
     */
    public function testGetObjectOverWroteOptions()
    {
        $this->S3Client->expects($this->once())->method('__call')->with($this->equalTo('getObject'), $this->equalTo([[
            'Bucket' => 'test-bucket',
            'Key' => 'test-key',
            'ACL' => 'overwrote',
            'overwrote-options' => true,
        ]]))->willReturn($this->Result);

        $this->Connection->getObject(
            'test-key',
            [
                'ACL' => 'overwrote',
                'overwrote-options' => true,
            ]
        );
    }

    /**
     * Test headObject method
     */
    public function testHeadObject()
    {
        $this->S3Client->expects($this->once())->method('__call')->with($this->equalTo('headObject'), $this->equalTo([[
            'Bucket' => 'test-bucket',
            'Key' => 'test-key',
        ]]))->willReturn($this->Result);

        $this->Connection->headObject('/test-key');
    }

    /**
     * Test headObject method
     */
    public function testHeadObjectOverWroteOptions()
    {
        $this->S3Client->expects($this->once())->method('__call')->with($this->equalTo('headObject'), $this->equalTo([[
            'Bucket' => 'test-bucket',
            'Key' => 'test-key',
            'overwrote-options' => true,
        ]]))->willReturn($this->Result);

        $this->Connection->headObject(
            '/test-key',
            [
                'overwrote-options' => true,
            ]
        );
    }

    /**
     * Test putObject method
     */
    public function testPutObject()
    {
        $this->S3Client->expects($this->once())->method('__call')->with($this->equalTo('putObject'), $this->equalTo([[
            'Bucket' => 'test-bucket',
            'Key' => 'test-key',
            'Body' => 'test-body',
            'ACL' => 'public-read',
        ]]))->willReturn($this->Result);

        $this->Connection->putObject('/test-key', 'test-body');
    }

    /**
     * Test putObject method
     */
    public function testPutObjectOverWroteOptions()
    {
        $this->S3Client->expects($this->once())->method('__call')->with($this->equalTo('putObject'), $this->equalTo([[
            'Bucket' => 'test-bucket',
            'Key' => 'test-key',
            'Body' => 'test-body',
            'ACL' => 'public-read',
            'overwrote-options' => true,
        ]]))->willReturn($this->Result);

        $this->Connection->putObject(
            '/test-key',
            'test-body',
            [
                'overwrote-options' => true,
            ]
        );
    }
}
