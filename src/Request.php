<?php

/**
 * 执行网络请求
 */

namespace Acm;

class Request {
	/**
	 * @var string
	 */
	private $responseBody;
	
	/**
	 * @var int
	 */
	private $responseStatusCode;
	
	/**
	 * @var array
	 */
	private $responseHeader;
	
	/**
	 * 默认网络超时时间
	 *
	 * @var int
	 */
	const DEFAULT_TIME_OUT = 5;
	
	/**
	 * POST请求的content-type
	 */
	const POST_CONTENT_TYPE = [
		'JSON' => 'json',
		'FORM' => 'form_params',
	];
	
	/**
	 * @var \GuzzleHttp\Client
	 */
	private $guzzle;
	
	/**
	 * @var Request
	 */
	private static $instance;
	
	private function __construct(int $timeout = self::DEFAULT_TIME_OUT) {
		$this->guzzle = new \GuzzleHttp\Client(['timeout' => $timeout]);
	}
	
	public static function getInstance() {
		if (!(self::$instance instanceof self)) {
			self::$instance = new self();
		}
		return self::$instance;
	}
	
	/**
	 * 执行GET请求
	 *
	 * @param string $url
	 * @param array  $query_param
	 * @param array  $header
	 * @return void
	 */
	public function doGet(string $url, array $query_param = [], array $header = []): void {
		$response = $this->guzzle->get($url, ['headers' => $header, 'query' => $query_param]);
		$this->setResponseStatusCode($response->getStatusCode());
		$this->setResponseBody($response->getBody()->__toString());
		$this->setResponseHeader($response->getHeaders());
		
		return;
	}
	
	/**
	 * 执行POST请求
	 *
	 * @param string $url
	 * @param array  $post_param
	 * @param array  $header
	 * @param string $content_type
	 * @return void
	 */
	public function doPOST(string $url, array $post_param = [], array $header = [], string $content_type = self::POST_CONTENT_TYPE['FORM']): void {
		$response = $this->guzzle->post($url, ['headers' => $header, $content_type => $post_param]);
		
		$this->setResponseStatusCode($response->getStatusCode());
		$this->setResponseBody($response->getBody()->__toString());
		$this->setResponseHeader($response->getHeaders());
		
		return;
	}
	
	private function setResponseStatusCode(int $code) {
		$this->responseStatusCode = $code;
	}
	
	private function setResponseBody(string $body) {
		$this->responseBody = $body;
	}
	
	private function setResponseHeader(array $header) {
		$this->responseHeader = $header;
	}
	
	public function getResponseBody() {
		return $this->responseBody;
	}
	
	public function getResponseStatusCode() {
		return $this->responseStatusCode;
	}
	
	public function getResponseHeader() {
		return $this->responseHeader;
	}
}