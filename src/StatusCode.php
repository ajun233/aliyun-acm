<?php

/**
 * 错误状态码定义
 */

namespace Acm;

class StatusCode {
	const SUCCESS      = 0;
	const NOT_FOUND    = 404;
	const CLIENT_ERROR = 400; // 由客户端语法引发的报错，一般是发送的内容问题
	const TIME_OUT     = 408;
	const UNKNOWN      = 1024;
}