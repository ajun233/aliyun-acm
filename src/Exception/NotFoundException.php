<?php

/**
 * 未找到对应配置项
 */

namespace Acm\Exception;

use \Acm\StatusCode;
use \Throwable;

class NotFoundException extends \Exception {
	public function __construct(string $message = "", int $code = 0, Throwable $previous = null) {
		parent::__construct($message, StatusCode::NOT_FOUND, $previous);
	}
}