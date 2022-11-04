<?php

declare(strict_types = 1);

/**
 * Caldera Storage
 * Storage abstraction layer, part of Vecode Caldera
 * @author  biohzrdmx <github.com/biohzrdmx>
 * @copyright Copyright (c) 2022 Vecode. All rights reserved
 */

namespace Caldera\Storage;

use RuntimeException;
use Throwable;

use Caldera\Storage\Adapter\AdapterInterface;

class StorageException extends RuntimeException {

	/**
	 * Adapter instance
	 * @var AdapterInterface
	 */
	protected $adapter;

	/**
	 * Constructor
	 * @param AdapterInterface $adapter Adapter instance
	 */
	public function __construct(AdapterInterface $adapter, string $message = '', int $code = 0, ?Throwable $previous = null) {
		parent::__construct($message, $code, $previous);
		$this->adapter = $adapter;
	}

	/**
	 * Get adapter instance
	 * @return AdapterInterface
	 */
	public function getAdapter(): AdapterInterface {
		return $this->adapter;
	}
}
