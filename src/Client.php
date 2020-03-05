<?php
namespace Acm;

use Acm\AcmException;
use Acm\Exception\NotFoundException;

define('DEFAULT_PORT', 8080);

/**
 * Class Aliyun_ACM_Client
 * The basic client to manage ACM
 */
class Client {
	
	protected $accessKey;
	
	protected $secretKey;
	
	protected $endPoint;
	
	protected $nameSpace;
	
	protected $port;
	
	protected $appName;
	
	public $serverList = [];
	
	public function __construct($endpoint, $port) {
		$this->endPoint = $endpoint;
		$this->port     = $port;
	}
	
	/**
	 * @param mixed $accessKey
	 */
	public function setAccessKey($accessKey) {
		$this->accessKey = $accessKey;
	}
	
	/**
	 * @param mixed $secretKey
	 */
	public function setSecretKey($secretKey) {
		$this->secretKey = $secretKey;
	}
	
	/**
	 * @param mixed $nameSpace
	 */
	public function setNameSpace($nameSpace) {
		$this->nameSpace = $nameSpace;
	}
	
	/**
	 * @param mixed $appName
	 */
	public function setAppName($appName) {
		$this->appName = $appName;
	}
	
	/**
	 * 获取可用服务器列表
	 *
	 * @return string
	 */
	private function getServerListStr(): string {
		$server_host = str_replace(['host', 'port'], [$this->endPoint, $this->port],
			'http://host:port/diamond-server/diamond');
		
		Request::getInstance()->doGet($server_host);
		
		if (Request::getInstance()->getResponseStatusCode() != '200') {
			print '[getServerList] got invalid http response: (' . $server_host . '.';
		}
		
		$serverRawList = Request::getInstance()->getResponseBody();
		return $serverRawList;
	}
	
	/**
	 * 从服务器池子中随机挑选一台进行请求
	 */
	public function refreshServerList() {
		$this->serverList = [];
		$serverRawList    = $this->getServerListStr();
		if (is_string($serverRawList)) {
			$serverArray = explode("\n", $serverRawList);
			$serverArray = array_filter($serverArray);
			foreach ($serverArray as $value) {
				$value            = trim($value);
				$singleServerList = explode(':', $value);
				$singleServer     = null;
				if (count($singleServerList) == 1) {
					$singleServer = new Server($value,
						constant('DEFAULT_PORT'),
						Util::isIpv4($value));
				} else {
					$singleServer = new Server($singleServerList[0],
						$singleServerList[1],
						Util::isIpv4($value));
				}
				$this->serverList[$singleServer->url] = $singleServer;
			}
		}
	}
	
	public function getServerList() {
		return $this->serverList;
	}
	
	public function getConfig($dataId, $group) {
		if (!is_string($this->secretKey) ||
			!is_string($this->accessKey)) {
			throw new AcmException('Invalid auth string', "invalid auth info for dataId: $dataId");
		}
		
		Util::checkDataId($dataId);
		$group = Util::checkGroup($group);
		
		$servers      = $this->serverList;
		$singleServer = $servers[array_rand($servers)];
		
		$acm_host = str_replace(['host', 'port'], [$singleServer->url, $singleServer->port],
			'http://host:port/diamond-server/config.co');
		
		$query_param = [
			'dataId' => urlencode($dataId),
			'group'  => urlencode($group),
			'tenant' => urlencode($this->nameSpace),
		];
		
		$headers = $this->getCommonHeaders($group);
		
		Request::getInstance()->doGet($acm_host, $query_param, $headers);
		
		if (Request::getInstance()->getResponseStatusCode() == StatusCode::NOT_FOUND) {
			throw new NotFoundException('Configure undefined');
		}
		
		// 其余未定义的错误场景
		if (Request::getInstance()->getResponseStatusCode() != '200') {
			throw new \Exception("[GETCONFIG] got invalid http response: ({$acm_host})");
		}
		$rawData = Request::getInstance()->getResponseBody();
		
		return $rawData;
	}
	
