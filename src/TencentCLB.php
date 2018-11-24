
<?php
/**
 * Tencent CLB Class
 *
 * PHP version 7
 *
 * @category  PHP
 * @author    耿贤坤 <gengxiankun@126.com>
 */
namespace gengxiankun\phpclb;

use GuzzleHttp\Client;

/**
 * Tencent CLB Class
 *
 * @property string secretId  腾讯云Api安全凭证，用于标识 API 调用者身份
 * @property string secretKey 腾讯云Api安全凭证，用于加密签名字符串和服务器端验证签名字符串的密钥
 * @property string region 	  腾讯云实例所在区域
 *
 * @category  PHP
 * @author    耿贤坤 <gengxiankun@126.com>
 */
class TencentCLB
{
	/**
	 * 腾讯云Api安全凭证，用于标识 API 调用者身份
	 */
	public $secretId;

	/**
	 * 腾讯云Api安全凭证，用于加密签名字符串和服务器端验证签名字符串的密钥
	 */
	public $secretKey;

	/**
	 * 腾讯云实例所在区域
	 */
	public $region = 'gz';

	/**
	 * Guzzle客户端
	 */
	protected $_guzzleClient;

	/**
	 * 腾讯云 base host
	 */
	protected $_baseUrl;

	/**
	 * 腾讯云 clb api
	 */
	const CLB_BASE_URL = 'https://lb.api.qcloud.com/v2/index.php';

	/**
	 * 修改应用型七层监听器转发规则上云服务器的权重的Action
	 */
	const MFSB_ACTION = 'ModifyForwardSeventhBackends';

	/**
	 * 修改应用型四层监听器转发规则上云服务器的权重的Action
	 */
	const MFFBW_ACTION = 'ModifyForwardFourthBackendsWeight';

	/**
	 * 修改应用型七层监听器转发规则上云服务器的端口的Action
	 */
	const MFSBP_ACTION = 'ModifyForwardSeventhBackendsPort';

	/**
	 * 修改应用型四层监听器转发规则上云服务器的端口的Action
	 */
	const MFFBP_ACTION = 'ModifyForwardFourthBackendsPort';

	/**
	 * 查询应用型负载均衡云服务器列表的Action
	 */
	const DFLB_ACTION = 'DescribeForwardLBBackends';

	/**
	 * 绑定云服务器到应用型负载均衡七层监听器的转发规则上的Action
	 */
	const RIWLBSL_ACTIOM = 'RegisterInstancesWithForwardLBSeventhListener';

	/**
	 * 绑定云服务器到应用型负载均衡四层监听器的转发规则上的Action
	 */
	const RIWFLBFL_ACTION = 'RegisterInstancesWithForwardLBFourthListener';

	/**
	 * 解绑应用型负载均衡七层监听器转发规则上的云服务器的Action
	 */
	const DIFFLB_ACTION = 'DeregisterInstancesFromForwardLB';

	/**
	 * 解绑应用型负载均衡四层监听器转发规则上的云服务器的Action
	 */
	const DIFFLBFL_ACTION = 'DeregisterInstancesFromForwardLBFourthListener';

	/**
	 * api签名方式
	 */
	const SIGNA_TURE_METHODS = 'HmacSHA256';

	/**
	 * 初始化
	 */
	public function __construct($config = null)
	{
		@extract($config);

		$this->secretId = $secretId ?? $this->secretId;
		$this->secretKey = $secretKey ?? $this->secretKey;
		$this->region = $region ?? $this->region;
	}

	/**
	 * 获取Guzzle的客户端
	 *
	 * @return \GuzzleHttp\Client
	 */
	protected function guzzleClient()
	{
		if (!($this->_guzzleClient instanceof Client)) {
			$this->_guzzleClient = new Client([
				// Base URI is used with relative requests
    			'base_uri' => self::CLB_BASE_URL,
    			// You can set any number of default request options.
    			'timeout'  => 5.0,
			]);
		}

		return $this->_guzzleClient;
	}

	/**
	 * 出去http标示，生成相关Url的host
	 *
	 * @return string
	 */
	protected function getBaseHost()
	{
		if (!$this->_baseUrl) {
			$this->_baseUrl = substr(self::CLB_BASE_URL, 8);
		}

		return $this->_baseUrl;
	}

