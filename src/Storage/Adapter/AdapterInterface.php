<?php

declare(strict_types = 1);

/**
 * Caldera Storage
 * Storage abstraction layer, part of Vecode Caldera
 * @author  biohzrdmx <github.com/biohzrdmx>
 * @copyright Copyright (c) 2022 Vecode. All rights reserved
 */

namespace Caldera\Storage\Adapter;

interface AdapterInterface {

	/**
	 * Check if the file exists
	 * @param  string $path Path to the file
	 * @return bool
	 */
	public function exists(string $path): bool;

	/**
	 * Read file contents
	 * @param  string $path Path to the file
	 * @return string
	 */
	public function read(string $path): string;

	/**
	 * Write file contents
	 * @param  string $path      Path to the file
	 * @param  string $content   Data to write
	 * @param  array  $config   Config array
	 * @return bool
	 */
	public function write(string $path, string $content, array $config = []): bool;

	/**
	 * Write file contents
	 * @param  string $path Path to the file
	 * @return bool
	 */
	public function delete(string $path): bool;

	/**
	 * Get file size
	 * @param  string $path Path to the file
	 * @return int
	 */
	public function size(string $path): int;

	/**
	 * Get file last modified timestamp
	 * @param  string $path Path to the file
	 * @return int
	 */
	public function lastModified(string $path): int;

	/**
	 * Get file absolute path
	 * @param  string $path Path to the file
	 * @return string
	 */
	public function path(string $path): string;

	/**
	 * Copy a file to a new location
	 * @param  string  $from Copy from
	 * @param  string  $to   Copy to
	 * @return bool
	 */
	public function copy(string $from, string $to): bool;

	/**
	 * Move a file to a new location
	 * @param  string  $from Move from
	 * @param  string  $to   Move to
	 * @return bool
	 */
	public function move(string $from, string $to): bool;

	/**
	 * Get files in the given directory
	 * @param  string  $directory Directory to scan
	 * @param  bool    $recursive Whether to scan the directory recursively or not
	 * @return array
	 */
	public function files(string $directory, bool $recursive = false): array;

	/**
	 * Get directories in the given directory
	 * @param  string  $directory Directory to scan
	 * @param  bool    $recursive Whether to scan the directory recursively or not
	 * @return array
	 */
	public function directories(string $directory, bool $recursive = false): array;

	/**
	 * Create a directory
	 * @param  string $path Directory path
	 * @return bool
	 */
	public function createDirectory(string $path): bool;

	/**
	 * Delete a directory
	 * @param  string $path Directory path
	 * @return bool
	 */
	public function deleteDirectory(string $path): bool;
}
