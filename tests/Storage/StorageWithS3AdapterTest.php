<?php

declare(strict_types = 1);

/**
 * Caldera Storage
 * Storage abstraction layer, part of Vecode Caldera
 * @author  biohzrdmx <github.com/biohzrdmx>
 * @copyright Copyright (c) 2022 Vecode. All rights reserved
 */

namespace Caldera\Tests\Storage;

use Exception;

use PHPUnit\Framework\TestCase;

use Caldera\Storage\Storage;
use Caldera\Storage\Adapter\S3Adapter;
use Caldera\Storage\StorageException;

class StorageWithS3AdapterTest extends TestCase {

	/**
	 * Adapter instance
	 * @var S3Adapter
	 */
	protected static $adapter;

	/**
	 * Storage instance
	 * @var Storage
	 */
	protected static $storage;

	protected function setUp(): void {
		# Create storage
		$bucket = getenv('S3_BUCKET') ?: '';
		$access_key = getenv('S3_ACCESS_KEY') ?: '';
		$secret_key = getenv('S3_SECRET_KEY') ?: '';
		$endpoint = getenv('S3_ENDPOINT') ?: '';
		self::$adapter = new S3Adapter($bucket, $access_key, $secret_key, $endpoint);
		self::$storage = new Storage(self::$adapter);
	}

	public function testFileExists() {
		# Check for a file that must exist
		$file = 'test/.gitignore';
		$exists = self::$storage->exists($file);
		$this->assertTrue($exists);
	}

	public function testGetFileSize() {
		# Try to get its size (in this this case it shoud be non-zero)
		$file = 'test/.gitignore';
		$size = self::$storage->size($file);
		$this->assertNotEquals(0, $size);
	}

	public function testGetAbsolutePath() {
		# Try to get its absolute path
		$file = 'test/.gitignore';
		$path = self::$storage->path($file);
		$this->assertNotEmpty($path);
	}

	public function testGetAbsolutePathOfDummyFile() {
		# Try to get its absolute path of non-existing file
		try {
			self::$storage->path('test/dummy.txt');
			$this->fail('This must throw a StorageException');
		} catch (Exception $e) {
			$this->assertInstanceOf(StorageException::class, $e);
		}
	}

	public function testGetFileLastModified() {
		# Try to get its last-modified timestamp
		$file = 'test/.gitignore';
		$lastModified = self::$storage->lastModified($file);
		$this->assertNotEmpty($lastModified);
	}

	public function testWriteThenReadFile() {
		# Write a file
		$file = 'test/storage_test.md';
		$contents = '# Storage Test';
		self::$storage->write($file, $contents, [
			'overwrite' => true,
			'Content-Type' => 'text/markdown',
			'x-amz-acl' => 'public-read'
		]);
		$data = self::$storage->read($file);
		$this->assertEquals($contents, $data);
	}

	public function testWriteFileWithoutOverwriteFlag() {
		# Try to write over existing file without overwrite flag
		$file = 'test/storage_test.md';
		$contents = '# Storage Test';
		try {
			self::$storage->write($file, $contents);
			$this->fail('This must throw a StorageException');
		} catch (Exception $e) {
			$this->assertInstanceOf(StorageException::class, $e);
		}
	}

	public function testAppendFile() {
		# Append data to existing file
		$file = 'test/storage_test.md';
		$result = self::$storage->append($file, '12345');
		$this->assertTrue($result);
	}

	public function testPrependFile() {
		# Prepend data to existing file
		$file = 'test/storage_test.md';
		$result = self::$storage->prepend($file, '12345');
		$this->assertTrue($result);
	}

	public function testCopyAndMoveFile() {
		# Copying and moving
		$file = 'test/storage_test.md';
		$result = self::$storage->copy($file, 'test/storage_copy.md');
		$this->assertTrue($result);
		$result = self::$storage->move('test/storage_copy.md', 'test/storage_move.md');
		$this->assertTrue($result);
		# Copying and moving, non-existing target
		$result = self::$storage->copy('dummy.txt', 'test/storage_copy.md');
		$this->assertFalse($result);
		$result = self::$storage->move('dummy.txt', 'test/storage_move.md');
		$this->assertFalse($result);
		# Make sure the file has the same contents
		$this->assertEquals(self::$storage->size($file), self::$storage->size('test/storage_move.md'));
	}

	public function testDeleteFile() {
		# Delete a file
		$file = 'test/storage_test.md';
		self::$storage->delete($file);
		self::$storage->delete('test/storage_move.md');
		# Check that the file has been deleted
		$missing = self::$storage->missing($file);
		$this->assertTrue($missing);
	}

	# Dummy tests, as the methods they test don't work on this adapter currently (but may do in a future release)

	public function testFileListingNonRecursive() {
		# File listing, non recursively
		$files = self::$storage->files('/');
		$this->assertIsArray($files);
		$this->assertEmpty($files);
	}

	public function testFileListingRecursive() {
		# File listing, recursively
		$files = self::$storage->files('/', true);
		$this->assertIsArray($files);
		$this->assertEmpty($files);
	}

	public function testDirectoryListingNonRecursive() {
		# List directories, non recursively
		$directories = self::$storage->directories('/');
		$this->assertIsArray($directories);
		$this->assertEmpty($directories);
	}

	public function testDirectoryListingRecursive() {
		# List directories, recursively
		$directories = self::$storage->directories('/', true);
		$this->assertIsArray($directories);
		$this->assertEmpty($directories);
	}

	public function testDeleteDirectory() {
		# Create directory
		self::$storage->createDirectory('dummy');
		# Delete directory
		$deleted = self::$storage->deleteDirectory('dummy');
		$this->assertFalse($deleted);
	}
}
