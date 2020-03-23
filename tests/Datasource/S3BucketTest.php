<?php
declare(strict_types=1);

namespace S3Bucket\Test\TestCase\Datasource;

use Cake\TestSuite\TestCase;
use GuzzleHttp\Psr7\Stream;
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
 */
class S3BucketTest extends TestCase
{
    /**
     * tear down method
     *
     * @return void
     */
    public function tearDown(): void
    {
        \Mockery::close();
    }

    /**
     * Test Connection APIs
     */
    public function testConnectionApi()
    {
        // Create Connection mock -> using ConnectionManager mock
        $connectionMock = \Mockery::mock('\S3Bucket\Datasource\Connection');
        $connectionMock->shouldReceive('copyObject')
            ->once()
            ->with('/test-src-key', '/test-dest-key', ['option' => true]);
        $connectionMock->shouldReceive('deleteObject')
            ->once()
            ->with('/test-key', ['option' => true]);
        $connectionMock->shouldReceive('deleteObjects')
            ->once()
            ->with(['/test-key1', '/test-key2', '/test-key3'], ['option' => true]);
        $connectionMock->shouldReceive('doesObjectExist')
            ->once()
            ->with('/test-key', ['option' => true]);
        $connectionMock->shouldReceive('getObject')
            ->once()
            ->with('/test-key', ['option' => true]);
        $connectionMock->shouldReceive('headObject')
            ->once()
            ->with('/test-key', ['option' => true]);
        $connectionMock->shouldReceive('putObject')
            ->once()
            ->with('/test-key', 'test-content', ['option' => true]);

        // Set ConnectionManager mock
        MockConnectionManager::$connection = $connectionMock;

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
        // Create string stream -> using \Aws\Result mock
        $contentString = 'Sample Text';
        $stream = fopen('php://memory', 'r+');
        fwrite($stream, $contentString);
        rewind($stream);

        // Create \Aws\Result mock -> using Connection mock
        $awsResultMock = \Mockery::mock('\Aws\Result');
        $awsResultMock->shouldReceive('get')
            ->once()
            ->with('Body')
            ->andReturn(new Stream($stream));

        // Create Connection mock -> using ConnectionManager mock
        $connectionMock = \Mockery::mock('\S3Bucket\Datasource\Connection');
        $connectionMock->shouldReceive('getObject')
            ->once()
            ->with('/test-key', [])
            ->andReturn($awsResultMock);

        // Set ConnectionManager mock
        MockConnectionManager::$connection = $connectionMock;

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
        // Create Connection mock -> using ConnectionManager mock
        $connectionMock = \Mockery::mock('\S3Bucket\Datasource\Connection');
        $connectionMock->shouldReceive('copyObject')
            ->once()
            ->with('/test-src-key', '/test-dest-key', []);
        $connectionMock->shouldReceive('deleteObject')
            ->once()
            ->with('/test-src-key', []);

        // Set ConnectionManager mock
        MockConnectionManager::$connection = $connectionMock;

        // Test start.
        $S3Bucket = new MockS3Bucket();
        $S3Bucket->moveObject('/test-src-key', '/test-dest-key');
    }
}
