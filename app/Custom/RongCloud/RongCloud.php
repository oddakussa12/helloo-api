<?php
namespace App\Custom\RongCloud;

use Latrell\RongCloud\RongCloudException;
use Latrell\RongCloud\RongCloud as Rong;

class RongCloud extends Rong
{

	protected $api;

	public function __construct($config)
	{
		$this->api = new Api($config['app_key'], $config['app_secret']);
	}

	public function getApi()
	{
		return $this->api;
	}

	public function __call($method, $parameters)
	{
		if (in_array($method, [
			'getApi'
		])) {
			return call_user_func_array([
				$this,
				$method
			], $parameters);
		}

		ob_start();

		$ret = call_user_func_array([
			$this->api,
			$method
		], $parameters);

		$message = ob_get_clean();

		if ($message !== '') {
			throw new RongCloudException("{$method} : {$message}");
		}

		$json = json_decode($ret, true);

		if (@$json['code'] !== 200) {
			throw new RongCloudException($ret, @$json['code']);
		}

		return $json;
	}
}
