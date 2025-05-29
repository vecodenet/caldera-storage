<?php

declare(strict_types = 1);

/**
 * Caldera Storage
 * Storage abstraction layer, part of Vecode Caldera
 * @author  biohzrdmx <github.com/biohzrdmx>
 * @copyright Copyright (c) 2022 Vecode. All rights reserved
 */

namespace Caldera\Storage;

use Caldera\Storage\Adapter\AdapterInterface;

class Storage {

	/**
	 * Storage adapter
	 * @var AdapterInterface
	 */
	protected AdapterInterface $adapter;

	/**
	 * Constructor
	 * @param AdapterInterface $adapter Storage adapter
	 */
	public function __construct(AdapterInterface $adapter) {
		$this->adapter = $adapter;
	}

	/**
	 * Check if the file exists
	 * @param  string $path Path to the file
	 * @return bool
	 */
	public function exists(string $path): bool {
		return $this->adapter->exists($path);
	}

	/**
	 * Check if the file does not exist
	 * @param  string $path Path to the file
	 * @return bool
	 */
	public function missing(string $path): bool {
		return !$this->adapter->exists($path);
	}

	/**
	 * Read file contents
	 * @param  string $path Path to the file
	 * @return string
	 */
	public function read(string $path): string {
		return $this->adapter->read($path);
	}

	/**
	 * Write file contents
	 * @param  string $path     Path to the file
	 * @param  string $contents Data to write
	 * @param  array  $config   Config array
	 * @return bool
	 */
	public function write(string $path, string $contents, array $config = []): bool {
		return $this->adapter->write($path, $contents, $config);
	}

	/**
	 * Prepend to a file.
	 * @param  string  $path
	 * @param  string  $data
	 * @param  string  $separator
	 * @return bool
	 */
	public function prepend($path, $data, $separator = PHP_EOL): bool {
		if ($this->exists($path)) {
			return $this->write($path, $data . $separator . $this->read($path), ['overwrite' => true]);
		}
		return $this->write($path, $data);
	}

	/**
	 * Append to a file.
	 * @param  string  $path
	 * @param  string  $data
	 * @param  string  $separator
	 * @return bool
	 */
	public function append($path, $data, $separator = PHP_EOL): bool {
		if ($this->exists($path)) {
			return $this->write($path, $this->read($path) . $separator . $data, ['overwrite' => true]);
		}
		return $this->write($path, $data);
	}

	/**
	 * Write file contents
	 * @param  string $path Path to the file
	 * @return bool
	 */
	public function delete(string $path): bool {
		return $this->adapter->delete($path);
	}

	/**
	 * Get file size
	 * @param  string $path Path to the file
	 * @return int
	 */
	public function size(string $path): int {
		return $this->adapter->size($path);
	}

	/**
	 * Get file last modified timestamp
	 * @param  string $path Path to the file
	 * @return int
	 */
	public function lastModified(string $path): int {
		return $this->adapter->lastModified($path);
	}

	/**
	 * Get file absolute path
	 * @param  string $path Path to the file
	 * @return string
	 */
	public function path(string $path): string {
		return $this->adapter->path($path);
	}

	/**
	 * Copy a file to a new location
	 * @param  string  $from Copy from
	 * @param  string  $to   Copy to
	 * @return bool
	 */
	public function copy(string $from, string $to): bool {
		return $this->adapter->copy($from, $to);
	}

	/**
	 * Move a file to a new location
	 * @param  string  $from Move from
	 * @param  string  $to   Move to
	 * @return bool
	 */
	public function move(string $from, string $to): bool {
		return $this->adapter->move($from, $to);
	}

	/**
	 * Get files in the given directory
	 * @param  string  $directory Directory to scan
	 * @param  bool    $recursive Whether to scan the directory recursively or not
	 * @return array
	 */
	public function files(string $directory, bool $recursive = false): array {
		return $this->adapter->files($directory, $recursive);
	}

	/**
	 * Get directories in the given directory
	 * @param  string  $directory Directory to scan
	 * @param  bool    $recursive Whether to scan the directory recursively or not
	 * @return array
	 */
	public function directories(string $directory, bool $recursive = false): array {
		return $this->adapter->directories($directory, $recursive);
	}

	/**
	 * Create a directory
	 * @param  string $path Directory path
	 * @return bool
	 */
	public function createDirectory(string $path): bool {
		return $this->adapter->createDirectory($path);
	}

	/**
	 * Delete a directory
	 * @param  string $path Directory path
	 * @return bool
	 */
	public function deleteDirectory(string $path): bool {
		return $this->adapter->deleteDirectory($path);
	}
}