	/**
	 * 生成api签名串
	 *
	 * @param parameter 	array  请求参数
	 * @param requestMethod string 请求方式 GET/POST
	 *
	 * @return string 签名串
	 */
	protected function getSignStr($parameter, $requestMethod)
	{
		ksort($parameter);

		$srcStr = $requestMethod . $this->getBaseHost() . '?' . http_build_query($parameter);

		$signStr = base64_encode(hash_hmac('sha1', $srcStr, $this->secretKey, true));

		return $signStr;
	}

	/**
	 * 获取公共请求参数
	 *
	 * @param action string 相关借口Action
	 *
	 * @return arrray 公共参数
	 */
	protected function getPublicRequestParameter($action)
	{
		// 拼装公共请求参数
		$parameter = [];
		$parameter['Action'] = $action;
		$parameter['Region'] = $this->region;
		$parameter['Timestamp'] = time();
		$parameter['Nonce'] = mt_rand(10000, 99999);
		$parameter['SecretId'] = $this->secretId;

		return $parameter;
	}

	protected function getQueryParameter($action, $requestParameter, $requestMethod = 'GET')
	{
		$query = array_merge($this->getPublicRequestParameter($action), $requestParameter);
		$query['Signature'] = $this->getSignStr($query, $requestMethod);

		return $query;
	}

	/**
	 * 修改应用型七层监听器转发规则上云服务器的权重
	 *
	 * @param loadBalancerId 		string 负载均衡实例 ID，可通过 [DescribeLoadBalancers](https://cloud.tencent.com/document/api/214/1261) 接口同时入参 forward 字段为 1 或者 -1 来查询。
	 * @param listenerId     		string 应用型负载均衡监听器 ID，可通过 DescribeForwardLBListeners 接口查询。
	 * @param backends_n_instanceId string 云服务器的唯一 ID，可通过 DescribeInstances 接口返回字段中的 unInstanceId 字段获取；此接口支持同时输入多台主机的实例 ID（如：要输入两台主机，则设置 backends.1.instanceId&backends.2.instanceId）。
	 * @param backends_n_port 		int    负载均衡实例监听器后端云服务器监听端口，可选值：1~65535。
	 * @param backends_n_weight		int    云服务器的权重，取值范围：0~100，默认为 10。
	 * @param locationIds_n			string 指定的规则 ID。
	 * @param domain 				string 监听器转发规则的域名。
	 * @param url 					string 要绑定的监听器转发规则的路径。
	 *
	 * @return json
	 */
	public function modifyForwardSeventhBackends(
		$loadBalancerId, 
		$listenerId, 
		$backends_n_instanceId, 
		$backends_n_port, 
		$backends_n_weight = null,
		$locationIds_n = null,
		$domain	= null,
		$url = null
	)
	{
		$query = $this->getQueryParameter(self::MFSB_ACTION, [
			'loadBalancerId' => $loadBalancerId,
			'listenerId' => $listenerId,
			'backends.1.instanceId' => $backends_n_instanceId,
			'backends.1.port' => $backends_n_port,
			'backends.1.weight' => $backends_n_weight,
			'locationIds.1' => $locationIds_n,
			'domain' => $domain,
			'url' => $url
		]);

		$response = $this->guzzleClient()->request('GET', '', [
			'query' => $query,
			'debug' => true
		]);

		return $response->getBody()->getContents();
	}

	/**
	 * 修改应用型四层监听器转发规则上云服务器的权重
	 *
	 * @param loadBalancerId 		string 负载均衡实例 ID，可通过 [DescribeLoadBalancers](https://cloud.tencent.com/document/api/214/1261) 接口同时入参 forward 字段为 1 或者 -1 来查询。
	 * @param listenerId     		string 应用型负载均衡监听器 ID，可通过 DescribeForwardLBListeners 接口查询。
	 * @param backends_n_instanceId string 云服务器的唯一 ID，可通过 DescribeInstances 接口返回字段中的 unInstanceId 字段获取；此接口支持同时输入多台主机的实例 ID（如：要输入两台主机，则设置 backends.1.instanceId&backends.2.instanceId）。
	 * @param backends_n_port 		int    负载均衡实例监听器后端云服务器监听端口，可选值：1~65535。
	 * @param backends_n_weight		int    云服务器的权重，取值范围：0~100，默认为 10。
	 *
	 * @return json
	 */
	public function modifyForwardFourthBackendsWeight(
		$loadBalancerId, 
		$listenerId, 
		$backends_n_instanceId, 
		$backends_n_port, 
		$backends_n_weight = null
	)
	{
		$query = $this->getQueryParameter(self::MFFBW_ACTION, [
			'loadBalancerId' => $loadBalancerId,
			'listenerId' => $listenerId,
			'backends.1.instanceId' => $backends_n_instanceId,
			'backends.1.port' => $backends_n_port,
			'backends.1.weight' => $backends_n_weight
		]);

		$response = $this->guzzleClient()->request('GET', '', [
			'query' => $query,
			'debug' => true
		]);

		return $response->getBody()->getContents();
	}

