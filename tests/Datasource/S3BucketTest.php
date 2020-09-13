<?php
declare(strict_types=1);

namespace S3Bucket\Test\TestCase\Datasource;

use Aws\Result;
use Cake\TestSuite\TestCase;
use GuzzleHttp\Psr7\Stream;
use S3Bucket\Datasource\Connection;
use S3Bucket\Datasource\S3Bucket;

class MockConnectionManager
{
    public static $connection;

    public static function get()
    {
        return static::$connection;
    }
}

class MockS3Bucket extends S3Bucket
{
    protected static $_ConnectionManager = MockConnectionManager::class;
}

/**
 * S3Bucket Testcase
 *
 * @property \S3Bucket\Datasource\Connection $Connection
 * @property \Aws\Result $Result
 */
class S3BucketTest extends TestCase
{
    /**
     * setup
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->Connection = $this->createPartialMock(Connection::class, [
            'copyObject',
            'deleteObject',
            'deleteObjects',
            'doesObjectExist',
            'getObject',
            'headObject',
            'putObject',
        ]);
        MockConnectionManager::$connection = $this->Connection;
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
    }

    /**
     * Test Connection APIs
     */
    public function testConnectionApi()
    {
        $this->Connection->expects($this->once())->method('copyObject')->with($this->equalTo('/test-src-key'), $this->equalTo('/test-dest-key'), $this->equalTo(['option' => true, 'prefix' => '']))->willReturn($this->Result);
        $this->Connection->expects($this->once())->method('deleteObject')->with($this->equalTo('/test-key'), $this->equalTo(['option' => true, 'prefix' => '']))->willReturn($this->Result);
        $this->Connection->expects($this->once())->method('deleteObjects')->with($this->equalTo(['/test-key1', '/test-key2', '/test-key3']), $this->equalTo(['option' => true, 'prefix' => '']))->willReturn($this->Result);
        $this->Connection->expects($this->once())->method('doesObjectExist')->with($this->equalTo('/test-key'), $this->equalTo(['option' => true, 'prefix' => '']))->willReturn(true);
        $this->Connection->expects($this->once())->method('getObject')->with($this->equalTo('/test-key'), $this->equalTo(['option' => true, 'prefix' => '']))->willReturn($this->Result);
        $this->Connection->expects($this->once())->method('headObject')->with($this->equalTo('/test-key'), $this->equalTo(['option' => true, 'prefix' => '']))->willReturn($this->Result);
        $this->Connection->expects($this->once())->method('copyObject')->with($this->equalTo('/test-src-key'), $this->equalTo('/test-dest-key'), $this->equalTo(['option' => true, 'prefix' => '']))->willReturn($this->Result);

        // Test start.
        $S3Bucket = new MockS3Bucket();
        $S3Bucket->copyObject('/test-src-key', '/test-dest-key', ['option' => true]);
        $S3Bucket->deleteObject('/test-key', ['option' => true]);
        $S3Bucket->deleteObjects(['/test-key1', '/test-key2', '/test-key3'], ['option' => true]);
        $S3Bucket->doesObjectExist('/test-key', ['option' => true]);
        $S3Bucket->getObject('/test-key', ['option' => true]);
        $S3Bucket->headObject('/test-key', ['option' => true]);
        $S3Bucket->putObject('/test-key', 'test-content', ['option' => true]);
    }

    /**
     * Test getObjectBody method
     */
    public function testGetObjectBody()
    {
        $contentString = 'Sample Text';
        $stream = fopen('php://memory', 'r+');
        fwrite($stream, $contentString);
        rewind($stream);

        $this->Result->expects($this->once())->method('get')->with($this->equalTo('Body'))->willReturn(new Stream($stream));
        $this->Connection->expects($this->once())->method('getObject')->with($this->equalTo('/test-key'), $this->equalTo(['prefix' => '']))->willReturn($this->Result);

        // Test start.
        $S3Bucket = new MockS3Bucket();
        $result = $S3Bucket->getObjectBody('/test-key');

        // Assertion
        $this->assertEquals($contentString, $result->__toString());
    }

    /**
     * Test moveObject method
     */
    public function testMoveObject()
    {
        $this->Connection->expects($this->once())->method('copyObject')->with($this->equalTo('/test-src-key'), $this->equalTo('/test-dest-key'), $this->equalTo(['prefix' => '']))->willReturn($this->Result);
        $this->Connection->expects($this->once())->method('deleteObject')->with($this->equalTo('/test-src-key'), $this->equalTo(['prefix' => '']))->willReturn($this->Result);

        // Test start.
        $S3Bucket = new MockS3Bucket();
        $S3Bucket->moveObject('/test-src-key', '/test-dest-key');
    }
}
