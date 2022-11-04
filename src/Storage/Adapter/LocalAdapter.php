<?php

declare(strict_types = 1);

/**
 * Caldera Storage
 * Storage abstraction layer, part of Vecode Caldera
 * @author  biohzrdmx <github.com/biohzrdmx>
 * @copyright Copyright (c) 2022 Vecode. All rights reserved
 */

namespace Caldera\Storage\Adapter;

use DirectoryIterator;
use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

use Caldera\Storage\StorageException;

class LocalAdapter implements AdapterInterface {

	/**
	 * Root directory
	 * @var string
	 */
	protected $path = '';

	/**
	 * Constructor
	 * @param string $path Root path
	 */
	public function __construct(string $path) {
		$this->path = rtrim($path, DIRECTORY_SEPARATOR);
	}

	/**
	 * Check if the file exists
	 * @param  string $path Path to the file
	 * @return bool
	 */
	public function exists(string $path): bool {
		$path = $this->getAbsolutePath($path);
		return file_exists($path);
	}

	/**
	 * Read file contents
	 * @param  string $path Path to the file
	 * @return string
	 */
	public function read(string $path): string {
		$path = $this->path($path);
		$contents = @file_get_contents($path);
		return $contents ?: '';
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
		if ( $this->exists($path) && !$overwrite ) {
			throw new StorageException($this, 'File already exists');
		}
		$path = $this->getAbsolutePath($path);
		return @file_put_contents($path, $contents) > 0;
	}

	/**
	 * Write file contents
	 * @param  string $path      Path to the file
	 * @return bool
	 */
	public function delete(string $path): bool {
		$path = $this->path($path);
		return unlink($path);
	}

	/**
	 * Get file size
	 * @param  string $path Path to the file
	 * @return int
	 */
	public function size(string $path): int {
		$path = $this->path($path);
		$size = filesize($path);
		return $size ?: 0;
	}

	/**
	 * Get file last modified timestamp
	 * @param  string $path Path to the file
	 * @return int
	 */
	public function lastModified(string $path): int {
		$path = $this->path($path);
		$time = filemtime($path);
		return $time ?: 0;
	}

	/**
	 * Get file absolute path
	 * @param  string $path Path to the file
	 * @return string
	 */
	public function path(string $path): string {
		$ret = '';
		if ( $this->exists($path) ) {
			$ret = $this->getAbsolutePath($path);
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
		$from = $this->getAbsolutePath($from);
		$to = $this->getAbsolutePath($to);
		if ( file_exists($from) ) {
			return copy($from, $to);
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
		$from = $this->getAbsolutePath($from);
		$to = $this->getAbsolutePath($to);
		if ( file_exists($from) ) {
			return rename($from, $to);
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
		$path = $this->getAbsolutePath($directory);
		return $recursive ? $this->getDirectoryContentsRecursively($path) : $this->getDirectoryContents($path);
	}

	/**
	 * Get directories in the given directory
	 * @param  string  $directory Directory to scan
	 * @param  bool    $recursive Whether to scan the directory recursively or not
	 * @return array
	 */
	public function directories(string $directory, bool $recursive = false): array {
		$path = $this->getAbsolutePath($directory);
		return $recursive ? $this->getDirectoryContentsRecursively($path, 1) : $this->getDirectoryContents($path, 1);
	}

	/**
	 * Create a directory
	 * @param  string $path Directory path
	 * @return bool
	 */
	public function createDirectory(string $path): bool {
		$ret = false;
		$path = $this->getAbsolutePath($path);
		if ( @mkdir($path, 0755, true) ) {
			$ret = true;
		}
		return $ret;
	}

	/**
	 * Delete a directory
	 * @param  string $path Directory path
	 * @return bool
	 */
	public function deleteDirectory(string $path): bool {
		$ret = false;
		$path = $this->getAbsolutePath($path);
		if ( file_exists($path) && is_dir($path) ) {
			$ret = @rmdir($path);
		} else {
			throw new StorageException($this, 'Invalid directory');
		}
		return $ret;
	}

	/**
	 * List directory contents
	 * @param  string $path   Directory path
	 * @param  int    $filter Filter type
	 * @return array
	 */
	protected function getDirectoryContents(string $path, int $filter = 0): array {
		$iterator = new DirectoryIterator($path);
		$files = [];
		foreach ($iterator as $file) {
			switch ($filter) {
				case 0: # Only files
					if (! $file->isDir() ) {
						$files[] = $file->getPathname();
					}
				break;
				case 1: # Only directories
					if ( $file->isDir() && !$file->isDot() ) {
						$files[] = $file->getPathname();
					}
				break;
			}
		}
		sort($files, SORT_REGULAR | SORT_FLAG_CASE);
		return $files;
	}

	/**
	 * List directory contents recursively
	 * @param  string $path   Directory path
	 * @param  int    $filter Filter type
	 * @return array
	 */
	protected function getDirectoryContentsRecursively(string $path, int $filter = 0): array {
		$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::SELF_FIRST);
		$files = [];
		foreach ($iterator as $file) {
			switch ($filter) {
				case 0: # Only files
					if (! $file->isDir() ) {
						$files[] = $file->getPathname();
					}
				break;
				case 1: # Only directories
					if ( $file->isDir() ) {
						$files[] = $file->getPathname();
					}
				break;
			}
		}
		sort($files, SORT_REGULAR | SORT_FLAG_CASE);
		return $files;
	}

	/**
	 * Get the absolute path of a file
	 * @param  string $path Path to the file
	 * @return string
	 */
	protected function getAbsolutePath(string $path) {
		$path = ltrim( $path, '/' );
		$path = $this->normalizePath($path);
		return sprintf('%s%s%s', $this->path, '/', $path);
	}

	/**
	 * Normalize file path
	 * @param  string $path Path to normalize
	 * @return string
	 */
	protected function normalizePath(string $path): string {
		# Replace backslashes with slashes
		$path = str_replace('\\', '/', $path);
		# Block Unicode white-space
		if ( preg_match('#\p{C}+#u', $path) ) {
			throw new StorageException($this, 'Invalid path');
		}
		# Check parts for path traversal
		$parts = [];
		foreach (explode('/', $path) as $part) {
			switch ($part) {
				case '':
				case '.':
					break;
				case '..':
					if ( empty($parts) ) {
						throw new StorageException($this, 'Directory traversal detected');
					}
					array_pop($parts);
					break;
				default:
					$parts[] = $part;
					break;
			}
		}
		# Rebuild the path
		return implode('/', $parts);
	}
}