	/**
	 * 修改应用型七层监听器转发规则上云服务器的端口
	 *
	 * @param loadBalancerId 		string 负载均衡实例 ID，可通过 [DescribeLoadBalancers](https://cloud.tencent.com/document/api/214/1261) 接口同时入参 forward 字段为 1 或者 -1 来查询。
	 * @param listenerId     		string 应用型负载均衡监听器 ID，可通过 DescribeForwardLBListeners 接口查询。
	 * @param backends_n_instanceId string 云服务器的唯一 ID，可通过 DescribeInstances 接口返回字段中的 unInstanceId 字段获取；此接口支持同时输入多台主机的实例 ID（如：要输入两台主机，则设置 backends.1.instanceId&backends.2.instanceId）。
	 * @param backends_n_port 		int    负载均衡实例监听器后端云服务器监听端口，可选值：1~65535。
	 * @param backends_n_newort 	int    负载均衡实例监听器后端云服务器监听端口，可选值：1~65535。
	 * @param backends_n_weight		int    云服务器的权重，取值范围：0~100，默认为 10。
	 * @param locationIds_n			string 指定的规则 ID。
	 * @param domain 				string 监听器转发规则的域名。
	 * @param url 					string 要绑定的监听器转发规则的路径。
	 *
	 * @return json
	 */
	public function modifyForwardSeventhBackendsPort(
		$loadBalancerId, 
		$listenerId, 
		$backends_n_instanceId, 
		$backends_n_port, 
		$backends_n_newPort,
		$backends_n_weight = null,
		$locationIds_n = null,
		$domain	= null,
		$url = null
	)
	{
		$query = $this->getQueryParameter(self::MFSBP_ACTION, [
			'loadBalancerId' => $loadBalancerId,
			'listenerId' => $listenerId,
			'backends.1.instanceId' => $backends_n_instanceId,
			'backends.1.port' => $backends_n_port,
			'backends.1.newPort' => $backends_n_newPort,
			'backends.1.weight' => $backends_n_weight,
			'locationIds.1' => $locationIds_n,
			'domain' => $domain,
			'url' => $url
		]);

		$response = $this->guzzleClient()->request('GET', '', [
			'query' => $query,
			'debug' => true
		]);

		return $response->getBody()->getContents();
	}

	/**
	 * 修改应用型四层监听器转发规则上云服务器的端口
	 *
	 * @param loadBalancerId 		string 负载均衡实例 ID，可通过 [DescribeLoadBalancers](https://cloud.tencent.com/document/api/214/1261) 接口同时入参 forward 字段为 1 或者 -1 来查询。
	 * @param listenerId     		string 应用型负载均衡监听器 ID，可通过 DescribeForwardLBListeners 接口查询。
	 * @param backends_n_instanceId string 云服务器的唯一 ID，可通过 DescribeInstances 接口返回字段中的 unInstanceId 字段获取；此接口支持同时输入多台主机的实例 ID（如：要输入两台主机，则设置 backends.1.instanceId&backends.2.instanceId）。
	 * @param backends_n_port 		int    负载均衡实例监听器后端云服务器监听端口，可选值：1~65535。
	 * @param backends_n_newPort 	int    负载均衡实例监听器后端云服务器监听端口，可选值：1~65535。
	 * @param backends_n_weight		int    云服务器的权重，取值范围：0~100，默认为 10。
	 *
	 * @return json
	 */
	public function modifyForwardFourthBackendsPort(
		$loadBalancerId, 
		$listenerId, 
		$backends_n_instanceId, 
		$backends_n_port, 
		$backends_n_newPort, 
		$backends_n_weight = null
	)
	{
		$query = $this->getQueryParameter(self::MFFBP_ACTION, [
			'loadBalancerId' => $loadBalancerId,
			'listenerId' => $listenerId,
			'backends.1.instanceId' => $backends_n_instanceId,
			'backends.1.port' => $backends_n_port,
			'backends.1.newPort' => $backends_n_newPort,
			'backends.1.weight' => $backends_n_weight
		]);

		$response = $this->guzzleClient()->request('GET', '', [
			'query' => $query,
			'debug' => true
		]);

		return $response->getBody()->getContents();
	}

