<?php
// A client for SendParcel API
// Based on documentation at https://sendparcel.poslaju.com.my/apiv1

namespace apih\SendParcel;

class Client
{
	const DEMO_URL = 'http://sendparcel-test.ap-southeast-1.elasticbeanstalk.com/apiv1/';
	const LIVE_URL = 'https://sendparcel.poslaju.com.my/apiv1/';

	protected $api_key;
	protected $api_secret;
	protected $url;
	protected $use_ssl = true;
	protected $last_error;

	public function __construct($api_key, $api_secret)
	{
		$this->api_key = $api_key;
		$this->api_secret = $api_secret;
		$this->url = self::LIVE_URL;
	}

	public function useDemo($flag = true)
	{
		$this->url = $flag ? self::DEMO_URL : self::LIVE_URL;
	}

	public function useSsl($flag = true)
	{
		$this->use_ssl = $flag;
	}

	public function getUrl()
	{
		return $this->url;
	}

	public function getLastError()
	{
		return $this->last_error;
	}

	protected function logError($function, $request, $response)
	{
		$this->last_error = [
			'function' => $function,
			'request' => $request,
			'response' => $response
		];

		$error_message = 'SendParcel Error:' . PHP_EOL;
		$error_message .= 'function: ' . $function . PHP_EOL;
		$error_message .= 'request: ' . PHP_EOL;
		$error_message .= '-> url: ' . $request['url'] . PHP_EOL;
		$error_message .= '-> data: ' . json_encode($request['data']) . PHP_EOL;
		$error_message .= 'response: ' . PHP_EOL;
		$error_message .= '-> http_code: ' . $response['http_code'] . PHP_EOL;
		$error_message .= '-> body: ' . $response['body'] . PHP_EOL;

		error_log($error_message);
	}

	protected function snakeCase($value)
	{
		return strtolower(preg_replace('/(?<!^)([A-Z])/', '_' . '$1', $value));
	}

	protected function curlInit()
	{
		$this->last_error = null;

		$ch = curl_init();

		curl_setopt($ch, CURLOPT_HEADER, false);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);

		if ($this->use_ssl === false) {
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
		}

		return $ch;
	}

	protected function curlPostRequest($function, $data = [], $return_raw_data = false)
	{
		$action = $this->snakeCase($function);
		$url = $this->url . $action;

		$data = array_merge([
			'api_key' => $this->api_key
		], $data);

		$ch = $this->curlInit();

		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
		curl_setopt($ch, CURLOPT_URL, $url);

		$body = curl_exec($ch);
		$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

		curl_close($ch);

		if ($return_raw_data) return $body;

		$decoded_body = json_decode($body, true);

		if ($http_code !== 200 || json_last_error() !== JSON_ERROR_NONE) {
			$this->logError(
				$function,
				compact('url', 'data'),
				compact('http_code', 'body')
			);

			return null;
		}

		return $decoded_body;
	}

	public function me()
	{
		return $this->curlPostRequest(__FUNCTION__);
	}

	public function getPostcodeDetails($postcode)
	{
		return $this->curlPostRequest(__FUNCTION__, ['postcode' => $postcode]);
	}

	public function checkPrice($data)
	{
		return $this->curlPostRequest(__FUNCTION__, $data);
	}

	public function getParcelSizes()
	{
		return $this->curlPostRequest(__FUNCTION__);
	}

	public function getContentTypes()
	{
		return $this->curlPostRequest(__FUNCTION__);
	}

	public function createShipment($data)
	{
		return $this->curlPostRequest(__FUNCTION__, $data);
	}

	public function getCartItems()
	{
		return $this->curlPostRequest(__FUNCTION__);
	}

	public function checkout($shipment_keys)
	{
		return $this->curlPostRequest(__FUNCTION__, ['shipment_keys' => $shipment_keys]);
	}

	public function getShipmentStatuses()
	{
		return $this->curlPostRequest(__FUNCTION__);
	}

	public function getShipments($shipment_keys)
	{
		return $this->curlPostRequest(__FUNCTION__, ['shipment_keys' => $shipment_keys]);
	}

	public function getShipmentHistory($page = 1)
	{
		return $this->curlPostRequest(__FUNCTION__, ['page' => $page]);
	}

	public function getConsignmentNote($data)
	{
		return $this->curlPostRequest(__FUNCTION__, $data, true);
	}

	public function checkPriceBulk($data)
	{
		return $this->curlPostRequest(__FUNCTION__, $data);
	}

	public function createBulkAwb($data)
	{
		return $this->curlPostRequest(__FUNCTION__, $data);
	}

	public function getBulkTrackingNo($integration_order_id)
	{
		return $this->curlPostRequest(__FUNCTION__, ['integration_order_id' => $integration_order_id]);
	}
}
?>