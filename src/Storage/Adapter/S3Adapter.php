<?php

declare(strict_types = 1);

/**
 * Caldera Storage
 * Storage abstraction layer, part of Vecode Caldera
 * @author  biohzrdmx <github.com/biohzrdmx>
 * @copyright Copyright (c) 2022 Vecode. All rights reserved
 */

namespace Caldera\Storage\Adapter;

use S3\S3;

use Caldera\Storage\StorageException;

class S3Adapter implements AdapterInterface {

	/**
	 * Bucket name
	 * @var string
	 */
	protected $bucket = '';

	/**
	 * S3 instance
	 * @var S3
	 */
	protected $s3;

	/**
	 * Cache array
	 * @var array
	 */
	protected $cache = [];

	/**
	 * Constructor
	 * @param string $bucket     Bucket name
	 * @param string $access_key Access key
	 * @param string $secret_key Secret key
	 * @param string $endpoint   Endpoint URL
	 */
	public function __construct(string $bucket, string $access_key, string $secret_key, string $endpoint = '') {
		$this->bucket = $bucket;
		$this->s3 = new S3($access_key, $secret_key, $endpoint);
	}

	/**
	 * Check if the file exists
	 * @param  string $path Path to the file
	 * @return bool
	 */
	public function exists(string $path): bool {
		$metadata = $this->getMetadata($path);
		return $metadata !== null && $metadata->code == 200;
	}

	/**
	 * Read file contents
	 * @param  string $path Path to the file
	 * @return string
	 */
	public function read(string $path): string {
		$response = $this->s3->getObject($this->bucket, $path);
		return $response->error === null ? $response->body : '';
	}

	/**
	 * Write file contents
	 * @param  string $path     Path to the file
	 * @param  string $contents Data to write
	 * @param  array  $config   Config array
	 * @return bool
	 */
	public function write(string $path, string $contents, array $config = []): bool {
		$overwrite = $config['overwrite'] ?? false;
		$headers = [];
		if ($config) {
			foreach ($config as $key => $value) {
				if ($key == 'overwrite') continue;
				$headers[$key] = $value;
			}
		}
		if ( $this->exists($path) && !$overwrite ) {
			throw new StorageException($this, 'File already exists');
		}
		$response = $this->s3->putObject($this->bucket, $path, $contents, $headers);
		return $response->error === null;
	}

	/**
	 * Write file contents
	 * @param  string $path      Path to the file
	 * @return bool
	 */
	public function delete(string $path): bool {
		$response = $this->s3->deleteObject($this->bucket, $path);
		return $response->error === null;
	}

	/**
	 * Get file size
	 * @param  string $path Path to the file
	 * @return int
	 */
	public function size(string $path): int {
		$ret = 0;
		$metadata = $this->getMetadata($path);
		if ($metadata) {
			$ret = (int) ($metadata->headers['Content-Length'] ?? 0);
		} else {
			$ret = 0;
		}
		return $ret;
	}

	/**
	 * Get file last modified timestamp
	 * @param  string $path Path to the file
	 * @return int
	 */
	public function lastModified(string $path): int {
		$ret = 0;
		$metadata = $this->getMetadata($path);
		if ($metadata) {
			$last_modified = $metadata->headers['Last-Modified'] ?? '';
			$ret = $last_modified ? strtotime($last_modified) : 0;
		} else {
			$ret = 0;
		}
		return $ret;
	}

	/**
	 * Get file absolute path
	 * @param  string $path Path to the file
	 * @return string
	 */
	public function path(string $path): string {
		$ret = '';
		if ( $this->exists($path) ) {
			$ret = $path;
		} else {
			throw new StorageException($this, 'File does not exist');
		}
		return $ret;
	}

	/**
	 * Copy a file to a new location
	 * @param  string  $from Copy from
	 * @param  string  $to   Copy to
	 * @return bool
	 */
	public function copy(string $from, string $to): bool {
		if ( $this->exists($from) ) {
			return $this->write( $to, $this->read($from) );
		} else {
			return false;
		}
	}

	/**
	 * Move a file to a new location
	 * @param  string  $from Move from
	 * @param  string  $to   Move to
	 * @return bool
	 */
	public function move(string $from, string $to): bool {
		if ( $this->exists($from) ) {
			$write = $this->write( $to, $this->read($from) );
			$delete = $this->delete($from);
			return ($write && $delete);
		} else {
			return false;
		}
	}

	/**
	 * Get files in the given directory
	 * @param  string  $directory Directory to scan
	 * @param  bool    $recursive Whether to scan the directory recursively or not
	 * @return array
	 */
	public function files(string $directory, bool $recursive = false): array {
		return [];
	}

	/**
	 * Get directories in the given directory
	 * @param  string  $directory Directory to scan
	 * @param  bool    $recursive Whether to scan the directory recursively or not
	 * @return array
	 */
	public function directories(string $directory, bool $recursive = false): array {
		return [];
	}

	/**
	 * Create a directory
	 * @param  string $path Directory path
	 * @return bool
	 */
	public function createDirectory(string $path): bool {
		return false;
	}

	/**
	 * Delete a directory
	 * @param  string $path Directory path
	 * @return bool
	 */
	public function deleteDirectory(string $path): bool {
		return false;
	}

	/**
	 * Get object metadata
	 * @param  string $path Object path
	 * @return mixed
	 */
	protected function getMetadata(string $path) {
		$ret = null;
		$hash = md5($path);
		if ( isset( $this->cache[$hash] ) ) {
			$ret = $this->cache[$hash];
		} else {
			$response = $this->s3->getObjectInfo($this->bucket, $path);
			if ( $response->error === null && $response->code === 200 ) {
				$this->cache[$hash] = $response;
				$ret = $response;
			}
		}
		return $ret;
	}
}