	/**
	 * 查询应用型负载均衡云服务器列表
	 *
	 * @param loadBalancerId string 负载均衡实例 ID，可通过 [DescribeLoadBalancers](https://cloud.tencent.com/document/api/214/1261) 接口同时入参 forward 字段为 1 或者 -1 来查询。
	 *
	 * @return json
	 */
	public function describeForwardLBBackends($loadBalancerId)
	{
		$query = $this->getQueryParameter(self::DFLB_ACTION, [
			'loadBalancerId' => $loadBalancerId
		]);

		$response = $this->guzzleClient()->request('GET', '', [
			'query' => $query,
			'debug' => true
		]);

		return $response->getBody()->getContents();
	}

	/**
	 * 绑定云服务器到应用型负载均衡七层监听器的转发规则上
	 *
	 * @param loadBalancerId 		string 负载均衡实例 ID，可通过 [DescribeLoadBalancers](https://cloud.tencent.com/document/api/214/1261) 接口同时入参 forward 字段为 1 或者 -1 来查询。
	 * @param listenerId     		string 应用型负载均衡监听器 ID，可通过 DescribeForwardLBListeners 接口查询。
	 * @param backends_n_instanceId string 云服务器的唯一 ID，可通过 DescribeInstances 接口返回字段中的 unInstanceId 字段获取；此接口支持同时输入多台主机的实例 ID（如：要输入两台主机，则设置 backends.1.instanceId&backends.2.instanceId）。
	 * @param backends_n_port 		int    负载均衡实例监听器后端云服务器监听端口，可选值：1~65535。
	 * @param backends_n_weight		int    云服务器的权重，取值范围：0~100，默认为 10。
	 * @param locationIds_n			string 指定的规则 ID。
	 * @param domain 				string 监听器转发规则的域名。
	 * @param url 					string 要绑定的监听器转发规则的路径。
	 *
	 * @return json
	 */
	public function registerInstancesWithForwardLBSeventhListener(
		$loadBalancerId, 
		$listenerId, 
		$backends_n_instanceId, 
		$backends_n_port, 
		$backends_n_weight = null,
		$locationIds_n = null,
		$domain	= null,
		$url = null
	)
	{
		$query = $this->getQueryParameter(self::RIWLBSL_ACTIOM, [
			'loadBalancerId' => $loadBalancerId,
			'listenerId' => $listenerId,
			'backends.1.instanceId' => $backends_n_instanceId,
			'backends.1.port' => $backends_n_port,
			'backends.1.weight' => $backends_n_weight,
			'locationIds.1' => $locationIds_n,
			'domain' => $domain,
			'url' => $url
		]);

		$response = $this->guzzleClient()->request('GET', '', [
			'query' => $query,
			'debug' => true
		]);

		return $response->getBody()->getContents();
	}

	/**
	 * 绑定云服务器到应用型负载均衡四层监听器的转发规则上
	 *
	 * @param loadBalancerId 		string 负载均衡实例 ID，可通过 [DescribeLoadBalancers](https://cloud.tencent.com/document/api/214/1261) 接口同时入参 forward 字段为 1 或者 -1 来查询。
	 * @param listenerId     		string 应用型负载均衡监听器 ID，可通过 DescribeForwardLBListeners 接口查询。
	 * @param backends_n_instanceId string 云服务器的唯一 ID，可通过 DescribeInstances 接口返回字段中的 unInstanceId 字段获取；此接口支持同时输入多台主机的实例 ID（如：要输入两台主机，则设置 backends.1.instanceId&backends.2.instanceId）。
	 * @param backends_n_port 		int    负载均衡实例监听器后端云服务器监听端口，可选值：1~65535。
	 * @param backends_n_weight		int    云服务器的权重，取值范围：0~100，默认为 10。
	 *
	 * @return json
	 */
	public function registerInstancesWithForwardLBFourthListener(
		$loadBalancerId, 
		$listenerId, 
		$backends_n_instanceId, 
		$backends_n_port, 
		$backends_n_weight = null
	)
	{
		$query = $this->getQueryParameter(self::RIWFLBFL_ACTION, [
			'loadBalancerId' => $loadBalancerId,
			'listenerId' => $listenerId,
			'backends.1.instanceId' => $backends_n_instanceId,
			'backends.1.port' => $backends_n_port,
			'backends.1.weight' => $backends_n_weight
		]);

		$response = $this->guzzleClient()->request('GET', '', [
			'query' => $query,
			'debug' => true
		]);

		return $response->getBody()->getContents();
	}

