<?php

class DescoPrepaid {
	private $baseURL = 'https://prepaid.desco.org.bd/api/';
	private $username = '';
	private $password = '';
	private $cookie = '';
	private $timeout = 30;
	public $urls = [];
	private $unsets = ['consumerMessages', 'accountId'];

	public function __construct($baseURL, $username, $password, $cookie = '', $timeout = 30) {
		$this->baseURL = $baseURL;
		$this->username = $username;
		$this->password = $password;
		$this->cookie = $cookie;
		$this->timeout = $timeout;
		$this->urls = (Object)['signin' => '/auth/login', 'balance' => '/accounts/$1/balance', 'info' => '/accounts/$1/summary'];
	}

	public function callApi($url, $content = '', $method = 'GET') {
		$url = $this->baseURL.$url;
		$method = strtoupper($method);
		$headers = [
			'Content-Type: application/json',
			'Cookie: '.$this->cookie,
		];

		$output = file_get_contents($url, false, stream_context_create(['http' => ['method' => $method, 'header'  => $headers, 'content' => $content, 'timeout' => $this->timeout, 'ignore_errors' => true]]));
		if (!empty($output)) {
			$result = json_decode($output);
			if ($result === null || !empty($result->errorCode)) {
				$this->auth();
				$headers = [
					'Content-Type: application/json',
					'Cookie: '.$this->cookie,
				];
				$output = file_get_contents($url, false, stream_context_create(['http' => ['method' => $method, 'header'  => $headers, 'content' => $content, 'timeout' => $this->timeout, 'ignore_errors' => true]]));
				if (!empty($output)) {
					$result = json_decode($output);
				}
			}

			if ($result !== null) {
				foreach ($this->unsets as $unsetKey) {
					if (isset($result->$unsetKey)) {
						unset($result->$unsetKey);
					}
				}
				return (Object)['success' => 'true', 'result' => $result];
			}
		}

		return (Object)['success' => false, 'result' => null];
	}

	public function auth() {
		$url = $this->baseURL.$this->urls->signin;
		$headers = ['Content-Type: application/json'];
		$content = json_encode(['username' => $this->username, 'password' => base64_decode($this->password)]);

		$output = file_get_contents($url, false, stream_context_create(['http' => ['method' => 'POST', 'header'  => $headers, 'content' => $content, 'timeout' => $this->timeout, 'ignore_errors' => true]]));

		if (!empty($output)) {
			$response = json_decode($output);
			if (!empty($response->errorCode)) {
				error_log("Authentication failed: " . $response->message);
				return false;
			}

			if ($response->authenticated) {
				$headers = http_get_last_response_headers();

				$requestHeaders = [];
				foreach ($headers as $header) {
					if (stripos(trim($header), 'Set-Cookie:') === 0) {
						$requestHeaders[] = explode(';', trim(substr($header, 11)))[0];
					}
				}

				$this->cookie = implode('; ', $requestHeaders);
				return true;
			}
		}

		$this->cookie = '';
		return false;
	}

	public function getCookie() {
		return $this->cookie;
	}
}
