<?php

namespace Acm;

class Server {
	
	public $url;
	
	public $port;
	
	public $isIpv4;
	
	public function __construct($url, $port, $isIpv4) {
		$this->url    = $url;
		$this->port   = $port;
		$this->isIpv4 = $isIpv4;
	}
}