	/**
	 * 解绑应用型负载均衡七层监听器转发规则上的云服务器
	 *
	 * @param loadBalancerId 		string 负载均衡实例 ID，可通过 [DescribeLoadBalancers](https://cloud.tencent.com/document/api/214/1261) 接口同时入参 forward 字段为 1 或者 -1 来查询。
	 * @param listenerId     		string 应用型负载均衡监听器 ID，可通过 DescribeForwardLBListeners 接口查询。
	 * @param backends_n_instanceId string 云服务器的唯一 ID，可通过 DescribeInstances 接口返回字段中的 unInstanceId 字段获取；此接口支持同时输入多台主机的实例 ID（如：要输入两台主机，则设置 backends.1.instanceId&backends.2.instanceId）。
	 * @param backends_n_port 		int    负载均衡实例监听器后端云服务器监听端口，可选值：1~65535。
	 * @param locationIds_n			string 指定的规则 ID。
	 * @param domain 				string 监听器转发规则的域名。
	 * @param url 					string 要绑定的监听器转发规则的路径。
	 *
	 * @return json
	 */
	public function deregisterInstancesFromForwardLB(
		$loadBalancerId, 
		$listenerId, 
		$backends_n_instanceId, 
		$backends_n_port,
		$locationIds_n = null,
		$domain	= null,
		$url = null
	)
	{
		$query = $this->getQueryParameter(self::DIFFLB_ACTION, [
			'loadBalancerId' => $loadBalancerId,
			'listenerId' => $listenerId,
			'backends.1.instanceId' => $backends_n_instanceId,
			'backends.1.port' => $backends_n_port,
			'locationIds.1' => $locationIds_n,
			'domain' => $domain,
			'url' => $url
		]);

		$response = $this->guzzleClient()->request('GET', '', [
			'query' => $query,
			'debug' => true
		]);

		return $response->getBody()->getContents();
	}

	/**
	 * 解绑应用型负载均衡四层监听器转发规则上的云服务器
	 *
	 * @param loadBalancerId 		string 负载均衡实例 ID，可通过 [DescribeLoadBalancers](https://cloud.tencent.com/document/api/214/1261) 接口同时入参 forward 字段为 1 或者 -1 来查询。
	 * @param listenerId     		string 应用型负载均衡监听器 ID，可通过 DescribeForwardLBListeners 接口查询。
	 * @param backends_n_instanceId string 云服务器的唯一 ID，可通过 DescribeInstances 接口返回字段中的 unInstanceId 字段获取；此接口支持同时输入多台主机的实例 ID（如：要输入两台主机，则设置 backends.1.instanceId&backends.2.instanceId）。
	 * @param backends_n_port 		int    负载均衡实例监听器后端云服务器监听端口，可选值：1~65535。
	 * @param backends_n_weight		int    云服务器的权重，取值范围：0~100，默认为 10。
	 *
	 * @return json
	 */
	public function deregisterInstancesFromForwardLBFourthListener(
		$loadBalancerId, 
		$listenerId, 
		$backends_n_instanceId, 
		$backends_n_port,
		$backends_n_weight = null
	)
	{
		$query = $this->getQueryParameter(self::DIFFLBFL_ACTION, [
			'loadBalancerId' => $loadBalancerId,
			'listenerId' => $listenerId,
			'backends.1.instanceId' => $backends_n_instanceId,
			'backends.1.port' => $backends_n_port,
			'backends.1.weight' => $backends_n_weight
		]);

		$response = $this->guzzleClient()->request('GET', '', [
			'query' => $query,
			'debug' => true
		]);

		return $response->getBody()->getContents();
	}
}