	/**
	 * 推送配置
	 *
	 * @param string $dataId
	 * @param string $group
	 * @param string $content
	 * @return string
	 * @throws AcmException
	 */
	public function publishConfig(string $dataId, string $group, string $content): string {
		if (!is_string($this->secretKey) ||
			!is_string($this->accessKey)) {
			throw new AcmException('Invalid auth string', "invalid auth info for dataId: $dataId");
		}
		
		Util::checkDataId($dataId);
		$group = Util::checkGroup($group);
		
		$servers      = $this->serverList;
		$singleServer = $servers[array_rand($servers)];
		
		$acm_host = str_replace(['host', 'port'], [$singleServer->url, $singleServer->port],
			'http://host:port/diamond-server/basestone.do?method=syncUpdateAll');
		
		$post_param = [
			'dataId'  => $dataId,
			'group'   => $group,
			'tenant'  => $this->nameSpace,
			'content' => $content,
		];
		
		if (is_string($this->appName)) {
			$post_param['appName'] = $this->appName;
		}
		
		$headers = $this->getCommonHeaders($group);
		
		Request::getInstance()->doPOST($acm_host, $post_param, $headers);
		
		if (Request::getInstance()->getResponseStatusCode() != 200) {
			throw new AcmException(StatusCode::UNKNOWN, '[PUBLISHCONFIG] got invalid http response: (' . $acm_host . '#' . Request::getInstance()
			                                                                                                                      ->getResponseStatusCode());
		}
		
		$rawData = Request::getInstance()->getResponseBody();
		
		return $rawData;
	}
	
	/**
	 * 获取指定租户的所有配置
	 *
	 * @param string $tenant
	 * @param int    $page
	 * @param int    $page_size
	 * @return array
	 * @throws AcmException
	 */
	public function getAllConfigByTenant(string $tenant, int $page, int $page_size): array {
		if (!is_string($this->secretKey) ||
			!is_string($this->accessKey)) {
			throw new AcmException('Invalid auth string', "invalid auth info");
		}
		
		$servers      = $this->serverList;
		$singleServer = $servers[array_rand($servers)];
		
		$acm_host = str_replace(['host', 'port'], [$singleServer->url, $singleServer->port],
			'http://host:port/diamond-server/basestone.do?method=getAllConfigByTenant');
		
		$query_param = [
			'tenant'   => urlencode($this->nameSpace),
			'pageNo'   => urlencode($page),
			'pageSize' => urlencode($page_size),
			'method'   => 'getAllConfigByTenant',
		];
		
		// 要注意，这个接口，签名不需要带上group，group要显式指定空字符串，不参与签名
		$headers = $this->getCommonHeaders('');
		
		Request::getInstance()->doGET($acm_host, $query_param, $headers);
		
		if (Request::getInstance()->getResponseStatusCode() != 200) {
			throw new AcmException(StatusCode::UNKNOWN, '[PUBLISHCONFIG] got invalid http response: (' . $acm_host . '#' . Request::getInstance()
			                                                                                                                      ->getResponseStatusCode());
		}
		
		$rawData = Request::getInstance()->getResponseBody();
		
		return json_decode($rawData, true);
	}
	
	
	public function removeConfig($dataId, $group) {
		if (!is_string($this->secretKey) ||
			!is_string($this->accessKey)) {
			throw new AcmException('Invalid auth string', "invalid auth info for dataId: $dataId");
		}
		
		Util::checkDataId($dataId);
		$group = Util::checkGroup($group);
		
		$servers      = $this->serverList;
		$singleServer = $servers[array_rand($servers)];
		
		$acm_host = str_replace(['host', 'port'], [$singleServer->url, $singleServer->port],
			'http://host:port/diamond-server//datum.do?method=deleteAllDatums');
		
		$post_param = [
			'dataId' => $dataId,
			'group'  => $group,
			'tenant' => $this->nameSpace,
		];
		
		$headers = $this->getCommonHeaders($group);
		
		Request::getInstance()->doPOST($acm_host, $post_param, $headers);
		
		if (Request::getInstance()->getResponseStatusCode() != '200') {
			print '[REMOVECONFIG] got invalid http response: (' . $acm_host . '#' . Request::getInstance()
			                                                                               ->getResponseStatusCode();
		}
		
		$rawData = Request::getInstance()->getResponseBody();
		
		return $rawData;
	}
	
	private function getCommonHeaders($group) {
		$headers                           = [];
		$headers['Diamond-Client-AppName'] = 'ACM-SDK-PHP';
		$headers['Client-Version']         = '0.0.1';
		$headers['Content-Type']           = 'application/x-www-form-urlencoded; charset=utf-8';
		$headers['exConfigInfo']           = 'true';
		$headers['Spas-AccessKey']         = $this->accessKey;
		
		$ts                   = round(microtime(true) * 1000);
		$headers['timeStamp'] = $ts;
		
		$signStr = $this->nameSpace . '+';
		if (is_string($group) && !empty($group)) {
			$signStr .= $group . "+";
		}
		$signStr                   = $signStr . $ts;
		$headers['Spas-Signature'] = base64_encode(hash_hmac('sha1', $signStr, $this->secretKey, true));
		return $headers;
	}
}