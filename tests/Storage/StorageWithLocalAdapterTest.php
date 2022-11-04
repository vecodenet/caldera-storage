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
use Caldera\Storage\Adapter\LocalAdapter;
use Caldera\Storage\StorageException;

class StorageWithLocalAdapterTest extends TestCase {

	/**
	 * Storage path
	 * @var string
	 */
	protected static $path;

	/**
	 * Adapter instance
	 * @var LocalAdapter
	 */
	protected static $adapter;

	/**
	 * Storage instance
	 * @var Storage
	 */
	protected static $storage;

	protected function setUp(): void {
		# Create storage
		self::$path = dirname(__DIR__) . '/output';
		self::$adapter = new LocalAdapter(self::$path);
		self::$storage = new Storage(self::$adapter);
	}

	public function testFileExists() {
		# Check for a file that must exist
		$file = '.gitignore';
		$exists = self::$storage->exists($file);
		$this->assertTrue($exists);
	}

	public function testGetFileSize() {
		# Try to get its size (in this this case it shoud be non-zero)
		$file = '.gitignore';
		$size = self::$storage->size($file);
		$this->assertNotEquals(0, $size);
	}

	public function testGetAbsolutePath() {
		# Try to get its absolute path
		$file = '.gitignore';
		$path = self::$storage->path($file);
		$this->assertNotEmpty($path);
		$this->assertTrue( file_exists($path) );
	}

	public function testGetAbsolutePathOfDummyFile() {
		# Try to get its absolute path of non-existing file
		try {
			self::$storage->path('dummy.txt');
			$this->fail('This must throw a StorageException');
		} catch (Exception $e) {
			$this->assertInstanceOf(StorageException::class, $e);
		}
	}

	public function testGetFileLastModified() {
		# Try to get its last-modified timestamp
		$file = '.gitignore';
		$lastModified = self::$storage->lastModified($file);
		$this->assertNotEmpty($lastModified);
	}

	public function testDirectoryTraversalMustThrowException() {
		# Try to do a directory traversal
		try {
			self::$storage->exists('../bootstrap.php');
			$this->fail('This must throw a StorageException');
		} catch (StorageException $e) {
			$this->assertInstanceOf(LocalAdapter::class, $e->getAdapter());
		} catch (Exception $e) {
			$this->fail('The exception must be an instance of StorageException');
		}
	}

	public function testUnicodeWhitespaceMustThrowException() {
		# Try to sneak unicode garbage
		try {
			self::$storage->exists("s\x09i.php");
			$this->fail('This must throw a StorageException');
		} catch (Exception $e) {
			$this->assertInstanceOf(StorageException::class, $e);
		}
	}

	public function testWriteThenReadFile() {
		# Write a file
		$file = 'storage_test.md';
		$contents = '# Storage Test';
		self::$storage->write($file, $contents, ['overwrite' => true]);
		$data = self::$storage->read($file);
		$this->assertEquals($contents, $data);
	}

	public function testWriteFileWithoutOverwriteFlag() {
		# Try to write over existing file without overwrite flag
		$file = 'storage_test.md';
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
		$file = 'storage_test.md';
		$result = self::$storage->append($file, '12345');
		$this->assertTrue($result);
	}

	public function testPrependFile() {
		# Prepend data to existing file
		$file = 'storage_test.md';
		$result = self::$storage->prepend($file, '12345');
		$this->assertTrue($result);
	}

	public function testCopyAndMoveFile() {
		# Copying and moving
		$file = 'storage_test.md';
		self::$storage->copy($file, 'storage_copy.md');
		self::$storage->move('storage_copy.md', 'storage_move.md');
		# Copying and moving, non-existing target
		$result = self::$storage->copy('dummy.txt', 'storage_copy.md');
		$this->assertFalse($result);
		$result = self::$storage->move('dummy.txt', 'storage_move.md');
		$this->assertFalse($result);
		# Make sure the file has the same contents
		$this->assertEquals(self::$storage->size($file), self::$storage->size('storage_move.md'));
	}

	public function testDeleteFile() {
		# Delete a file
		$file = 'storage_test.md';
		$data = self::$storage->delete($file);
		$data = self::$storage->delete('storage_move.md');
		# Check that the file has been deleted
		$missing = self::$storage->missing($file);
		$this->assertTrue($missing);
	}

	public function testFileListingNonRecursive() {
		# Create a new storage object
		$path = dirname(__DIR__);
		$adapter = new LocalAdapter($path);
		$storage = new Storage($adapter);
		# File listing, non recursively
		$files = $storage->files('/');
		$this->assertIsArray($files);
		$this->assertContains(dirname(__DIR__) . DIRECTORY_SEPARATOR . 'bootstrap.php', $files);
	}

	public function testFileListingRecursive() {
		# Create a new storage object
		$path = dirname(__DIR__);
		$adapter = new LocalAdapter($path);
		$storage = new Storage($adapter);
		# File listing, recursively
		$files = $storage->files('/', true);
		$this->assertIsArray($files);
		$this->assertContains(dirname(__DIR__) . DIRECTORY_SEPARATOR . 'output' . DIRECTORY_SEPARATOR . '.gitignore', $files);
	}

	public function testDirectoryListingNonRecursive() {
		# Create a new storage object
		$path = dirname(__DIR__);
		$adapter = new LocalAdapter($path);
		$storage = new Storage($adapter);
		# List directories, non recursively
		$directories = $storage->directories('/');
		$this->assertIsArray($directories);
		$this->assertContains(dirname(__DIR__) . DIRECTORY_SEPARATOR . 'output', $directories);
	}

	public function testDirectoryListingRecursive() {
		# Create a new storage object
		$path = dirname(__DIR__);
		$adapter = new LocalAdapter($path);
		$storage = new Storage($adapter);
		# Create directory
		$storage->createDirectory('output/test');
		# List directories, recursively
		$directories = $storage->directories('/', true);
		$this->assertIsArray($directories);
		# Check if directoy was created
		$this->assertContains(dirname(__DIR__) . DIRECTORY_SEPARATOR . 'output' . DIRECTORY_SEPARATOR . 'test', $directories);
	}

	public function testDeleteDirectory() {
		# Create a new storage object
		$path = dirname(__DIR__);
		$adapter = new LocalAdapter($path);
		$storage = new Storage($adapter);
		# Delete directory
		$deleted = $storage->deleteDirectory('output/test');
		$this->assertTrue($deleted);
		# Try to delete non-existing directory
		try {
			$storage->deleteDirectory('output/foo');
			$this->fail('This must throw a StorageException');
		} catch (Exception $e) {
			$this->assertInstanceOf(StorageException::class, $e);
		}
	}
}
